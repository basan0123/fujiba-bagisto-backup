<?php

namespace Fujiba\Accounting\Observers;

use Fujiba\Accounting\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function created(Model $model): void
    {
        $this->log($model, 'created', null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        if (empty($changes)) {
            return;
        }

        // Noise reduction: skip when the only change is in the ignore list.
        $ignored = config(
            'accounting.audit.ignore_attribute_only_changes.' . get_class($model),
            []
        );
        if ($ignored && empty(array_diff(array_keys($changes), $ignored))) {
            return;
        }

        $original = array_intersect_key($model->getOriginal(), $changes);
        $this->log($model, 'updated', $original, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted', $model->getOriginal(), null);
    }

    public function restored(Model $model): void
    {
        $this->log($model, 'restored', null, $model->getAttributes());
    }

    protected function log(Model $model, string $action, ?array $before, ?array $after): void
    {
        // The audit log writes itself outside the observed model set, so there
        // is no recursion risk; AuditLog has no observer attached.
        AuditLog::create([
            'table_name'  => $model->getTable(),
            'record_id'   => (int) ($model->getKey() ?? 0),
            'action'      => $action,
            'before_json' => $this->scrub($before),
            'after_json'  => $this->scrub($after),
            'user_id'     => $this->currentUserId(),
            'user_name'   => $this->currentUserName(),
            'ip'          => request()?->ip(),
        ]);
    }

    protected function scrub(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }
        // Strip values that won't survive JSON cleanly (objects, resources).
        return array_map(function ($v) {
            if (is_object($v) && method_exists($v, '__toString')) {
                return (string) $v;
            }
            if (is_resource($v)) {
                return null;
            }
            return $v;
        }, $data);
    }

    protected function currentUserId(): ?int
    {
        $admin = auth('admin')->user();
        if ($admin) {
            return (int) $admin->getKey();
        }
        $user = auth()->user();
        return $user ? (int) $user->getKey() : null;
    }

    protected function currentUserName(): ?string
    {
        $admin = auth('admin')->user();
        if ($admin) {
            return $admin->name ?? $admin->email ?? null;
        }
        $user = auth()->user();
        return $user ? ($user->name ?? $user->email ?? null) : null;
    }
}

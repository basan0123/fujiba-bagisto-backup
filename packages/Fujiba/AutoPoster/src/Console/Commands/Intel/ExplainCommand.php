<?php

namespace Fujiba\AutoPoster\Console\Commands\Intel;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Explainability — print, in plain language, why the agent chose a creative.
 *
 * A marketing decision nobody can interrogate is a marketing decision nobody
 * should trust, so every planned creative carries a decision_trace and this
 * command renders it. Anything the trace does not record is printed as
 * "not recorded" rather than invented.
 */
class ExplainCommand extends Command
{
    protected $signature = 'fujiba:intel:explain {creative_id}';

    protected $description = 'Explain why the agent planned a particular creative';

    public function handle(): int
    {
        $id = (int) $this->argument('creative_id');
        $c = DB::table('fi_creatives')->where('id', $id)->first();

        if (! $c) {
            $this->error("No creative {$id}.");

            return self::FAILURE;
        }

        $trace = json_decode($c->decision_trace ?? '{}', true) ?: [];

        $say = function (string $heading, $value, string $fallback = 'not recorded') {
            $this->line('');
            $this->line("<options=bold>{$heading}</>");
            if (is_array($value)) {
                if (! $value) {
                    $this->line("  {$fallback}");

                    return;
                }
                foreach ($value as $k => $v) {
                    $v = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v;
                    $this->line(is_int($k) ? "  - {$v}" : "  - {$k}: {$v}");
                }

                return;
            }
            $this->line('  '.(($value === null || $value === '') ? $fallback : $value));
        };

        $this->line(str_repeat('=', 68));
        $this->line("CREATIVE #{$c->id}  [{$c->approval_state}/{$c->publish_state}]");
        $this->line(str_repeat('=', 68));

        $say('WHY THIS PRODUCT', $trace['why_product'] ?? $this->productLine($c));
        $say('WHY THIS AUDIENCE', $trace['why_audience'] ?? $c->audience);
        $say('WHY THIS LANGUAGE', $trace['why_language'] ?? $c->language);
        $say('WHY THIS STORY', $trace['why_story'] ?? $c->story);
        $say('WHY THESE COMPONENTS', $trace['components'] ?? []);
        $say('WHY THIS CTA', $trace['why_cta'] ?? $c->cta);
        $say('WHY THIS TIME', $trace['why_time'] ?? $c->planned_time);

        $say('FACTS USED', $this->facts($trace));
        $say('MARKETING MEMORY USED', $this->memory($trace));

        $this->line('');
        $this->line('<options=bold>PROFIT GUARD</>');
        $this->renderGuard($id, $trace);

        $say('FABRIC INTEGRITY', json_decode($c->fabric_integrity ?? 'null', true) ?? []);

        $this->line('');
        $this->line('<options=bold>RISK / CONFIDENCE</>');
        $this->line('  quality_score: '.($c->quality_score ?? 'not scored'));
        $this->line('  low_data: '.(($trace['low_data'] ?? null) ? 'YES — priors used, treat with caution' : 'no'));
        if (! empty($trace['risks'])) {
            foreach ((array) $trace['risks'] as $r) {
                $this->line("  - {$r}");
            }
        }

        $this->line('');
        $this->line('  tracking_ref: '.($c->tracking_ref ?: 'none'));
        $this->line('  generation cost: ¥'.number_format((float) $c->cost_jpy, 2));
        $this->line('');

        return self::SUCCESS;
    }

    protected function productLine($c): ?string
    {
        if (! $c->product_id) {
            return null;
        }

        $sku = DB::table('products')->where('id', $c->product_id)->value('sku');
        $stock = (int) DB::table('product_inventories')->where('product_id', $c->product_id)->sum('qty');

        return "product {$c->product_id} ({$sku}), stock {$stock}";
    }

    protected function facts(array $trace): array
    {
        $ids = $trace['fact_ids'] ?? [];
        if (! $ids) {
            return [];
        }

        return DB::table('fi_facts')->whereIn('id', $ids)->get()
            ->map(fn ($f) => "{$f->field} = {$f->value}  [{$f->source_type}, confidence ".
                ($f->confidence ?? '—').']')
            ->all();
    }

    protected function memory(array $trace): array
    {
        $ids = $trace['memory_ids'] ?? [];
        if (! $ids) {
            return [];
        }

        return DB::table('fi_marketing_memory')->whereIn('id', $ids)->get()
            ->map(fn ($m) => "[{$m->kind}] {$m->statement}  (confidence {$m->confidence}, n={$m->sample_size})")
            ->all();
    }

    protected function renderGuard(int $creativeId, array $trace): void
    {
        $row = DB::table('fi_profit_guard_log')
            ->where('creative_id', $creativeId)
            ->orderByDesc('id')->first();

        if (! $row) {
            $this->line('  not recorded');

            return;
        }

        $this->line('  decision: '.($row->allowed ? 'ALLOW' : 'BLOCK — '.$row->reason));
        $this->line('  expected value: ¥'.number_format((float) $row->expected_value_jpy, 2));
        $this->line('  estimated cost: ¥'.number_format((float) $row->estimated_cost_jpy, 2));
        $this->line('  ROI: '.($row->roi === null ? 'n/a (no cost)' : round((float) $row->roi, 2)));

        foreach ((array) json_decode($row->inputs ?? '{}', true) as $k => $v) {
            $this->line("    {$k}: {$v}");
        }

        if ($row->low_data) {
            $this->line('  NOTE: conservative priors used — not measured history.');
        }
    }
}

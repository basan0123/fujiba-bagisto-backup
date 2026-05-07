<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantProvisioningJob extends Model
{
    protected $table = 'tenant_provisioning_jobs';

    protected $fillable = [
        'tenant_id',
        'domain',
        'source_tenant_id',
        'source_channels',
        'admin_email',
        'plan_id',
        'initiated_by',
        'overall_status',
        'steps',
        'last_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'source_channels' => 'array',
        'steps'           => 'array',
        'started_at'      => 'datetime',
        'finished_at'     => 'datetime',
    ];

    public const STEPS = [
        'create_db'        => 'Create tenant row + DB',
        'migrate'          => 'Run tenants:migrate',
        'wipe_seed'        => 'Replace seeded channel/categories/attributes',
        'copy_catalog'     => 'Copy catalog (categories, attributes, products, tax, CMS, theme)',
        'rsync_images'     => 'Rsync product image directories',
        'create_admin'     => 'Create admin user',
        'attach_domain'    => 'Insert domain row',
        'finalize'         => 'Finalize and produce login URL',
    ];

    public static function freshStepsArray(): array
    {
        $out = [];
        foreach (self::STEPS as $key => $label) {
            $out[$key] = [
                'key'         => $key,
                'label'       => $label,
                'status'      => 'pending',
                'message'     => null,
                'started_at'  => null,
                'finished_at' => null,
            ];
        }
        return $out;
    }
}

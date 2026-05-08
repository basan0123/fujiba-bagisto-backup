<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-order training flag.
 *
 * Distinct from pos_users.training_mode (per-agent toggle): an order is
 * marked is_training=1 if the cashier was in training mode at sync time,
 * OR if the client explicitly sent trainingMode=true for this single
 * sale (e.g. demo-on-the-fly without flipping the agent flag).
 *
 * Reports/exports MUST filter out is_training=1 rows from sales totals.
 * Index is low-cardinality but fine as-is — kept off the hot path.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pos_order', 'is_training')) {
            Schema::table('pos_order', function (Blueprint $table) {
                $table->boolean('is_training')
                    ->default(0)
                    ->after('order_note')
                    ->comment('1 = sandbox/training sale; exclude from sales reports.');
            });
        }

        if (! Schema::hasIndex('pos_order', 'pos_order_is_training_index')) {
            Schema::table('pos_order', function (Blueprint $table) {
                $table->index('is_training', 'pos_order_is_training_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('pos_order', 'pos_order_is_training_index')) {
            Schema::table('pos_order', function (Blueprint $table) {
                $table->dropIndex('pos_order_is_training_index');
            });
        }

        if (Schema::hasColumn('pos_order', 'is_training')) {
            Schema::table('pos_order', function (Blueprint $table) {
                $table->dropColumn('is_training');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * pos_tables — restaurant floor map. One row per dine-in table.
 *
 * Idempotent: skips if the table already exists so multiple runs
 * over the same tenant DB are safe.
 *
 * Scoped to an outlet (single-outlet shops just see one section).
 * position_x / position_y let merchants drag tables on a floor-map
 * layout (UI is Phase F+1). status is a soft enum so the API can
 * grow without a schema change:
 *
 *   free      — empty, available
 *   occupied  — has an open ticket
 *   reserved  — booked but not seated
 *   dirty     — needs bussing (post-payment, pre-clean)
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pos_tables')) {
            return;
        }

        Schema::create('pos_tables', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedInteger('outlet_id')->index();
            $t->string('label', 32);
            $t->unsignedSmallInteger('seat_count')->default(2);
            $t->integer('position_x')->default(0);
            $t->integer('position_y')->default(0);
            $t->string('status', 16)->default('free');
            $t->string('note', 250)->nullable();
            $t->timestamps();

            $t->unique(['outlet_id', 'label'], 'uq_pos_tables_outlet_label');
            $t->index(['outlet_id', 'status'], 'idx_pos_tables_outlet_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_tables');
    }
};

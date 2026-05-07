<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sagawa_shipments')) {
            $ordersIdIsBigInt = $this->columnIsBigInt('orders', 'id');

            Schema::create('sagawa_shipments', function (Blueprint $table) use ($ordersIdIsBigInt) {
                $table->id();

                if ($ordersIdIsBigInt) {
                    $table->unsignedBigInteger('order_id');
                } else {
                    $table->unsignedInteger('order_id');
                }

                $table->date('delivery_date')->nullable();
                $table->string('delivery_time', 30)->nullable();

                $table->enum('status', ['pending', 'ready', 'shipped', 'failed'])
                    ->default('pending');

                $table->string('tracking_number', 64)->nullable();
                $table->json('raw_response')->nullable();

                $table->timestamp('shipped_at')->nullable();
                $table->timestamps();

                $table->unique('order_id');
                $table->index(['status', 'delivery_date']);

                $table->foreign('order_id')
                    ->references('id')
                    ->on('orders')
                    ->cascadeOnDelete();
            });

            return;
        }

        // Table exists — add anything missing (idempotent upgrade path).
        Schema::table('sagawa_shipments', function (Blueprint $table) {
            if (! Schema::hasColumn('sagawa_shipments', 'tracking_number')) {
                $table->string('tracking_number', 64)->nullable();
            }

            if (! Schema::hasColumn('sagawa_shipments', 'raw_response')) {
                $table->json('raw_response')->nullable();
            }

            if (! Schema::hasColumn('sagawa_shipments', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sagawa_shipments');
    }

    private function columnIsBigInt(string $table, string $column): bool
    {
        if (! Schema::hasTable($table)) {
            return true;
        }

        $type = strtolower((string) DB::selectOne(
            'SELECT DATA_TYPE AS t
               FROM information_schema.columns
              WHERE table_schema = DATABASE()
                AND table_name = ?
                AND column_name = ?',
            [$table, $column]
        )?->t);

        return $type === 'bigint';
    }
};

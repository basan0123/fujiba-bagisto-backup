<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Order-level discount columns on pos_order.
 *
 * pos_order.discount_amount already exists but reflects the SUM of
 * line-level discounts (computed from per-line custom_price drift in
 * OrderMutation@syncOrder). It conflates the two and can't tell a
 * report whether a sale used a 10% blanket promo or 10 individual
 * markdowns.
 *
 * order_discount_amount + order_discount_reason are the explicit
 * order-level fields the cashier sets via the "Order discount"
 * action in the cart, persisted as-is so reports can split:
 *   line discounts  = pos_order.discount_amount
 *   order discounts = order_discount_amount
 *
 * Both nullable so legacy rows (pre-this-migration) keep their
 * existing semantics. New rows: when no order-level discount is
 * applied both columns are NULL (don't pollute every cash sale
 * with a zero row); when a discount IS applied the reason defaults
 * to 'unspecified' until the picker UI lands. Idempotent — guarded
 * with hasColumn so tenants:migrate re-runs cleanly per the
 * project's stancl/tenancy convention.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pos_order', 'order_discount_amount')) {
            Schema::table('pos_order', function (Blueprint $table) {
                $table->decimal('order_discount_amount', 12, 4)
                    ->nullable()
                    ->after('is_training')
                    ->comment('Order-level discount in base currency; NULL = no order-level discount applied.');
            });
        }

        if (! Schema::hasColumn('pos_order', 'order_discount_reason')) {
            Schema::table('pos_order', function (Blueprint $table) {
                $table->string('order_discount_reason', 250)
                    ->nullable()
                    ->after('order_discount_amount')
                    ->comment('Free-text justification or picker code; defaults to "unspecified" when amount > 0 and client did not send one.');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pos_order', 'order_discount_reason')) {
            Schema::table('pos_order', function (Blueprint $table) {
                $table->dropColumn('order_discount_reason');
            });
        }

        if (Schema::hasColumn('pos_order', 'order_discount_amount')) {
            Schema::table('pos_order', function (Blueprint $table) {
                $table->dropColumn('order_discount_amount');
            });
        }
    }
};

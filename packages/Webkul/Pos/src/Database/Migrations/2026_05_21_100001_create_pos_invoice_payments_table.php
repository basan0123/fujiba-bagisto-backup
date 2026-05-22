<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Standalone invoice builder — payment ledger.
 *
 * Two changes, both idempotent so tenants:migrate re-runs cleanly
 * (project memory: feedback_migration_idempotency):
 *
 *   1. invoices.amount_paid — running paid total, denormalised onto the
 *      invoice so the list/stats query can derive payment status
 *      (unpaid / partial / paid) without summing the ledger every row.
 *      Kept in sync by Webkul\Pos\Mutations\Outlet\InvoiceMutation@addPayment.
 *
 *   2. pos_invoice_payments — one row per part-payment so we can show a
 *      payment history (deposit → balance) on the invoice. payment status
 *      is DERIVED from SUM(amount) vs invoice.grand_total, never stored as
 *      a truth column.
 *
 * No FK on invoice_id: Bagisto's invoices.id is INT UNSIGNED
 * (feedback_bagisto_fk_int_unsigned) and we only ever query by it; a plain
 * indexed column avoids the cross-type FK foot-gun on older clones.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'amount_paid')) {
            Schema::table('invoices', function (Blueprint $t) {
                $t->decimal('amount_paid', 12, 4)->default(0)->after('grand_total');
            });
        }

        if (! Schema::hasTable('pos_invoice_payments')) {
            Schema::create('pos_invoice_payments', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedInteger('invoice_id')->index();
                $t->unsignedInteger('order_id')->index();
                // Which outlet recorded the payment — list/stats scope by this.
                $t->unsignedInteger('outlet_id')->index();
                $t->decimal('amount', 12, 4);
                // pos_cash | pos_card | bank_transfer | other — soft enum.
                $t->string('method', 32)->default('pos_cash');
                $t->string('reference', 120)->nullable();
                $t->string('note', 250)->nullable();
                // pos_users.id who took the payment (audit / "received by").
                $t->unsignedInteger('received_by')->nullable();
                $t->timestamp('paid_at')->nullable();
                $t->timestamps();

                $t->index(['invoice_id', 'paid_at'], 'idx_pos_inv_pay_invoice_paid');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_invoice_payments');

        if (Schema::hasColumn('invoices', 'amount_paid')) {
            Schema::table('invoices', function (Blueprint $t) {
                $t->dropColumn('amount_paid');
            });
        }
    }
};

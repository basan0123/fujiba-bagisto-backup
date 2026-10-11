<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS-J — catalog data problems found by scanning product_flat (staging only).
 * Every row is a PROPOSAL: nothing here is applied to Bagisto product tables
 * until a human approves it. Re-scans upsert by fingerprint and never reset a
 * status a human has changed. Supplier-code-in-name cases are deliberately
 * not stored here (separate proposal) — the scanner only counts them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fi_catalog_issues')) {
            return;
        }

        Schema::create('fi_catalog_issues', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('product_id')->nullable()->index();
            $t->string('sku', 191)->nullable();
            $t->string('name', 255)->nullable();
            $t->string('channel', 32)->default('default');
            $t->string('locale', 32)->nullable();                 // affected locale(s), comma list; NULL = not locale-specific
            $t->string('issue_type', 48)->index();                // sku_name_mismatch|colour_letter_mismatch|name_locale_mismatch|duplicate_name|url_key_name_mismatch
            $t->text('current_value')->nullable();
            $t->text('proposed_fix')->nullable();
            $t->text('evidence')->nullable();
            $t->decimal('confidence', 4, 3)->nullable();          // confidence in the proposed fix
            $t->string('severity', 16)->default('medium');        // high|medium|low
            $t->string('status', 16)->default('proposed');        // proposed|approved|rejected|applied|...
            $t->string('source', 64)->default('ws_j_catalog_scan'); // ws_j_catalog_scan|ws_j_known_seed
            $t->string('fingerprint', 191)->unique();
            $t->boolean('internal_only')->default(true);
            $t->timestamp('last_seen_at')->nullable();
            $t->string('reviewed_by', 64)->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();

            $t->index(['issue_type', 'severity', 'status'], 'fi_catalog_issues_triage_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fi_catalog_issues');
    }
};

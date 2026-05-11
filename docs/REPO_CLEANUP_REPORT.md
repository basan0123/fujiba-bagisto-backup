# Repo cleanup sprint — report

Date: 2026-05-11
Sprint: Repo cleanup (2-3h budget, completed in scope)
Starting state: 87 uncommitted items on master, multiple prior sprints
left in-flight, schema-ahead-of-repo on Fujiba production.

---

## 1. What's now on master

Master gained 9 commits, all small + isolated + complete + reviewed-in-line.

```
e43319b chore: gitignore chatpion-sync log timestamps + move UI audit to docs
c247d25 fix(email): use array access for $additionalDetails in order emails
fc8e1cb fix(receipt): use incl-tax mirror only when tax actually applied
d5747ff feat(admin): customer avatar + last-cart timestamp on customers index
77a7530 feat(admin): thumbnail column on Reporting > Products top-selling panels
b274672 feat(inventory): synchronous product_inventory_indices refresh via observers
3a4c17e feat(invoices-admin): override core DataGrids + Reporting via container bind
3077a0b fix(address): gate email/postcode/phone relax on customer flag, not channel code
30e64e2 docs: REPO_CLEANUP_INVENTORY.md — uncommitted-work classification
```

Each maps to a memory-file rationale (see commit messages) and is
isolated enough to deploy independently.

Working tree on master: **clean**.

---

## 2. Feature branches

Three feature branches isolate in-flight or ambiguous work. None
merged. All include explicit "UNREVIEWED" warnings in their commit
messages so a future agent or reviewer doesn't accidentally fast-forward
them.

### `feature/order-workflow-v2`

- Last commit: `812d2e8` (1 commit ahead of master)
- 48 files, +4799/-477
- DESIGN.md, OrderUpdateService (836 lines), 6 unit + 1 feature test,
  5 artisan commands, 2 migrations, DTOs, events, listeners
- **Schema state:** the two migrations are ALREADY APPLIED on Fujiba
  production but NOT on A6, tenant1, tenant2, or demo-store. This is
  the schema-ahead-of-repo problem.
- **Next steps before merge:**
  1. Run the new tests (`phpunit --testsuite="Fujiba OrdersAdmin Unit Test"`)
  2. Decide: backfill the migrations on A6/staging/auto-tenants, OR
     squash them into `tenant_master_template` so new tenants inherit
     them at signup
  3. Get K to sign off on DESIGN.md (memory note says it's awaiting
     review)
  4. Reconcile the 9,488 historical phantom-shipped lines (separate
     terminal-data task — NOT blocking the merge)

### `feature/bot-flow-manager`

- Last commit: `03e1183` (1 commit ahead of master)
- 14 files, +2740/-34
- FlowManagerController, FlowValidator, 817-line admin Vue editor,
  BotFlowSender extensions, ChatPion-side patch files for the onamae host
- **Next steps before merge:**
  1. Confirm the three `chatpion_api_*.php` files have been deployed
     to the ChatPion host on onamae (memory: `project_bot_flow_manager.md`
     says this is required)
  2. Smoke test `/admin/fujiba-livechat/flows` on a staging tenant
  3. Verify auto messenger_profile push works after a flow edit

### `feature/catalog-download`

- Last commit: `07b1cc8` (1 commit ahead of master)
- 10 files, +1020 lines
- 593-line CatalogDownloadController, PDF + stock-preview blade views,
  ACL + admin menu + routes + en/ja translations
- **Next steps before merge:**
  1. Smoke test on staging — click through `/admin/fujiba-catalog/download`
     and confirm PDF generates + stock preview shows correct numbers
  2. No migration involved; safe to merge after smoke test

---

## 3. What was discarded

Nothing was discarded. Every modification and untracked file was either:
- committed to master (complete + isolated work)
- committed to a feature branch (in-flight or unreviewed work)
- added to .gitignore (operational state)
- moved to docs/ (the UI polish audit)

The deletion of `Fujiba/InvoicesAdmin/src/Listeners/AutoConfirmCodPayment.php`
lives on `feature/order-workflow-v2` only — master still has it. It will
be removed from master when v2 merges.

---

## 4. Schema state per tenant

| Tenant     | `2026_05_11_000001_add_canceled_at` | `2026_05_11_000002_create_fujiba_order_audit_log` | Lives on branch        |
|------------|-------------------------------------|---------------------------------------------------|------------------------|
| Fujiba     | ✅ APPLIED                          | ✅ APPLIED                                        | feature/order-workflow-v2 |
| A6         | ❌                                   | ❌                                                 | feature/order-workflow-v2 |
| tenant1    | ❌                                   | ❌                                                 | feature/order-workflow-v2 |
| tenant2    | ❌                                   | ❌                                                 | feature/order-workflow-v2 |
| demo-store | ❌                                   | ❌                                                 | feature/order-workflow-v2 |

**The asymmetric production state is preserved.** Fujiba had these
migrations applied prior to the cleanup sprint; the cleanup did not
add or remove them on any tenant. The branch is now the source of
truth for what produced Fujiba's schema state.

---

## 5. Recommended next-up

In order of priority:

### Most urgent: feature/order-workflow-v2

The schema-ahead-of-repo on Fujiba is the single most fragile thing in
the codebase right now. Until this branch merges (or the migrations are
deliberately reverted), Fujiba is running production code that doesn't
match master HEAD.

Two viable paths to merge:

**Path A — bring everyone up to Fujiba's level**
- Apply both migrations to A6 (production sign-off required)
- Apply both to tenant1, tenant2, demo-store (no sign-off needed)
- Update `tenant_master_template` so future auto-signups get them
- Merge to master

**Path B — squash into master template only, keep migrations as files**
- Update `tenant_master_template` to include both schema changes
- Leave A6 etc. on the pre-v2 schema; the migrations file becomes
  a no-op for them via `if (Schema::hasColumn(...))` guard
- Merge to master

Path A is cleaner long-term; Path B is faster if K wants to defer the
A6 schema bump. **Decision lives with K + the user.**

### Second: feature/bot-flow-manager

Standalone deploy once the ChatPion-side patches are confirmed live
on onamae. No schema impact.

### Third: feature/catalog-download

Easiest to merge — single feature, isolated package, no schema.
Smoke test → merge.

---

## 6. Constraints honored

All hard constraints from the sprint brief were respected:

- ✅ NEVER pushed to production tenants (kimono/mmfood/a6/fujiba) — no
  tenant DB writes whatsoever
- ✅ NEVER deleted applied migrations — the two v2 migrations live on
  the branch; Fujiba's applied state stays as-is
- ✅ NEVER force-pushed or rewrote history — every commit is additive
- ✅ Ambiguous clusters went to feature branches, not master (cluster 1,
  cluster 2, cluster 4 all branched)
- ✅ All work in /var/www/bagisto — pos-native untouched
- ✅ Auth-gateway commits (0aca3a9, 8dd8883) untouched on master

No `REPO_CLEANUP_DECISIONS_NEEDED.md` was written because no hard
constraint was hit — all ambiguous work was branchable.

---

## 7. Numbers

- **Starting state:** 87 uncommitted items (50 modified + 37 untracked)
- **Ending state on master:** working tree clean
- **Commits on master:** 9 (each isolated, each with rationale)
- **Commits on feature branches:** 3 (one per branch)
- **Files moved to .gitignore:** 9 (6 chatpion-sync stamps + cron.log* glob + chatpion-deploy dir glob)
- **Files relocated:** 1 (UI-POLISH-AUDIT.md → docs/)
- **Files discarded:** 0

---

## 8. Followup memory updates suggested

Two memory entries should be updated by the next agent / human reviewer:

1. **`project_order_workflow_refactor_v2.md`** — note that the work
   now lives on `feature/order-workflow-v2`, not in working tree.
2. **`project_bot_flow_manager.md`** — same: `feature/bot-flow-manager`.

I have NOT updated these memory files in this sprint — the work isn't
finished (merged), so the memories shouldn't say it is.

#!/usr/bin/env bash
# ChatPion -> Bagisto incremental sync wrapper.
# Loads ChatPion tables into a local staging DB via SSH+mysqldump, then runs `php artisan chatpion:sync`.

set -euo pipefail

# -------- Config (edit if env changes) --------
SSH_KEY="/var/www/Fujiba1.pem"
SSH_HOST="r9382355@www1105.onamae.ne.jp"
SSH_PORT="8022"

CP_DB_HOST="mysql1026.onamae.ne.jp"
CP_DB_USER="hrlc6_fujiba_user"
CP_DB_PASS='Fujiba@202501'
CP_DB_NAME="hrlc6_chatpion_db"

BG_DB_USER="bagisto"
BG_DB_PASS="Bagisto2024"
BG_DB_NAME="tenant_1bd1181f-fcd5-4238-a12d-a207d6ea4006"
STAGE_DB="chatpion_stage"

ART_DIR="/var/www/bagisto"
STATE_DIR="${ART_DIR}/storage/chatpion-sync"
mkdir -p "$STATE_DIR"
LOG_FILE="${STATE_DIR}/sync_$(date +%Y%m%d_%H%M%S).log"

# -------- Args --------
MODE="dry-run"; ENTITY="all"; STORE="all"; SINCE=""; INIT=0
while [[ $# -gt 0 ]]; do
    case $1 in
        --apply)       MODE="apply" ;;
        --dry-run)     MODE="dry-run" ;;
        --entity=*)    ENTITY="${1#*=}" ;;
        --store=*)     STORE="${1#*=}" ;;
        --since=*)     SINCE="${1#*=}" ;;
        --init-schema) INIT=1 ;;
        -h|--help)
            sed -n '1,30p' "$0"; exit 0 ;;
        *) echo "Unknown arg: $1" >&2; exit 2 ;;
    esac
    shift
done

# -------- Helpers --------
err() { echo "[$(date +%H:%M:%S)] ERROR: $*" | tee -a "$LOG_FILE" >&2; }
log() { echo "[$(date +%H:%M:%S)] $*"        | tee -a "$LOG_FILE"; }
trap 'err "aborted on line $LINENO"' ERR

bg_root() { MYSQL_PWD="$BG_DB_PASS" mysql --default-character-set=utf8mb4 -u"$BG_DB_USER" "$@"; }
stage()   { MYSQL_PWD="$BG_DB_PASS" mysql --default-character-set=utf8mb4 -u"$BG_DB_USER" "$STAGE_DB" "$@"; }

remote_mysql() {
    ssh -i "$SSH_KEY" -p "$SSH_PORT" -o StrictHostKeyChecking=no -o ConnectTimeout=15 "$SSH_HOST" \
        "MYSQL_PWD='${CP_DB_PASS}' mysql -h${CP_DB_HOST} -u${CP_DB_USER} ${CP_DB_NAME} -N --default-character-set=utf8mb4 -e \"$1\""
}
remote_dump() {
    local table="$1" where="${2:-}" where_arg=""
    [[ -n "$where" ]] && where_arg="--where=$(printf '%q' "$where")"
    ssh -i "$SSH_KEY" -p "$SSH_PORT" -o StrictHostKeyChecking=no -o ConnectTimeout=15 "$SSH_HOST" \
        "MYSQL_PWD='${CP_DB_PASS}' mysqldump -h${CP_DB_HOST} -u${CP_DB_USER} \
           --no-tablespaces --single-transaction --skip-lock-tables \
           --skip-triggers --set-gtid-purged=OFF \
           --default-character-set=utf8mb4 --quick \
           ${CP_DB_NAME} ${table} ${where_arg}" \
      | sed -E 's#/\*!50013 DEFINER=[^*]+ *\*/##; s# DEFINER=`[^`]+`@`[^`]+`##'
}

read_watermark() {
    local store="$1"
    local ent="$2"
    local f="${STATE_DIR}/last_run_${store}_${ent}.txt"
    if [[ -n "${SINCE:-}" ]]; then echo "$SINCE 00:00:00"
    elif [[ -f "$f" ]];       then cat "$f"
    else echo "1970-01-01 00:00:00"
    fi
}
write_watermark() {
    [[ "$MODE" == "apply" ]] || return 0
    echo "$3" > "${STATE_DIR}/last_run_${1}_${2}.txt"
}

stage_load() {
    local tbl="$1" where="$2"
    log "  load: $tbl${where:+ where=$where}"
    stage -e "DROP TABLE IF EXISTS \`$tbl\`;"
    remote_dump "$tbl" "$where" | stage
}

# -------- Sanity --------
log "mode=$MODE entity=$ENTITY store=$STORE init=$INIT since-override=${SINCE:-<watermark>}"

if ! remote_mysql "SELECT 1" >/dev/null 2>&1; then
    err "cannot reach remote ChatPion DB via SSH"; exit 3
fi
bg_root -e "SELECT 1" >/dev/null || { err "cannot reach Bagisto MySQL"; exit 3; }

bg_root -e "CREATE DATABASE IF NOT EXISTS \`${STAGE_DB}\` DEFAULT CHARACTER SET utf8mb4;"

# -------- Init schema --------
if [[ "$INIT" -eq 1 ]]; then
    log "init-schema: add customers.chatpion_subscribe_id (unique by subscribe_id+channel) and backfill"
    MYSQL_PWD="$BG_DB_PASS" mysql --default-character-set=utf8mb4 -u"$BG_DB_USER" "$BG_DB_NAME" 2>&1 <<SQL | tee -a "$LOG_FILE"
ALTER TABLE customers
  ADD COLUMN IF NOT EXISTS chatpion_subscribe_id VARCHAR(64) NULL;
-- drop old single-col unique if present
DROP INDEX IF EXISTS customers_chatpion_subscribe_id_unique ON customers;
-- add composite unique if missing
CREATE UNIQUE INDEX IF NOT EXISTS customers_chatpion_subscribe_id_channel_unique
  ON customers (chatpion_subscribe_id, channel_id);
UPDATE customers
   SET chatpion_subscribe_id = SUBSTRING_INDEX(email,'@',1)
 WHERE chatpion_subscribe_id IS NULL
   AND email LIKE '%@messenger.local';
SELECT COUNT(*) total, COUNT(chatpion_subscribe_id) with_sid FROM customers;
SQL
    log "init-schema complete."
    exit 0
fi

# -------- Store list --------
case "$STORE" in
    9)   STORES=(9) ;;
    10)  STORES=(10) ;;
    all) STORES=(9 10) ;;
    *)   err "bad --store"; exit 2 ;;
esac

# -------- Load per store --------
for s in "${STORES[@]}"; do
    ts_now=$(remote_mysql "SELECT NOW()")

    if [[ "$ENTITY" == "customers" || "$ENTITY" == "all" ]]; then
        wm=$(read_watermark "$s" customers)
        stage_load "messenger_bot_subscriber" \
            "last_communicated_at >= '${wm}' OR subscribed_at >= '${wm}'"
    fi

    if [[ "$ENTITY" == "products" || "$ENTITY" == "all" ]]; then
        wm=$(read_watermark "$s" products)
        stage_load "ecommerce_product" \
            "store_id=${s} AND deleted='0' AND (updated_at IS NULL OR updated_at >= '${wm}')"
    fi

    if [[ "$ENTITY" == "orders" || "$ENTITY" == "all" ]]; then
        wm=$(read_watermark "$s" orders)
        stage_load "ecommerce_cart" \
            "store_id=${s} AND (updated_at >= '${wm}' OR ordered_at >= '${wm}') AND payment_method IS NOT NULL AND payment_method<>'' AND action_type='checkout'"
        cart_ids=$(stage -N -e "SET SESSION group_concat_max_len=4294967295; SELECT COALESCE(GROUP_CONCAT(id),'0') FROM ecommerce_cart;" | tr -d '\n')
        [[ -z "$cart_ids" ]] && cart_ids="0"
        stage_load "ecommerce_cart_item" "cart_id IN (${cart_ids})"
    fi

    # Run artisan per store (so watermarks are per-store on success)
    log "artisan: store=$s entity=$ENTITY mode=$MODE"
    cd "$ART_DIR"
    ARTISAN_ARGS=(--tenant-db="$BG_DB_NAME" --stage-db="$STAGE_DB" --entity="$ENTITY" --store="$s")
    [[ "$MODE" == "apply" ]] && ARTISAN_ARGS+=(--apply)

    if php artisan chatpion:sync "${ARTISAN_ARGS[@]}" 2>&1 | tee -a "$LOG_FILE"; then
        if [[ "$MODE" == "apply" ]]; then
            case "$ENTITY" in
                all)       write_watermark "$s" products  "$ts_now"
                           write_watermark "$s" customers "$ts_now"
                           write_watermark "$s" orders    "$ts_now" ;;
                products|customers|orders) write_watermark "$s" "$ENTITY" "$ts_now" ;;
            esac
        fi
    else
        err "artisan failed for store=$s — watermarks NOT advanced"
        exit 4
    fi
done

log "Done. Log: $LOG_FILE"
[[ "$MODE" == "dry-run" ]] && log "dry-run: no writes. Re-run with --apply."
exit 0

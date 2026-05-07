<template>
    <div class="product-stock">
        <span v-if="product.in_stock" class="stock-badge in" :class="{ low: lowStock }">
            <svg v-if="!lowStock" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            <svg v-else viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 9v4"/><path d="M12 17h.01"/>
                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <span v-if="lowStock">{{ $t('Only {n} left', { n: stockQty }) }}</span>
            <span v-else>{{ $t('In Stock') }}</span>
        </span>
        <span v-else class="stock-badge out">
            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            {{ $t('Out Of Stock') }}
        </span>
    </div>
</template>

<script>
    export default {
        name: 'product-stock',

        props: ['product'],

        computed: {
            stockQty () {
                // Bagisto exposes inventory_indices when manage_stock is true.
                if (!this.product) return 0;
                if (this.product.total_quantity != null) return parseInt(this.product.total_quantity, 10) || 0;
                var idx = this.product.inventory_indices || [];
                if (Array.isArray(idx) && idx.length) {
                    return idx.reduce(function (acc, i) { return acc + (parseInt(i.qty || 0, 10)); }, 0);
                }
                return 0;
            },
            lowStock () {
                return this.product && this.product.in_stock && this.stockQty > 0 && this.stockQty <= 5;
            },
        },
    }
</script>

<style scoped lang="scss">
    .product-stock {
        font-size: 13px;
        font-weight: 600;
        margin: 8px 0;
    }
    .stock-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 99px;
        font-size: 12px;
        font-weight: 700;

        &.in   { background: rgba(45,122,74,.10);  color: #2d7a4a; }
        &.low  { background: rgba(212,168,67,.15); color: #b8862c; }
        &.out  { background: rgba(200,34,10,.10);  color: #c8220a; }
    }
</style>

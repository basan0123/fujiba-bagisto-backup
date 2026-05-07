<template>
    <div class="order-success v2">
        <div class="success-card">
            <div class="check-burst">
                <svg viewBox="0 0 80 80" width="76" height="76">
                    <circle cx="40" cy="40" r="36" fill="none" stroke="currentColor" stroke-width="3" class="ring"/>
                    <polyline points="24 42 36 54 58 30" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" class="tick"/>
                </svg>
            </div>

            <h2>{{ $t('Thank you for your order') }}</h2>

            <p class="lead" v-if="orderId">
                {{ $t('Order') }}
                <span class="oid">#{{ orderId }}</span>
            </p>

            <p class="muted">{{ $t('We will email you an order confirmation with details and tracking info.') }}</p>

            <div class="next-row">
                <router-link class="btn-secondary" :to="'/customer/account/orders/' + orderId" v-if="orderId">
                    {{ $t('Detail') }}
                </router-link>
                <router-link class="btn-primary" :to="'/'">
                    {{ $t('Continue Shopping') }}
                </router-link>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'order-success',

        data: () => ({ orderId: null }),

        mounted () {
            if (! this.$route.params.id) {
                this.$router.push({ name: 'home' });
            } else {
                this.orderId = this.$route.params.id;
            }
        },
    };
</script>

<style scoped lang="scss">
    .order-success.v2 {
        min-height: 70vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 18px;
    }
    .success-card {
        background: #fff;
        border-radius: 22px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.07);
        padding: 36px 24px 30px;
        text-align: center;
        max-width: 440px;
        width: 100%;
    }
    .check-burst {
        color: #2d7a4a;
        margin-bottom: 18px;
        display: inline-block;
    }
    .check-burst .ring {
        stroke-dasharray: 226;
        stroke-dashoffset: 226;
        animation: ring-draw 0.6s ease-out 0.05s forwards;
    }
    .check-burst .tick {
        stroke-dasharray: 60;
        stroke-dashoffset: 60;
        animation: tick-draw 0.4s ease-out 0.55s forwards;
    }
    @keyframes ring-draw { to { stroke-dashoffset: 0; } }
    @keyframes tick-draw { to { stroke-dashoffset: 0; } }

    h2 {
        font-size: 22px;
        font-weight: 700;
        margin: 0 0 10px;
        color: #1a0800;
    }
    .lead {
        font-size: 15px;
        color: rgba(0,0,0,0.78);
        margin: 0 0 8px;
    }
    .oid {
        font-weight: 700;
        color: var(--p, #c8420a);
        margin-left: 4px;
    }
    .muted {
        font-size: 13px;
        color: rgba(0,0,0,0.55);
        margin: 0 0 22px;
        line-height: 1.5;
    }
    .next-row {
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
    }
    .btn-primary, .btn-secondary {
        display: inline-block;
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 700;
        border-radius: 12px;
        text-decoration: none;
        min-width: 140px;
        text-align: center;
    }
    .btn-primary { background: var(--p, #c8420a); color: #fff; }
    .btn-secondary { background: #f5f0eb; color: #1a0800; }
</style>

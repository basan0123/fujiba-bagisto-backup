<template>
    <div class="header">
        <!-- Back button intentionally removed — the global top nav now owns
             the only Back affordance. Pages that need a custom slot can
             still pass `slot="back-botton"` for arbitrary content. -->
        <slot name="back-botton"></slot>

        <slot name="content">
            <h2 class="display-in-bl">{{ title }}</h2>
        </slot>

        <slot name="navbar-right"></slot>
    </div>
</template>

<script>
    export default {
        name: 'custom-header',

        props: ['title'],

        methods: {
            handleBack () {
                if (
                    window.history.length <= 2
                    || this.$route.name == "dashboard"
                ) {
                    this.$router.push({name: 'home'});
                } else {
                    this.$router.back();
                }
            }
        }
    }
</script>

<style scoped lang="scss">
    .header {
        top: 0;
        width: 100%;
        z-index: 101;
        padding: 15px;
        display: block;
        position: fixed;
        max-height: 56px;
        background: #ffffff;
        border-bottom: 1px solid rgba(0, 0, 0, 0.12);

        > div,
        .content-section {
            vertical-align: top;
            display: inline-block;
        }

        .display-block {
            display: block;
        }

        .bg-black {
            background: #000000;
        }

        .icon {
            float: left;
            cursor: pointer;
            margin-right: 32px;
        }

        .home-icon {
            float: right;
            margin-right: 7px;
        }
    }
</style>
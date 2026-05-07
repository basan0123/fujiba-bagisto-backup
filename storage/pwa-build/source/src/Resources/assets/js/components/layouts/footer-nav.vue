<template>
    <div class="footer" :class="{ 'footer-hidden': isScrollingDown }">
        <span class="sepator"></span>

        <p>{{ $t('You have just reached to the bottom of page.') }}</p>

        <a @click.prevent="backToTop">{{ $t('Back To Top') }}</a>
    </div>
</template>

<script>
    export default {
        name: 'footer-nav',

        data () {
            return {
                isScrollingDown: false,
                _lastScrollY: 0,
                _ticking: false,
            }
        },

        mounted () {
            this._lastScrollY = window.scrollY;
            window.addEventListener('scroll', this._onScroll, { passive: true });
        },

        beforeDestroy () {
            window.removeEventListener('scroll', this._onScroll);
        },

        methods: {
            _onScroll () {
                if (this._ticking) return;
                this._ticking = true;
                requestAnimationFrame(() => {
                    const y = window.scrollY;
                    const delta = y - this._lastScrollY;
                    if (Math.abs(delta) >= 10) {
                        this.isScrollingDown = delta > 0;
                        this._lastScrollY = y;
                    }
                    this._ticking = false;
                });
            },

            backToTop () {
                $("html, body").animate({scrollTop: 0}, 1000);
            }
        }
    }
</script>

<style lang="scss">
    .footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 10px 0;
        text-align: center;
        background: #ffffff;
        transition: transform 0.3s ease;
        z-index: 99;

        &.footer-hidden {
            transform: translateY(100%);
        }

        .sepator {
            display: inline-block;
            border-bottom: 1px solid #F5F5F5;;
            width: 100px;
            margin-bottom: 10px;
        }

        p {
            font-size: 10px;
            margin-bottom: 10px;
            color: #757575;
        }

        a {
            font-size: 10px;
            color: #000000;
            text-transform: uppercase;
            cursor:pointer;
        }
    }
</style>

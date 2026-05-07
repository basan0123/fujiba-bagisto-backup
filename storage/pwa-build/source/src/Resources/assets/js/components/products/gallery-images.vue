<template>
    <div class="gallery-images" dir="ltr">
        <carousel
            :per-page="1"
            pagination-color="#E8E8E8"
            pagination-active-color="#979797"
        >
            <slide
                :key='image.uid'
                v-if="product.images.length"
                v-for="(image, index) in product.images"
            >
                <div>
                    <video v-if="image.type == 'video'" :src="image.small_image_url" type="video/mp4" width="100%" height="200px" controls>
                        Not Supported
                    </video>

                    <img v-else
                        alt="original-image-url"
                        :src="image.original_image_url"
                        class="zoomable"
                        @click="openLightbox(index)"
                    />
                </div>
            </slide>

            <slide v-if="! product.images.length">
                <img
                    alt="base-image-original"
                    :src="product.base_image.original_image_url"
                    class="zoomable"
                    @click="openLightbox(0, product.base_image.original_image_url)"
                />
            </slide>
        </carousel>

        <!-- Lightbox overlay -->
        <div v-if="lightboxOpen" class="pwa-lightbox" @click.self="closeLightbox">
            <button class="pwa-lightbox-close" @click="closeLightbox" aria-label="Close">×</button>
            <button v-if="hasMultiple" class="pwa-lightbox-prev" @click="prev" aria-label="Previous">‹</button>
            <img class="pwa-lightbox-img" :src="lightboxSrc" alt=""/>
            <button v-if="hasMultiple" class="pwa-lightbox-next" @click="next" aria-label="Next">›</button>
            <div v-if="hasMultiple" class="pwa-lightbox-count">
                {{ lightboxIndex + 1 }} / {{ product.images.length }}
            </div>
        </div>
    </div>
</template>

<script>
    import { Carousel, Slide } from 'vue-carousel';
    export default {
        name: 'gallery-images',

        components: { Carousel, Slide },

        props: ['product'],

        data () {
            return {
                lightboxOpen: false,
                lightboxIndex: 0,
                lightboxOverrideSrc: null,
            }
        },

        computed: {
            hasMultiple () {
                return this.product && this.product.images && this.product.images.length > 1;
            },
            lightboxSrc () {
                if (this.lightboxOverrideSrc) return this.lightboxOverrideSrc;
                var imgs = (this.product && this.product.images) || [];
                var img  = imgs[this.lightboxIndex];
                return img ? (img.original_image_url || img.small_image_url) : '';
            },
        },

        methods: {
            openLightbox (index, overrideSrc) {
                this.lightboxIndex       = index || 0;
                this.lightboxOverrideSrc = overrideSrc || null;
                this.lightboxOpen        = true;
                document.body.style.overflow = 'hidden';
                document.addEventListener('keydown', this._onKey);
                // Tell the chrome (top nav) to hide while the user is zoomed in.
                EventBus.$emit('pwa.zoom.open');
            },
            closeLightbox () {
                this.lightboxOpen        = false;
                this.lightboxOverrideSrc = null;
                document.body.style.overflow = '';
                document.removeEventListener('keydown', this._onKey);
                EventBus.$emit('pwa.zoom.close');
            },
            prev () {
                var n = this.product.images.length;
                this.lightboxIndex = (this.lightboxIndex - 1 + n) % n;
            },
            next () {
                var n = this.product.images.length;
                this.lightboxIndex = (this.lightboxIndex + 1) % n;
            },
            _onKey (e) {
                if (e.key === 'Escape')     this.closeLightbox();
                if (e.key === 'ArrowLeft')  this.prev();
                if (e.key === 'ArrowRight') this.next();
            },
        },

        beforeDestroy () {
            if (this.lightboxOpen) this.closeLightbox();
        },
    }
</script>

<style lang="scss" scoped>
    .VueCarousel-wrapper {
        img {
            max-width: 275px;
            max-height: 275px;
        }
    }
    img.zoomable { cursor: zoom-in; }

    .pwa-lightbox {
        position: fixed; inset: 0; z-index: 9999;
        background: rgba(0, 0, 0, 0.92);
        display: flex; align-items: center; justify-content: center;
        animation: pwaLightFade 0.18s ease-out;
    }
    @keyframes pwaLightFade { from { opacity: 0; } to { opacity: 1; } }

    .pwa-lightbox-img {
        max-width: 92vw; max-height: 88vh;
        object-fit: contain;
        animation: pwaLightZoom 0.2s ease-out;
    }
    @keyframes pwaLightZoom { from { transform: scale(0.96); } to { transform: scale(1); } }

    .pwa-lightbox-close, .pwa-lightbox-prev, .pwa-lightbox-next {
        position: absolute;
        background: rgba(255, 255, 255, 0.12);
        border: 0; color: #fff; font-size: 32px; line-height: 1;
        width: 44px; height: 44px; border-radius: 50%;
        cursor: pointer; transition: background 0.18s;
        display: flex; align-items: center; justify-content: center;
        -webkit-tap-highlight-color: transparent;
    }
    .pwa-lightbox-close:hover, .pwa-lightbox-prev:hover, .pwa-lightbox-next:hover {
        background: rgba(255, 255, 255, 0.22);
    }
    .pwa-lightbox-close { top: max(env(safe-area-inset-top, 16px), 16px); right: 16px; }
    .pwa-lightbox-prev  { left: 12px;  top: 50%; transform: translateY(-50%); }
    .pwa-lightbox-next  { right: 12px; top: 50%; transform: translateY(-50%); }
    .pwa-lightbox-count {
        position: absolute; bottom: 24px; left: 50%; transform: translateX(-50%);
        color: rgba(255, 255, 255, 0.7); font-size: 13px; font-weight: 500;
        background: rgba(0, 0, 0, 0.4); padding: 4px 12px; border-radius: 99px;
    }
</style>

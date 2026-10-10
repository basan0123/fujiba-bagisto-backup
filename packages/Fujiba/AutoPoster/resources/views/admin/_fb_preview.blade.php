{{-- Live Facebook post preview.

     <v-fb-preview> mirrors how the post will look in the News Feed. It reads
     the caption / format straight from the page's form controls (by CSS
     selector) so the Blade forms stay plain HTML, and it follows carousel
     reordering through the window "ap:items" event.

     Props: items (cards: name, price, image), page-name, page-avatar,
     caption-source, format-source, format, messenger, caption. --}}

@pushOnce('scripts')
    <script type="text/x-template" id="v-fb-preview-template">
        <div>
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px;">
                <span class="ap-muted ap-small">How it will look on Facebook</span>
                <div class="ap-device-toggle">
                    <button type="button" :class="{ 'is-active': device === 'desktop' }" @click="device = 'desktop'">Desktop</button>
                    <button type="button" :class="{ 'is-active': device === 'mobile' }" @click="device = 'mobile'">Mobile</button>
                </div>
            </div>

            <div class="ap-preview-stage" :class="{ 'is-mobile': device === 'mobile' }">
                <div class="ap-fb">
                    <div class="ap-fb-head">
                        <img v-if="pageAvatar" :src="pageAvatar" alt="" class="ap-fb-avatar" style="background: #fff;">
                        <div v-else class="ap-fb-avatar">@{{ initials }}</div>
                        <div>
                            <div class="ap-fb-name">@{{ pageName }}</div>
                            <div class="ap-fb-meta">
                                @{{ when }} ·
                                <svg viewBox="0 0 16 16" width="12" height="12" fill="currentColor"><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm5.9 7.3h-2.4a12.6 12.6 0 0 0-1-4.6 6.5 6.5 0 0 1 3.4 4.6zM8 1.5c.7.9 1.4 2.8 1.6 5.8H6.4c.2-3 .9-4.9 1.6-5.8zM5.5 2.7a12.6 12.6 0 0 0-1 4.6H2.1a6.5 6.5 0 0 1 3.4-4.6zM2.1 8.7h2.4c.1 1.8.4 3.4 1 4.6a6.5 6.5 0 0 1-3.4-4.6zM8 14.5c-.7-.9-1.4-2.8-1.6-5.8h3.2c-.2 3-.9 4.9-1.6 5.8zm2.5-1.2c.6-1.2.9-2.8 1-4.6h2.4a6.5 6.5 0 0 1-3.4 4.6z"/></svg>
                            </div>
                        </div>
                        <span class="ap-fb-dots">···</span>
                    </div>

                    <div v-if="captionText" class="ap-fb-text" :class="{ 'is-collapsed': collapsible && ! expanded }" v-html="captionHtml"></div>
                    <div v-else class="ap-fb-text ap-fb-empty">Your caption will appear here…</div>
                    <button v-if="collapsible" type="button" class="ap-fb-more" @click="expanded = ! expanded">
                        @{{ expanded ? 'See less' : 'See more' }}
                    </button>

                    {{-- Carousel --}}
                    <div v-if="currentFormat !== 'single_image' && list.length" class="ap-fb-carousel">
                        <div v-for="(item, i) in list" :key="item.product_id || i" class="ap-fb-card" :style="{ animationDelay: (i * 40) + 'ms' }">
                            <img v-if="item.image" :src="item.image" alt="" loading="lazy">
                            <div v-else class="ap-fb-noimg">No image</div>
                            <div class="ap-fb-card-body">
                                <div style="min-width: 0;">
                                    <div class="ap-fb-card-title">@{{ item.name }}</div>
                                    <div class="ap-fb-card-sub">@{{ price(item.price) || domain }}</div>
                                </div>
                                <span class="ap-fb-cta">Send message</span>
                            </div>
                        </div>
                    </div>

                    {{-- Single image + link --}}
                    <div v-else-if="list.length" class="ap-fb-single">
                        <img v-if="list[0].image" :src="list[0].image" alt="">
                        <div v-else class="ap-fb-noimg">No image</div>
                        <div class="ap-fb-card-body">
                            <div style="min-width: 0;">
                                <div class="ap-fb-card-sub" style="text-transform: uppercase; font-size: 12px;">@{{ domain }}</div>
                                <div class="ap-fb-card-title">@{{ list[0].name }}</div>
                            </div>
                            <span class="ap-fb-cta">Send message</span>
                        </div>
                    </div>

                    <div v-else class="ap-fb-empty" style="padding: 30px 16px; text-align: center; border-top: 1px solid #e4e6eb;">
                        Select products to see the carousel
                    </div>

                    <div class="ap-fb-stats">
                        <span class="ap-fb-react">
                            <span><i style="background: #1877f2;">👍</i><i style="background: #f33e58; margin-left: -4px;">❤</i></span>
                            <span>24</span>
                        </span>
                        <span>3 comments · 2 shares</span>
                    </div>
                    <div class="ap-fb-bar">
                        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 10v11H3V10zM7 10l4-8a3 3 0 0 1 3 3v4h5.5a2 2 0 0 1 2 2.3l-1.4 8A2 2 0 0 1 18 21H7"/></svg>Like</span>
                        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a8 8 0 0 1-11.6 7.1L3 21l1.9-6.4A8 8 0 1 1 21 12z"/></svg>Comment</span>
                        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 5 6 6-6 6M21 11H9a6 6 0 0 0-6 6v2"/></svg>Share</span>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-fb-preview', {
            template: '#v-fb-preview-template',

            props: {
                items:         { type: Array,  default: () => [] },
                pageName:      { type: String, default: 'Your Page' },
                pageAvatar:    { type: String, default: '' },
                caption:       { type: String, default: '' },
                captionSource: { type: String, default: '' },
                format:        { type: String, default: 'multi_photo' },
                formatSource:  { type: String, default: '' },
                messenger:     { type: String, default: '' },
                when:          { type: String, default: 'Just now' },
            },

            data() {
                return {
                    list: [...this.items],
                    captionText: this.caption,
                    currentFormat: this.format,
                    device: 'desktop',
                    expanded: false,
                };
            },

            computed: {
                initials() {
                    return (this.pageName || '?').trim().slice(0, 1).toUpperCase();
                },

                domain() {
                    try {
                        return new URL(this.messenger || location.origin).host.replace(/^www\./, '');
                    } catch (e) {
                        return location.host;
                    }
                },

                collapsible() {
                    return this.captionText.split('\n').length > 6 || this.captionText.length > 380;
                },

                // Escape first, then linkify URLs and highlight hashtags the
                // way Facebook renders them.
                captionHtml() {
                    const esc = this.captionText
                        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

                    return esc
                        .replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>')
                        .replace(/(^|\s)(#[\p{L}\p{N}_]+)/gu, '$1<a>$2</a>');
                },
            },

            mounted() {
                this.bind(this.captionSource, 'input', el => { this.captionText = el.value; });
                this.bind(this.formatSource, 'change', el => { this.currentFormat = el.value; });

                this.onItems = e => { this.list = [...e.detail]; };
                window.addEventListener('ap:items', this.onItems);
            },

            beforeUnmount() {
                window.removeEventListener('ap:items', this.onItems);
            },

            methods: {
                bind(selector, event, fn) {
                    if (! selector) {
                        return;
                    }

                    // The source controls are rendered in the same Vue pass;
                    // wait a tick so they exist in the DOM.
                    this.$nextTick(() => {
                        const el = document.querySelector(selector);

                        if (! el) {
                            return;
                        }

                        fn(el);
                        el.addEventListener(event, () => fn(el));
                    });
                },

                price(p) {
                    if (! (Number(p) > 0)) {
                        return '';
                    }

                    return '¥' + Number(p).toLocaleString('ja-JP', { maximumFractionDigits: 0 });
                },
            },
        });
    </script>
@endPushOnce

<template>
    <div class="content" v-if="category">
        <layered-navigation
            :category-id="category.id"
            @onFilterApplied="filterProducts($event)"
            v-show="category.display_mode == 'products_only' || category.display_mode == 'products_and_description'"
            >
        </layered-navigation>

        <div class="category-banner" v-if="category.image_url">
            <img alt="category-image" :src="category.image_url"/>
        </div>

        <div
            v-if="category.display_mode == 'description_only' || category.display_mode == 'products_and_description'"
            class="category-description panel">
                <div class="panel-content" v-html="category.description">
                </div>
        </div>

        <div v-if="category.display_mode == 'products_only' || category.display_mode == 'products_and_description'">
            <div class="panel" v-if="products.length">
                <div class="panel-content">
                    <div class="view-toggle">
                        <span class="view-label">{{ products.length }} {{ $t('items') }}</span>
                        <div class="view-btns">
                            <button class="vbtn" :class="{ act: gridCols === 2 }" @click="setCols(2)" :title="$t('2 per row')">
                                <svg width="14" height="14" viewBox="0 0 14 14"><rect x="0" y="0" width="6" height="6" rx="1" fill="currentColor"/><rect x="8" y="0" width="6" height="6" rx="1" fill="currentColor"/><rect x="0" y="8" width="6" height="6" rx="1" fill="currentColor"/><rect x="8" y="8" width="6" height="6" rx="1" fill="currentColor"/></svg>
                            </button>
                            <button class="vbtn" :class="{ act: gridCols === 3 }" @click="setCols(3)" :title="$t('3 per row')">
                                <svg width="14" height="14" viewBox="0 0 14 14"><rect x="0" y="0" width="3.5" height="6" rx="1" fill="currentColor"/><rect x="5.25" y="0" width="3.5" height="6" rx="1" fill="currentColor"/><rect x="10.5" y="0" width="3.5" height="6" rx="1" fill="currentColor"/><rect x="0" y="8" width="3.5" height="6" rx="1" fill="currentColor"/><rect x="5.25" y="8" width="3.5" height="6" rx="1" fill="currentColor"/><rect x="10.5" y="8" width="3.5" height="6" rx="1" fill="currentColor"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="product-list" :class="'product-grid-' + gridCols">

                        <product-card v-for="product in products" :key='product.uid' :product="product"></product-card>

                    </div>

                    <pagination :label="$t('Load More Products')" v-bind="productPagination" @onPaginate="paginate($event)"></pagination>
                </div>
            </div>

            <no-product-found v-else></no-product-found>
        </div>

        <div class="panel" v-if="childCategories.length">
            <div class="panel-heading">
                {{ $t('Explore') }} {{ category.name }}
            </div>

            <div class="panel-content">
                <ul class="category-list">
                    <li v-for="category in childCategories" :key="category.id">
                        <router-link :to="'/categories/' + category.id">
                            {{ category.name }}

                            <i class="icon sharp-arrow-right-icon"></i>
                        </router-link>
                    </li>
                </ul>
            </div>
        </div>

        <div v-if="category.display_mode == 'description_only'" class="panel" style="margin-bottom: 0">
            <div class="panel-content">
                <footer-nav></footer-nav>
            </div>
        </div>
    </div>
</template>

<script>
    import FooterNav         from '../layouts/footer-nav';
    import LayeredNavigation from './layered-navigation';
    import ProductCard       from '../products/card';
    import Pagination        from '../shared/pagination';
    import NoProductFound    from './no-product-found';

    export default {
        name: 'category',

        components: { LayeredNavigation, FooterNav, ProductCard, Pagination, NoProductFound },

        data () {
            var savedCols = 2;
            try {
                var c = parseInt(localStorage.getItem('pwa.gridCols') || '2', 10);
                if (c === 2 || c === 3) savedCols = c;
            } catch (_) {}
			return {
				category: null,

				childCategories: [],

                products: [],

                productPagination: {},

                gridCols: savedCols,

				params: {
                    'category_id': this.$route.params.id,
                    'limit': 1
                },
			}
		},

        watch: {
            '$route.params.id': function (id) {
                this.params = { 'category_id': id };

                this.getCategory(id);
            }
        },

        mounted () {
            this.getCategory(this.$route.params.id);
        },

        methods: {
            setCols (n) {
                this.gridCols = n;
                try { localStorage.setItem('pwa.gridCols', String(n)); } catch (_) {}
            },

            getCategory (categoryId) {
                var this_this = this;

                EventBus.$emit('show-ajax-loader');

                this.$http.get('/api/v1/categories/' + categoryId)
                    .then(function(response) {

                        this_this.category = response.data.data;

                        this_this.childCategories = [];

                        if (this_this.category.display_mode == 'description_only') {
                            this_this.getChildCategories(categoryId);
                        }

                        this_this.products = [];

                        this_this.getProducts();

                        EventBus.$emit('hide-ajax-loader');
                    })
                    .catch(function (error) {});
            },

            getChildCategories (categoryId) {
                var this_this = this;

                EventBus.$emit('show-ajax-loader');

                this.$http.get('/api/v1/descendant-categories', { params: { parent_id: categoryId } })
                    .then(function(response) {
                        this_this.childCategories = response.data.data;

                        EventBus.$emit('hide-ajax-loader');
                    })
                    .catch(function (error) {});
            },

            getProducts () {
                if (this.category.display_mode == 'description_only')
                    return;

                var this_this = this;

                EventBus.$emit('show-ajax-loader');

                this.$http.get("/api/v1/products", { params: this.params })
                    .then(function(response) {
                        EventBus.$emit('hide-ajax-loader');

                        response.data.data.forEach(function(product) {
                            this_this.products.push(product);
                        });

                        this_this.productPagination = response.data.meta;
                    })
                    .catch(function (error) {});
            },

            filterProducts (filters) {
                this.products = [];

                delete this.params['page'];

                for(var key in filters) {
                    if (key == 'sort')  {
                        this.params['sort'] = filters.sort;
                    } else {
                        if (filters[key].length) {
                            if (key == 'price') {
                                if (filters[key][1]) {
                                    this.params[key] = filters[key].join(',');
                                }
                            } else {
                                this.params[key] = filters[key].join(',');
                            }
                        } else {
                            delete this.params[key];
                        }
                    }
                }

                this.getProducts();
            },

            paginate (page) {
                this.params['page'] = page;

                this.getProducts();
            }
        }
    }
</script>

<style lang="scss" scoped>
    @import '~@/_variables.scss';

    .category-banner {
        margin-bottom : 28px;

        img {
            width: 100%;
        }
    }

    .category-list {
        li {
            margin-bottom: 8px;

            &:last-child {
                margin-bottom: 0;
            }

            a {
                background: #F5F5F5;
                font-size: 16px;
                color: #000000;
                padding: 15px;
                display: block;
                text-transform: uppercase;

                .icon {
                    float: right;
                }
            }
        }
    }

    .view-toggle {
        display: flex; align-items: center; justify-content: space-between;
        padding: 8px 12px;
    }
    .view-label { font-size: 11px; color: #9a7a5a; font-weight: 500; }
    .view-btns  { display: flex; gap: 3px; background: #f2ede8; padding: 3px; border-radius: 8px; }
    .vbtn {
        width: 28px; height: 26px; border: 0; background: transparent;
        border-radius: 6px; color: #9a7a5a; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: 0.18s;
    }
    .vbtn.act { background: #fff; color: var(--p, #c8420a); box-shadow: 0 1px 3px rgba(0,0,0,0.07); }

    .product-list.product-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 4px 10px 12px; }
    .product-list.product-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 7px; padding: 4px 10px 12px; }
</style>

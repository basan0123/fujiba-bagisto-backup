<template>
    <div class="layered-filter-wrapper">
        <div class="filter" v-sticky>
            <ul>
                <li @click="bottomSheets.sort = true">
                    <i class="icon sharp-sort-icon"></i>
                    {{ $t('Sort') }}
                </li>

                <li @click="bottomSheets.filter = true">
                    <i class="icon sharp-filter-icon"></i>
                    {{ $t('Filter') }}
                    <span v-if="activeFilterCount" class="chip-count">{{ activeFilterCount }}</span>
                </li>
            </ul>
        </div>

        <div class="quick-chip-strip" v-if="quickChips.length">
            <button
                v-for="chip in quickChips"
                :key="chip.code + ':' + chip.id"
                class="qchip"
                :class="{ on: isChipActive(chip), color: chip.swatch }"
                :style="chip.swatch ? { background: chip.swatch } : null"
                @click="toggleChip(chip)"
                :title="chip.label">
                <span v-if="!chip.swatch">{{ chip.label }}</span>
                <i v-if="chip.swatch && isChipActive(chip)" class="icon sharp-done-white"></i>
            </button>
            <button v-if="activeFilterCount" class="qchip clear" @click="clearAndApply">
                {{ $t('Clear') }} ✕
            </button>
        </div>

        <bottom-sheet :show="bottomSheets.sort" @onBottomSheetClose="bottomSheets.sort = false;">
            <div slot="header">
                {{ $t('Sort By') }}
            </div>

            <div slot="content">
                <ul>
                    <li :key="index" v-for="(option, index) in sortOptions">
                        <span class="radio" :class="option.code">
                            <input
                                type="radio"
                                :id="option.code"
                                name="sort"
                                v-bind:value="option.code"
                                v-model="appliedFilters.sort"
                                @change="sort()">

                            <label class="radio-view" :for="sort.code"></label>

                            {{ option.label }}
                        </span>
                    </li>
                </ul>
            </div>
        </bottom-sheet>

        <bottom-sheet :show="bottomSheets.filter" :class-name="'filter-bottom-sheet'" @onBottomSheetClose="bottomSheets.filter = false;">
            <div slot="header">
                {{ $t('Filters') }}

                <span class="label label-grey" @click="clearFilter">{{ $t('Clear All') }}</span>
            </div>

            <div slot="content">
                <div class="filter-attributes">
                    <div class="filter-item" :key="index" v-for="(attribute, index) in attributes">

                        <div class="filter-title">
                            {{ attribute.name }}
                        </div>

                        <div class="filter-content" :class="[attribute.name, attribute.type]">
                            <ol v-if="attribute.type == 'select' && attribute.code != 'color'">
                                <li :key="index" v-for="(option, index) in attribute.options">
                                    <label :for="attribute.code + '_' + option.id"></label>

                                    <input type="checkbox"
                                        :id="attribute.code + '_' + option.id"
                                        v-bind:value="option.id"
                                        v-model="appliedFilters[attribute.code]"/>

                                    {{ option.name }}

                                    <i class="icon sharp-done-green"></i>
                                </li>
                            </ol>

                            <div class="swatch-container" v-if="attribute.type == 'select' && attribute.code == 'color'">
                                <label
                                    class="swatch"
                                    :key="index" v-for="(option, index) in attribute.options"
                                    :for="attribute.code + '_' + option.id"
                                    :style="{ background: option.name }">
                                    <input type="checkbox"
                                        :id="attribute.code + '_' + option.id"
                                        v-bind:value="option.id"
                                        v-model="appliedFilters[attribute.code]"/>

                                    <i class="icon sharp-done-white"></i>
                                </label>
                            </div>

                            <div class="price-slider" v-if="attribute.type == 'price'">
                                <div class="price-range">
                                    <span class="min-price">{{ appliedFilters[attribute.code][0] | currency }}</span>

                                    <span class="max-price">{{ appliedFilters[attribute.code][1] | currency }}</span>
                                </div>

                                <vue-slider
                                    ref="slider"
                                    v-model="appliedFilters[attribute.code]"
                                    v-bind="sliderConfig"
                                    @callback="priceRangeUpdated(attribute.code, $event)"
                                    @onCallback="priceRangeUpdated(attribute.code, $event)"
                                ></vue-slider>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="filter-action">
                    <span class="cancel-btn" @click="cancelFilter">{{ $t('Cancel') }}</span>

                    <button class="btn btn-black" @click="applyFilter">{{ $t('Apply') }}</button>
                </div>
            </div>
        </bottom-sheet>
    </div>
</template>

<script>
    import BottomSheet from '../shared/bottom-sheet';
    import VueSlider   from 'vue-slider-component';
    import 'vue-slider-component/theme/antd.css';

    export default {
        name: 'layered-navigation',

        props: ['categoryId'],

        components: { BottomSheet, VueSlider },

        data () {
			return {
                bottomSheets: {
                    sort: false,
                    filter: false
                },

                sortOptions: [
                    {
                        'code': 'name-asc',
                        'label': this.$t('From A-Z'),
                        'sort': 'name',
                        'order': 'asc'
                    }, {
                        'code': 'name-desc',
                        'label': this.$t('From Z-A'),
                        'sort': 'name',
                        'order': 'desc'
                    }, {
                        'code': 'created_at-desc',
                        'label': this.$t('Newest First'),
                        'sort': 'created_at',
                        'order': 'desc'
                    }, {
                        'code': 'created_at-asc',
                        'label': this.$t('Oldest First'),
                        'sort': 'created_at',
                        'order': 'asc'
                    }, {
                        'code': 'price-asc',
                        'label': this.$t('Cheapest First'),
                        'sort': 'price',
                        'order': 'asc'
                    }, {
                        'code': 'price-desc',
                        'label': this.$t('Expensive First'),
                        'sort': 'price',
                        'order': 'desc'
                    }
                ],

                attributes: [],

                appliedFilters: {},

                sliderConfig: {
                    lazy: true,
                    max: 5000,
                    dotSize: 10,
                    height: 2,
                    processStyle: {
                        'backgroundColor': '#616161'
                    },
                    tooltipStyle: {
                        'display': 'none'
                    }
                },

                page: 1,
			}
		},

        computed: {
            activeFilterCount () {
                var n = 0;
                for (var k in this.appliedFilters) {
                    var v = this.appliedFilters[k];
                    if (k === 'sort') continue;
                    if (k === 'price' && Array.isArray(v) && v[1]) { n++; continue; }
                    if (Array.isArray(v) && v.length) n += v.length;
                }
                return n;
            },
            quickChips () {
                var out = [];
                var preferred = ['color', 'brand', 'size', 'material'];
                var seen = {};
                var self = this;
                preferred.forEach(function (code) {
                    var attr = self.attributes.find(function (a) { return a.code === code && a.type === 'select'; });
                    if (!attr || !attr.options) return;
                    seen[code] = true;
                    attr.options.slice(0, 6).forEach(function (opt) {
                        out.push({
                            code: code,
                            id: opt.id,
                            label: opt.name || opt.label,
                            swatch: code === 'color' ? (opt.swatch_value || opt.name) : null,
                        });
                    });
                });
                this.attributes.forEach(function (attr) {
                    if (seen[attr.code] || attr.type !== 'select' || !attr.options) return;
                    if (out.length >= 14) return;
                    attr.options.slice(0, 4).forEach(function (opt) {
                        if (out.length >= 14) return;
                        out.push({ code: attr.code, id: opt.id, label: opt.name || opt.label, swatch: null });
                    });
                });
                return out;
            },
        },

        mounted () {
            this.getFilerableAttributes();
        },

        watch: {
            '$route.params.id': function (id) {
                this.clearFilter()
            }
        },

        methods: {
            getFilerableAttributes () {
                var self = this;
                EventBus.$emit('show-ajax-loader');

                this.$http.get('/api/categories/attributes', { params: {
                        category_id: this.categoryId,
                    }
                })
                .then(response => {
                    self.attributes = response.data.data;

                    EventBus.$emit('hide-ajax-loader');

                    self.attributes.forEach(attribute => {
                        self.$set(self.appliedFilters, attribute.code, attribute.type == 'price' ? [0, 0] : []);

                        if (attribute.type === 'select' && (!attribute.options || !attribute.options.length)) {
                            self.loadAttributeOptions(attribute);
                        }
                    });
                })
                .catch(function (error) {});
            },

            loadAttributeOptions (attribute) {
                var self = this;
                this.$http.get('/api/categories/attributes/' + attribute.id + '/options')
                    .then(function (resp) {
                        var opts = (resp && resp.data && resp.data.data) || [];
                        var idx = self.attributes.findIndex(function (a) { return a.id === attribute.id; });
                        if (idx !== -1) {
                            self.$set(self.attributes[idx], 'options', opts);
                            self.attributes = self.attributes.slice();
                        }
                    })
                    .catch(function () {});
            },

            cancelFilter () {
                this.bottomSheets.filter = false;

                this.clearFilter();
            },

            clearFilter () {
                for (var key in this.appliedFilters) {
                    this.$set(this.appliedFilters, key, key == 'price' ? [0, 0] : [])
                }
            },

            applyFilter () {
                this.bottomSheets.filter = false;

                this.$emit('onFilterApplied', this.appliedFilters)
            },

            sort () {
                this.bottomSheets.sort = false;

                this.$emit('onFilterApplied', this.appliedFilters)
            },

            isChipActive (chip) {
                var arr = this.appliedFilters[chip.code];
                return Array.isArray(arr) && arr.indexOf(chip.id) !== -1;
            },

            toggleChip (chip) {
                if (!Array.isArray(this.appliedFilters[chip.code])) {
                    this.$set(this.appliedFilters, chip.code, []);
                }
                var arr = this.appliedFilters[chip.code].slice();
                var i = arr.indexOf(chip.id);
                if (i === -1) arr.push(chip.id); else arr.splice(i, 1);
                this.$set(this.appliedFilters, chip.code, arr);
                this.$emit('onFilterApplied', this.appliedFilters);
            },

            clearAndApply () {
                this.clearFilter();
                this.$emit('onFilterApplied', this.appliedFilters);
            },
        }
    }
</script>

<style lang="scss">
    .quick-chip-strip {
        display: flex;
        gap: 6px;
        overflow-x: auto;
        padding: 8px 12px 10px;
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .quick-chip-strip::-webkit-scrollbar { display: none; }
    .qchip {
        flex: 0 0 auto;
        border: 1px solid rgba(0,0,0,0.12);
        background: #fff;
        color: rgba(0,0,0,0.78);
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        transition: 0.15s;
    }
    .qchip:hover { border-color: var(--p, #c8420a); }
    .qchip.on {
        background: var(--p, #c8420a);
        color: #fff;
        border-color: var(--p, #c8420a);
    }
    .qchip.color {
        width: 28px; height: 28px; padding: 0;
        border-radius: 50%;
        position: relative;
        overflow: hidden;
    }
    .qchip.color.on {
        box-shadow: 0 0 0 2px var(--p, #c8420a);
        border-color: #fff;
    }
    .qchip.color .icon { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); }
    .qchip.clear {
        background: rgba(200,34,10,0.08);
        color: #c8220a;
        border-color: transparent;
    }

    .chip-count {
        display: inline-block;
        background: var(--p, #c8420a);
        color: #fff;
        font-size: 10px;
        line-height: 16px;
        min-width: 16px;
        padding: 0 4px;
        border-radius: 8px;
        margin-left: 6px;
        text-align: center;
        vertical-align: middle;
    }

    .layered-filter-wrapper {
        height: auto;
        width: 100%;
        z-index: 50;
        position: sticky;
        top: 56px;
        background: var(--bg, #fff);

        .filter {
            z-index: 10;
            background: #FFFFFF;
            box-shadow: 0 10px 10px 0 rgba(0, 0, 0, 0.04), 0 1px 4px 0 rgba(0, 0, 0, 0.16);
            width: 100%;
            height: 56px;

            &.fixed-header {
                position: fixed;
                top: 0;
                left: 0;
            }

            ul {
                li {
                    // width: 33.33%;
                    width: 50%;
                    float: left;
                    padding: 16px;
                    font-size: 14px;
                    color: rgba(0, 0, 0, 0.86);
                    font-weight: 600;
                    text-transform: uppercase;
                    cursor: pointer;

                    .icon {
                        vertical-align: middle;
                        margin-right: 8px;
                    }
                }
            }
        }
    }

    .filter-bottom-sheet {
        .bottom-sheet-content {
            overflow-y: auto;
            padding-bottom: 68px;
            background: #F5F5F5;
        }
    }

    .filter-bottom-sheet {
        .bottom-sheet-header {
            .label {
                float: right;
            }
        }
    }

    .filter-attributes {
        background: #F5F5F5;

        .filter-item {
            margin-bottom: 12px;
            background: #ffffff;

            &:last-child {
                margin-bottom: 0;
            }

            .filter-title {
                padding: 16px;
                font-size: 12px;
                text-transform: uppercase;
                color: rgba(0, 0, 0, 0.6);
                border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            }

            &:first-child {
                .filter-title {
                    border-top: 1px solid rgba(0, 0, 0, 0.08);
                }
            }

            .filter-content {
                width: 100%;
                padding-left: 16px;
                display: inline-block;

                &.color {
                    padding: 5px;
                }

                &.price {
                    padding: 16px;
                }

                ol {
                    padding: 0;
                    list-style: none;

                    li {
                        border-bottom: 1px solid rgba(0, 0, 0, 0.08);
                        padding-left: 0px !important;
                        position: relative;
                        font-size: 14px;
                        font-weight: 500;

                        label {
                            position: absolute;
                            width: 100%;
                            height: 100%;
                            top: 0;
                        }

                        input {
                            display: none;
                        }

                        &:last-child {
                            border-bottom: 0;
                        }

                        .icon.sharp-done-green {
                            float: right;
                            display: none;
                        }

                        input:checked + .icon.sharp-done-green {
                            display: inline-block;
                        }
                    }
                }

                .swatch-container {
                    .swatch {
                        width: 48px;
                        height: 48px;
                        float: left;
                        padding: 11px;
                        margin: 10px;

                        input {
                            display: none;
                        }

                        .icon.sharp-done-white {
                            display: none;
                        }

                        input:checked + .icon.sharp-done-white {
                            display: inline-block;
                        }
                    }
                }

                .price-slider {
                    .price-range {
                        width: 100%;
                        float: left;
                        margin-bottom: 16px;

                        .min-price {
                            float: left;
                        }

                        .max-price {
                            float: right;
                        }
                    }
                }
            }
        }
    }

    .filter-action {
        position: fixed;
        bottom: 0;
        padding: 6px 8px;
        width: 100%;
        background: #ffffff;
        box-shadow: 0 -10px 10px 0 rgba(0, 0, 0, 0.04), 0 -1px 4px 0 rgba(0, 0, 0, 0.16);

        .cancel-btn {
            font-size: 14px;
            color: rgba(0,0,0,0.56);
            padding: 13px 50px;
            text-transform: uppercase;
            float: left;
        }

        .btn {
            float: right;
        }
    }
</style>

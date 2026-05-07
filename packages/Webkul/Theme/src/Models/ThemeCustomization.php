<?php

namespace Webkul\Theme\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Webkul\Admin\Database\Factories\ThemeFactory;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\Theme\Contracts\ThemeCustomization as ThemeCustomizationContract;

class ThemeCustomization extends TranslatableModel implements ThemeCustomizationContract
{
    use HasFactory;

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatedAttributes = [
        'options',
    ];

    /**
     * With the translations given attributes
     *
     * @var array
     */
    protected $with = ['translations'];

    /**
     * Image carousel precision.
     *
     * @var string
     */
    public const IMAGE_CAROUSEL = 'image_carousel';

    /**
     * Product carousel precision.
     *
     * @var string
     */
    public const PRODUCT_CAROUSEL = 'product_carousel';

    /**
     * Category carousel precision.
     *
     * @var string
     */
    public const CATEGORY_CAROUSEL = 'category_carousel';

    /**
     * Footer links precision.
     *
     * @var string
     */
    public const FOOTER_LINKS = 'footer_links';

    /**
     * Static precision.
     *
     * @var string
     */
    public const STATIC_CONTENT = 'static_content';

    /**
     * All products grid with infinite scroll.
     *
     * @var string
     */
    public const ALL_PRODUCTS_GRID = 'all_products_grid';

    /**
     * Services Content.
     *
     * @var string
     */
    public const SERVICES_CONTENT = 'services_content';

    /**
     * Product grid with attribute-based filters.
     *
     * @var string
     */
    public const PRODUCT_GRID_FILTER = 'product_grid_filter';

    /**
     * Cast options field to array.
     *
     * @var array
     */
    protected $casts = [
        'options' => 'array',
    ];

    /**
     * Add fillable properties
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'name',
        'options',
        'sort_order',
        'status',
        'channel_id',
        'theme_code',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return ThemeFactory::new();
    }

    /**
     * Resolve translatable `options`. Falls back to the channel's default
     * locale when the requested locale has no translation row, or has an
     * empty options payload. Lets admins translate per-locale when they
     * want to, while unedited locales transparently inherit the default.
     */
    public function getOptionsAttribute()
    {
        $locale = app()->getLocale();

        $hasContent = function ($trans) {
            if (! $trans) {
                return false;
            }
            $raw = $trans->getAttributes()['options'] ?? null;
            return ! empty($raw) && $raw !== '[]' && $raw !== 'null';
        };

        $current = $this->translate($locale);
        if ($hasContent($current)) {
            return $current->options;
        }

        $channelDefault = optional(optional(\Webkul\Core\Models\Channel::find($this->channel_id))->default_locale)->code;
        if ($channelDefault && $channelDefault !== $locale) {
            $fallback = $this->translate($channelDefault);
            if ($hasContent($fallback)) {
                return $fallback->options;
            }
        }

        foreach ($this->translations as $trans) {
            if ($trans->locale === $locale || $trans->locale === $channelDefault) {
                continue;
            }
            if ($hasContent($trans)) {
                return $trans->options;
            }
        }

        return $current ? $current->options : [];
    }
}

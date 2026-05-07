<?php

namespace Webkul\Pos\Queries\Common;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Core\Repositories\ExchangeRateRepository;
use Webkul\Tax\Repositories\TaxCategoryRepository;

class GlobalDataQuery extends Controller
{
    /**
     * Configuration codes for pos frontend
     *
     * @var array
     */
    protected $configurationsCodes = [
        'pos.settings.general.pos_logo',
        'pos.settings.general.heading_on_login',
        'pos.settings.general.sub_heading_on_login',
        'pos.settings.general.footer_content',
        'pos.settings.general.footer_note',
        'pos.settings.general.pos_logo',
        'pos.settings.barcode.hide',
        'pos.settings.product.allow_sku',
        'pos.settings.barcode.prefix',
        'sales.taxes.categories.product',
        'sales.taxes.calculation.based_on',
        'sales.taxes.calculation.product_prices',
        'sales.shipping.origin.country',
        'sales.shipping.origin.state',
        'sales.shipping.origin.postcode',
        'sales.taxes.default_destination_calculation.country',
        'sales.taxes.default_destination_calculation.state',
        'sales.taxes.default_destination_calculation.post_code',
    ];

    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        protected CoreConfigRepository $coreConfigRepository,
        protected ExchangeRateRepository $exchangeRateRepository,
        protected TaxCategoryRepository $taxCategoryRepository,
        protected CategoryRepository $categoryRepository,
    ) {}

    /**
     * Login page details.
     */
    public function index(): array
    {
        $channel = core()->getCurrentChannel();

        $exchangeRates = $this->exchangeRateRepository->all();

        $taxCategories = $this->taxCategoryRepository->with('tax_rates')->all();

        $configurations = $this->coreConfigRepository
            ->whereIn('code', $this->configurationsCodes)
            ->get()
            ->map(function ($config) {
                if ($config->code == 'pos.settings.general.pos_logo') {
                    $config->value = Storage::url($config->value);
                }

                return $config;
            })
            ->toArray();

        return [
            'base_currency'   => $channel->base_currency->code,
            'categories'      => $this->getCategories($channel->root_category_id),
            'configurations'  => $configurations,
            'countries'       => core()->countries(),
            'country_states'  => $this->groupedStatesByCountries(),
            'currencies'      => $channel->currencies,
            'default_country' => config('app.default_country'),
            'default_locale'  => $channel->default_locale->code,
            'exchange_rates'  => $exchangeRates,
            'locales'         => $channel->locales,
            'tax_categories'  => $taxCategories,
        ];
    }

    /**
     * Get the home page categories
     */
    private function getCategories(int $id): Collection
    {
        return $this->categoryRepository->getVisibleCategoryTree($id);
    }

    /**
     * Get the countries with their states.
     */
    private function groupedStatesByCountries(): array
    {
        $countryStates = [];

        foreach (core()->groupedStatesByCountries() as $countryCode => $states) {
            $countryStates[] = [
                'countryCode' => $countryCode,
                'states'      => $states,
            ];
        }

        return $countryStates;
    }
}

<?php

return [
    /**
     * Application service providers.
     */
    App\Providers\AppServiceProvider::class,
    App\Providers\MessengerButtonServiceProvider::class,

    /**
     * Webkul's service providers.
     */
    Webkul\Admin\Providers\AdminServiceProvider::class,
    Webkul\Attribute\Providers\AttributeServiceProvider::class,
    Webkul\BookingProduct\Providers\BookingProductServiceProvider::class,
    Webkul\CMS\Providers\CMSServiceProvider::class,
    Webkul\CartRule\Providers\CartRuleServiceProvider::class,
    Webkul\CatalogRule\Providers\CatalogRuleServiceProvider::class,
    Webkul\Category\Providers\CategoryServiceProvider::class,
    Webkul\Checkout\Providers\CheckoutServiceProvider::class,
    Webkul\Core\Providers\CoreServiceProvider::class,
    Webkul\Core\Providers\EnvValidatorServiceProvider::class,
    Webkul\Customer\Providers\CustomerServiceProvider::class,
    Webkul\DataGrid\Providers\DataGridServiceProvider::class,
    Webkul\DataTransfer\Providers\DataTransferServiceProvider::class,
    Webkul\DebugBar\Providers\DebugBarServiceProvider::class,
    Webkul\FPC\Providers\FPCServiceProvider::class,
    Webkul\GDPR\Providers\GDPRServiceProvider::class,
    Webkul\Installer\Providers\InstallerServiceProvider::class,
    Webkul\Inventory\Providers\InventoryServiceProvider::class,
    Webkul\MagicAI\Providers\MagicAIServiceProvider::class,
    Webkul\Marketing\Providers\MarketingServiceProvider::class,
    Webkul\Notification\Providers\NotificationServiceProvider::class,
    Webkul\Payment\Providers\PaymentServiceProvider::class,
    Webkul\Paypal\Providers\PaypalServiceProvider::class,
    Webkul\PayPay\Providers\PayPayServiceProvider::class,
    Webkul\Komoju\Providers\KomojuServiceProvider::class,
    Webkul\Product\Providers\ProductServiceProvider::class,
    Webkul\Rule\Providers\RuleServiceProvider::class,
    Webkul\Sales\Providers\SalesServiceProvider::class,
    Webkul\Shipping\Providers\ShippingServiceProvider::class,
    Webkul\Shop\Providers\ShopServiceProvider::class,
    Webkul\Sitemap\Providers\SitemapServiceProvider::class,
    Webkul\SocialLogin\Providers\SocialLoginServiceProvider::class,
    Webkul\SocialShare\Providers\SocialShareServiceProvider::class,
    Webkul\Tax\Providers\TaxServiceProvider::class,
    Webkul\Theme\Providers\ThemeServiceProvider::class,
    Webkul\User\Providers\UserServiceProvider::class,
    Webkul\Pos\Providers\PosServiceProvider::class,
    Stancl\Tenancy\TenancyServiceProvider::class,
    App\Providers\TenancyServiceProvider::class,
    App\Providers\ShippingServiceProvider::class,
    Fujiba\LiveChat\Providers\LiveChatServiceProvider::class,
    Fujiba\SagawaDelivery\Providers\SagawaDeliveryServiceProvider::class,
    Fujiba\Reorder\Providers\ReorderServiceProvider::class,
    Fujiba\PwaFrontend\Providers\PwaFrontendServiceProvider::class,
    Fujiba\Dashboard\Providers\FujibaDashboardServiceProvider::class,
    Fujiba\InventoryControl\Providers\InventoryControlServiceProvider::class,
    Fujiba\OrdersAdmin\Providers\OrdersAdminServiceProvider::class,
    Fujiba\InvoicesAdmin\Providers\InvoicesAdminServiceProvider::class,
    Fujiba\Notification\Providers\NotificationServiceProvider::class,
    Fujiba\AutoLogin\Providers\AutoLoginServiceProvider::class,
    Fujiba\TikTokShop\Providers\TikTokShopServiceProvider::class,
    Fujiba\AdminSecurity\Providers\AdminSecurityServiceProvider::class,
    Fujiba\CatalogAdmin\Providers\CatalogAdminServiceProvider::class,
    Fujiba\FacebookCatalog\Providers\FacebookCatalogServiceProvider::class,
    Fujiba\AutoPoster\Providers\AutoPosterServiceProvider::class,
    Fujiba\MessengerBridge\Providers\MessengerBridgeServiceProvider::class,
    Fujiba\Accounting\Providers\AccountingServiceProvider::class,
    Fujiba\AuthGateway\Providers\AuthGatewayServiceProvider::class,
    Fujiba\CustomerRanks\Providers\CustomerRanksServiceProvider::class,
    // KimonoPromo: tiered cart_fixed override — UNREGISTERED. The real promo lives
    // in bulk_bonus_rules (collect.totals.after), not cart rules, so this override
    // is the wrong layer and stays off. Package left on disk, inert.
    // Fujiba\KimonoPromo\Providers\KimonoPromoServiceProvider::class,
    Webkul\Waste\Providers\WasteServiceProvider::class,

];

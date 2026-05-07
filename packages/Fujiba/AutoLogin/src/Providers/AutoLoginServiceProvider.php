<?php

namespace Fujiba\AutoLogin\Providers;

use Illuminate\Support\ServiceProvider;

class AutoLoginServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/admin.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'autologin');

        // Tenant migrations are NOT loaded here; they are registered in
        // config/tenancy.php under migration_parameters['--path'].
    }

    public function register(): void
    {
    }
}

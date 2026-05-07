<?php

namespace Fujiba\AdminSecurity\Providers;

use Illuminate\Support\ServiceProvider;

class AdminSecurityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/admin.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'admin-security');
    }
}

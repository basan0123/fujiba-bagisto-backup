<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

// All tenant subdomain traffic goes through these middlewares
// This file is loaded by TenancyServiceProvider for ALL routes on tenant domains
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    // Bagisto handles all actual routes via its own service providers
    // We just need tenancy initialized before any route runs
});

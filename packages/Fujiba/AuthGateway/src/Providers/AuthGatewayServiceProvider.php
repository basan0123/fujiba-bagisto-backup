<?php

namespace Fujiba\AuthGateway\Providers;

use Fujiba\AuthGateway\Console\Commands\ImportExistingTenantsCommand;
use Fujiba\AuthGateway\Console\Commands\RotateJwtKeyCommand;
use Fujiba\AuthGateway\Services\JwtService;
use Fujiba\AuthGateway\Services\RateLimiter;
use Fujiba\AuthGateway\Services\Slugger;
use Fujiba\AuthGateway\Services\TenantProvisioner;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AuthGatewayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/auth-gateway.php',
            'auth-gateway'
        );

        $this->app->singleton(JwtService::class, function () {
            $cfg = config('auth-gateway.jwt');
            return new JwtService(
                algorithm: $cfg['algorithm'],
                secret:    (string) $cfg['secret'],
                issuer:    $cfg['issuer'],
                audience:  $cfg['audience'],
                kid:       $cfg['kid'],
                ttlSeconds: (int) $cfg['ttl_seconds']
            );
        });

        $this->app->singleton(Slugger::class, function () {
            return new Slugger(
                reserved:  config('auth-gateway.reserved_subdomains', []),
                maxLength: 40,
                suffixRetries: 5,
            );
        });

        $this->app->singleton(RateLimiter::class, fn () => new RateLimiter());

        $this->app->bind(TenantProvisioner::class, function ($app) {
            return new TenantProvisioner(
                $app->make(\App\Services\SuperAdmin\TenantCloner::class)
            );
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportExistingTenantsCommand::class,
                RotateJwtKeyCommand::class,
            ]);
        }
    }

    protected function registerRoutes(): void
    {
        // Domain-scoped: only register gateway routes when the host matches.
        // We DON'T inspect the request here (provider boot runs early); we
        // register the routes inside a domain group so Laravel's router does
        // the host match per-request.
        $domains = config('auth-gateway.gateway_domains', []);

        if (empty($domains)) {
            return;
        }

        // Compose a regex-friendly set of hosts. Laravel domain group uses
        // straight string match (with parameters), not regex, so we register
        // the same route group once per domain.
        Route::middleware([])->group(function () use ($domains) {
            foreach ($domains as $host) {
                Route::domain($host)->group(__DIR__.'/../../routes/api.php');
            }
        });
    }
}

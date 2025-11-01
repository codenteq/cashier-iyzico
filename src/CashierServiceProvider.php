<?php

namespace Codenteq\Iyzico;

use Codenteq\Iyzico\Contracts\InvoiceRenderer;
use Codenteq\Iyzico\Http\Middleware\VerifyWebhookSignature;
use Codenteq\Iyzico\Services\InvoiceRendererService;
use Illuminate\Support\ServiceProvider;

class CashierServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cashier.php', 'cashier');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'cashier-iyzico');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/cashier.php' => config_path('cashier.php'),
            ], 'cashier-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'cashier-migrations');

            $this->publishes([
                __DIR__.'/../tests' => base_path('tests/Feature/cashier'),
            ], 'cashier-tests');
        }

        $this->app['router']->aliasMiddleware('verify-iyzico-webhook', VerifyWebhookSignature::class);

        $this->app->bind(InvoiceRenderer::class, function ($app) {
            return $app->make(config('cashier.invoices.renderer', InvoiceRendererService::class));
        });
    }
}

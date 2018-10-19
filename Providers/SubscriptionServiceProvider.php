<?php

namespace Subscription\Providers;

use Illuminate\Support\ServiceProvider;
use Srmklive\PayPal\Facades\PayPal;
use Srmklive\PayPal\Providers\PayPalServiceProvider;
use Subscription\PayPalClient;
use Subscription\Service;

/**
 * Class SubscriptionServiceProvider
 *
 * @const PAY_PAL_DRIVER - PayPal driver
 *
 * @package Subscription\Providers
 */
class SubscriptionServiceProvider extends ServiceProvider
{

    const PAY_PAL_DRIVER = 'pay-pal-subscription';

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $migrationPath = __DIR__ . '/../migrations';
        $configPath = __DIR__ . '/../config';
        $this->publishes([
            $migrationPath => database_path('migrations'),
            $configPath => config_path(),
        ]);
    }

    /**
     * Register the application Libreries.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../config/subscription.php';
        $this->mergeConfigFrom($configPath, 'subscription');
        $this->app->register(PayPalServiceProvider::class);
        $this->app->alias('PayPal', PayPal::class);

        $this->app->singleton(self::PAY_PAL_DRIVER, function() {
            return new PayPalClient();
        });
        $this->app->singleton('sub-service', function() {
            return new Service();
        });
    }
}

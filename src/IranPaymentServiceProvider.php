<?php

namespace Dena\IranPayment;

use Illuminate\Support\ServiceProvider;

class IranPaymentServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->mergeConfigFrom(__DIR__.'/../config/iranpayment.php', 'iranpayment');
		$this->publishes([
			__DIR__.'/../config/iranpayment.php' => config_path('iranpayment.php'),
		], 'config');

		if ($this->isLumen()) {
            $this->app->configure('iranpayment');
		}

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
		$this->publishes([
			__DIR__.'/../database/migrations' => $this->app->databasePath().'/migrations',
		], 'migrations');

		$this->loadViewsFrom(__DIR__.'/../resources/views', 'iranpayment');
		$this->publishes([
			__DIR__.'/../resources/views' => resource_path('views/vendor/iranpayment'),
		], 'views');

		$this->publishes([
			__DIR__.'/../resources/assets' => public_path('vendor/iranpayment'),
		], 'assets');

		if (app('config')->get('app.env', 'production') !== 'production') {
			$this->loadRoutesFrom(__DIR__.'/routes.php');
		}
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

    /**
     * Check if app uses Lumen.
     *
     * @return bool
     */
    private function isLumen(): bool
    {
        return str_contains($this->app->version(), 'Lumen');
    }
}

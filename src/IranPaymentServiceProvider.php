<?php

namespace Dena\IranPayment;

use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;

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
		]);

		if ($this->app instanceof LumenApplication) {
            $this->app->configure('iranpayment');
		}
		
		$this->publishes([
			__DIR__.'/../database/migrations/2017_03_01_000000_create_iranpayment_transactions_table.php' => $this->app->databasePath().'/migrations/2017_03_01_000000_create_iranpayment_transactions_table.php',
		]);

		$this->loadViewsFrom(__DIR__.'/../resources/views', 'iranpayment');
		$this->publishes([
			__DIR__.'/../resources/views' => resource_path('views/vendor/iranpayment'),
		]);
		$this->publishes([
			__DIR__.'/../resources/assets' => public_path('vendor/iranpayment'),
		]);
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
}

<?php

namespace Dena\IranPayment;

use Illuminate\Support\ServiceProvider;

use Config;

class IranPaymentServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__.'/config/iranpayment.php' => base_path('config/iranpayment.php'),
		]);
		$this->publishes([
			__DIR__.'/database/migrations/create_iran_payment_transactions_table.php' => $this->app->databasePath().'/migrations/2016_11_01_000000_create_iran_payment_transactions_table.php',
		]);
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
	}
}

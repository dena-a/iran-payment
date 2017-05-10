<?php

namespace Dena\IranPayment;

use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;

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
			__DIR__.'/config/iranpayment.php' => config_path('config/iranpayment.php'),
		]);
		if ($this->app instanceof LumenApplication) {
            $this->app->configure('iranpayment');
        }
		$this->publishes([
			__DIR__.'/database/migrations/2016_11_01_000000_create_iran_payment_transactions_table.php' => $this->app->databasePath().'/migrations/2016_11_01_000000_create_iran_payment_transactions_table.php',
		]);
		$this->publishes([
			__DIR__.'/database/migrations/2017_01_01_000000_alter_table_iran_payment_transactions.php' => $this->app->databasePath().'/migrations/2017_01_01_000000_alter_table_iran_payment_transactions.php',
		]);
		$this->publishes([
			__DIR__.'/resources/views/iranpayment' => $this->app->basePath().'/resources/views/iranpayment',
		]);
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->register(\Vinkla\Hashids\HashidsServiceProvider::class);
	}
}

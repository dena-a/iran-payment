<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIranPaymentTransactionsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('iran_payment_transactions', function (Blueprint $table) {
			$table->increments('id');
			$table->integer('user_id')->unsigned();
			$table->foreign('user_id')->references('id')->on('users');
			$table->string('reference_id')->index();
			$table->tinyInteger('gateway')->unsigned();
			$table->double('amount', 16, 4)->unsigned();
			$table->tinyInteger('status')->unsigned()->length(1);
			$table->string('tracking_code')->nullable()->index();
			$table->string('card_number')->nullable();
			$table->timestamp('payment_date')->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('iran_payment_transactions');
	}
}

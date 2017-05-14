<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Dena\IranPayment\GatewayAbstract;

class CreateIranPaymentTransactionsTable extends Migration
{

	public function up()
	{
		Schema::create('iranpayment_transactions', function (Blueprint $table) {
			$table->increments('id');
			$table->integer('user_id')->unsigned()->nullable();
			$table->string('transaction_code')->nullable()->index();
			$table->string('gateway', 16)->index();
			$table->decimal('amount', 16, 4)->unsigned();
			$table->string('currency', 3)->default(GatewayAbstract::IRR);
			$table->tinyInteger('status')->unsigned()->length(1);
			$table->string('tracking_code')->nullable()->index();
			$table->string('reference_number')->nullable()->index();
			$table->string('card_number')->nullable();
			$table->text('description')->nullable();
			$table->text('extra')->nullable();
			$table->timestamp('paid_at')->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('iranpayment_transactions');
	}
}

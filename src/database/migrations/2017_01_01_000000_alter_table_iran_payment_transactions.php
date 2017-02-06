<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableIranPaymentTransactions extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('iran_payment_transactions', function (Blueprint $table) {
			$table->string('receipt_number')->nullable()->index()->after('tracking_code');
			$table->tinyInteger('currency')->unsigned()->length(1)->default(IranPayment::PC_RIAL)->after('status');
			$table->text('description')->nullable()->after('card_number');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('iran_payment_transactions', function (Blueprint $table) {
			$table->dropColumn('currency');
			$table->dropColumn('description');
		});
	}
}

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Dena\IranPayment\Traits\IranPaymentDatabase;

class AlterIranPaymentTransactionsTable extends Migration
{
	use IranPaymentDatabase;

	public function up()
	{
		Schema::table($this->getTable(), function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('card_number');
            $table->string('email')->nullable()->after('full_name');
		});
	}

	public function down()
	{
		if (app('config')->get('app.env') === 'testing') return;
        Schema::table($this->getTable(), function ($table) {
            $table->dropColumn('full_name');
            $table->dropColumn('email');
        });
	}
}

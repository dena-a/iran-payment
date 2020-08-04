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
            $table->string('full_name')->nullable()->before('mobile');
            $table->string('email')->nullable()->before('mobile');
		});
	}

	public function down()
	{
        Schema::table($this->getTable(), function ($table) {
            $table->dropColumn('full_name');
            $table->dropColumn('email');
        });
	}
}

<?php

use Dena\IranPayment\Traits\IranPaymentDatabase;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AlterIranPaymentTransactionsTable extends Migration
{
    use IranPaymentDatabase;

    public function up()
    {
        Schema::table($this->getTable(), function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('card_number');
            $table->string('email')->nullable()->after('full_name');
            $table->json('gateway_data')->nullable()->after('extra');
        });
    }

    public function down()
    {
        Schema::table($this->getTable(), function ($table) {
            $table->dropColumn('email');
        });
        Schema::table($this->getTable(), function ($table) {
            $table->dropColumn('full_name');
        });
        Schema::table($this->getTable(), function ($table) {
            $table->dropColumn('gateway_data');
        });
    }
}

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Dena\IranPayment\Traits\IranPaymentDatabase;

class AddGatewayData extends Migration
{
    use IranPaymentDatabase;

    public function up()
    {
        Schema::table($this->getTable(), function (Blueprint $table) {
            $table->json('gateway_data')->nullable()->after('extra');
        });
    }

    public function down()
    {
        Schema::table($this->getTable(), function ($table) {
            $table->dropColumn('gateway_data');
        });
    }
}

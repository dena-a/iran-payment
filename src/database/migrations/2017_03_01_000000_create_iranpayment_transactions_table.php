<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Dena\IranPayment\Providers\BaseProvider;
use Dena\IranPayment\Traits\IranPaymentDatabase;

class CreateIranPaymentTransactionsTable extends Migration
{
	use IranPaymentDatabaseabase;
	
	public function up()
	{
		Schema::create($this->getTable(), function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->integer('user_id')->unsigned()->nullable()->index();
			$table->string('transaction_code')->nullable()->index();
			$table->string('gateway', 16)->index();
			$table->decimal('amount', 16, 4)->unsigned();
			$table->string('currency', 3)->default(BaseProvider::IRR);
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
		Schema::dropIfExists($this->getTable());
	}
}

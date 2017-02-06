<?php

namespace Dena\IranPayment\Models;

use Illuminate\Database\Eloquent\Model;

class IranPaymentTransaction extends Model
{
	const T_INIT			= 0;
	const T_SUCCEED			= 1;
	const T_FAILED			= 2;
	const T_PENDING			= 3;
	const T_VERIFY_PENDING	= 4;

	protected $table	= 'iran_payment_transactions';

	protected $guarded	= ['id'];

	protected $fillable	= [
		'reference_id',
		'gateway',
		'amount',
		'currency',
		'status',
		'tracking_code',
		'receipt_number',
		'card_number',
		'description',
		'payment_date',
	];

	protected $hidden	= [
	];

}
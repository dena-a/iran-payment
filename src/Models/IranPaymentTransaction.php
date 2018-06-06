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
	const T_PAID_BACK		= 5;
	const T_CANCELED		= 6;

	protected $table		= 'iranpayment_transactions';

	protected $guarded		= ['id'];

	protected $fillable		= [
		'transaction_code',
		'gateway',
		'amount',
		'currency',
		'tracking_code',
		'reference_number',
		'card_number',
		'description',
		'extra',
	];

	protected $hidden	= [
	];

}
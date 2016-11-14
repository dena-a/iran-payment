<?php

namespace Dena\IranPayment\Models;

use Illuminate\Database\Eloquent\Model;

class IranPaymentTransaction extends Model
{

	protected $table	= 'iran_payment_transactions';

	protected $guarded	= ['id'];

	protected $fillable	= [
		'reference_id',
		'gateway',
		'amount',
		'status',
		'tracking_code',
		'card_number',
		'payment_date',
	];

	protected $hidden	= [
	];

}
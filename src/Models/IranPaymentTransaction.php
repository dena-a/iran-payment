<?php

namespace Dena\IranPayment\Models;

use Illuminate\Database\Eloquent\Model;

use Dena\IranPayment\Traits\IranPaymentDatabase as DatabaseTrait;

class IranPaymentTransaction extends Model
{
	use DatabaseTrait;

	const T_INIT			= 0;
	const T_SUCCEED			= 1;
	const T_FAILED			= 2;
	const T_PENDING			= 3;
	const T_VERIFY_PENDING	= 4;
	const T_PAID_BACK		= 5;
	const T_CANCELED		= 6;

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

	/**
     * Get all of the owning payable models.
     */
    public function payable()
    {
        return $this->morphTo();
    }

}
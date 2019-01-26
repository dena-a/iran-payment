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

	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	protected $fillable		= [
		'gateway',
		'amount',
		'currency',
		'tracking_code',
		'reference_number',
		'card_number',
		'mobile',
		'description',
		'errors',
		'extra',
	];

	/**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
	protected $hidden	= [
		//
	];

	/**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'status_text',
    ];

	/**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'extra' => 'array',
    ];

	/**
     * Get all of the owning payable models.
     */
    public function payable()
    {
        return $this->morphTo();
	}
	
	/**
	 * Paid Back function
	 *
	 * @param array $params
	 * @return void
	 */
	public function paidBack(array $params = null)
	{
		$this->fill($params);
		$this->status = self::T_PAID_BACK;
		$this->save();
	}

	public function getStatusTextAttribute()
	{
		//@TODO::add translation
		switch($this->status) {
			case self::T_INIT:
				return 'ایجاد شده';
			case self::T_SUCCEED:
				return 'موفق';
			case self::T_FAILED:
				return 'ناموفق';
			case self::T_PENDING:
				return 'درجریان';
			case self::T_VERIFY_PENDING:
				return 'در انتظار تایید';
			case self::T_PAID_BACK:
				return 'برگشت وجه';
			case self::T_CANCELED:
				return 'انصراف';
			default:
				return '-';
		}
	}
}
<?php

namespace Dena\IranPayment\Traits;

trait UserData
{
    /**
     * User Mobile variable
     *
     * @var string
     */
    protected $mobile = null;

    /**
     * Set Mobile function
     *
     * @param string $mobile
     * @return void
     */
    public function setMobile(string $mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get Mobile function
     *
     * @return void
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * User Description variable
     *
     * @var string
     */
    protected $user_description = null;
    
    /**
     * Set User Description function
     *
     * @param string $user_description
     * @return void
     */
    public function setUserDescription(string $user_description)
    {
        $this->user_description = $user_description;

        return $this;
    }

    /**
     * Get User Description function
     *
     * @return void
     */
    public function getUserDescription()
    {
        return $this->user_description;
    }
}
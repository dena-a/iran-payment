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
     * Description variable
     *
     * @var string
     */
    protected $description = null;
    
    /**
     * Set User Description function
     *
     * @param string $description
     * @return void
     */
    public function setDescription(string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get Description function
     *
     * @return void
     */
    public function getDescription()
    {
        return $this->description;
    }
}
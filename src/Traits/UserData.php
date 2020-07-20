<?php

namespace Dena\IranPayment\Traits;

trait UserData
{
    /**
     * User Mobile variable
     *
     * @var string|null
     */
    protected ?string $mobile = null;

    /**
     * Transaction Description variable
     *
     * @var string|null
     */
    protected ?string $description = null;

    /**
     * Valid Card Number variable for tansaction
     *
     * @var string|null
     */
    protected ?string $valid_card_number = null;

    /**
     * Set User Mobile function
     *
     * @param string $mobile
     * @return $this
     */
    public function setMobile(string $mobile): self
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get User Mobile function
     *
     * @return string|null
     */
    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    /**
     * Set Transaction Description function
     *
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get Transaction Description function
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set Valid Card Number function
     *
     * @param string $valid_card_number
     * @return $this
     */
    public function setValidCardNumber(string $valid_card_number): self
    {
        $this->valid_card_number = $valid_card_number;

        return $this;
    }

    /**
     * Get Valid Card Number function
     *
     * @return string|null
     */
    public function getValidCardNumber(): ?string
    {
        return $this->valid_card_number;
    }
}

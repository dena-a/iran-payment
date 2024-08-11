<?php

namespace Dena\IranPayment\Traits;

trait UserData
{
    /**
     * User Full Name variable
     */
    protected ?string $full_name = null;

    /**
     * User Mobile variable
     */
    protected ?string $mobile = null;

    /**
     * User Email variable
     */
    protected ?string $email = null;

    /**
     * Transaction Description variable
     */
    protected ?string $description = null;

    /**
     * Valid Card Number variable for transaction
     */
    protected ?string $valid_card_number = null;

    /**
     * Set User Full Name function
     *
     * @return $this
     */
    public function setFullname(string $full_name): self
    {
        $this->full_name = $full_name;

        return $this;
    }

    /**
     * Get User Full Name function
     */
    public function getFullname(): ?string
    {
        return $this->full_name;
    }

    /**
     * Set User Mobile function
     *
     * @return $this
     */
    public function setMobile(string $mobile): self
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get User Mobile function
     */
    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    /**
     * Set User Email function
     *
     * @return $this
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get User Email function
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Set Transaction Description function
     *
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get Transaction Description function
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set Valid Card Number function
     *
     * @return $this
     */
    public function setValidCardNumber(string $valid_card_number): self
    {
        $this->valid_card_number = $valid_card_number;

        return $this;
    }

    /**
     * Get Valid Card Number function
     */
    public function getValidCardNumber(): ?string
    {
        return $this->valid_card_number;
    }
}

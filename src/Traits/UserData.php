<?php

namespace Dena\IranPayment\Traits;

trait UserData
{
    /**
     * User Full Name variable
     *
     * @var string|null
     */
    protected ?string $full_name = null;

    /**
     * User Mobile variable
     *
     * @var string|null
     */
    protected ?string $mobile = null;

    /**
     * User Email variable
     *
     * @var string|null
     */
    protected ?string $email = null;

    /**
     * Transaction Description variable
     *
     * @var string|null
     */
    protected ?string $description = null;

    /**
     * Valid Card Number variable for transaction
     *
     * @var string|null
     */
    protected ?string $valid_card_number = null;

    /**
     * Set User Full Name function
     *
     * @param string $full_name
     * @return $this
     */
    public function setFullname(string $full_name): self
    {
        $this->full_name = $full_name;

        return $this;
    }

    /**
     * Get User Full Name function
     *
     * @return string|null
     */
    public function getFullname(): ?string
    {
        return $this->full_name;
    }

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
     * Set User Email function
     *
     * @param string $email
     * @return $this
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get User Email function
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
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

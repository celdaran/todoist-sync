<?php namespace App\Service;

/**
 * Database result structure
 */
class Result
{
    /** @var bool */
    private bool $succeeded;

    /** @var bool */
    private bool $inserted;

    /** @var bool */
    private bool $updated;

    /** @var bool */
    private bool $deleted;

    /** @var string */
    private string $message;

    /**
     * Result constructor
     */
    public function __construct()
    {
        $this->succeeded = false;
        $this->inserted = false;
        $this->updated = false;
        $this->deleted = false;
        $this->message = '';
    }

    //------------------------------------------------------------------
    // Return table data
    //------------------------------------------------------------------

    /**
     * @param bool $value
     */
    public function setSucceeded(bool $value = true)
    {
        $this->succeeded = $value;
    }

    /**
     * @param bool $value
     */
    public function setInserted(bool $value = true)
    {
        $this->inserted = $value;
    }

    /**
     * @param bool $value
     */
    public function setUpdated(bool $value = true)
    {
        $this->updated = $value;
    }

    /**
     * @param bool $value
     */
    public function setDeleted(bool $value = true)
    {
        $this->deleted = $value;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    /**
     * @return bool
     */
    public function getSucceeded(): bool
    {
        return $this->succeeded;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function succeeded(): bool
    {
        return $this->getSucceeded();
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getInserted(): bool
    {
        return $this->inserted;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getUpdated(): bool
    {
        return $this->updated;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getMessage(): string
    {
        return $this->message;
    }

}

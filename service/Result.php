<?php namespace App\Service;

/**
 * Database result structure
 */
class Result
{
    private bool $succeeded;
    private bool $inserted;
    private bool $updated;
    private bool $deleted;
    private string $message;

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

    public function setSucceeded(bool $value = true)
    {
        $this->succeeded = $value;
    }

    public function setInserted(bool $value = true)
    {
        $this->inserted = $value;
    }

    public function setUpdated(bool $value = true)
    {
        $this->updated = $value;
    }

    public function setDeleted(bool $value = true)
    {
        $this->deleted = $value;
    }

    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    public function getSucceeded(): bool
    {
        return $this->succeeded;
    }

    public function succeeded(): bool
    {
        return $this->getSucceeded();
    }

    public function getInserted(): bool
    {
        return $this->inserted;
    }

    public function getUpdated(): bool
    {
        return $this->updated;
    }

    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

}

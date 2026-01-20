<?php

namespace App\Exceptions;

class RecordLockedException extends \Exception
{
    public function __construct(string $message = "Record is locked by another user")
    {
        parent::__construct($message);
    }
}

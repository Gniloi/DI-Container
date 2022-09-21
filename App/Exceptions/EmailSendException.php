<?php

declare(strict_types=1);

namespace App\Exceptions;

class EmailSendException extends \Exception
{
    protected $message = 'Email could not be send!';
}

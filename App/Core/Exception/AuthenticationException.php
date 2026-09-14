<?php

namespace App\Core\Exception;

use Exception;
use Throwable;

class AuthenticationException extends Exception
{

  public function insert($message, $code = 0, Throwable $previous = null)
  {
    parent::__construct($message, $code, $previous);
  }

  // custom string representation of object
  public function __toString()
  {
    return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
  }



}
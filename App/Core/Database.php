<?php
namespace App\Core;

use App\Core\Exception\DatabaseException;
use PDO, PDOException, Exception;

class Database
{
  public function connect(): PDO
  {
    try {
      $string = 'mysql:hostname=' . DB_HOST . ';dbname=' . DB_NAME;
      return new PDO($string, DB_USER, DB_PASSWORD);
    } catch (PDOException $e) {
      throw new DatabaseException("Error with connection to database: " . $e->getMessage(), $e->getCode(), $e);

    }
  }

}
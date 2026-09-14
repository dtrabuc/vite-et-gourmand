<?php
namespace App\Core;

class Security
{

  public static function isLogged(): bool
  {
    return isset($_SESSION['user']);
  }

  public static function isUser(): bool
  {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'user';
  }

  public static function isAdmin(): bool
  {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
  }

  public static function getCurrentUserId(): int|bool
  {
    return (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) ? $_SESSION['user']['id'] : false;
  }
  public static function hashPassword($password): string
  {
    return password_hash($password, PASSWORD_DEFAULT);
  }

  public static function verifyPassword(string $password, string $ddbServer): bool
  {
    return password_verify($password, $ddbServer);

  }

  public static function verifyCsrf(): bool
  {
    $token = htmlspecialchars($_POST['token']);
    if ($_SESSION['csrf_token'] === $token) {
      return true;
    }

    return false;
  }

}

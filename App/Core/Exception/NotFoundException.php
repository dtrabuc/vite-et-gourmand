<?php
declare(strict_types=1);

namespace App\Core\Exception;

use RuntimeException;

/**
 * Ressource ou route introuvable : produit une réponse 404.
 */
final class NotFoundException extends RuntimeException
{
}

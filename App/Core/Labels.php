<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Libellés français des valeurs techniques, communs aux vues, aux services et aux e-mails.
 */
final class Labels
{
    public const ORDER_STATUS = [
        'pending' => 'En attente',
        'accepted' => 'Acceptée',
        'preparing' => 'En préparation',
        'delivering' => 'En livraison',
        'delivered' => 'Livrée',
        'awaiting_return' => 'En attente du retour de matériel',
        'completed' => 'Terminée',
        'cancelled' => 'Annulée',
    ];

    public const SERVICE_TYPE = [
        'delivery' => 'Livraison',
        'pickup' => 'À emporter',
        'on_site' => 'Sur place',
    ];

    public const DIETARY_REGIME = [
        'classic' => 'Classique',
        'vegetarian' => 'Végétarien',
        'vegan' => 'Végan',
        'other' => 'Autre',
    ];

    public const QUOTE_STATUS = [
        'new' => 'Nouvelle',
        'in_review' => 'En cours de traitement',
        'quoted' => 'Devis envoyé',
        'accepted' => 'Acceptée',
        'declined' => 'Refusée',
        'closed' => 'Clôturée',
    ];

    public static function orderStatus(string $status): string
    {
        return self::ORDER_STATUS[$status] ?? $status;
    }

    public static function serviceType(string $serviceType): string
    {
        return self::SERVICE_TYPE[$serviceType] ?? $serviceType;
    }

    public static function dietaryRegime(string $regime): string
    {
        return self::DIETARY_REGIME[$regime] ?? $regime;
    }

    public static function quoteStatus(string $status): string
    {
        return self::QUOTE_STATUS[$status] ?? $status;
    }
}

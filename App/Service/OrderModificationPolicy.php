<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use DateTimeImmutable;

/**
 * Délai pendant lequel le client peut modifier ou annuler sa commande lui-même.
 *
 * Deux conditions (énoncé + règle du traiteur) :
 * - la commande est encore « en attente » (pas encore acceptée par l'équipe) ;
 * - le délai depuis la commande n'est pas dépassé :
 *     prestation le jour même      → 15 minutes,
 *     prestation le lendemain      → 24 heures,
 *     prestation plus tard          → 48 heures,
 *   et jamais après l'heure de la prestation.
 * Passé ce délai, il faut contacter l'équipe (qui peut encore modifier, après appel).
 */
final class OrderModificationPolicy
{
    public const SAME_DAY_MINUTES = 15;
    public const NEXT_DAY_HOURS = 24;
    public const LATER_HOURS = 48;

    public static function deadline(Order $order): ?DateTimeImmutable
    {
        $created = $order->getCreatedAt();
        $service = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i',
            $order->getDeliveryDate() . ' ' . substr($order->getDeliveryTime(), 0, 5)
        );
        if (!$created instanceof \DateTimeInterface || $service === false) {
            return null;
        }

        $created = DateTimeImmutable::createFromInterface($created);
        $days = (int) $created->setTime(0, 0)->diff($service->setTime(0, 0))->format('%r%a');
        $deadline = match (true) {
            $days <= 0 => $created->modify('+' . self::SAME_DAY_MINUTES . ' minutes'),
            $days === 1 => $created->modify('+' . self::NEXT_DAY_HOURS . ' hours'),
            default => $created->modify('+' . self::LATER_HOURS . ' hours'),
        };

        return $deadline < $service ? $deadline : $service;
    }

    public static function customerCanChange(Order $order, ?DateTimeImmutable $now = null): bool
    {
        $deadline = self::deadline($order);

        return $order->getStatus() === 'pending'
            && $deadline !== null
            && ($now ?? new DateTimeImmutable()) <= $deadline;
    }

    /** Pourquoi le client ne peut plus modifier (message affiché). */
    public static function refusal(Order $order): string
    {
        if ($order->getStatus() !== 'pending') {
            return 'Cette commande a déjà été acceptée par l’équipe : contactez-nous pour toute modification.';
        }

        $deadline = self::deadline($order);

        return 'Le délai de modification est dépassé' . ($deadline !== null
            ? ' (jusqu’au ' . french_date($deadline->format('Y-m-d H:i'), true) . ')'
            : '') . ' : contactez-nous pour toute modification.';
    }

    /** Règle en clair, rappelée sur les pages de commande. */
    public static function rule(): string
    {
        return sprintf(
            'Modification ou annulation possible tant que la commande n’est pas acceptée, dans un délai de %d min si la prestation a lieu le jour même, %d h si elle a lieu le lendemain et %d h au-delà.',
            self::SAME_DAY_MINUTES,
            self::NEXT_DAY_HOURS,
            self::LATER_HOURS
        );
    }
}

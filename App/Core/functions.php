<?php
declare(strict_types=1);

use App\Core\Config;

function config(?string $key = null, mixed $default = null): mixed
{
    static $config;

    $config ??= new Config(require dirname(__DIR__, 2) . '/config/app.php');

    if ($key === null) {
        return $config->all();
    }

    return $config->get($key, $default);
}
/**
 * Petite icône « i » qui affiche une aide au survol, au toucher et au clavier
 * (infobulle Bootstrap, voir public/assets/js/accessibility.js).
 * Le même texte est en texte caché : un lecteur d'écran le lit sans avoir à survoler.
 * À placer juste APRÈS un <label> (jamais dedans : un bouton dans un label est invalide).
 */
function info_tip(string $text): string
{
    $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    return '<button type="button" class="info-tip" data-bs-toggle="tooltip" data-bs-title="' . $safe . '">'
        . '<span aria-hidden="true">i</span>'
        . '<span class="visually-hidden">Aide : ' . $safe . '</span>'
        . '</button>';
}

/** Images de secours Unsplash déjà référencées par database/mongodb-init.js.
 * Les images présentes dans MongoDB restent prioritaires.
 */
function menu_image_fallbacks(): array
{
    static $images = [
        1 => ['url' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=1200&h=800&q=82', 'alt_text' => 'Soupe et légumes de saison'],
        2 => ['url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2e8c0?auto=format&fit=crop&w=1200&h=800&q=82', 'alt_text' => 'Assiette végétale colorée'],
        3 => ['url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=1200&h=800&q=82', 'alt_text' => 'Menu gourmand pour enfant'],
        4 => ['url' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1200&h=800&q=82', 'alt_text' => 'Table généreuse pour un repas convivial'],
        5 => ['url' => 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&fit=crop&w=1200&h=800&q=82', 'alt_text' => 'Assiette fraîche et végétale'],
        6 => ['url' => 'https://images.unsplash.com/photo-1504754524776-8f4f37790ca0?auto=format&fit=crop&w=1200&h=800&q=82', 'alt_text' => 'Repas familial autour de produits frais'],
        7 => ['url' => 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?auto=format&fit=crop&w=1200&h=800&q=82', 'alt_text' => 'Poisson cuisiné et garniture'],
        8 => ['url' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=1200&h=800&q=82', 'alt_text' => 'Ingrédients frais pour une cuisine végétale'],
        9 => ['url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1200&h=800&q=82', 'alt_text' => 'Dressage gastronomique pour une réception'],
        10 => ['url' => 'https://images.unsplash.com/photo-1574672280600-4accfa5b6f98?auto=format&fit=crop&w=1200&h=800&q=82', 'alt_text' => 'Chapon rôti pour le repas de Noël'],
    ];

    return $images;
}

/** Retourne la couverture MongoDB du menu ou une image Unsplash de secours. */
function menu_cover_image(array $covers, int $menuId, string $title): array
{
    $cover = $covers[$menuId] ?? null;
    if (is_array($cover) && trim((string) ($cover['url'] ?? '')) !== '') {
        return $cover;
    }

    $fallbacks = menu_image_fallbacks();
    $fallback = $fallbacks[$menuId] ?? $fallbacks[1];
    return [
        'url' => $fallback['url'],
        'alt_text' => $title . ' — ' . $fallback['alt_text'],
        'source' => 'Unsplash',
    ];
}

/**
 * Ce que comprend un menu, par personne, à partir de ses plats.
 * Un convive a droit à 1 apéritif, 1 entrée, 1 plat, 1 dessert et 1 digestif
 * (au choix quand le menu en propose plusieurs) et à tous les accompagnements.
 *
 * @param array<int, array{name: string, category: string}> $dishes
 * @return list<array{key: string, label: string, names: list<string>, summary: string}>
 */
function menu_composition(array $dishes): array
{
    $groups = [
        'aperitif' => ['Apéritif', 'apéritif', 'apéritifs'],
        'starter' => ['Entrée', 'entrée', 'entrées'],
        'main' => ['Plat', 'plat', 'plats'],
        'side' => ['Accompagnement', 'accompagnement', 'accompagnements'],
        'dessert' => ['Dessert', 'dessert', 'desserts'],
        'digestif' => ['Digestif', 'digestif', 'digestifs'],
    ];
    $result = [];

    foreach ($groups as $key => [$label, $singular, $plural]) {
        $names = [];
        foreach ($dishes as $dish) {
            if (($dish['category'] ?? '') === $key) {
                $names[] = (string) $dish['name'];
            }
        }
        if ($names === []) {
            continue;
        }

        $count = count($names);
        if ($key === 'side') {
            // les accompagnements sont tous servis
            $summary = $count . ' ' . ($count > 1 ? $plural : $singular);
        } else {
            $summary = '1 ' . $singular . ($count > 1 ? ' au choix parmi ' . $count : '');
        }

        $result[] = ['key' => $key, 'label' => $label, 'names' => $names, 'summary' => $summary];
    }

    return $result;
}

/** Phrase courte : « 1 entrée · 1 plat · 2 accompagnements · 1 dessert ». */
function menu_composition_summary(array $dishes): string
{
    return implode(' · ', array_column(menu_composition($dishes), 'summary'));
}

/** « dimanche 8 novembre 2026 » (ou « … à 10:59 ») à partir d'une date SQL. */
function french_date(string $value, bool $withTime = false): string
{
    $timestamp = strtotime(trim($value));
    if ($timestamp === false) {
        return trim($value);
    }

    $days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    $months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août',
        'septembre', 'octobre', 'novembre', 'décembre'];

    $text = $days[(int) date('w', $timestamp)] . ' ' . date('j', $timestamp) . ' '
        . $months[(int) date('n', $timestamp) - 1] . ' ' . date('Y', $timestamp);

    return $withTime ? $text . ' à ' . date('H:i', $timestamp) : $text;
}

/** Badges « adresse vérifiée / saisie manuelle / hors zone / frais à confirmer » d'une commande livrée. */
function delivery_badges(\App\Entity\Order $order): string
{
    if ($order->getServiceType() !== 'delivery') {
        return '';
    }

    $badges = [];
    if ($order->getAddressStatus() === 'verified') {
        $badges[] = '<span class="badge text-bg-success">adresse vérifiée</span>';
    } elseif ($order->getAddressStatus() === 'manual') {
        $badges[] = '<span class="badge text-bg-warning">saisie manuelle</span>';
    }
    if ($order->getDeliveryZone() === 'outside') {
        $badges[] = '<span class="badge text-bg-info">hors zone</span>';
    }
    if ($order->isDeliveryCostPending()) {
        $badges[] = '<span class="badge text-bg-danger">frais de livraison à confirmer</span>';
    }

    return $badges === [] ? '' : ' ' . implode(' ', $badges);
}

/** Liste HTML des choix d'une commande (« Apéritif : 10 × Kir… »), échappée. */
function order_items_html(array $items): string
{
    $lines = \App\Service\OrderCompositionService::describe($items);
    if ($lines === []) {
        return '';
    }

    return '<ul class="order-items small mb-0">' . implode('', array_map(
        static fn (string $line): string => '<li>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</li>',
        $lines
    )) . '</ul>';
}

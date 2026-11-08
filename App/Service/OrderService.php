<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\Labels;
use App\Entity\Order;
use App\Repository\MenuRepository;
use App\Repository\OpeningHoursRepository;
use App\Repository\OrderItemRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use InvalidArgumentException;

final readonly class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private UserRepository $userRepository,
        private MenuRepository $menuRepository,
        private OpeningHoursRepository $openingHoursRepository,
        private MailService $mailService,
        private MenuStatisticsService $menuStatisticsService,
        private NotificationService $notificationService,
        private DeliveryDistanceService $deliveryDistanceService,
        private MenuService $menuService,
        private PhoneValidator $phoneValidator,
        private OrderCompositionService $compositionService,
        private OrderItemRepository $orderItemRepository
    ) {
    }

    public function createOrder(
        int $userId,
        int $menuId,
        int $numberOfPeople,
        string $deliveryDate,
        string $deliveryTime,
        string $deliveryAddress,
        string $deliveryCity,
        string $deliveryPostalCode,
        ?string $customization = null,
        string $serviceType = 'delivery',
        string $paymentMethod = 'cash_on_site',
        ?string $deliveryInstructions = null,
        ?string $contactPhone = null,
        bool $addressManual = false,
        bool $acceptOutsideZone = false,
        mixed $items = null
    ): array {
        $user = $this->userRepository->findById($userId);
        $menu = $this->menuRepository->findById($menuId);

        if ($user === null) {
            throw new InvalidArgumentException('Utilisateur non trouvé.');
        }

        if ($menu === null || $menu->getAvailableStock() < 1) {
            throw new InvalidArgumentException('Menu non trouvé ou indisponible.');
        }

        if ($numberOfPeople < 1) {
            throw new InvalidArgumentException('Le nombre de personnes doit être supérieur à 0.');
        }

        if ($numberOfPeople < $menu->getMinPeople()) {
            throw new InvalidArgumentException(
                sprintf('Ce menu nécessite au minimum %d personne(s).', $menu->getMinPeople())
            );
        }

        if (!array_key_exists($serviceType, Labels::SERVICE_TYPE)) {
            throw new InvalidArgumentException('Mode de prestation invalide.');
        }

        // choix du client dans le menu (options, remplacements, allergènes à retirer)
        $orderItems = $this->compositionService->normalize($menu, $numberOfPeople, $items);
        $customization = trim((string) $customization);
        if (mb_strlen($customization) > 1000) {
            throw new InvalidArgumentException('Les précisions pour la cuisine sont limitées à 1 000 caractères.');
        }
        $customization = $customization !== '' ? $customization : null;

        if ($paymentMethod !== 'cash_on_site') {
            throw new InvalidArgumentException('Le paiement est effectué en espèces sur place.');
        }

        $contactPhone = $this->validContactPhone(trim((string) $contactPhone));

        // format, pas de 15 min, date passée et horaires : tout est vérifié ici (comme pour les modifications)
        $this->validateServiceDateTime($deliveryDate, $deliveryTime);

        $deliveryDistanceKm = null;
        $deliveryCost = 0.00;
        $addressStatus = null;
        $deliveryZone = null;
        $costPending = false;

        if ($serviceType === 'delivery') {
            // Adresse vérifiée sur OpenStreetMap (ou saisie manuelle acceptée), zone et frais calculés ici.
            $delivery = $this->deliveryDistanceService->quote(
                $deliveryAddress,
                $deliveryPostalCode,
                $deliveryCity,
                $addressManual,
                $acceptOutsideZone
            );
            $deliveryDistanceKm = $delivery['distance_km'];
            $deliveryCost = $delivery['cost'];
            $addressStatus = $delivery['address_status'];
            $deliveryZone = $delivery['zone'];
            $costPending = $delivery['cost_pending'];
        }

        if ($serviceType === 'pickup') {
            $deliveryAddress = '';
            $deliveryCity = '';
            $deliveryPostalCode = '';
            $deliveryInstructions = null;
        }

        if ($serviceType === 'on_site') {
            [$deliveryAddress, $deliveryCity, $deliveryPostalCode] = DeliveryDistanceService::COMPANY_ADDRESS;
            $deliveryInstructions = null;
        }

        [$menuPrice, $discountRate] = $this->calculateMenuPrice(
            $menu->getBasePrice(),
            $menu->getMinPeople(),
            $numberOfPeople
        );

        $orderData = [
            'user_id' => $userId,
            'menu_id' => $menuId,
            'number_of_people' => $numberOfPeople,
            'order_date' => date('Y-m-d H:i:s'),
            'delivery_date' => $deliveryDate,
            'delivery_time' => $deliveryTime,
            'delivery_address' => trim($deliveryAddress),
            'delivery_city' => trim($deliveryCity),
            'delivery_postal_code' => trim($deliveryPostalCode),
            'delivery_distance_km' => $deliveryDistanceKm,
            'address_status' => $addressStatus,
            'delivery_zone' => $deliveryZone,
            'delivery_cost' => $deliveryCost,
            'customization' => $customization,
            'service_type' => $serviceType,
            'payment_method' => $paymentMethod,
            'delivery_instructions' => $deliveryInstructions,
            'contact_phone' => trim((string) $contactPhone),
            'menu_price' => $menuPrice,
            'discount_rate' => $discountRate,
            'total_price' => round($menuPrice + $deliveryCost, 2),
            'status' => 'pending',
            'equipment_loaned' => false,
        ];

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            // Une commande n'est considérée comme reçue par le site
            // qu'après l'enregistrement de l'ordre, la réservation du stock
            // et la création de son premier historique dans la même transaction.
            $orderId = $this->orderRepository->create($orderData);
            $this->orderItemRepository->replaceForOrder($orderId, $orderItems);
            $this->orderRepository->decreaseMenuStock($menuId);
            $this->orderRepository->addToHistory($orderId, 'pending', $userId, 'Commande créée');

            if ($orderId <= 0) {
                throw new \RuntimeException('La commande n’a pas pu être enregistrée.');
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }

        // Le stock a changé : le menu mis en cache n'est plus à jour.
        $this->menuService->invalidateMenu($menuId);

        $mailDetails = [
            'id' => $orderId,
            'order_date' => $orderData['order_date'],
            'menu_title' => $menu->getTitle(),
            'number_of_people' => $numberOfPeople,
            'per_person' => MenuPricing::perPerson($menu->getBasePrice(), $menu->getMinPeople()),
            'discount_rate' => $discountRate,
            'menu_price' => $menuPrice,
            'delivery_cost' => $deliveryCost,
            'delivery_cost_pending' => $costPending,
            'delivery_zone' => $deliveryZone,
            'total_price' => $orderData['total_price'],
            'service_type' => $serviceType,
            'delivery_date' => $deliveryDate,
            'delivery_time' => $deliveryTime,
            'delivery_address' => trim($deliveryAddress),
            'delivery_city' => trim($deliveryCity),
            'delivery_postal_code' => trim($deliveryPostalCode),
            'delivery_instructions' => trim((string) $deliveryInstructions),
            'customization' => trim((string) $customization),
            'composition' => OrderCompositionService::describe($this->compositionService->withNames($orderItems)),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail(),
            'contact_phone' => $contactPhone,
        ];

        try {
            $this->mailService->sendOrderConfirmationEmail(
                $user->getEmail(),
                $user->getFirstName(),
                $mailDetails
            );

            $this->mailService->sendOrderNotificationToStaff(
                $this->mailService->companyAddress(),
                $mailDetails
            );
        } catch (\Throwable $exception) {
            error_log('Order email notification error: ' . $exception->getMessage());
        }

        // Les notifications sont secondaires par rapport à l'enregistrement.
        // Une panne de notification ne doit jamais faire échouer une commande déjà commitée.
        try {
            $this->notificationService->notify(
                $userId,
                'order',
                'Commande n° ' . $orderId . ' envoyée ✓',
                'Nous avons bien reçu votre commande. Notre équipe la vérifie et l’accepte, en général sous 24 h : '
                . 'vous serez prévenu ici et par e-mail.',
                $orderId
            );

            $this->notificationService->notifyStaff(
                'order',
                'Nouvelle commande ' . $orderId,
                'Commande de ' . $user->getFirstName() . ' ' . $user->getLastName()
                . ' — ' . $menu->getTitle()
                . ' — ' . $numberOfPeople . ' personne(s)'
                . ' — total ' . number_format((float) $orderData['total_price'], 2, ',', ' ') . ' €.'
                . ' Prestation : ' . Labels::serviceType($serviceType)
                . ' le ' . $deliveryDate . ' à ' . $deliveryTime . '.',
                $orderId
            );
        } catch (\Throwable $exception) {
            error_log('Order internal notification error: ' . $exception->getMessage());
        }

        return [
            'success' => true,
            'order_id' => $orderId,
            'menu_price' => $menuPrice,
            'discount_rate' => $discountRate,
            'delivery_cost' => $deliveryCost,
            'total_price' => $orderData['total_price'],
        ];
    }

    /**
     * Valide une date et un créneau de prestation dans le fuseau Europe/Paris.
     */
    public function validateServiceDateTime(string $deliveryDate, string $deliveryTime): void
    {
        $timezone = new \DateTimeZone('Europe/Paris');
        $now = new \DateTimeImmutable('now', $timezone);

        $requested = \DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i',
            $deliveryDate . ' ' . $deliveryTime,
            $timezone
        );
        $errors = \DateTimeImmutable::getLastErrors();

        if (
            $requested === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        ) {
            throw new InvalidArgumentException('La date ou l’heure de prestation est invalide.');
        }

        if (!preg_match('/^\d{2}:\d{2}$/', $deliveryTime)) {
            throw new InvalidArgumentException('Le créneau horaire est invalide.');
        }

        [$hour, $minute] = array_map('intval', explode(':', $deliveryTime));
        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59 || $minute % 15 !== 0) {
            throw new InvalidArgumentException('Le créneau doit être aligné sur 15 minutes.');
        }

        if ($requested <= $now) {
            throw new InvalidArgumentException(
                $deliveryDate === $now->format('Y-m-d')
                    ? 'L’heure de prestation est déjà passée.'
                    : 'La date de prestation ne peut pas être passée.'
            );
        }

        $windows = $this->openingHoursRepository->getWindowsByDay((int) $requested->format('N'));

        if ($windows === []) {
            throw new InvalidArgumentException('Cette date est fermée. Veuillez choisir un autre jour.');
        }

        $requestedTime = $requested->format('H:i:s');

        foreach ($windows as [$opening, $closing]) {
            if ($requestedTime >= $opening && $requestedTime < $closing) {
                return;
            }
        }

        throw new InvalidArgumentException('Ce créneau est en dehors des horaires d’ouverture.');
    }

    public function getAvailableTimeSlots(string $deliveryDate): array
    {
        $timezone = new \DateTimeZone('Europe/Paris');
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $deliveryDate, $timezone);
        $errors = \DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        ) {
            return [];
        }

        $windows = $this->openingHoursRepository->getWindowsByDay((int) $date->format('N'));
        if ($windows === []) {
            return [];
        }

        $now = new \DateTimeImmutable('now', $timezone);
        $slots = [];

        foreach ($windows as [$opening, $closing]) {
            $slot = new \DateTimeImmutable(
                $date->format('Y-m-d') . ' ' . substr($opening, 0, 5),
                $timezone
            );
            $end = new \DateTimeImmutable(
                $date->format('Y-m-d') . ' ' . substr($closing, 0, 5),
                $timezone
            );

            while ($slot < $end) {
                if ($slot > $now) {
                    $slots[] = $slot->format('H:i');
                }
                $slot = $slot->modify('+15 minutes');
            }
        }

        return array_values(array_unique($slots));
    }

    public function getServiceDateAvailability(string $deliveryDate): array
    {
        $timezone = new \DateTimeZone('Europe/Paris');
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $deliveryDate, $timezone);
        $errors = \DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        ) {
            throw new InvalidArgumentException('La date de prestation est invalide.');
        }

        $days = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];
        $dayOfWeek = (int) $date->format('N');
        $windows = $this->openingHoursRepository->getWindowsByDay($dayOfWeek);

        return [
            'date' => $date->format('Y-m-d'),
            'day_of_week' => $dayOfWeek,
            'day_label' => $days[$dayOfWeek] ?? '',
            'open' => $windows !== [],
            'windows' => $windows,
            'slots' => $this->getAvailableTimeSlots($date->format('Y-m-d')),
        ];
    }

    public function getMenuAvailabilityForPeople(int $numberOfPeople): array
    {
        if ($numberOfPeople < 1) {
            throw new InvalidArgumentException('Le nombre de convives doit être supérieur à 0.');
        }

        $menus = $this->menuRepository->findAllForOrderSelection();
        $result = [];

        foreach ($menus as $menu) {
            $stockAvailable = $menu->getAvailableStock() > 0;
            $catalogMinimum = $menu->getMinPeople();
            $peopleAvailable = $numberOfPeople >= $catalogMinimum;
            $available = $stockAvailable && $peopleAvailable;
            $reason = !$stockAvailable
                ? 'Stock indisponible.'
                : (!$peopleAvailable
                    ? sprintf('Ce menu nécessite au minimum %d personne(s).', $catalogMinimum)
                    : null);

            $quote = MenuPricing::quote($menu->getBasePrice(), $catalogMinimum, $numberOfPeople);

            $result[] = [
                'id' => $menu->getId(),
                'title' => $menu->getTitle(),
                'min_people' => $menu->getMinPeople(),
                'base_price' => $menu->getBasePrice(),
                'price_per_person' => $quote['per_person'],
                'available_stock' => $menu->getAvailableStock(),
                'available' => $available,
                'reason' => $reason,
                'gross_price' => $peopleAvailable ? $quote['gross'] : null,
                'menu_price' => $peopleAvailable ? $quote['total'] : null,
                'discount_rate' => $peopleAvailable ? $quote['discount_rate'] : null,
                'discount_amount' => $peopleAvailable ? $quote['discount_amount'] : null,
            ];
        }

        return $result;
    }

    public function calculatePricePreview(
        int $menuId,
        int $numberOfPeople,
        string $serviceType,
        string $deliveryAddress = '',
        string $deliveryPostalCode = '',
        string $deliveryCity = '',
        bool $addressManual = false,
        bool $acceptOutsideZone = false
    ): array {
        if ($numberOfPeople < 1) {
            throw new InvalidArgumentException('Le nombre de convives est invalide.');
        }

        if (!array_key_exists($serviceType, Labels::SERVICE_TYPE)) {
            throw new InvalidArgumentException('Mode de prestation invalide.');
        }

        $menu = $this->menuRepository->findById($menuId);

        if ($menu === null || $menu->getAvailableStock() < 1) {
            throw new InvalidArgumentException('Menu indisponible.');
        }

        if ($numberOfPeople < $menu->getMinPeople()) {
            throw new InvalidArgumentException(
                sprintf('Ce menu nécessite au minimum %d personne(s).', $menu->getMinPeople())
            );
        }

        $quote = MenuPricing::quote($menu->getBasePrice(), $menu->getMinPeople(), $numberOfPeople);
        $menuPrice = $quote['total'];
        $discountRate = $quote['discount_rate'];
        // détail affiché dans le récapitulatif de commande
        $detail = [
            'menu_title' => $menu->getTitle(),
            'per_person' => $quote['per_person'],
            'gross_price' => $quote['gross'],
            'discount_amount' => $quote['discount_amount'],
        ];

        if ($serviceType !== 'delivery') {
            return $detail + [
                'ready' => true,
                'menu_price' => $menuPrice,
                'discount_rate' => $discountRate,
                'delivery_cost' => 0.00,
                'total_price' => round($menuPrice, 2),
            ];
        }

        $notReady = fn (string $message): array => $detail + [
            'ready' => false,
            'menu_price' => $menuPrice,
            'discount_rate' => $discountRate,
            'delivery_cost' => null,
            'total_price' => null,
            'message' => $message,
        ];

        if (trim($deliveryAddress) === '' || trim($deliveryPostalCode) === '' || trim($deliveryCity) === '') {
            return $notReady('Renseignez l’adresse, le code postal et la ville pour calculer la livraison.');
        }

        try {
            $delivery = $this->deliveryDistanceService->quote(
                $deliveryAddress,
                $deliveryPostalCode,
                $deliveryCity,
                $addressManual,
                $acceptOutsideZone
            );
        } catch (InvalidArgumentException $exception) {
            return $notReady($exception->getMessage());
        }

        return $detail + [
            'ready' => true,
            'menu_price' => $menuPrice,
            'discount_rate' => $discountRate,
            'delivery_cost' => $delivery['cost_pending'] ? null : $delivery['cost'],
            'delivery_cost_pending' => $delivery['cost_pending'],
            'delivery_zone' => $delivery['zone'],
            'distance_km' => $delivery['distance_km'],
            'total_price' => round($menuPrice + $delivery['cost'], 2),
            'message' => $delivery['cost_pending']
                ? 'Adresse saisie manuellement : les frais de livraison vous seront confirmés par l’équipe avant validation.'
                : '',
        ];
    }

    private function calculateMenuPrice(float $basePrice, int $minPeople, int $numberOfPeople): array
    {
        // règles communes au catalogue et à la commande (voir MenuPricing)
        $quote = MenuPricing::quote($basePrice, $minPeople, $numberOfPeople);

        return [$quote['total'], $quote['discount_rate']];
    }

    public function getUserOrders(int $userId): array
    {
        return $this->orderRepository->findByUserId($userId);
    }

    public function getOrdersForStaff(?string $status = null, ?string $customer = null): array
    {
        return $this->orderRepository->findForStaff($status, $customer);
    }

    public function getOrderById(int $orderId): ?Order
    {
        return $this->orderRepository->findById($orderId);
    }

    /**
     * Statuts qu'on peut atteindre depuis chaque statut. Seule source de vérité :
     * la liste de l'écran équipe (admin/orders.php) est construite à partir d'ici.
     * Pour « livrée », le serveur tranche selon la case « matériel prêté ».
     */
    public const ALLOWED_TRANSITIONS = [
        'pending' => ['accepted', 'cancelled'],
        'accepted' => ['preparing', 'cancelled'],
        'preparing' => ['delivering', 'cancelled'],
        'delivering' => ['delivered', 'cancelled'],
        'delivered' => ['awaiting_return', 'completed'],
        'awaiting_return' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    /**
     * Statuts dans lesquels l'équipe peut encore modifier le contenu d'une commande.
     */
    public const STAFF_EDITABLE_STATUSES = ['pending', 'accepted', 'preparing'];

    /**
     * Champs modifiables d'une commande (tout sauf le menu).
     */
    public const EDITABLE_FIELDS = [
        'number_of_people',
        'delivery_date',
        'delivery_time',
        'service_type',
        'contact_phone',
        'delivery_address',
        'delivery_city',
        'delivery_postal_code',
        'address_manual',
        'accept_outside_zone',
        'delivery_cost_override',
        'customization',
        'delivery_instructions',
    ];

    /**
     * Modification par le client : possible tant que la commande n'est pas acceptée,
     * quel que soit le mode de prestation. Le menu ne peut pas être changé.
     */
    public function updateCustomerOrder(int $orderId, int $userId, array $input): void
    {
        $order = $this->orderRepository->findById($orderId);

        if ($order === null || $order->getUserId() !== $userId) {
            throw new InvalidArgumentException('Commande introuvable.');
        }

        // en attente ET dans le délai (15 min / 24 h / 48 h selon la date de la prestation)
        if (!OrderModificationPolicy::customerCanChange($order)) {
            throw new InvalidArgumentException(OrderModificationPolicy::refusal($order));
        }

        // les frais confirmés à la main sont réservés à l'équipe
        unset($input['delivery_cost_override']);
        $this->applyOrderChanges($order, $input, $userId, 'Modification par le client');
    }

    /**
     * Modification par un employé après avoir contacté le client : le mode de contact
     * et le motif sont obligatoires et conservés dans l'historique de la commande.
     */
    public function updateOrderByStaff(
        int $orderId,
        int $staffId,
        array $input,
        string $contactMode,
        string $reason
    ): void {
        $order = $this->orderRepository->findById($orderId);

        if ($order === null) {
            throw new InvalidArgumentException('Commande introuvable.');
        }

        if (!in_array($order->getStatus(), self::STAFF_EDITABLE_STATUSES, true)) {
            throw new InvalidArgumentException(
                'Une commande en livraison, livrée, terminée ou annulée ne peut plus être modifiée.'
            );
        }

        $contactMode = trim($contactMode);
        $reason = trim($reason);

        if ($contactMode === '' || $reason === '') {
            throw new InvalidArgumentException(
                'Le mode de contact du client et le motif de la modification sont obligatoires.'
            );
        }

        $summary = $this->applyOrderChanges(
            $order,
            $input,
            $staffId,
            'Modification par l’équipe — Contact client : ' . $contactMode . ' — Motif : ' . $reason
        );

        try {
            $this->notificationService->notify(
                $order->getUserId(),
                'order',
                'Commande modifiée',
                'Suite à notre échange (' . $contactMode . '), la commande #' . $orderId
                    . ' a été modifiée : ' . $summary . '.',
                $orderId
            );
        } catch (\Throwable $exception) {
            error_log('Order internal notification error: ' . $exception->getMessage());
        }
    }

    /**
     * Valide les nouvelles valeurs, recalcule le prix, enregistre la commande et
     * ajoute une ligne à l'historique (statut inchangé) décrivant les changements.
     *
     * @return string Résumé des changements.
     */
    private function applyOrderChanges(Order $order, array $input, int $actorId, string $notePrefix): string
    {
        $numberOfPeople = filter_var(
            $input['number_of_people'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($numberOfPeople === false) {
            throw new InvalidArgumentException('Nombre de convives invalide.');
        }

        $serviceType = trim((string) ($input['service_type'] ?? ''));

        if (!array_key_exists($serviceType, Labels::SERVICE_TYPE)) {
            throw new InvalidArgumentException('Mode de prestation invalide.');
        }

        $deliveryDate = trim((string) ($input['delivery_date'] ?? ''));
        $deliveryTime = substr(trim((string) ($input['delivery_time'] ?? '')), 0, 5);
        $this->validateServiceDateTime($deliveryDate, $deliveryTime);

        $contactPhone = $this->validContactPhone(trim((string) ($input['contact_phone'] ?? '')));

        $menu = $this->menuRepository->findById($order->getMenuId());

        if ($menu === null) {
            throw new InvalidArgumentException('Le menu de la commande est introuvable.');
        }

        if ($numberOfPeople < $menu->getMinPeople()) {
            throw new InvalidArgumentException(
                sprintf('Ce menu nécessite au minimum %d personne(s).', $menu->getMinPeople())
            );
        }

        $address = trim((string) ($input['delivery_address'] ?? ''));
        $city = trim((string) ($input['delivery_city'] ?? ''));
        $postalCode = trim((string) ($input['delivery_postal_code'] ?? ''));
        $instructions = trim((string) ($input['delivery_instructions'] ?? ''));

        $sameAddress = $order->getServiceType() === 'delivery'
            && $address === $order->getDeliveryAddress()
            && $postalCode === (string) $order->getDeliveryPostalCode()
            && $city === (string) $order->getDeliveryCity();

        if ($serviceType === 'delivery' && $sameAddress) {
            // adresse inchangée : on garde la vérification et les frais déjà retenus
            $distanceKm = $order->getDeliveryDistanceKm();
            $deliveryCost = $order->getDeliveryCost();
            $addressStatus = $order->getAddressStatus();
            $deliveryZone = $order->getDeliveryZone();

            // adresse saisie à la main : l'équipe confirme les frais après avoir appelé le client
            $override = str_replace(',', '.', trim((string) ($input['delivery_cost_override'] ?? '')));
            if ($override !== '' && $order->isDeliveryCostPending()) {
                if (!is_numeric($override) || (float) $override < 0 || (float) $override > 1000) {
                    throw new InvalidArgumentException('Frais de livraison confirmés invalides (0 à 1 000 €).');
                }
                $deliveryCost = round((float) $override, 2);
            }
        } elseif ($serviceType === 'delivery') {
            // Adresse revérifiée ; « saisie manuelle » et « hors zone » viennent du formulaire de modification.
            $delivery = $this->deliveryDistanceService->quote(
                $address,
                $postalCode,
                $city,
                !empty($input['address_manual']),
                !empty($input['accept_outside_zone'])
            );
            $distanceKm = $delivery['distance_km'];
            $deliveryCost = $delivery['cost'];
            $addressStatus = $delivery['address_status'];
            $deliveryZone = $delivery['zone'];
        } else {
            $addressStatus = null;
            $deliveryZone = null;
            [$address, $city, $postalCode] = $serviceType === 'on_site'
                ? DeliveryDistanceService::COMPANY_ADDRESS
                : ['', '', ''];
            $distanceKm = null;
            $instructions = '';
            $deliveryCost = 0.00;
        }

        [$menuPrice, $discountRate] = $this->calculateMenuPrice(
            $menu->getBasePrice(),
            $menu->getMinPeople(),
            $numberOfPeople
        );

        // choix dans le menu : nouveaux choix envoyés, sinon anciens choix ajustés au nombre de convives
        $currentItems = $this->orderItemRepository->findForOrders([$order->getId()])[$order->getId()] ?? [];
        $currentRows = array_map(static fn (array $item): array => [
            'slot_dish_id' => $item['slot_dish_id'],
            'dish_id' => $item['dish_id'],
            'category' => $item['category'],
            'quantity' => $item['quantity'],
            'without_allergens' => $item['without_allergens'],
        ], $currentItems);
        if (is_array($input['items'] ?? null) && $input['items'] !== []) {
            $newRows = $this->compositionService->normalize($menu, $numberOfPeople, $input['items']);
        } elseif ($currentRows === []) {
            $newRows = $this->compositionService->defaults($menu, $numberOfPeople);
        } else {
            $newRows = $this->compositionService->rescale($currentRows, $order->getNumberOfPeople(), $numberOfPeople);
        }
        $compositionChanged = json_encode($newRows) !== json_encode($currentRows);

        $customization = trim((string) ($input['customization'] ?? $order->getCustomization() ?? ''));
        if (mb_strlen($customization) > 1000) {
            throw new InvalidArgumentException('Les précisions pour la cuisine sont limitées à 1 000 caractères.');
        }

        $data = [
            'customization' => $customization !== '' ? $customization : null,
            'number_of_people' => $numberOfPeople,
            'delivery_date' => $deliveryDate,
            'delivery_time' => $deliveryTime,
            'service_type' => $serviceType,
            'contact_phone' => $contactPhone,
            'delivery_address' => $address,
            'delivery_city' => $city,
            'delivery_postal_code' => $postalCode,
            'delivery_distance_km' => $distanceKm,
            'address_status' => $addressStatus,
            'delivery_zone' => $deliveryZone,
            'delivery_instructions' => $instructions !== '' ? $instructions : null,
            'menu_price' => $menuPrice,
            'delivery_cost' => $deliveryCost,
            'discount_rate' => $discountRate,
            'total_price' => round($menuPrice + $deliveryCost, 2),
        ];

        $summary = $this->describeChanges($order, $data);
        if ($compositionChanged) {
            $summary = ltrim($summary . ' ; Choix du menu : '
                . implode(' / ', OrderCompositionService::describe($this->compositionService->withNames($newRows))), ' ;');
        }

        if ($summary === '') {
            throw new InvalidArgumentException('Aucune modification n’a été saisie.');
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $this->orderRepository->updateDetails($order->getId(), $data);
            $this->orderItemRepository->replaceForOrder($order->getId(), $newRows);
            $this->orderRepository->addToHistory(
                $order->getId(),
                $order->getStatus(),
                $actorId,
                $notePrefix . ' — ' . $summary
            );
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }

        return $summary;
    }

    private function describeChanges(Order $order, array $data): string
    {
        $money = static fn (float $value): string => number_format($value, 2, ',', ' ') . ' €';

        $fields = [
            'Personnes' => [(string) $order->getNumberOfPeople(), (string) $data['number_of_people']],
            'Date' => [$order->getDeliveryDate(), $data['delivery_date']],
            'Heure' => [substr($order->getDeliveryTime(), 0, 5), $data['delivery_time']],
            'Prestation' => [
                Labels::serviceType($order->getServiceType()),
                Labels::serviceType($data['service_type']),
            ],
            'Téléphone' => [$order->getContactPhone(), $data['contact_phone']],
            'Adresse' => [
                trim($order->getDeliveryAddress() . ' ' . $order->getDeliveryPostalCode() . ' ' . $order->getDeliveryCity()),
                trim($data['delivery_address'] . ' ' . $data['delivery_postal_code'] . ' ' . $data['delivery_city']),
            ],
            'Instructions' => [(string) $order->getDeliveryInstructions(), (string) $data['delivery_instructions']],
            'Précisions cuisine' => [(string) $order->getCustomization(), (string) $data['customization']],
            'Total' => [$money($order->getTotalPrice()), $money($data['total_price'])],
        ];

        $changes = [];
        foreach ($fields as $label => [$before, $after]) {
            if ($before !== $after) {
                $changes[] = $label . ' : ' . ($before !== '' ? $before : '—') . ' → ' . ($after !== '' ? $after : '—');
            }
        }

        return implode(' ; ', $changes);
    }

    /**
     * Même règle qu'à l'inscription (PhoneValidator). Retourne le numéro au format E.164.
     */
    private function validContactPhone(string $contactPhone): string
    {
        $error = $this->phoneValidator->validate($contactPhone);

        if ($error !== null) {
            throw new InvalidArgumentException($error);
        }

        return $this->phoneValidator->normalize($contactPhone);
    }

    public function updateOrderStatus(
        int $orderId,
        string $status,
        ?int $changedByUserId = null,
        string $notes = '',
        ?string $cancellationReason = null,
        ?bool $equipmentLoaned = null
    ): void {
        $order = $this->orderRepository->findById($orderId);

        if ($order === null) {
            throw new InvalidArgumentException('Commande non trouvée.');
        }

        if (!array_key_exists($status, Labels::ORDER_STATUS)) {
            throw new InvalidArgumentException('Statut de commande invalide.');
        }

        if ($order->getStatus() === $status) {
            throw new InvalidArgumentException('La commande possède déjà ce statut.');
        }

        if (!in_array($status, self::ALLOWED_TRANSITIONS[$order->getStatus()] ?? [], true)) {
            throw new InvalidArgumentException('Transition de statut non autorisée.');
        }

        // Le contrôle porte sur la valeur de « matériel prêté » après application
        // de la case cochée dans le même envoi, et non sur l'ancienne valeur.
        $equipmentAfterUpdate = $equipmentLoaned ?? $order->isEquipmentLoaned();

        if (
            $order->getStatus() === 'delivered'
            && $status === 'awaiting_return'
            && !$equipmentAfterUpdate
        ) {
            throw new InvalidArgumentException(
                'Le statut « en attente du retour de matériel » nécessite un prêt de matériel.'
            );
        }

        if (
            $order->getStatus() === 'delivered'
            && $status === 'completed'
            && $equipmentAfterUpdate
        ) {
            throw new InvalidArgumentException(
                'Cette commande doit passer par le retour du matériel avant d’être terminée.'
            );
        }

        if ($status === 'cancelled' && trim((string) $cancellationReason) === '') {
            throw new InvalidArgumentException('Un motif est obligatoire pour annuler une commande.');
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            if ($equipmentLoaned !== null && $order->isEquipmentLoaned() !== $equipmentLoaned) {
                $this->orderRepository->setEquipmentLoaned($orderId, $equipmentLoaned);
                $order->setEquipmentLoaned($equipmentLoaned);
            }

            $this->orderRepository->updateStatus(
                $orderId,
                $status,
                $status === 'cancelled' ? trim((string) $cancellationReason) : null
            );

            if ($status === 'cancelled' && $order->getStatus() !== 'cancelled') {
                $this->orderRepository->increaseMenuStock($order->getMenuId());
            }

            $this->orderRepository->addToHistory(
                $orderId,
                $status,
                $changedByUserId,
                $notes
            );

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }

        if ($status === 'cancelled') {
            // Stock rendu : invalide le menu mis en cache.
            $this->menuService->invalidateMenu($order->getMenuId());
        }

        try {
            $this->notificationService->notify(
                $order->getUserId(),
                'order',
                self::statusTitle($status, $orderId),
                self::statusMessage($status, $order->getServiceType()) . ($notes !== '' ? ' ' . $notes : ''),
                $orderId
            );
        } catch (\Throwable $exception) {
            error_log('Order internal notification error: ' . $exception->getMessage());
        }

        $user = $this->userRepository->findById($order->getUserId());

        if ($user === null) {
            return;
        }

        try {
            if (in_array($status, ['accepted', 'preparing', 'delivering', 'delivered', 'cancelled'], true)) {
                $this->mailService->sendOrderStatusEmail(
                    $user->getEmail(),
                    $user->getFirstName(),
                    $orderId,
                    self::statusTitle($status, $orderId),
                    self::statusMessage($status, $order->getServiceType())
                        . ($status === 'cancelled' && $cancellationReason ? ' Motif : ' . $cancellationReason . '.' : '')
                );
            } elseif ($status === 'awaiting_return') {
                $this->mailService->sendEquipmentReturnNoticeEmail(
                    $user->getEmail(),
                    $user->getFirstName(),
                    $orderId
                );
            } elseif ($status === 'completed') {
                $this->mailService->sendReviewInvitationEmail(
                    $user->getEmail(),
                    $user->getFirstName(),
                    $orderId
                );
                $this->menuStatisticsService->aggregateAndStore();
            }
        } catch (\Throwable $exception) {
            error_log('Order status notification error: ' . $exception->getMessage());
        }
    }

    /** Options d'un menu pour le formulaire (catégories, plats, remplacements, allergènes). */
    public function compositionOptions(int $menuId): array
    {
        $menu = $this->menuRepository->findById($menuId);
        if ($menu === null) {
            throw new InvalidArgumentException('Menu introuvable.');
        }

        return $this->compositionService->options($menu);
    }

    /** Choix enregistrés de plusieurs commandes, indexés par commande. */
    public function itemsForOrders(array $orderIds): array
    {
        return $this->orderItemRepository->findForOrders($orderIds);
    }

    /** Titre court d'une notification de changement d'état (cloche, e-mail). */
    public static function statusTitle(string $status, int $orderId): string
    {
        return match ($status) {
            'accepted' => 'Commande n° ' . $orderId . ' acceptée ✓',
            'preparing' => 'Commande n° ' . $orderId . ' en préparation',
            'delivering' => 'Commande n° ' . $orderId . ' en route',
            'delivered' => 'Commande n° ' . $orderId . ' livrée',
            'awaiting_return' => 'Commande n° ' . $orderId . ' : retour du matériel',
            'completed' => 'Commande n° ' . $orderId . ' terminée',
            'cancelled' => 'Commande n° ' . $orderId . ' annulée',
            default => 'Commande n° ' . $orderId . ' : ' . Labels::orderStatus($status),
        };
    }

    public static function statusMessage(string $status, string $serviceType): string
    {
        return match ($status) {
            'accepted' => 'Bonne nouvelle : notre équipe a validé votre commande. Elle sera prête à la date prévue.',
            'preparing' => 'Nos cuisiniers préparent votre commande.',
            'delivering' => $serviceType === 'delivery'
                ? 'Votre commande est en route vers votre adresse.'
                : 'Votre commande est prête.',
            'delivered' => 'Votre commande vous a été remise. Bon appétit !',
            'awaiting_return' => 'Pensez à rapporter le matériel prêté sous 10 jours ouvrés.',
            'completed' => 'Prestation terminée. Merci ! Vous pouvez laisser un avis depuis « Mes commandes ».',
            'cancelled' => 'Votre commande a été annulée.',
            default => 'État de la commande : ' . Labels::orderStatus($status) . '.',
        };
    }

    public function getOrderHistory(int $orderId): array
    {
        return $this->orderRepository->getHistory($orderId);
    }

}

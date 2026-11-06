<?php
declare(strict_types=1);

namespace App\Entity;

class Order
{
    private int $id;
    private ?string $orderNumber = null;
    private int $userId;
    private int $menuId;
    private int $numberOfPeople;
    private string $orderDate;
    private string $deliveryDate;
    private string $deliveryTime;
    private string $deliveryAddress;
    private string $deliveryCity;
    private string $deliveryPostalCode;
    private ?float $deliveryDistanceKm = null;
    private ?string $customization = null;
    private string $serviceType = 'delivery';
    private string $paymentMethod = 'cash_on_site';
    private ?string $deliveryInstructions = null;
    private string $contactPhone = '';
    private float $deliveryCost;
    private float $menuPrice;
    private float $discountRate;
    private float $totalPrice;
    private string $status;
    private bool $equipmentLoaned = false;
    private ?string $cancellationReason = null;
    private ?\DateTimeInterface $createdAt = null;
    private ?\DateTimeInterface $updatedAt = null;
    // Informations d'affichage, renseignées par les requêtes avec jointure (liste équipe).
    private ?string $customerName = null;
    private ?string $customerEmail = null;
    private ?string $menuTitle = null;

    public function getId(): int { return $this->id; }
    public function getCustomerName(): ?string { return $this->customerName; }
    public function setCustomerName(?string $customerName): void { $this->customerName = $customerName; }
    public function getCustomerEmail(): ?string { return $this->customerEmail; }
    public function setCustomerEmail(?string $customerEmail): void { $this->customerEmail = $customerEmail; }
    public function getMenuTitle(): ?string { return $this->menuTitle; }
    public function setMenuTitle(?string $menuTitle): void { $this->menuTitle = $menuTitle; }
    public function getOrderNumber(): string
    {
        if ($this->orderNumber !== null && $this->orderNumber !== '') {
            return $this->orderNumber;
        }

        return 'VG-' . date('Ymd', strtotime($this->orderDate) ?: time()) . '-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
    public function setOrderNumber(?string $orderNumber): void { $this->orderNumber = $orderNumber; }
    public function setId(int $id): void { $this->id = $id; }
    public function getUserId(): int { return $this->userId; }
    public function setUserId(int $userId): void { $this->userId = $userId; }
    public function getMenuId(): int { return $this->menuId; }
    public function setMenuId(int $menuId): void { $this->menuId = $menuId; }
    public function getNumberOfPeople(): int { return $this->numberOfPeople; }
    public function setNumberOfPeople(int $numberOfPeople): void { $this->numberOfPeople = $numberOfPeople; }
    public function getOrderDate(): string { return $this->orderDate; }
    public function setOrderDate(string $orderDate): void { $this->orderDate = $orderDate; }
    public function getDeliveryDate(): string { return $this->deliveryDate; }
    public function setDeliveryDate(string $deliveryDate): void { $this->deliveryDate = $deliveryDate; }
    public function getDeliveryTime(): string { return $this->deliveryTime; }
    public function setDeliveryTime(string $deliveryTime): void { $this->deliveryTime = $deliveryTime; }
    public function getDeliveryAddress(): string { return $this->deliveryAddress; }
    public function setDeliveryAddress(string $deliveryAddress): void { $this->deliveryAddress = $deliveryAddress; }
    public function getDeliveryCity(): string { return $this->deliveryCity; }
    public function setDeliveryCity(string $deliveryCity): void { $this->deliveryCity = $deliveryCity; }
    public function getDeliveryPostalCode(): string { return $this->deliveryPostalCode; }
    public function setDeliveryPostalCode(string $deliveryPostalCode): void { $this->deliveryPostalCode = $deliveryPostalCode; }
    public function getDeliveryDistanceKm(): ?float { return $this->deliveryDistanceKm; }
    public function setDeliveryDistanceKm(?float $deliveryDistanceKm): void { $this->deliveryDistanceKm = $deliveryDistanceKm; }
    public function getCustomization(): ?string { return $this->customization; }
    public function setCustomization(?string $customization): void { $this->customization = $customization; }
    public function getServiceType(): string { return $this->serviceType; }
    public function setServiceType(string $serviceType): void { $this->serviceType = $serviceType; }
    public function getPaymentMethod(): string { return $this->paymentMethod; }
    public function setPaymentMethod(string $paymentMethod): void { $this->paymentMethod = $paymentMethod; }
    public function getDeliveryInstructions(): ?string { return $this->deliveryInstructions; }
    public function setDeliveryInstructions(?string $deliveryInstructions): void { $this->deliveryInstructions = $deliveryInstructions; }
    public function getContactPhone(): string { return $this->contactPhone; }
    public function setContactPhone(string $contactPhone): void { $this->contactPhone = $contactPhone; }
    public function getDeliveryCost(): float { return $this->deliveryCost; }
    public function setDeliveryCost(float $deliveryCost): void { $this->deliveryCost = $deliveryCost; }
    public function getMenuPrice(): float { return $this->menuPrice; }
    public function setMenuPrice(float $menuPrice): void { $this->menuPrice = $menuPrice; }
    public function getDiscountRate(): float { return $this->discountRate; }
    public function setDiscountRate(float $discountRate): void { $this->discountRate = $discountRate; }
    public function getTotalPrice(): float { return $this->totalPrice; }
    public function setTotalPrice(float $totalPrice): void { $this->totalPrice = $totalPrice; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function isEquipmentLoaned(): bool { return $this->equipmentLoaned; }
    public function setEquipmentLoaned(bool $equipmentLoaned): void { $this->equipmentLoaned = $equipmentLoaned; }
    public function getCancellationReason(): ?string { return $this->cancellationReason; }
    public function setCancellationReason(?string $cancellationReason): void { $this->cancellationReason = $cancellationReason; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): void { $this->createdAt = $createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void { $this->updatedAt = $updatedAt; }
}

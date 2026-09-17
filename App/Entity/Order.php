<?php
namespace App\Entity;

class Order
{
    private int $id;
    private int $userId;
    private int $menuId;
    private int $numberOfPeople;
    private string $orderDate; // datetime
    private string $deliveryDate; // date
    private string $deliveryTime; // time
    private string $deliveryAddress;
    private float $deliveryCost;
    private float $menuPrice;
    private float $totalPrice;
    private string $status; // pending, accepted, preparing, delivering, delivered, awaiting_return, completed, cancelled
    private ?\DateTimeInterface $createdAt;
    private ?\DateTimeInterface $updatedAt;

    // Getters and setters

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getMenuId(): int
    {
        return $this->menuId;
    }

    public function setMenuId(int $menuId): void
    {
        $this->menuId = $menuId;
    }

    public function getNumberOfPeople(): int
    {
        return $this->numberOfPeople;
    }

    public function setNumberOfPeople(int $numberOfPeople): void
    {
        $this->numberOfPeople = $numberOfPeople;
    }

    public function getOrderDate(): string
    {
        return $this->orderDate;
    }

    public function setOrderDate(string $orderDate): void
    {
        $this->orderDate = $orderDate;
    }

    public function getDeliveryDate(): string
    {
        return $this->deliveryDate;
    }

    public function setDeliveryDate(string $deliveryDate): void
    {
        $this->deliveryDate = $deliveryDate;
    }

    public function getDeliveryTime(): string
    {
        return $this->deliveryTime;
    }

    public function setDeliveryTime(string $deliveryTime): void
    {
        $this->deliveryTime = $deliveryTime;
    }

    public function getDeliveryAddress(): string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(string $deliveryAddress): void
    {
        $this->deliveryAddress = $deliveryAddress;
    }

    public function getDeliveryCost(): float
    {
        return $this->deliveryCost;
    }

    public function setDeliveryCost(float $deliveryCost): void
    {
        $this->deliveryCost = $deliveryCost;
    }

    public function getMenuPrice(): float
    {
        return $this->menuPrice;
    }

    public function setMenuPrice(float $menuPrice): void
    {
        $this->menuPrice = $menuPrice;
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): void
    {
        $this->totalPrice = $totalPrice;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
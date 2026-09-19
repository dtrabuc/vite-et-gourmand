<?php
namespace App\Entity;

class Menu
{
    private int $id;
    private string $title;
    private string $description;
    private string $theme;
    private string $dietaryRegime = 'classic';
    private int $minPeople;
    private float $basePrice;
    private string $conditions;
    private int $availableStock;
    private ?\DateTimeInterface $createdAt = null;
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = $title; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function getTheme(): string { return $this->theme; }
    public function setTheme(string $theme): void { $this->theme = $theme; }
    public function getDietaryRegime(): string { return $this->dietaryRegime; }
    public function setDietaryRegime(string $dietaryRegime): void { $this->dietaryRegime = $dietaryRegime; }
    public function getMinPeople(): int { return $this->minPeople; }
    public function setMinPeople(int $minPeople): void { $this->minPeople = $minPeople; }
    public function getBasePrice(): float { return $this->basePrice; }
    public function setBasePrice(float $basePrice): void { $this->basePrice = $basePrice; }
    public function getConditions(): string { return $this->conditions; }
    public function setConditions(string $conditions): void { $this->conditions = $conditions; }
    public function getAvailableStock(): int { return $this->availableStock; }
    public function setAvailableStock(int $availableStock): void { $this->availableStock = $availableStock; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): void { $this->createdAt = $createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void { $this->updatedAt = $updatedAt; }
}

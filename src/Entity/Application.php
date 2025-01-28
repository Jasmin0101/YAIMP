<?php

namespace App\Entity;

use App\Enums\ActionEnum;
use App\Repository\ApplicationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicationRepository::class)]
class Application
{

    // id заявки 
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    //Через пользователя достаем  портфель получается  
    #[ORM\ManyToOne(inversedBy: 'applications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // Индикатор ценной бумаги в заявке 
    #[ORM\ManyToOne(inversedBy: 'applications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Stock $stock = null;
    // Количество 
    #[ORM\Column]
    private ?int $quantity = null;
    // Цена за единицу ценной бумаги 
    #[ORM\Column]
    private ?float $price = null;
    // Статус заявки 
    #[ORM\Column(type: 'string', enumType: ActionEnum::class)]
    private ?ActionEnum $action = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getStock(): ?Stock
    {
        return $this->stock;
    }

    public function setStock(?Stock $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }
    public function getAction(): ?ActionEnum
    {
        return $this->action;
    }

    public function setAction(?ActionEnum $action): static
    {
        $this->action = $action;
        return $this;
    }

}

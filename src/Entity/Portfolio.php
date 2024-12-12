<?php

namespace App\Entity;

use App\Repository\PortfolioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PortfolioRepository::class)]
class Portfolio
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Указывает связь "многие-к-одному" между Portfolio и User.
    // Один пользователь может иметь много портфелей,
    //  но каждый портфель связан только с одним пользователем.
    //inversedBy: 'portfolios'
    // Указывает, что обратная связь описана в сущности User в поле $portfolios.

    #[ORM\ManyToOne(inversedBy: 'portfolios')]
    // Указывает что  поле пользователя не должно быть нулабельным тк у каждого портфеля обязательно сущ пользователь 
    #[ORM\JoinColumn(nullable: false)]

    // хранит объект пользователя
    private ?User $user = null;

    #[ORM\Column]

    // балансе 
    private ?float $balance = null;

    // Оп оп вот она связь с Депозитарием
    // PHPDoc-аннотация, указывающая, что это поле содержит коллекцию объектов
    /**
     * @var Collection<int, Depositary>
     */

    // Тут аналогично связи пользователя с портфелем, один ко многим, 1 портфель =? многа бумяг 
    #[ORM\OneToMany(targetEntity: Depositary::class, mappedBy: 'portfolio')]
    private Collection $depositaries;

    public function __construct()
    {
        $this->depositaries = new ArrayCollection();
    }

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

    public function getBalance(): ?float
    {
        return $this->balance;
    }

    public function setBalance(float $balance): static
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * @return Collection<int, Depositary>
     */
    public function getDepositaries(): Collection
    {
        return $this->depositaries;
    }

    public function addDepositary(Depositary $depositary): static
    {
        if (!$this->depositaries->contains($depositary)) {
            $this->depositaries->add($depositary);
            $depositary->setPortfolio($this);
        }

        return $this;
    }
    // addPortfolio()
// Добавляет объект Depositary в коллекцию и устанавливает связь с текущим портфелем.

    // removePortfolio()
// Удаляет объект Depositary из коллекции и разрывает связ

    // public function removePortfolio(Depositary $portfolio): static
    // {
    //     if ($this->Portfolio->removeElement($portfolio)) {
    //         // set the owning side to null (unless already changed)
    //         if ($portfolio->getPortfolio() === $this) {
    //             $portfolio->setPortfolio(null);
    //         }
    //     }

    //     return $this;
    // }
}

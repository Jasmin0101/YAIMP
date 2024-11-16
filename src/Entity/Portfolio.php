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

    #[ORM\ManyToOne(inversedBy: 'portfolios')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?float $balance = null;

    /**
     * @var Collection<int, Depositary>
     */
    #[ORM\OneToMany(targetEntity: Depositary::class, mappedBy: 'portfolio')]
    private Collection $Portfolio;

    public function __construct()
    {
        $this->Portfolio = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?User
    {
        return $this->user;
    }

    public function setUserId(?User $user): static
    {
        $this->user_id = $user;

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
    public function getPortfolio(): Collection
    {
        return $this->Portfolio;
    }

    public function addPortfolio(Depositary $portfolio): static
    {
        if (!$this->Portfolio->contains($portfolio)) {
            $this->Portfolio->add($portfolio);
            $portfolio->setPortfolio($this);
        }

        return $this;
    }

    public function removePortfolio(Depositary $portfolio): static
    {
        if ($this->Portfolio->removeElement($portfolio)) {
            // set the owning side to null (unless already changed)
            if ($portfolio->getPortfolio() === $this) {
                $portfolio->setPortfolio(null);
            }
        }

        return $this;
    }
}

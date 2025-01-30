<?php

namespace App\Service;

use App\Entity\Application;
use App\Entity\Depositary;
use App\Entity\Portfolio;
use App\Enums\ActionEnum;
use App\Repository\ApplicationRepository;
use App\Repository\PortfolioRepository;
use App\Repository\DepositaryRepository;
use App\Repository\StockRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class DealService
{
    public function __construct(
        private readonly StockRepository $stockRepository,
        private readonly UserRepository $userRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly PortfolioRepository $portfolioRepository,
        private readonly DepositaryRepository $depositoryRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function executeDeal(int $myApplicationId): void
    {
        $myApplication = $this->applicationRepository->find($myApplicationId);
        if (!$myApplication) {
            return;
        }

        $usersApplications = $this->applicationRepository->findAll();

        foreach ($usersApplications as $userApplication) {
            if ($userApplication->getId() === $myApplicationId) {
                continue;
            }

            // Проверяем, чтобы пользователи были разными
            if ($userApplication->getPortfolio()->getUser() === $myApplication->getPortfolio()->getUser()) {
                continue;
            }

            // Проверяем, что заявки совпадают по цене, количеству и активу, но имеют противоположное действие
            if (
                $userApplication->getStock()->getId() === $myApplication->getStock()->getId() &&
                $userApplication->getPrice() === $myApplication->getPrice() &&
                $userApplication->getQuantity() === $myApplication->getQuantity() &&
                $userApplication->getAction() !== $myApplication->getAction()
            ) {
                $this->processExchange($myApplication, $userApplication);
                return;
            }
        }
    }

    private function processExchange(Application $buyApplication, Application $sellApplication): void
    {
        if ($buyApplication->getAction() === ActionEnum::BUY) {
            $buyer = $buyApplication->getPortfolio();
            $seller = $sellApplication->getPortfolio();
        } else {
            $buyer = $sellApplication->getPortfolio();
            $seller = $buyApplication->getPortfolio();
        }

        $price = $buyApplication->getPrice();
        $quantity = $buyApplication->getQuantity();
        $totalAmount = $price * $quantity;

        // Обновляем баланс пользователей
        $buyer->setBalance($buyer->getBalance() - $totalAmount);
        $seller->setBalance($seller->getBalance() + $totalAmount);

        // Обновляем количество ценных бумаг у покупателей и продавцов
        $this->updateDepositary($buyer, $buyApplication->getStock(), $quantity);
        $this->updateDepositary($seller, $sellApplication->getStock(), -$quantity);

        // Удаляем заявки
        $this->entityManager->remove($buyApplication);
        $this->entityManager->remove($sellApplication);
        $this->entityManager->flush();
    }

    private function updateDepositary(Portfolio $portfolio, $stock, int $quantity): void
    {
        $depositary = $this->depositoryRepository->findOneBy([
            'portfolio' => $portfolio,
            'stock' => $stock
        ]);

        if ($depositary) {
            $depositary->setQuantity($depositary->getQuantity() + $quantity);
        } else {
            if ($quantity > 0) {
                $depositary = new Depositary();
                $depositary->setPortfolio($portfolio);
                $depositary->setStock($stock);
                $depositary->setQuantity($quantity);
                $this->entityManager->persist($depositary);
            }
        }

        $this->entityManager->flush();
    }
}

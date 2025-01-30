<?php

namespace App\Service;

use App\Repository\ApplicationRepository;
use App\Repository\PortfolioRepository;
use App\Repository\DepositaryRepository;
use App\Repository\StockRepository;
use App\Repository\UserRepository;

class DealService
{
    public function __construct(
        private readonly StockRepository $stockRepository,
        private readonly UserRepository $userRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly PortfolioRepository $portfolioRepository,
        private readonly DepositaryRepository $depositoryRepository
    ) {
    }

    public function executeDeal(int $myApplicationId): void
    {
        $usersApplications = $this->applicationRepository->findAll();
        $myApplication = $this->applicationRepository->findById($myApplicationId);

        if (!$myApplication) {
            throw new \Exception("Application not found");
        }

        foreach ($usersApplications as $userApplication) {
            if (!$userApplication instanceof Application) {
                continue;
            }

            if ($userApplication->getId() === $myApplicationId) {
                continue;
            }

            // Проверяем, чтобы пользователи были разными
            if ($userApplication->getPortfolio()->getUser()->getId() === $myApplication->getPortfolio()->getUser()->getId()) {
                continue;
            }

            if (
                $userApplication->getStock()->getId() === $myApplication->getStock()->getId() &&
                $userApplication->getPrice() === $myApplication->getPrice() &&
                $userApplication->getQuantity() === $myApplication->getQuantity() &&
                $userApplication->getAction() !== $myApplication->getAction()
            ) {
                if ($myApplication->getAction() === 'buy') {
                    $buyer = $myApplication->getPortfolio();
                    $seller = $userApplication->getPortfolio();
                } else {
                    $buyer = $userApplication->getPortfolio();
                    $seller = $myApplication->getPortfolio();
                }

                $totalPrice = $myApplication->getPrice() * $myApplication->getQuantity();

                // Обновляем баланс пользователей
                $buyer->setBalance($buyer->getBalance() - $totalPrice);
                $seller->setBalance($seller->getBalance() + $totalPrice);

                // Обновляем количество ценных бумаг (через Depositary)
                $sellerDepositary = $seller->getDepositary();
                $buyerDepositary = $buyer->getDepositary();

                if (!$sellerDepositary || !$buyerDepositary) {
                    throw new \Exception("Depositary not found for one of the users");
                }

                $sellerDepositary->setQuantity($sellerDepositary->getQuantity() - $myApplication->getQuantity());
                $buyerDepositary->setQuantity($buyerDepositary->getQuantity() + $myApplication->getQuantity());

                // Сохраняем изменения в базе
                $this->portfolioRepository->save($buyer);
                $this->portfolioRepository->save($seller);
                $this->depositoryRepository->save($sellerDepositary);
                $this->depositoryRepository->save($buyerDepositary);

                // Удаляем заявки из базы
                $this->applicationRepository->removeApplication($myApplication);
                $this->applicationRepository->removeApplication($userApplication);

                break; // Завершаем выполнение после первой успешной сделки
            }
        }
    }
}

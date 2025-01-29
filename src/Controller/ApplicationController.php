<?php

namespace App\Controller;

use App\Entity\Application;
use App\Enums\ActionEnum;
use App\Form\ApplicationType;
use App\Form\DTO\CreateApplicationRequest;
use App\Repository\ApplicationRepository;
use App\Repository\PortfolioRepository;
use App\Repository\DepositaryRepository;
use App\Repository\StockRepository;
use App\Repository\UserRepository;
use LDAP\Result;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Config\Security\AccessControlConfig;


class ApplicationController extends AbstractController
{
    public function __construct(
        private readonly StockRepository $stockRepository,
        private readonly UserRepository $userRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly PortfolioRepository $portfolioRepository,
        private readonly DepositaryRepository $depositoryRepository
    ) {
    }
    #[Route('/glass/stocks/myApplication', name: 'app_stock_glass_view_my_application', methods: ['GET'])]
    public function viewMyApplications(Request $request): Response
    {

        $userId = $this->getUser()->getId();

        // Получаем все портфели пользователя
        $portfolios = $this->portfolioRepository->findByUserId($userId);
        if (!$portfolios) {
            return $this->json([
                'message' => 'No portfolios found for this user.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Массив для хранения всех заявок
        $allApplications = [];

        // Проходим по всем портфелям и получаем заявки из каждого
        foreach ($portfolios as $portfolio) {
            // Получаем все депозитарии для текущего портфеля
            $depositories = $this->depositoryRepository->findByPortfolioId($portfolio->getId());
            if (empty($depositories)) {
                continue; // Если нет депозитариев, переходим к следующему портфелю
            }

            // Извлекаем все акции из депозитариев и добавляем в общий массив
            foreach ($depositories as $depository) {
                // Получаем все акции для данного депозитария
                $stocks = $this->stockRepository->findByDepositoryId($depository->getId());
                foreach ($stocks as $stock) {
                    // Здесь можно добавлять логику для фильтрации или обработки данных
                    $allApplications[] = $stock; // Добавляем заявку в общий список
                }
            }
        }

        // Если заявки не найдены, возвращаем сообщение
        if (empty($allApplications)) {
            return $this->json([
                'message' => 'No applications found for this user.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Возвращаем данные в Twig-шаблон
        return $this->render('glass/stock_glass_my_application.html.twig', [
            'stocks' => $allApplications,
            'BUY' => ActionEnum::BUY,
            'SELL' => ActionEnum::SELL,
        ]);
    }
}

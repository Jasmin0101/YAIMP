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

class GlassController extends AbstractController
{
    public function __construct(
        private readonly StockRepository $stockRepository,
        private readonly UserRepository $userRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly PortfolioRepository $portfolioRepository,
        private readonly DepositaryRepository $depositoryRepository
    ) {

    }
    #[Route('/glass/stock/{stockId}', name: 'app_stock_glass', methods: ['GET'])]
    public function getStockGlass(int $stockId): Response
    {
        $stock = $this->stockRepository->findById($stockId);
        if ($stock == null) {
            throw $this->createNotFoundException("Stock not found");
        }

        return $this->render('glass/stock_glass_index.html.twig', [
            'stock' => $stock,
            'BUY' => ActionEnum::BUY,
            'SELL' => ActionEnum::SELL,
        ]);
    }
    #[Route('/glass/stock/{stockId}', name: 'app_stock_glass_create_application', methods: ['POST'])]
    public function createApplication(int $stockId, Request $request): Response
    {
        $userId = $request->getPayload()->get('user_id');
        $quantity = $request->getPayload()->get('quantity');
        $price = $request->getPayload()->get('price');
        $action = ActionEnum::from($request->getPayload()->get('action'));

        $stock = $this->stockRepository->findById($stockId);
        $users = $this->userRepository->findBy(['id' => $userId]);



        $application = new Application();
        $application->setStock($stock);
        $application->setQuantity($quantity);
        $application->setAction($action);
        $application->setPrice($price);
        $application->setUser(current($users));

        $this->applicationRepository->saveApplication($application);


        return new Response("OK ");
    }

    #[Route('/glass/stock/{stockId}', name: 'app_stock_glass_update_application', methods: ['PATCH'])]

    public function updateApplication(int $stockId, Request $request)
    {

        $applicationId = $request->getPayload()->get('application_id');
        $quantity = $request->getPayload()->get('quantity');
        $price = $request->getPayload()->get('price');

        $application = $this->applicationRepository->find($applicationId);

        $application->setQuantity($quantity);
        $application->setPrice($price);

        $this->applicationRepository->saveApplication($application);
        return new Response('OK', Response::HTTP_ACCEPTED);


    }

    #[Route('/glass/stock/{stockId}', name: 'app_stock_glass_delete_application', methods: ['DELETE'])]

    public function deleteApplication(int $stockId, Request $request)
    {

        $applicationId = $request->getPayload()->get('application_id');


        $application = $this->applicationRepository->find($applicationId);

        $this->applicationRepository->removeApplication($application);

        return new Response('OK', Response::HTTP_ACCEPTED);
    }

    #[Route('/glass/stocks', name: 'app_stock_glass_view', methods: ['GET'])]

    public function viewApplications(): Response
    {
        // Получаем список всех акций через StockRepository
        $stocks = $this->stockRepository->findAll();

        // Если ничего не найдено, возвращаем сообщение
        if (empty($stocks)) {
            return $this->json([
                'message' => 'No stocks found in the database.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Возвращаем данные в Twig-шаблон
        return $this->render('glass/stock_glass_view.html.twig', [
            'stocks' => $stocks,
            'BUY' => ActionEnum::BUY,
            'SELL' => ActionEnum::SELL,
        ]);
    }


    #[Route('/glass/stocks/myApplication', name: 'app_stock_glass_view_my_application', methods: ['GET'])]
    public function viewMyApplications(Request $request): Response
    {
        // Получаем user_id из запроса (или из текущего пользователя)
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



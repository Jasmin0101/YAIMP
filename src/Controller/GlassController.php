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
use App\Service\DealService;
use LDAP\Result;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Config\Security\AccessControlConfig;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
class GlassController extends AbstractController
{
    public function __construct(
        private readonly StockRepository $stockRepository,
        private readonly UserRepository $userRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly PortfolioRepository $portfolioRepository,
        private readonly DepositaryRepository $depositoryRepository,
        private readonly DealService $dealService
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


    #[Route('/applications/create', name: 'app_create_application', methods: ['POST'])]
    public function createApplication(Request $request): Response
    {
        $userId = $this->getUser();
        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }


        $quantity = $request->getPayload()->get('quantity');
        if ($quantity < 0) {
            return $this->json([
                'message' => 'Quantity must be greater than 0',
            ], Response::HTTP_BAD_REQUEST);
        }
        $price = $request->getPayload()->get('price');
        if ($price < 0) {
            return $this->json([
                'message' => 'Price must be greater than 0',
            ], Response::HTTP_BAD_REQUEST);
        }

        $stockId = $request->getPayload()->get('stock');


        $action = ActionEnum::from($request->getPayload()->get('action'));
        $portfolioId = $request->getPayload()->get('portfolio');
        $depositary = $this->depositoryRepository->find($portfolioId);
        if ($stockId != $depositary->getStock()->getId()) {
            return $this->json([
                'message' => 'Stock is not in Portfolio ',
            ], Response::HTTP_NOT_FOUND);
        }

        $portfolio = $this->portfolioRepository->find($portfolioId);
        $stock = $this->stockRepository->find($stockId);

        if ($depositary->getQuantity() < $quantity) {
            return $this->json([
                'message' => 'Not enough stocks in the portfolio',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($depositary->getPortfolio()->getBalance() < $quantity * $price && $action == ActionEnum::BUY) {
            return $this->json([
                'message' => 'Not enough money in the portfolio',
            ], Response::HTTP_NOT_FOUND);
        }
        $application = new Application();

        $application->setQuantity($quantity);
        $application->setAction($action);
        $application->setPrice($price);
        $application->setStock($stock);
        $application->setPortfolio($portfolio);

        $this->applicationRepository->saveApplication($application);

        $this->dealService->executeDeal($application->getId());

        return new RedirectResponse('/applications/my/view');


    }
    #[Route('/applications/update/{applicationId}', name: 'app_update_application', methods: ['POST'])]
    public function updateApplication(Request $request, int $applicationId)
    {
        $quantity = $request->getPayload()->get('quantity');
        if ($quantity < 0) {
            return $this->json([
                'message' => 'Quantity must be greater than 0',
            ], Response::HTTP_BAD_REQUEST);
        }
        $price = $request->getPayload()->get('price');
        $action = ActionEnum::from($request->getPayload()->get('action'));


        $application = $this->applicationRepository->find($applicationId);
        if ($application == null) {
            return $this->json([
                'message' => 'Application not found',
            ], Response::HTTP_NOT_FOUND);
        }
        $application->setQuantity($quantity);
        $application->setPrice($price);
        $application->setAction($action);

        $this->applicationRepository->saveApplication($application);


        $this->dealService->executeDeal($applicationId);
        // Используем редирект с правильной формой пути
        return $this->redirectToRoute('app_update_application', ['applicationId' => $applicationId]);
    }


    #[Route('/applications/update/{applicationId}', name: 'app_update_show_application', methods: ['GET'])]

    public function showUpdateApplication(int $applicationId)
    {
        $user = $this->getUser();
        if ($user == null) {
            return $this->json([
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $application = $this->applicationRepository->find($applicationId);
        if ($application == null) {
            return $this->json([
                'message' => 'Application not found',
            ], Response::HTTP_NOT_FOUND);
        }
        return $this->render('glass/stock_applications_update.html.twig', [
            'application' => $application,
            'BUY' => ActionEnum::BUY,
            'SELL' => ActionEnum::SELL,
        ]);
    }

    #[Route('/application/delete/{applicationId}', name: 'app_stock_glass_delete_application', methods: ['POST'])]

    public function deleteApplication(int $applicationId, Request $request): Response
    {
        $application = $this->applicationRepository->find($applicationId);
        if ($application == null) {
            return $this->json([
                'message' => 'Application not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->applicationRepository->removeApplication($application);


        return new RedirectResponse('/applications/my/view');
    }

    #[Route('/applications', name: 'app_stock_glass_view', methods: ['GET'])]

    public function viewApplications(): Response
    {
        // Получаем список всех акций через StockRepository
        $application = $this->applicationRepository->findAll();
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
            'application' => $application,
            'BUY' => ActionEnum::BUY,
            'SELL' => ActionEnum::SELL,
        ]);
    }


    #[Route('/applications/my/view', name: 'app_view_my_application', methods: ['GET'])]
    public function viewMyApplications(Request $request): Response
    {
        $depositors = $this->depositoryRepository->findAll();
        $user = $this->getUser();
        if ($user == null) {
            return $this->json([
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        }
        $userPortfolios = $user->getPortfolios();
        if (empty($userPortfolios)) {
            return $this->json([
                'message' => 'No portfolios found for this user.',
            ], Response::HTTP_NOT_FOUND);
        }

        $userApplications = [];
        $applications = $this->applicationRepository->findAll();
        foreach ($depositors as $depository) {
            foreach ($userPortfolios as $userPortfolio) {
                if ($depository->getPortfolio()->getId() == $userPortfolio->getId()) {
                    $userDepository[] = $depository;
                }
            }
        }
        foreach ($applications as $application) {
            foreach ($userPortfolios as $userPortfolio) {
                if ($application->getPortfolio()->getId() == $userPortfolio->getId()) {
                    $userApplications[] = $application;
                }
            }

        }


        return $this->render('glass/stock_glass_my_application.html.twig', [
            'stocks' => $this->stockRepository->findAll(),
            'applications' => $userApplications,
            'depositories' => $userDepository,
            'portfolios' => $userPortfolios,
            'BUY' => ActionEnum::BUY,
            'SELL' => ActionEnum::SELL,
        ]);
    }

}



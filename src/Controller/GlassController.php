<?php

namespace App\Controller;


use App\Entity\Application;
use App\Enums\ActionEnum;
use App\Form\ApplicationType;
use App\Repository\ApplicationRepository;
use App\Repository\StockRepository;
use App\Repository\PortfolioRepository;
use App\Repository\DepositaryRepository;
use App\Service\DealService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
class GlassController extends AbstractController
{
    private $stockRepository;
    private $portfolioRepository;
    private $applicationRepository;
    private $dealService;

    public function __construct(
        StockRepository $stockRepository,
        PortfolioRepository $portfolioRepository,
        ApplicationRepository $applicationRepository,
        DealService $dealService,
        private readonly DepositaryRepository $depositoryRepository,
    ) {

        $this->stockRepository = $stockRepository;
        $this->portfolioRepository = $portfolioRepository;
        $this->applicationRepository = $applicationRepository;
        $this->dealService = $dealService;
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
    #[Route('/applications/create', name: 'app_create_application', methods: ['GET', 'POST'])]
    public function createApplication(Request $request, EntityManagerInterface $entityManager): Response
    {
        $application = new Application();
        $form = $this->createForm(ApplicationType::class, $application);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($application);
            $entityManager->flush();

            // Execute deal after saving application
            $this->dealService->executeDeal($application->getId());

            return $this->redirectToRoute('app_stock_glass_view');
        }

        return $this->render('glass/application_create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/applications/update/{applicationId}', name: 'app_update_application', methods: ['GET', 'POST'])]
    public function updateApplication(Request $request, int $applicationId, EntityManagerInterface $entityManager): Response
    {
        $application = $this->applicationRepository->find($applicationId);
        if (!$application) {
            throw $this->createNotFoundException('Application not found');
        }

        $form = $this->createForm(ApplicationType::class, $application);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Execute deal after updating application
            $this->dealService->executeDeal($application->getId());

            return $this->redirectToRoute('app_stock_glass_view');
        }

        return $this->render('glass/application_update.html.twig', [
            'form' => $form->createView(),
        ]);
    }




    #[Route('/application/delete/{applicationId}', name: 'app_stock_glass_delete_application', methods: ['POST'])]

    public function deleteApplication(int $applicationId, Request $request): Response
    {
        $application = $this->applicationRepository->find($applicationId);

        $this->applicationRepository->removeApplication($application);

        return new RedirectResponse('/applications/my/view');
    }

    #[Route('/applications', name: 'app_stock_glass_view', methods: ['GET'])]

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



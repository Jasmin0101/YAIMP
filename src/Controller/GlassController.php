<?php

namespace App\Controller;
use App\Entity\Application;
use App\Form\ApplicationType;
use Symfony\Component\HttpFoundation\Request;
use App\Enums\ActionEnum;
use App\Repository\StockRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GlassController extends AbstractController
{
    public function __construct(
        private readonly StockRepository $stockRepository, // формируем скл запрос в базу данных 
    ) {
    }

    #[Route('/glass/stock/{stockId}', name: 'app_glass', methods: ['GET'])]
    public function getStockGlass(int $stockId): Response
    {
        $stock = $this->stockRepository->findById($stockId);
        if ($stock == null) {
            throw $this->createNotFoundException("Stock not found");
        }

        return $this->render('glass/stock_glass_index.html.twig', [
            'controller_name' => 'GlassController',
            'stock' => $stock,
            'BUY' => ActionEnum::BUY,
            'SELL' => ActionEnum::SELL
        ]);
    }
    #[Route('/glass/stock/{stockId}', name: 'app_stock_glass_create_application', methods: ['POST'])]
    public function createApplication(int $stockId, Request $request): Response
    {
        $application = new Application();
        $form = $this->createForm(ApplicationType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

        }

        return new Response("'HELLO' {$request->getPayload()->getString('action')}");
    }
}

<?php

namespace App\Controller;

use App\Entity\Application;
use App\Enums\ActionEnum;
use App\Form\ApplicationType;
use App\Form\DTO\CreateApplicationRequest;
use App\Repository\ApplicationRepository;
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
        private readonly ApplicationRepository $applicationRepository
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

}
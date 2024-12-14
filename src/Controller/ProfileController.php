<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Portfolio;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectManagerDecorator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\PortfolioRepository;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }


    #[Route(path: '/profile/addPortfolio', name: 'add_portfolio', methods: ['POST'])]

    // entityManagerInterface Он предоставляет набор методов для работы с базой данных, таких как создание, чтение, обновление и удаление (CRUD)
    public function addPortfolio(EntityManagerInterface $entityManagerInterface): Response
    {
        $user = $this->getUser();

        $portfolio = new Portfolio();
        $portfolio->setBalance(0);
        $portfolio->setUser($user);
        //// tell Doctrine you want to (eventually) save the Product (no queries yet)

        $entityManagerInterface->persist($portfolio);
        // // actually executes the queries (i.e. the INSERT query)
        $entityManagerInterface->flush();
        return $this->redirectToRoute('app_profile');

    }

}

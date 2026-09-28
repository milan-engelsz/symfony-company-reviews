<?php

namespace App\Controller;

use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CompanyController extends AbstractController
{
    #[Route('/companies', name: 'company.index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $search = $request->query->get('search');

        $companyNamesWithStatistics = $entityManager->getRepository(Review::class)
            ->getCompanyNamesWithStatistics($search);

        return $this->render('company/index.html.twig', [
            'search' => $search,
            'companyNamesWithStatistics' => $companyNamesWithStatistics,
        ]);
    }
}

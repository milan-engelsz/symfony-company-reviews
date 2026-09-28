<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReviewController extends AbstractController
{
    #[Route('/', name: 'review.index', methods: ['GET'])]
    public function index(
        ReviewRepository $reviewRepository,
        PaginatorInterface $paginator,
        Request $request,
        #[Autowire(param: 'app.reviews.per_page')]
        int $perPage,
    ): Response {
        $queryBuilder = $reviewRepository->createOrderedByCreatedAtDescQueryBuilder();

        $paginatedReviews = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            $perPage
        );

        return $this->render('review/index.html.twig', [
            'paginatedReviews' => $paginatedReviews,
        ]);
    }

    #[Route('/review/new', name: 'review.new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $review = new Review();
        $form = $this->createForm(ReviewType::class, $review);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($review);
            $entityManager->flush();

            $this->addFlash('success', $translator->trans('flash.review_submitted'));

            return $this->redirectToRoute('review.index');
        }

        return $this->render('review/new.html.twig', [
            'review_form' => $form->createView(),
        ]);
    }

    #[Route('/review/{review}', name: 'review.show', methods: ['GET'])]
    public function show(Review $review): Response
    {
        return $this->render('review/show.html.twig', [
            'review' => $review,
        ]);
    }
}

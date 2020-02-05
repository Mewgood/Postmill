<?php

namespace App\Controller;

use App\Entity\ForumCategory;
use App\Form\ForumCategoryType;
use App\Form\Model\ForumCategoryData;
use App\Repository\ForumCategoryRepository;
use App\Repository\ForumRepository;
use App\SubmissionFinder\Criteria;
use App\SubmissionFinder\SubmissionFinder;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ForumCategoryController extends AbstractController {
    /**
     * @var ForumRepository
     */
    private $forums;

    /**
     * @var SubmissionFinder
     */
    private $submissionFinder;

    public function __construct(
        ForumRepository $forums,
        SubmissionFinder $submissionFinder
    ) {
        $this->forums = $forums;
        $this->submissionFinder = $submissionFinder;
    }

    public function category(ForumCategory $category, string $sortBy): Response {
        $forums = $this->forums->findForumsInCategory($category);

        $criteria = (new Criteria($sortBy))
            ->showForums(...$category->getForums())
            ->excludeHiddenForums();

        $submissions = $this->submissionFinder->find($criteria);

        return $this->render('forum_category/category.html.twig', [
            'category' => $category,
            'forums' => $forums,
            'sort_by' => $this->submissionFinder->getSortMode($sortBy),
            'submissions' => $submissions,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     */
    public function create(Request $request, EntityManagerInterface $em): Response {
        $data = new ForumCategoryData();

        $form = $this->createForm(ForumCategoryType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category = $data->toForumCategory();

            $em->persist($category);
            $em->flush();

            return $this->redirectToRoute('manage_forum_categories');
        }

        return $this->render('forum_category/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     */
    public function edit(ForumCategory $category, Request $request, EntityManagerInterface $em): Response {
        $data = new ForumCategoryData($category);

        $form = $this->createForm(ForumCategoryType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->updateForumCategory($category);

            $em->flush();

            return $this->redirectToRoute('manage_forum_categories');
        }

        return $this->render('forum_category/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     */
    public function manage(ForumCategoryRepository $repository, int $page): Response {
        $categories = $repository->findPaginated($page);

        return $this->render('forum_category/manage.html.twig', [
            'categories' => $categories,
        ]);
    }
}

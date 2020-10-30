<?php

namespace App\Controller;

use App\DataObject\BlocklistData;
use App\Form\BlocklistType;
use App\Repository\BlocklistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @IsGranted("ROLE_USER")
 * @IsGranted("ROLE_ADMIN", statusCode=403)
 */
class BlocklistController extends AbstractController {
    public function list(BlocklistRepository $sources): Response {
        return $this->render('blocklist/list.html.twig', [
            'sources' => $sources->findAll(),
        ]);
    }

    public function renderForm(): Response {
        $form = $this->createForm(BlocklistType::class, null, [
            'action' => $this->generateUrl('blocklist_add'),
        ]);

        return $this->render('blocklist/_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    public function add(Request $request, EntityManagerInterface $em): Response {
        $data = new BlocklistData();
        $form = $this->createForm(BlocklistType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entry = $data->toBlocklist();

            $em->persist($entry);
            $em->flush();

            return $this->redirectToRoute('blocklists');
        }

        return $this->render('blocklist/form_errors.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

<?php

namespace App\Controller;

use App\Entity\Site;
use App\Form\Model\SiteData;
use App\Form\SiteSettingsType;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SiteController extends AbstractController {
    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     *
     * @Entity("site", expr="repository.findCurrentSite()")
     */
    public function settings(Site $site, Request $request, ObjectManager $em): Response {
        $data = SiteData::createFromSite($site);

        $form = $this->createForm(SiteSettingsType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->updateSite($site);

            $em->flush();

            $this->addFlash('success', 'flash.site_settings_saved');

            return $this->redirectToRoute('site_settings');
        }

        return $this->render('site/settings.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

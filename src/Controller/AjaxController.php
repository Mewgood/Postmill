<?php

namespace App\Controller;

use App\Entity\User;
use App\Markdown\MarkdownConverter;
use Embed\Embed;
use Embed\Exceptions\InvalidUrlException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Helpers for Ajax-related stuff.
 */
final class AjaxController extends AbstractController {
    /**
     * JSON action for retrieving link titles.
     *
     * - 200 - Found a title
     * - 400 - Bad URL
     * - 404 - No title found
     *
     * @IsGranted("ROLE_USER")
     */
    public function fetchTitle(Request $request): Response {
        $url = $request->request->get('url');
        try {
            $title = Embed::create($url)->getTitle();

            if ((string) $title === '') {
                return $this->json(null, 404);
            }

            return $this->json(['title' => $title]);
        } catch (InvalidUrlException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @IsGranted("ROLE_USER")
     */
    public function markdownPreview(Request $request, MarkdownConverter $converter): Response {
        if ($request->getContentType() !== 'html') {
            throw new BadRequestHttpException('Expected HTML request body');
        }

        return new Response($converter->convertToHtml($request->getContent()));
    }

    public function userPopper(User $user): Response {
        return $this->render('ajax/user_popper.html.twig', [
            'user' => $user,
        ]);
    }
}

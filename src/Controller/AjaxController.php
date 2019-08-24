<?php

namespace App\Controller;

use App\Markdown\MarkdownConverter;
use Embed\Embed;
use Embed\Exceptions\InvalidUrlException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Helpers for Ajax-related stuff.
 *
 * @IsGranted("ROLE_USER")
 */
class AjaxController {
    /**
     * JSON action for retrieving link titles.
     *
     * - 200 - Found a title
     * - 400 - Bad URL
     * - 404 - No title found
     */
    public function fetchTitle(Request $request): Response {
        $url = $request->request->get('url');
        try {
            $title = Embed::create($url)->getTitle();

            if ((string) $title === '') {
                return new JsonResponse(null, 404);
            }

            return new JsonResponse(['title' => $title]);
        } catch (InvalidUrlException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function markdownPreview(Request $request, MarkdownConverter $converter): Response {
        if ($request->getContentType() !== 'html') {
            throw new BadRequestHttpException('Expected HTML request body');
        }

        return new Response($converter->convertToHtml($request->getContent()));
    }
}

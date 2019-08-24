<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Landing page for banned users.
 */
final class BanLandingPageController {
    private $twig;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
    }

    public function __invoke(): Response {
        return new Response($this->twig->render('ban/banned.html.twig'), 403);
    }
}

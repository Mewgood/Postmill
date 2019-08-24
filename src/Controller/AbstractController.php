<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AbstractController extends BaseAbstractController {
    /**
     * @param string|mixed $token
     *
     * @throws BadRequestHttpException if the token isn't valid
     */
    protected function validateCsrf(string $id, $token): void {
        if (!\is_string($token) || !$this->isCsrfTokenValid($id, $token)) {
            throw new BadRequestHttpException('Invalid CSRF token');
        }
    }

    protected function createNamedForm(
        $name,
        $type = FormType::class,
        $data = null,
        array $options = []
    ): FormInterface {
        return $this->container
            ->get('form.factory')
            ->createNamed($name, $type, $data, $options);
    }
}

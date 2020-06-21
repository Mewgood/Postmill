<?php

namespace App\Security\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class IpRateLimitedException extends AuthenticationException {
    public function __construct() {
        parent::__construct($this->getMessageKey());
    }

    public function getMessageKey(): string {
        return 'error.rate_limited';
    }
}

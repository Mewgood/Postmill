<?php

namespace App\Security\Exception;

use Symfony\Component\Security\Core\Exception\AccountStatusException;

class AccountBannedException extends AccountStatusException {
    public function __construct() {
        parent::__construct();
    }

    public function getMessageKey(): string {
        return 'Your account has been banned.';
    }
}

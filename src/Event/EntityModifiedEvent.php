<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EntityModifiedEvent extends Event {
    private $before;
    private $after;

    public function __construct($before, $after) {
        if (!isset($before, $after)) {
            throw new \InvalidArgumentException('$before and/or $after cannot be null');
        }

        $this->before = $before;
        $this->after = $after;
    }

    /**
     * @return object
     */
    public function getBefore() {
        return $this->before;
    }

    /**
     * @return object
     */
    public function getAfter() {
        return $this->after;
    }
}

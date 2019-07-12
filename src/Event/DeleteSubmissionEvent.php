<?php

namespace App\Event;

use App\Entity\Submission;
use Symfony\Contracts\EventDispatcher\Event;

final class DeleteSubmissionEvent extends Event {
    /**
     * @var Submission[]
     */
    private $submissions;

    public function __construct(Submission ...$submissions) {
        $this->submissions = $submissions;
    }

    public function getSubmissions(): array {
        return $this->submissions;
    }
}

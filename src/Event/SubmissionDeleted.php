<?php

namespace App\Event;

use App\Entity\Submission;
use Symfony\Contracts\EventDispatcher\Event;

final class SubmissionDeleted extends Event {
    /**
     * @var Submission[]
     */
    private $submissions;

    public function __construct(Submission ...$submissions) {
        $this->submissions = $submissions;
    }

    /**
     * @return Submission[]
     */
    public function getSubmissions(): array {
        return $this->submissions;
    }
}

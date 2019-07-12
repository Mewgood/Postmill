<?php

namespace App\Event;

use App\Entity\Submission;

/**
 * @method Submission getBefore()
 * @method Submission getAfter()
 */
class EditSubmissionEvent extends EntityModifiedEvent {
}

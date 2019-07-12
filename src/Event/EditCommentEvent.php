<?php

namespace App\Event;

use App\Entity\Comment;

/**
 * @method Comment getBefore()
 * @method Comment getAfter()
 */
class EditCommentEvent extends EntityModifiedEvent {
}

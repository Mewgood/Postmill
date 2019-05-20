<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class SubmissionPage {
    /**
     * @Assert\NotBlank(groups={"all"})
     * @Assert\Range(min=1, groups={"all"})
     */
    public $id;

    /**
     * @Assert\NotBlank(groups={"hot"})
     */
    public $ranking;

    /**
     * @Assert\NotBlank(groups={"active"})
     * @Assert\DateTime(format=\DateTimeInterface::RFC3339, groups={"active"})
     */
    public $lastActive;

    /**
     * @Assert\NotBlank(groups={"top", "controversial"})
     * @Assert\Range(min=-2147483648, max=2147483647, groups={"top", "controversial"})
     */
    public $netScore;

    /**
     * @Assert\NotBlank(groups={"most_commented"})
     * @Assert\Range(min=0, max=2147483647, groups={"most_commented"})
     */
    public $commentCount;
}

<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class CustomTextFlair extends Flair {
    /**
     * @ORM\Column(name="label_text", type="text")
     *
     * @var string
     */
    private $text;

    public function __construct(string $text) {
        if (trim($text) === '') {
            throw new \InvalidArgumentException('Text cannot be blank');
        }

        $this->text = $text;

        parent::__construct();
    }

    public function getLabel(): string {
        return $this->text;
    }

    public function getType(): string {
        return 'custom_text';
    }
}

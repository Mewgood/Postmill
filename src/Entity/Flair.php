<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity()
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="flair_type", type="string", length=40)
 * @ORM\DiscriminatorMap({
 *     "custom_text": "CustomTextFlair",
 * })
 */
abstract class Flair {
    /**
     * @ORM\Column(type="uuid")
     * @ORM\Id()
     *
     * @var UuidInterface
     */
    private $id;

    public function __construct() {
        $this->id = Uuid::uuid4();
    }

    public function getId(): UuidInterface {
        return $this->id;
    }

    abstract public function getLabel(): string;

    abstract public function getType(): string;
}

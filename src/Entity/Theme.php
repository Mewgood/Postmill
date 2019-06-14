<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

// This seems rather pointless right now, but in the future there will be themes
// outside of those defined in themes.json.

/**
 * @ORM\Entity()
 */
class Theme {
    /**
     * @ORM\Column(type="uuid")
     * @ORM\Id()
     *
     * @var UuidInterface
     */
    private $id;

    /**
     * @ORM\Column(type="text", unique=true)
     *
     * @var string
     */
    private $configKey;

    public function __construct(string $configKey) {
        $this->id = Uuid::uuid4();
        $this->configKey = $configKey;
    }

    public function getId(): UuidInterface {
        return $this->id;
    }

    public function getConfigKey(): string {
        return $this->configKey;
    }
}

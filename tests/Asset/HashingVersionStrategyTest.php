<?php

namespace App\Tests\Asset;

use App\Asset\HashingVersionStrategy;
use PHPUnit\Framework\TestCase;

class HashingVersionStrategyTest extends TestCase {
    /**
     * @var HashingVersionStrategy
     */
    private $strategy;

    protected function setUp(): void {
        $this->strategy = new HashingVersionStrategy(__DIR__.'/../Resources');
    }

    public function testGetVersion(): void {
        $this->assertEquals(
            '1052d25a298fce69',
            $this->strategy->getVersion('garbage.bin')
        );
    }

    public function testApplyVersion(): void {
        $this->assertEquals(
            'garbage.bin?1052d25a298fce69',
            $this->strategy->applyVersion('garbage.bin')
        );
    }
}

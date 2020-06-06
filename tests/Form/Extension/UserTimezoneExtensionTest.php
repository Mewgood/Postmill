<?php

namespace App\Tests\Form\Extension;

use App\Entity\User;
use App\Form\Extension\UserTimezoneExtension;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Security\Core\Security;

/**
 * @covers \App\Form\Extension\UserTimezoneExtension
 */
class UserTimezoneExtensionTest extends TypeTestCase {
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Security
     */
    private $security;

    protected function setUp(): void {
        $this->security = $this->createMock(Security::class);

        parent::setUp();
    }

    protected function getTypeExtensions(): array {
        return [
            new UserTimezoneExtension($this->security),
        ];
    }

    /**
     * @dataProvider provideExtendedFormTypes
     */
    public function testSetsOptionForAuthenticatedUser(string $formType): void {
        $this->setLoggedIn();

        $form = $this->factory->create($formType);

        $this->assertSame(
            'Europe/Oslo',
            $form->getConfig()->getOption('view_timezone')
        );
    }

    /**
     * @dataProvider provideExtendedFormTypes
     */
    public function testDoesNotOverridePreSetOption(string $type): void {
        $this->setLoggedIn();

        $form = $this->factory->create($type, null, [
            'view_timezone' => 'Europe/Moscow',
        ]);

        $this->assertSame(
            'Europe/Moscow',
            $form->getConfig()->getOption('view_timezone')
        );
    }

    /**
     * @dataProvider provideExtendedFormTypes
     */
    public function testDoesNotSetOptionWhenNotAuthenticated(string $type): void {
        $this->security
            ->expects($this->atLeastOnce())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(false);
        $this->security
            ->expects($this->never())
            ->method('getUser');

        $form = $this->factory->create($type);

        $this->assertNull($form->getConfig()->getOption('view_timezone'));
    }

    public function provideExtendedFormTypes(): \Generator {
        yield [DateTimeType::class];
        yield [DateType::class];
    }

    private function setLoggedIn(): void {
        $this->security
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true);

        $user = new User('u', 'p');
        $user->setTimezone(new \DateTimeZone('Europe/Oslo'));

        $this->security
            ->method('getUser')
            ->willReturn($user);
    }
}

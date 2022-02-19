<?php

namespace App\Tests\Form;

use App\DataObject\UserData;
use App\Entity\Site;
use App\Form\Type\HoneypotType;
use App\Form\UserType;
use App\Repository\SiteRepository;
use App\Security\Authentication;
use Gregwar\CaptchaBundle\Generator\CaptchaGenerator;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Form\UserType
 */
class UserTypeTest extends TypeTestCase {
    /**
     * @var SiteRepository&\PHPUnit\Framework\MockObject\MockObject
     */
    private $siteRepository;

    /**
     * @var Authentication&\PHPUnit\Framework\MockObject\MockObject
     */
    private $authentication;

    /**
     * @var AuthorizationCheckerInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $authorizationChecker;

    protected function setUp(): void {
        $this->siteRepository = $this->createMock(SiteRepository::class);
        $this->authentication = $this->createMock(Authentication::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        parent::setUp();
    }

    /**
     * @dataProvider provideCaptchaEnabled
     */
    public function testCaptchaToggle(bool $captchaEnabled): void {
        $site = new Site();
        $site->setRegistrationCaptchaEnabled($captchaEnabled);

        $this->siteRepository
            ->expects($this->atLeastOnce())
            ->method('findCurrentSite')
            ->willReturn($site);

        $form = $this->factory->create(UserType::class, null);

        $this->assertSame($captchaEnabled, $form->has('verification'));
    }

    public function testCaptchaNotToggledWhenEditing(): void {
        $site = new Site();
        $site->setRegistrationCaptchaEnabled(true);

        $this->siteRepository
            ->method('findCurrentSite')
            ->willReturn($site);

        $data = new UserData();
        $data->setId(420);

        $form = $this->factory->create(UserType::class, $data);

        $this->assertFalse($form->has('verification'));
    }

    protected function getExtensions(): array {
        $request = Request::create('/', 'POST', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return [
            new PreloadedExtension([
                new HoneypotType($requestStack),
                new UserType(
                    $this->createMock(UserPasswordHasherInterface::class),
                    $this->siteRepository,
                    $this->authentication,
                    $this->authorizationChecker
                ),
                new CaptchaType(
                    $requestStack,
                    $this->createMock(CaptchaGenerator::class),
                    $this->createMock(TranslatorInterface::class),
                    [
                        'as_url' => null,
                        'bypass_code' => 'bypass',
                        'humanity' => 1,
                        'reload' => null,
                        'invalid_message' => 'whatever',
                        'session_key' => 'whatever',
                    ]
                ),
            ], []),
        ];
    }

    public function provideCaptchaEnabled(): \Generator {
        yield [false];
        yield [true];
    }
}

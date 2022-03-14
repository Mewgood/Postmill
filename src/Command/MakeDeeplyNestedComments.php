<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Comment;
use App\Entity\Submission;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

final class MakeDeeplyNestedComments extends Command {
    protected static $defaultName = 'postmill:make-deeply-nested-comments';
    protected static $defaultDescription = 'Create deeply nested comments for test purposes';

    private EntityManagerInterface $entityManager;
    private UserRepository $users;
    private bool $production;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $users,
        KernelInterface $kernel
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->users = $users;
        $this->production = $kernel->getEnvironment() === 'prod';
    }

    protected function configure() {
        $this
            ->addArgument('parent', InputArgument::REQUIRED,
                'The ID of the submission (default) or comment '.
                '(<info>--comment</info>) to post comments to.',
            )
            ->addOption('comment', null, InputOption::VALUE_NONE, 'The parent is a comment')
            ->addOption('break-prod', null, InputOption::VALUE_NONE)
            ->addOption('number', null, InputOption::VALUE_REQUIRED, 'The number of comments to post', 10)
            ->addOption('visibility', null, InputOption::VALUE_REQUIRED,
                'The visibility to set for the comments. Comments may be '.
                '<info>visible</info>, <info>soft_deleted</info>, or '.
                '<info>trashed</info>.',
                'visible',
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);

        if ($this->production && !$input->getOption('break-prod')) {
            $io->caution(
                'You are running in production. If you still wish to run this '.
                'command, add the --break-prod option at your own risk.',
            );
            $io->info('Quitting.');

            return 1;
        }

        $parentIsComment = (bool) $input->getOption('comment');
        $number = max(1, (int) $input->getOption('number'));
        $parentId = $input->getArgument('parent');
        $visibilityMethod = $this->getVisibilityMethod(
            $input->getOption('visibility'),
        );

        /** @var Comment|Submission $parent */
        $parent = $this->entityManager->find(
            $parentIsComment ? Comment::class : Submission::class,
            $parentId,
        );

        $user = $this->users->findOneByUsername('!test') ?? (function () {
            $user = new User('!test', base64_encode(random_bytes(48)));
            $this->entityManager->persist($user);

            return $user;
        })();

        $io->info('Creating lots of nested comments');
        $io->progressStart($number);

        for ($i = 1; $i <= $number; $i++) {
            $parent = new Comment('test comment '.$i, $user, $parent, null);
            $parent->{$visibilityMethod}();

            $this->entityManager->persist($parent);
            $io->progressAdvance();

            if ($i % 25 === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->info('Created '.$number.' comments.');

        return 0;
    }

    private function getVisibilityMethod(string $visibility): string {
        switch ($visibility) {
        case 'visible':
            return 'restore';
        case 'soft_deleted':
            return 'softDelete';
        case 'trashed':
            return 'trash';
        default:
            throw new \Exception(
                'Unknown visibility, must be one of "visible", '.
                '"soft_deleted", or "trashed"',
            );
        }
    }
}

<?php

namespace App\Command;

use App\Entity\Comment;
use App\Entity\CommentVote;
use App\Entity\Message;
use App\Entity\Submission;
use App\Entity\SubmissionVote;
use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command for removing IP addresses associated with some entities.
 *
 * This is intended to be run in a cron job or similar to ensure visitor
 * privacy.
 */
class PruneIpAddressesCommand extends Command {
    protected static $defaultName = 'postmill:prune-ips';

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    public function __construct(EntityManagerInterface $manager) {
        parent::__construct();

        $this->manager = $manager;
    }

    protected function configure(): void {
        $this
            ->setAliases(['app:prune-ips'])
            ->setDescription('Prunes IP addresses associated with some entities')
            ->addOption('max-age', 'm', InputOption::VALUE_REQUIRED,
                'The maximum age (strtotime format) of an entity in seconds before its IP address is cleared.'
            )
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE,
                'Don\'t apply the changes to the database.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);

        if (!$io->confirm('Are you sure you wish to prune IP addresses?', !$input->isInteractive())) {
            $io->text('Aborting...');

            return 1;
        }

        if ($input->getOption('max-age')) {
            if (strtotime($input->getOption('max-age')) === false) {
                $io->error('Invalid date format');

                return 1;
            }

            $nowTime = new \DateTimeImmutable('@'.time());
            $maxTime = $nowTime->modify($input->getOption('max-age'));

            if ($maxTime > $nowTime) {
                $io->error('max-age option cannot be a future time');

                if ($io->isDebug()) {
                    $io->comment('now: '.$nowTime->format('c'));
                    $io->comment('max: '.$maxTime->format('c'));
                }

                return 1;
            }
        } else {
            $maxTime = null;
        }

        $this->manager->beginTransaction();

        $count = 0;
        $count += $this->clearIpsForEntity(Comment::class, $maxTime);
        $count += $this->clearIpsForEntity(CommentVote::class, $maxTime);
        $count += $this->clearIpsForEntity(Submission::class, $maxTime);
        $count += $this->clearIpsForEntity(SubmissionVote::class, $maxTime);
        $count += $this->clearIpsForEntity(Message::class, $maxTime);
        $count += $this->clearIpsForEntity(User::class, $maxTime, 'registrationIp', 'created');

        if ($input->getOption('dry-run')) {
            $this->manager->rollback();
        } else {
            $this->manager->commit();
            $this->manager->flush();
        }

        if ($count > 0) {
            $io->success(sprintf('Pruned IPs for %s entit%s.',
                number_format($count),
                $count !== 1 ? 'ies' : 'y'
            ));
        } else {
            $io->note('No entities with IP addresses.');
        }

        return 0;
    }

    private function clearIpsForEntity(
        string $entity,
        ?\DateTimeImmutable $maxTime,
        string $ipField = 'ip',
        string $timestampField = 'timestamp'
    ): int {
        $qb = $this->manager->createQueryBuilder()
            ->update($entity, 'e')
            ->set('e.'.$ipField, '?1')
            ->setParameter(1, null)
            ->where('e.'.$ipField.' IS NOT NULL');

        if ($maxTime) {
            $qb->andWhere('e.'.$timestampField.' <= ?2');
            $qb->setParameter(2, $maxTime, Types::DATETIMETZ_IMMUTABLE);
        }

        return $qb->getQuery()->execute();
    }
}

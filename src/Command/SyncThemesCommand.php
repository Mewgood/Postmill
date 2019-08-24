<?php

namespace App\Command;

use App\Entity\Theme;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class SyncThemesCommand extends Command {
    /**
     * @var ThemeRepository
     */
    private $repository;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * @var array
     */
    private $themesConfig;

    public function __construct(
        ThemeRepository $repository,
        EntityManagerInterface $manager,
        array $themesConfig
    ) {
        parent::__construct();

        $this->repository = $repository;
        $this->manager = $manager;
        $this->themesConfig = $themesConfig;

        unset($this->themesConfig['_default']);
    }

    protected function configure(): void {
        $this
            ->setName('app:theme:sync')
            ->setDescription('Sync theme configuration with database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);

        $keys = array_keys($this->themesConfig);

        $themes = $this->repository->createQueryBuilder('t', 't.configKey')
            ->where('t.configKey IN (:keys)')
            ->setParameter('keys', $keys)
            ->getQuery()
            ->execute();

        foreach (array_diff_key($this->themesConfig, $themes) as $key => $theme) {
            $changes = true;
            $io->text("Creating theme '$key'...");

            $this->manager->persist(new Theme($key));
        }

        $themes = $this->repository->createQueryBuilder('t', 't.configKey')
            ->where('t.configKey NOT IN (:keys)')
            ->setParameter('keys', $keys)
            ->getQuery()
            ->execute();

        foreach (array_diff_key($themes, $this->themesConfig) as $key => $theme) {
            $changes = true;
            $io->text("Removing theme '$key'...");

            $this->manager->remove($theme);
        }

        if (!($changes ?? false)) {
            $io->note('Nothing to be done.');

            return 1;
        }

        if (!$io->confirm('Is this OK?')) {
            $io->text('Aborting.');

            return 1;
        }

        $this->manager->flush();

        $io->success('Themes are synced!');

        return 0;
    }
}

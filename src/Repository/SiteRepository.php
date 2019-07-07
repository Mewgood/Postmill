<?php

namespace App\Repository;

use App\Entity\Site;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class SiteRepository extends ServiceEntityRepository {
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger = null) {
        parent::__construct($registry, Site::class);

        $this->logger = $logger ?? new NullLogger();
    }

    public function findCurrentSite(): ?Site {
        // we currently don't support multi-site
        $site = $this->find('00000000-0000-0000-0000-000000000000');

        if (!$site instanceof Site) {
            throw new \RuntimeException(
                'There should exist a site with a nil UUID in the database. Did you mess around with the "sites" table?'
            );
        }

        return $site;
    }

    /**
     * Returns a site name, regardless of database availability.
     */
    public function getCurrentSiteName(): string {
        try {
            $site = $this->findCurrentSite();

            assert($site instanceof Site);

            return $site->getSiteName();
        } catch (DBALException $e) {
            $this->logger->error((string) $e);

            return $_SERVER['SITE_NAME'] ?? '[name unavailable]';
        }
    }
}

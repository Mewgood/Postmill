<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;
use Ramsey\Uuid\Uuid;

final class Version20190706143243 extends AbstractMigration {
    public function getDescription(): string {
        return 'Store some site settings in database';
    }

    public function up(Schema $schema): void {
        $siteName = $_SERVER['SITE_NAME'] ?? 'Postmill';

        $this->addSql('CREATE TABLE sites (id UUID NOT NULL, site_name TEXT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('COMMENT ON COLUMN sites.id IS \'(DC2Type:uuid)\'');

        $this->addSql(
            'INSERT INTO sites (id, site_name) VALUES (?, ?)',
            [Uuid::NIL, $siteName],
            [Type::getType('uuid'), Type::STRING]
        );
    }

    public function down(Schema $schema): void {
        $this->addSql('DROP TABLE sites');
    }
}

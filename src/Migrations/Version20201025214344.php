<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201025214344 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add support for blocklists';
    }

    public function up(Schema $schema): void {
        $this->addSql('CREATE TABLE blocklists (id UUID NOT NULL, name TEXT NOT NULL, url TEXT NOT NULL, regex TEXT NOT NULL, ttl INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX blocklists_name_idx ON blocklists (name)');
        $this->addSql('CREATE UNIQUE INDEX blocklists_url_idx ON blocklists (url)');
        $this->addSql('COMMENT ON COLUMN blocklists.id IS \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema): void {
        $this->addSql('DROP TABLE blocklists');
    }
}

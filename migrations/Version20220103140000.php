<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220103140000 extends AbstractMigration
{
    public function getDescription(): string {
        return 'Add site settings for changing visibility for the log of wiki changes';
    }

    public function up(Schema $schema): void {
        $this->addSql('ALTER TABLE sites ADD wiki_log_public BOOLEAN DEFAULT TRUE NOT NULL');
    }

    public function down(Schema $schema): void {
        $this->addSql('ALTER TABLE sites DROP wiki_log_public');
    }
}

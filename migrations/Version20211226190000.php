<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211226190000 extends AbstractMigration
{
    public function getDescription(): string {
        return 'Add site settings for enabling username changes';
    }

    public function up(Schema $schema): void {
        $this->addSql('ALTER TABLE sites ADD username_change_enabled BOOLEAN DEFAULT TRUE NOT NULL');
    }

    public function down(Schema $schema): void {
        $this->addSql('ALTER TABLE sites DROP username_change_enabled');
    }
}

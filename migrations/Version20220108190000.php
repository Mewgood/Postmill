<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220108190000 extends AbstractMigration
{
    public function getDescription(): string {
        return 'Add site settings for preventing unwhitelisted user messages';
    }

    public function up(Schema $schema): void {
        $this->addSql('ALTER TABLE sites ADD unwhitelisted_user_messages_enabled BOOLEAN DEFAULT TRUE NOT NULL');
    }

    public function down(Schema $schema): void {
        $this->addSql('ALTER TABLE sites DROP unwhitelisted_user_messages_enabled');
    }
}

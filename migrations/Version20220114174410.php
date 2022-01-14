<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220114174410 extends AbstractMigration
{
    public function getDescription(): string {
        return 'Add site & forum settings for moderation log visibility';
    }

    public function up(Schema $schema): void {
        $this->addSql('ALTER TABLE forums ADD moderation_log_public BOOLEAN DEFAULT TRUE NOT NULL');
        $this->addSql('ALTER TABLE sites ADD moderators_can_set_forum_log_visibility TEXT DEFAULT FALSE NOT NULL');
    }

    public function down(Schema $schema): void {
        $this->addSql('ALTER TABLE forums DROP moderation_log_public');
        $this->addSql('ALTER TABLE sites DROP moderators_can_set_forum_log_visibility');
    }
}

<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201101143322 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add support for delayed publishing';
    }

    public function up(Schema $schema): void {
        $this->addSql('ALTER TABLE sites ADD submission_publication_delay DOUBLE PRECISION DEFAULT 0');
        $this->addSql('ALTER TABLE sites ALTER submission_publication_delay SET NOT NULL');
        $this->addSql('ALTER TABLE submissions ADD published_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('UPDATE submissions SET published_at = timestamp');
        $this->addSql('ALTER TABLE submissions ALTER published_at SET NOT NULL');
        $this->addSql('COMMENT ON COLUMN submissions.published_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('CREATE INDEX submissions_published_at_idx ON submissions (published_at)');
        $this->addSql('ALTER TABLE notifications ADD notify_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void {
        $this->addSql('ALTER TABLE sites DROP submission_publication_delay');
        $this->addSql('ALTER TABLE submissions DROP published_at');
        $this->addSql('ALTER TABLE notifications DROP notify_at');
    }
}

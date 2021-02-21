<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210220152236 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add support for submission flairs';
    }

    public function up(Schema $schema): void {
        $this->addSql('CREATE TABLE flairs (id UUID NOT NULL, flair_type VARCHAR(40) NOT NULL, label_text TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql("COMMENT ON COLUMN flairs.id IS '(DC2Type:uuid)'");
        $this->addSql('CREATE TABLE submissions_flairs (submission_id BIGINT NOT NULL, flair_id UUID NOT NULL, PRIMARY KEY(submission_id, flair_id))');
        $this->addSql('CREATE INDEX IDX_583F87DFE1FD4933 ON submissions_flairs (submission_id)');
        $this->addSql('CREATE INDEX IDX_583F87DF300CED32 ON submissions_flairs (flair_id)');
        $this->addSql("COMMENT ON COLUMN submissions_flairs.flair_id IS '(DC2Type:uuid)'");
        $this->addSql('ALTER TABLE submissions_flairs ADD CONSTRAINT FK_583F87DFE1FD4933 FOREIGN KEY (submission_id) REFERENCES submissions (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE submissions_flairs ADD CONSTRAINT FK_583F87DF300CED32 FOREIGN KEY (flair_id) REFERENCES flairs (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void {
        $this->addSql('ALTER TABLE submissions_flairs DROP CONSTRAINT FK_583F87DF300CED32');
        $this->addSql('DROP TABLE submissions_flairs');
        $this->addSql('DROP TABLE flairs');
    }
}

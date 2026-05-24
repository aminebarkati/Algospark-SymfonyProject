<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260524150744 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE problems ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE submissions ADD submitted_at DATETIME NOT NULL, ADD judged_at DATETIME DEFAULT NULL, ADD passed_tests INT DEFAULT 0 NOT NULL, ADD total_tests INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE problems DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE submissions DROP submitted_at, DROP judged_at, DROP passed_tests, DROP total_tests');
    }
}

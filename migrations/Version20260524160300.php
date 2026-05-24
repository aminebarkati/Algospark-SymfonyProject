<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the missing roles column to users for Symfony security.
 */
final class Version20260524160300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing roles column to users';
    }

    public function up(Schema $schema): void
    {
        $hasRolesColumn = (bool) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'roles'"
        );

        if (!$hasRolesColumn) {
            $this->addSql('ALTER TABLE users ADD roles JSON NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $hasRolesColumn = (bool) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'roles'"
        );

        if ($hasRolesColumn) {
            $this->addSql('ALTER TABLE users DROP roles');
        }
    }
}
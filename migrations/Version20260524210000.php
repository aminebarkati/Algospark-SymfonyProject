<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Baseline migration generated from the current application schema.
 */
final class Version20260524210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Baseline schema for the current application state.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE languages (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, compiler_command VARCHAR(255) DEFAULT NULL, file_extension VARCHAR(10) DEFAULT NULL, is_enabled TINYINT NOT NULL, UNIQUE INDEX UNIQ_A0D153795E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE test_cases (id INT AUTO_INCREMENT NOT NULL, input LONGTEXT NOT NULL, expected_output LONGTEXT NOT NULL, is_sample TINYINT NOT NULL, problem_id INT DEFAULT NULL, INDEX IDX_17C5A580A0DCED86 (problem_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE verdict_status (id INT AUTO_INCREMENT NOT NULL, verdict VARCHAR(50) NOT NULL, display_name VARCHAR(100) DEFAULT NULL, color_code VARCHAR(10) DEFAULT NULL, UNIQUE INDEX UNIQ_87EF42F823050A93 (verdict), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_favorites (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, favorite_user_id INT DEFAULT NULL, INDEX IDX_E489ED11A76ED395 (user_id), INDEX IDX_E489ED11FA3A7DFB (favorite_user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE problems (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(200) NOT NULL, description LONGTEXT NOT NULL, difficulty INT NOT NULL, category VARCHAR(300) NOT NULL, time_limit_ms INT NOT NULL, memory_limit_mb INT NOT NULL, success_count INT NOT NULL, total_attempts INT NOT NULL, acceptance_rate NUMERIC(5, 2) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8E6662452B36786B (title), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE submissions (id INT AUTO_INCREMENT NOT NULL, execution_time_ms INT DEFAULT NULL, memory_used_mb INT DEFAULT NULL, error_message LONGTEXT DEFAULT NULL, submitted_at DATETIME NOT NULL, judged_at DATETIME DEFAULT NULL, passed_tests INT DEFAULT 0 NOT NULL, total_tests INT DEFAULT NULL, user_id INT DEFAULT NULL, problem_id INT DEFAULT NULL, language_id INT DEFAULT NULL, verdict_id INT DEFAULT NULL, INDEX IDX_3F6169F7A76ED395 (user_id), INDEX IDX_3F6169F7A0DCED86 (problem_id), INDEX IDX_3F6169F782F1BAF4 (language_id), INDEX IDX_3F6169F71391DFBF (verdict_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(30) NOT NULL, email VARCHAR(150) NOT NULL, password VARCHAR(300) NOT NULL, roles JSON NOT NULL, bio LONGTEXT DEFAULT NULL, avatar_url VARCHAR(255) DEFAULT NULL, rating INT NOT NULL, is_admin TINYINT NOT NULL, updated_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username), UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE test_cases ADD CONSTRAINT FK_17C5A580A0DCED86 FOREIGN KEY (problem_id) REFERENCES problems (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_favorites ADD CONSTRAINT FK_E489ED11A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_favorites ADD CONSTRAINT FK_E489ED11FA3A7DFB FOREIGN KEY (favorite_user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE submissions ADD CONSTRAINT FK_3F6169F7A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE submissions ADD CONSTRAINT FK_3F6169F7A0DCED86 FOREIGN KEY (problem_id) REFERENCES problems (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE submissions ADD CONSTRAINT FK_3F6169F782F1BAF4 FOREIGN KEY (language_id) REFERENCES languages (id)');
        $this->addSql('ALTER TABLE submissions ADD CONSTRAINT FK_3F6169F71391DFBF FOREIGN KEY (verdict_id) REFERENCES verdict_status (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE submissions');
        $this->addSql('DROP TABLE user_favorites');
        $this->addSql('DROP TABLE test_cases');
        $this->addSql('DROP TABLE verdict_status');
        $this->addSql('DROP TABLE problems');
        $this->addSql('DROP TABLE languages');
        $this->addSql('DROP TABLE users');
    }
}
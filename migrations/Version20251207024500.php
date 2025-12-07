<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add missing columns for game session functionality
 */
final class Version20251207024500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add endedAt to game_session, isHost to game_participant, and isCorrect to player_answer';
    }

    public function up(Schema $schema): void
    {
        // Add ended_at to game_session
        $this->addSql('ALTER TABLE game_session ADD COLUMN ended_at DATETIME DEFAULT NULL');
        
        // Add is_host to game_participant
        $this->addSql('ALTER TABLE game_participant ADD COLUMN is_host BOOLEAN NOT NULL DEFAULT 0');
        
        // Add is_correct to player_answer
        $this->addSql('ALTER TABLE player_answer ADD COLUMN is_correct BOOLEAN NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        // Remove the added columns
        $this->addSql('ALTER TABLE game_session DROP COLUMN ended_at');
        $this->addSql('ALTER TABLE game_participant DROP COLUMN is_host');
        $this->addSql('ALTER TABLE player_answer DROP COLUMN is_correct');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204145043 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE answer (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, text VARCHAR(255) NOT NULL, is_correct BOOLEAN NOT NULL, order_index INTEGER NOT NULL, question_id INTEGER DEFAULT NULL, CONSTRAINT FK_DADD4A251E27F6BF FOREIGN KEY (question_id) REFERENCES question (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_DADD4A251E27F6BF ON answer (question_id)');
        $this->addSql('CREATE TABLE game_participant (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nickname VARCHAR(100) NOT NULL, total_score INTEGER NOT NULL, joined_at DATETIME NOT NULL, game_session_id INTEGER DEFAULT NULL, user_id INTEGER DEFAULT NULL, CONSTRAINT FK_9CA29138FE32B32 FOREIGN KEY (game_session_id) REFERENCES game_session (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_9CA2913A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_9CA29138FE32B32 ON game_participant (game_session_id)');
        $this->addSql('CREATE INDEX IDX_9CA2913A76ED395 ON game_participant (user_id)');
        $this->addSql('CREATE TABLE game_session (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, started_at DATETIME DEFAULT NULL, current_question_index INTEGER NOT NULL, quiz_id INTEGER DEFAULT NULL, host_id INTEGER DEFAULT NULL, CONSTRAINT FK_4586AAFB853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4586AAFB1FB8D185 FOREIGN KEY (host_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4586AAFB77153098 ON game_session (code)');
        $this->addSql('CREATE INDEX IDX_4586AAFB853CD175 ON game_session (quiz_id)');
        $this->addSql('CREATE INDEX IDX_4586AAFB1FB8D185 ON game_session (host_id)');
        $this->addSql('CREATE TABLE player_answer (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, points INTEGER NOT NULL, time_to_answer INTEGER NOT NULL, answered_at DATETIME NOT NULL, game_participant_id INTEGER DEFAULT NULL, question_id INTEGER DEFAULT NULL, selected_answer_id INTEGER DEFAULT NULL, CONSTRAINT FK_45DDDCFB47D66C55 FOREIGN KEY (game_participant_id) REFERENCES game_participant (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_45DDDCFB1E27F6BF FOREIGN KEY (question_id) REFERENCES question (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_45DDDCFBF24C5BEC FOREIGN KEY (selected_answer_id) REFERENCES answer (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_45DDDCFB47D66C55 ON player_answer (game_participant_id)');
        $this->addSql('CREATE INDEX IDX_45DDDCFB1E27F6BF ON player_answer (question_id)');
        $this->addSql('CREATE INDEX IDX_45DDDCFBF24C5BEC ON player_answer (selected_answer_id)');
        $this->addSql('CREATE TABLE question (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, text CLOB NOT NULL, points INTEGER NOT NULL, time_limit INTEGER NOT NULL, media_url VARCHAR(255) DEFAULT NULL, quiz_id INTEGER DEFAULT NULL, CONSTRAINT FK_B6F7494E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_B6F7494E853CD175 ON question (quiz_id)');
        $this->addSql('CREATE TABLE quiz (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, difficulty VARCHAR(50) NOT NULL, is_public BOOLEAN NOT NULL, is_active BOOLEAN NOT NULL, created_at DATETIME NOT NULL, created_by_id INTEGER DEFAULT NULL, CONSTRAINT FK_A412FA92B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_A412FA92B03A8386 ON quiz (created_by_id)');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE answer');
        $this->addSql('DROP TABLE game_participant');
        $this->addSql('DROP TABLE game_session');
        $this->addSql('DROP TABLE player_answer');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE quiz');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE messenger_messages');
    }
}

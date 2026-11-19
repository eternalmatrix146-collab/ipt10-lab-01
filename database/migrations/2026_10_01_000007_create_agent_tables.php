<?php

use Phantom\Database\Connection;
use Phantom\Database\Migration;

return new class extends Migration {
    public function up(Connection $db): void
    {
        // agent_conversations.messages stores the raw model message history (JSON, append-only).
        // agent_actions is an audit log of every tool the agent executed.
        $db->unprepared(<<<SQL
            CREATE TABLE agent_conversations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                session_token VARCHAR(64) NULL,
                channel ENUM('shop', 'admin') NOT NULL DEFAULT 'shop',
                messages LONGTEXT NOT NULL,
                transcript LONGTEXT NOT NULL,
                input_tokens INT UNSIGNED NOT NULL DEFAULT 0,
                output_tokens INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_conv_user (user_id),
                INDEX idx_conv_session (session_token),
                CONSTRAINT fk_conv_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE agent_actions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                conversation_id INT UNSIGNED NOT NULL,
                tool_name VARCHAR(60) NOT NULL,
                input JSON NOT NULL,
                output TEXT NOT NULL,
                is_error TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_actions_conv (conversation_id),
                CONSTRAINT fk_actions_conv FOREIGN KEY (conversation_id) REFERENCES agent_conversations (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);
    }

    public function down(Connection $db): void
    {
        $db->unprepared('DROP TABLE IF EXISTS agent_actions; DROP TABLE IF EXISTS agent_conversations;');
    }
};

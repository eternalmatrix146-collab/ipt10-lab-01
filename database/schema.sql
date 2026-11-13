-- Nested Forum Lab Schema
-- MySQL / MariaDB compatible

CREATE DATABASE IF NOT EXISTS nested_forum_lab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nested_forum_lab;

DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS discussions;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE discussions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_discussions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    discussion_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED DEFAULT NULL,
    author_id INT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_comments_id_discussion (id, discussion_id),
    CONSTRAINT fk_comments_discussion FOREIGN KEY (discussion_id) REFERENCES discussions(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_parent FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_parent_discussion FOREIGN KEY (parent_id, discussion_id) REFERENCES comments(id, discussion_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_comments_discussion_parent ON comments(discussion_id, parent_id);
CREATE INDEX idx_comments_parent ON comments(parent_id);


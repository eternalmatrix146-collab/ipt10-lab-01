-- Nested Forum Lab Seed Data
-- Fictional users, one discussion, seven comments, branch reaching displayed depth 4

USE nested_forum_lab;

-- Users (password hashes are for 'password123' - fictional only, never commit real credentials)
INSERT INTO users (username, email, password_hash, avatar) VALUES
('alice', 'alice@example.com', '$2y$10$qmSeY7Uiu7VzaeQkI8ur/Oz6WloUhpkE9yzby1H4mIjWncqsLlEAy', NULL),
('ben', 'ben@example.com', '$2y$10$qmSeY7Uiu7VzaeQkI8ur/Oz6WloUhpkE9yzby1H4mIjWncqsLlEAy', NULL),
('cara', 'cara@example.com', '$2y$10$qmSeY7Uiu7VzaeQkI8ur/Oz6WloUhpkE9yzby1H4mIjWncqsLlEAy', NULL),
('dee', 'dee@example.com', '$2y$10$qmSeY7Uiu7VzaeQkI8ur/Oz6WloUhpkE9yzby1H4mIjWncqsLlEAy', NULL),
('eli', 'eli@example.com', '$2y$10$qmSeY7Uiu7VzaeQkI8ur/Oz6WloUhpkE9yzby1H4mIjWncqsLlEAy', NULL);

-- Discussion 1: How do I learn PHP?
INSERT INTO discussions (user_id, title, body) VALUES
(1, 'How do I learn PHP?', 'Share your learning tips.');

-- Comments (branch: Alice -> Ben -> Cara -> Ben -> Eli reaches displayed depth 4)
INSERT INTO comments (discussion_id, parent_id, author_id, body) VALUES
(1, NULL,  1, 'Start with the PHP manual.'),              -- id 1, Alice, depth 0
(1, 1,     2, 'Try the examples as you read.'),         -- id 2, Ben, depth 1
(1, 2,     3, 'Build a small project too.'),            -- id 3, Cara, depth 2
(1, 1,     4, 'Which manual section first?'),           -- id 4, Dee, depth 1 (sibling of Ben)
(1, NULL,  5, 'Practice SQL as well.'),                 -- id 5, Eli, depth 0 (top-level)
(1, 3,     2, 'Start with a forum.'),                   -- id 6, Ben, depth 3
(1, 6,     5, 'Add replies next.');                     -- id 7, Eli, depth 4

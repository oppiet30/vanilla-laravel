-- Import into a dedicated tutorial database as an administrator.
-- No DROP, credentials, or changes to existing application tables.
CREATE TABLE IF NOT EXISTS demo_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stable IDs keep repeated imports from duplicating the sample rows.
INSERT INTO demo_users (id, name) VALUES (1, 'Ada Lovelace'), (2, 'Grace Hopper')
ON DUPLICATE KEY UPDATE id = id;

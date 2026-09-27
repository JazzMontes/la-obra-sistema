CREATE DATABASE la_obra_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE la_obra_db;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,

    status ENUM('pending', 'active', 'rejected', 'inactive')
        NOT NULL DEFAULT 'pending',

    role ENUM('admin', 'employee')
        DEFAULT NULL,

    must_change_password BOOLEAN
        NOT NULL DEFAULT FALSE,

    created_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,

    created_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);
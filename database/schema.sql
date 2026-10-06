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


-- =====================================================
-- SERVICIO DE PRODUCTOS Y COSTOS
-- La Obra
-- =====================================================


-- =====================================================
-- 1. CATEGORÍAS
-- =====================================================

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
);


-- =====================================================
-- 2. PRODUCTOS
-- =====================================================

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    category_id INT NOT NULL,
    type ENUM('prepared', 'resale') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    is_available BOOLEAN NOT NULL DEFAULT TRUE,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',

    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- =====================================================
-- 3. INSUMOS
-- =====================================================

CREATE TABLE inputs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
);


-- =====================================================
-- 4. RECETAS
-- =====================================================

CREATE TABLE recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    input_id INT NOT NULL,
    quantity DECIMAL(10,3) NOT NULL,

    CONSTRAINT fk_recipes_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_recipes_input
        FOREIGN KEY (input_id)
        REFERENCES inputs(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT uq_recipe_product_input
        UNIQUE (product_id, input_id)
);
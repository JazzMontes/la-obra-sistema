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


-- ============================================================
-- LA OBRA
-- VENTAS, PEDIDOS Y MÉTODOS DE PAGO
-- CORREGIDO PARA TU BASE DE DATOS ACTUAL
-- ============================================================


-- ============================================================
-- 1. PEDIDOS
-- ============================================================

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- IMPORTANTE:
    -- users.id es INT UNSIGNED en tu base original
    user_id INT UNSIGNED NOT NULL,

    order_status ENUM(
        'open',
        'confirmed',
        'cancelled'
    ) NOT NULL DEFAULT 'open',

    payment_status ENUM(
        'pending',
        'paid'
    ) NOT NULL DEFAULT 'pending',

    delivery_status ENUM(
        'pending',
        'delivered'
    ) NOT NULL DEFAULT 'pending',

    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmed_at DATETIME NULL,
    delivered_at DATETIME NULL,
    cancelled_at DATETIME NULL,

    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- ============================================================
-- 2. PRODUCTOS DEL PEDIDO
-- ============================================================

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT NOT NULL,

    -- products.id es INT normal en tu base actual
    product_id INT NOT NULL,

    quantity INT NOT NULL,

    -- Se conserva el precio que tenía cuando se pidió
    unit_price DECIMAL(10,2) NOT NULL,

    subtotal DECIMAL(10,2) NOT NULL,

    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT uq_order_product
        UNIQUE (order_id, product_id)
);


-- ============================================================
-- 3. PAGOS
-- ============================================================

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT NOT NULL,
    payment_method_id INT NOT NULL,

    amount DECIMAL(10,2) NOT NULL,

    -- Solo se utilizan cuando el pago sea en efectivo
    cash_received DECIMAL(10,2) NULL,
    change_amount DECIMAL(10,2) NULL,

    paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_payments_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_payments_method
        FOREIGN KEY (payment_method_id)
        REFERENCES payment_methods(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    -- Un pedido = un pago por ahora
    CONSTRAINT uq_payment_order
        UNIQUE (order_id)
);


-- ============================================================
-- 4. MÉTODOS INICIALES
-- ============================================================

INSERT IGNORE INTO payment_methods (name, status) VALUES
('Efectivo', 'active'),
('Transferencia', 'active'),
('Tarjeta', 'active');


USE la_obra_db;

-- ==========================================
-- SERVICIO DE COMPRAS Y GASTOS
-- SISTEMA LA OBRA
-- ==========================================


-- ==========================================
-- 1. TABLA DE COMPRAS
-- ==========================================

CREATE TABLE IF NOT EXISTS purchases (
    id INT NOT NULL AUTO_INCREMENT,
    purchase_date DATE NOT NULL,
    supplier VARCHAR(150) DEFAULT NULL,
    payment_method_id INT NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,

    PRIMARY KEY (id),

    INDEX idx_purchases_date (purchase_date),
    INDEX idx_purchases_payment_method (payment_method_id),
    INDEX idx_purchases_user (user_id),

    CONSTRAINT fk_purchases_payment_method
        FOREIGN KEY (payment_method_id)
        REFERENCES payment_methods(id),

    CONSTRAINT fk_purchases_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;



-- ==========================================
-- 2. DETALLE DE COMPRAS
-- ==========================================

CREATE TABLE IF NOT EXISTS purchase_items (
    id INT NOT NULL AUTO_INCREMENT,
    purchase_id INT NOT NULL,
    input_id INT NOT NULL,
    quantity DECIMAL(10,3) NOT NULL,
    unit_cost DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,

    PRIMARY KEY (id),

    INDEX idx_purchase_items_purchase (purchase_id),
    INDEX idx_purchase_items_input (input_id),

    CONSTRAINT fk_purchase_items_purchase
        FOREIGN KEY (purchase_id)
        REFERENCES purchases(id),

    CONSTRAINT fk_purchase_items_input
        FOREIGN KEY (input_id)
        REFERENCES inputs(id)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;



-- ==========================================
-- 3. HISTORIAL DE COMPRAS
-- ==========================================

CREATE TABLE IF NOT EXISTS purchase_history (
    id INT NOT NULL AUTO_INCREMENT,
    purchase_id INT NOT NULL,
    changed_by INT UNSIGNED NOT NULL,
    previous_data LONGTEXT NOT NULL,
    new_data LONGTEXT NOT NULL,
    change_reason VARCHAR(255) DEFAULT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    INDEX idx_purchase_history_purchase (purchase_id),
    INDEX idx_purchase_history_user (changed_by),

    CONSTRAINT fk_purchase_history_purchase
        FOREIGN KEY (purchase_id)
        REFERENCES purchases(id),

    CONSTRAINT fk_purchase_history_user
        FOREIGN KEY (changed_by)
        REFERENCES users(id)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;



-- ==========================================
-- 4. GASTOS Y SALIDAS DE CAJA
-- ==========================================

CREATE TABLE IF NOT EXISTS expenses (
    id INT NOT NULL AUTO_INCREMENT,
    amount DECIMAL(12,2) NOT NULL,
    reason VARCHAR(255) NOT NULL,
    user_id INT UNSIGNED NOT NULL,

    status ENUM('active','cancelled')
        NOT NULL DEFAULT 'active',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,

    cancelled_by INT UNSIGNED DEFAULT NULL,
    cancelled_at DATETIME DEFAULT NULL,
    cancellation_reason VARCHAR(255) DEFAULT NULL,

    PRIMARY KEY (id),

    INDEX idx_expenses_user (user_id),
    INDEX idx_expenses_date (created_at),
    INDEX idx_expenses_status (status),
    INDEX idx_expenses_cancelled_by (cancelled_by),

    CONSTRAINT fk_expenses_user
        FOREIGN KEY (user_id)
        REFERENCES users(id),

    CONSTRAINT fk_expenses_cancelled_by
        FOREIGN KEY (cancelled_by)
        REFERENCES users(id)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;



-- ==========================================
-- 5. HISTORIAL DE GASTOS
-- ==========================================

CREATE TABLE IF NOT EXISTS expense_history (
    id INT NOT NULL AUTO_INCREMENT,
    expense_id INT NOT NULL,

    action ENUM('updated','cancelled') NOT NULL,

    previous_data LONGTEXT NOT NULL,
    new_data LONGTEXT NOT NULL,

    change_reason VARCHAR(255) NOT NULL,
    changed_by INT UNSIGNED NOT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    INDEX idx_expense_history_expense (expense_id),
    INDEX idx_expense_history_user (changed_by),

    CONSTRAINT fk_expense_history_expense
        FOREIGN KEY (expense_id)
        REFERENCES expenses(id),

    CONSTRAINT fk_expense_history_user
        FOREIGN KEY (changed_by)
        REFERENCES users(id)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
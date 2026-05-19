-- ============================================
-- Photo Keychain Ordering System Database
-- ============================================

CREATE DATABASE IF NOT EXISTS keychain_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE keychain_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('admin','customer') DEFAULT 'customer',
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Keychain Designs Table
CREATE TABLE IF NOT EXISTS designs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    image VARCHAR(255),
    shape ENUM('circle','rectangle','heart','star','oval','custom') DEFAULT 'circle',
    material VARCHAR(50) DEFAULT 'Acrylic',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders Table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    design_id INT NOT NULL,
    photo_path VARCHAR(255),
    custom_text VARCHAR(200),
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending','processing','completed','cancelled') DEFAULT 'pending',
    special_notes TEXT,
    shipping_address TEXT,
    payment_proof VARCHAR(255),
    payment_status ENUM('unpaid','paid','refunded') DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (design_id) REFERENCES designs(id) ON DELETE RESTRICT
);

-- Order Status History
CREATE TABLE IF NOT EXISTS order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    note TEXT,
    changed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Insert Default Admin
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@keychain.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Default admin password: password

-- Insert Sample Designs
INSERT INTO designs (name, description, base_price, shape, material) VALUES
('Classic Circle', 'Timeless circular keychain with glossy finish', 149.00, 'circle', 'Acrylic'),
('Elegant Rectangle', 'Sleek rectangular design with matte finish', 159.00, 'rectangle', 'Acrylic'),
('Sweetheart', 'Romantic heart-shaped keychain, perfect for gifts', 169.00, 'heart', 'Acrylic'),
('Lucky Star', 'Fun star-shaped keychain for the bold ones', 169.00, 'star', 'Acrylic'),
('Oval Portrait', 'Portrait-friendly oval shape, great for photos', 155.00, 'oval', 'Acrylic'),
('Custom Shape', 'Design your own unique keychain shape', 199.00, 'custom', 'Acrylic');

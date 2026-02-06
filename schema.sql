-- --------------------------------------------------
-- DATABASE CREATION
-- --------------------------------------------------
DROP DATABASE IF EXISTS little_lemon;
CREATE DATABASE little_lemon;
USE little_lemon;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('user', 'admin') DEFAULT 'user',
    profile_image VARCHAR(255),
    is_guest BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tables for reservations
CREATE TABLE IF NOT EXISTS tables (
    table_id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50) NOT NULL,
    seats INT NOT NULL,
    status ENUM('available', 'unavailable') DEFAULT 'available'
);

-- Menu items
CREATE TABLE IF NOT EXISTS menu (
    menu_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255),
    category VARCHAR(50),
    dietary_tags SET('vegetarian', 'vegan', 'gluten-free', 'dairy-free', 'nut-free'),
    is_popular BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_price CHECK (price > 0)
);

-- Reservations
CREATE TABLE IF NOT EXISTS reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    table_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    party_size INT NOT NULL,
    special_requests TEXT,
    dietary_restrictions TEXT,
    status ENUM('pending', 'confirmed', 'booked', 'completed', 'cancelled', 'no-show') DEFAULT 'pending',
    reminder_sent BOOLEAN DEFAULT FALSE,
    guest_session_id VARCHAR(255), -- for guest tracking
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (table_id) REFERENCES tables(table_id)
);

-- Orders
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    menu_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10, 2) NOT NULL,
    special_instructions TEXT,
    status ENUM('pending', 'preparing', 'ready', 'served', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id),
    FOREIGN KEY (menu_id) REFERENCES menu(menu_id)
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

-- --------------------------------------------------
-- Indexes for performance
-- --------------------------------------------------
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_reservations_date ON reservations(date);
CREATE INDEX idx_reservations_user ON reservations(user_id);
CREATE INDEX idx_menu_category ON menu(category);

-- --------------------------------------------------
-- Sample data for immediate use
-- --------------------------------------------------

-- Sample Tables
INSERT IGNORE INTO tables (table_name, seats, status) VALUES 
('Table 1', 4, 'available'),
('Table 2', 2, 'available'),
('Table 3', 6, 'available'),
('Table 4', 4, 'available'),
('Table 5', 8, 'available'),
('Table 6', 2, 'available'),
('Table 7', 4, 'available'),
('Table 8', 6, 'available');

-- Sample Categories
INSERT IGNORE INTO categories (name, description) VALUES
('Appetizers', 'Start your meal with our delicious starters'),
('Main Courses', 'Hearty main dishes to satisfy your appetite'),
('Desserts', 'Sweet endings to perfect your dining experience'),
('Beverages', 'Refreshing drinks to complement your meal');

-- Sample Menu Items
INSERT IGNORE INTO menu (name, description, price, category, dietary_tags, is_popular) VALUES 
('Greek Salad', 'Fresh vegetables with feta cheese and olives', 12.99, 'Appetizers', 'vegetarian', TRUE),
('Bruschetta', 'Toasted bread topped with tomatoes and basil', 8.99, 'Appetizers', 'vegetarian', FALSE),
('Lamb Chops', 'Grilled lamb chops with herbs', 24.99, 'Main Courses', '', TRUE),
('Grilled Salmon', 'Fresh salmon with lemon butter sauce', 19.99, 'Main Courses', 'gluten-free', FALSE),
('Chicken Souvlaki', 'Grilled chicken skewers with tzatziki', 16.99, 'Main Courses', '', TRUE),
('Moussaka', 'Layered eggplant with meat and béchamel', 14.99, 'Main Courses', '', FALSE),
('Tiramisu', 'Classic Italian dessert with coffee', 7.99, 'Desserts', '', TRUE),
('Baklava', 'Sweet pastry with nuts and honey', 6.99, 'Desserts', 'vegetarian', FALSE),
('House Wine', 'Red or white wine selection', 22.99, 'Beverages', 'vegetarian,vegan', FALSE),
('Lemonade', 'Fresh homemade lemonade', 4.99, 'Beverages', 'vegetarian,vegan,gluten-free', TRUE);
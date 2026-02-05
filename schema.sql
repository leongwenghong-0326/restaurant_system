-- Database Schema for Restaurant System

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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reservations
CREATE TABLE IF NOT EXISTS reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    table_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    status ENUM('pending', 'booked', 'completed', 'cancelled') DEFAULT 'booked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id),
    FOREIGN KEY (menu_id) REFERENCES menu(menu_id)
);

-- Sample data for immediate use
-- Uncomment the sections below to populate your database with sample data

-- Sample Tables (8 different tables)
INSERT IGNORE INTO tables (table_name, seats, status) VALUES 
('Table 1', 4, 'available'),
('Table 2', 2, 'available'),
('Table 3', 6, 'available'),
('Table 4', 4, 'available'),
('Table 5', 8, 'available'),
('Table 6', 2, 'available'),
('Table 7', 4, 'available'),
('Table 8', 6, 'available');

-- Sample Menu Items (Mediterranean cuisine)
INSERT IGNORE INTO menu (name, description, price, category) VALUES 
('Greek Salad', 'Fresh vegetables with feta cheese and olives', 12.99, 'appetizer'),
('Bruschetta', 'Toasted bread topped with tomatoes and basil', 8.99, 'appetizer'),
('Lamb Chops', 'Grilled lamb chops with herbs', 24.99, 'main'),
('Grilled Salmon', 'Fresh salmon with lemon butter sauce', 19.99, 'main'),
('Chicken Souvlaki', 'Grilled chicken skewers with tzatziki', 16.99, 'main'),
('Moussaka', 'Layered eggplant with meat and béchamel', 14.99, 'main'),
('Tiramisu', 'Classic Italian dessert with coffee', 7.99, 'dessert'),
('Baklava', 'Sweet pastry with nuts and honey', 6.99, 'dessert'),
('House Wine', 'Red or white wine selection', 22.99, 'beverage'),
('Lemonade', 'Fresh homemade lemonade', 4.99, 'beverage');
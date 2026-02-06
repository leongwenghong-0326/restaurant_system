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

-- Reviews and ratings
CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reservation_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    food_rating INT CHECK (food_rating >= 1 AND food_rating <= 5),
    service_rating INT CHECK (service_rating >= 1 AND service_rating <= 5),
    atmosphere_rating INT CHECK (atmosphere_rating >= 1 AND atmosphere_rating <= 5),
    comment TEXT,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id)
);

-- Events and special occasions
CREATE TABLE IF NOT EXISTS events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_type ENUM('birthday', 'anniversary', 'corporate', 'wedding', 'other') NOT NULL,
    guest_count INT NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    budget DECIMAL(10, 2),
    special_requirements TEXT,
    status ENUM('inquiry', 'planning', 'confirmed', 'completed', 'cancelled') DEFAULT 'inquiry',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Promotions and special offers
CREATE TABLE IF NOT EXISTS promotions (
    promotion_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed', 'buy_x_get_y') NOT NULL,
    discount_value DECIMAL(10, 2),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User favorites
CREATE TABLE IF NOT EXISTS user_favorites (
    favorite_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    menu_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (menu_id) REFERENCES menu(menu_id),
    UNIQUE KEY unique_user_menu (user_id, menu_id)
);

-- Gallery images
CREATE TABLE IF NOT EXISTS gallery (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100),
    description TEXT,
    image_path VARCHAR(255) NOT NULL,
    category ENUM('food', 'interior', 'exterior', 'events', 'staff') NOT NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Contact messages
CREATE TABLE IF NOT EXISTS contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(100),
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied', 'closed') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_reservations_date ON reservations(date);
CREATE INDEX idx_reservations_user ON reservations(user_id);
CREATE INDEX idx_menu_category ON menu(category);
CREATE INDEX idx_reviews_user ON reviews(user_id);
CREATE INDEX idx_events_date ON events(event_date);

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

-- Sample Categories
INSERT IGNORE INTO categories (name, description) VALUES
('Appetizers', 'Start your meal with our delicious starters'),
('Main Courses', 'Hearty main dishes to satisfy your appetite'),
('Desserts', 'Sweet endings to perfect your dining experience'),
('Beverages', 'Refreshing drinks to complement your meal');

-- Sample Menu Items (Mediterranean cuisine)
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

-- Sample Gallery Images
INSERT IGNORE INTO gallery (title, description, image_path, category, is_featured) VALUES
('Traditional Greek Interior', 'Our authentic Mediterranean dining room', 'interior1.jpg', 'interior', TRUE),
('Chef Special Platter', 'Our signature dish presentation', 'food1.jpg', 'food', TRUE),
('Restaurant Exterior', 'Beautiful facade with outdoor seating', 'exterior1.jpg', 'exterior', TRUE),
('Wedding Celebration', 'Special event setup', 'events1.jpg', 'events', FALSE);

-- Sample Promotions
INSERT IGNORE INTO promotions (title, description, discount_type, discount_value, start_date, end_date, is_active) VALUES
('Happy Hour Special', '50% off selected appetizers', 'percentage', 50.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), TRUE),
('Family Dinner Deal', 'Free dessert with orders over RM80', 'fixed', 0.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), TRUE);
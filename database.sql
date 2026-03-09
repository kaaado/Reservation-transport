-- Phase 1 - Project Foundation
-- Transport Platform Database Schema

CREATE DATABASE IF NOT EXISTS transport_platform
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE transport_platform;

-- USERS Table
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('client', 'transporter', 'admin') NOT NULL DEFAULT 'client',
    status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_role (role)
) ENGINE=InnoDB;

-- VEHICLES Table
CREATE TABLE IF NOT EXISTS vehicles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id INT UNSIGNED NOT NULL,
    vehicle_type VARCHAR(50) NOT NULL,
    capacity DECIMAL(8,2) NOT NULL COMMENT 'Capacity in cubic meters/tons',
    plate_number VARCHAR(20) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_vehicles_owner (owner_id)
    -- idx_vehicles_plate dropped as UNIQUE constraint automatically creates one
) ENGINE=InnoDB;

-- RESERVATIONS Table
CREATE TABLE IF NOT EXISTS reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    vehicle_id INT UNSIGNED DEFAULT NULL,
    pickup_location VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    cargo_type VARCHAR(100) NOT NULL,
    volume DECIMAL(8,2) COMMENT 'Volume in cubic meters',
    weight DECIMAL(8,2) COMMENT 'Weight in kg',
    reservation_date DATETIME NOT NULL,
    status ENUM('pending', 'accepted', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    INDEX idx_reservations_client (client_id),
    INDEX idx_reservations_vehicle (vehicle_id),
    INDEX idx_reservations_status (status),
    INDEX idx_reservations_date (reservation_date)
) ENGINE=InnoDB;

-- EARNINGS Table
CREATE TABLE IF NOT EXISTS earnings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transporter_id INT UNSIGNED NOT NULL,
    reservation_id INT UNSIGNED NOT NULL UNIQUE COMMENT '1:1 relation to avoid duplicate billing',
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    INDEX idx_earnings_transporter (transporter_id)
) ENGINE=InnoDB;

-- SEED DATA
-- Default password for all users is 'password123'
-- Hashed with PHP BCRYPT algorithm ($2y$10$wTInFofUaH9S55.kM3h39Ohz3gYxX1kSTmO5w9oEIfiV4u/rGz/E.)

INSERT INTO users (name, email, password_hash, phone, role, status) VALUES 
('System Admin', 'admin@transport.local', '$2y$10$wTInFofUaH9S55.kM3h39Ohz3gYxX1kSTmO5w9oEIfiV4u/rGz/E.', '1112223333', 'admin', 'active'),
('Alice Client', 'alice@client.local', '$2y$10$wTInFofUaH9S55.kM3h39Ohz3gYxX1kSTmO5w9oEIfiV4u/rGz/E.', '5551234567', 'client', 'active'),
('Bob Client', 'bob@client.local', '$2y$10$wTInFofUaH9S55.kM3h39Ohz3gYxX1kSTmO5w9oEIfiV4u/rGz/E.', '5559876543', 'client', 'active'),
('Charlie Transport', 'charlie@transporter.local', '$2y$10$wTInFofUaH9S55.kM3h39Ohz3gYxX1kSTmO5w9oEIfiV4u/rGz/E.', '4445556666', 'transporter', 'active'),
('Dave Transport', 'dave@transporter.local', '$2y$10$wTInFofUaH9S55.kM3h39Ohz3gYxX1kSTmO5w9oEIfiV4u/rGz/E.', '7778889999', 'transporter', 'active');

INSERT INTO vehicles (owner_id, vehicle_type, capacity, plate_number, status) VALUES 
(4, 'Heavy Truck', 50.00, 'CH-890-TR', 'active'),
(5, 'Delivery Van', 12.50, 'DA-123-VN', 'active');

INSERT INTO reservations (client_id, vehicle_id, pickup_location, destination, cargo_type, volume, weight, reservation_date, status) VALUES 
(2, 4, 'Warehouse A, Industrial Park', 'Factory B, City Center', 'Raw Materials', 20.00, 1500.00, DATE_ADD(NOW(), INTERVAL 2 DAY), 'pending'),
(3, 5, 'Office C, Downtown', 'Retailer D, Suburbs', 'Electronics', 5.50, 200.00, DATE_SUB(NOW(), INTERVAL 1 DAY), 'completed');

INSERT INTO earnings (transporter_id, reservation_id, amount) VALUES 
(5, 2, 250.00);

CREATE DATABASE IF NOT EXISTS bookit;
USE bookit;

CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class VARCHAR(20), -- Premium / En-Suite / Twin...
    price INT
);

CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100), -- Breakfast/Lunch included...
    price INT
);

CREATE TABLE IF NOT EXISTS guests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50),
    birth_date DATE,
    booking_date DATE,
    room_id INT,
    service_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
);
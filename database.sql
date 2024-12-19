CREATE DATABASE alumni_locator;

USE alumni_locator;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    location POINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    SPATIAL INDEX(location)
);

-- Modified users table

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    location POINT NOT NULL,
    latitude DOUBLE NOT NULL, -- Latitude column
    longitude DOUBLE NOT NULL, -- Longitude column
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    SPATIAL INDEX(location),
    INDEX lat_lon_index (latitude, longitude)  -- Index on the latitude and longitude columns
);

CREATE TABLE alumni_networks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_alumni_networks (
    user_id INT NOT NULL,
    network_id INT NOT NULL,
    PRIMARY KEY (user_id, network_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (network_id) REFERENCES alumni_networks(id) ON DELETE CASCADE
);

-- Users located within 10 km of each other in San Francisco, California
INSERT INTO users (name, email, location) 
VALUES 
('Alice Green', 'alice.green@example.com', POINT(37.7749, -122.4194)),  -- San Francisco (Near downtown)
('Bob White', 'bob.white@example.com', POINT(37.7849, -122.4194)),    -- San Francisco (Near Mission District)
('Charlie Black', 'charlie.black@example.com', POINT(37.7749, -122.3994)), -- San Francisco (Near North Beach)
('Diana Blue', 'diana.blue@example.com', POINT(37.7649, -122.4194)),    -- San Francisco (Near SOMA)
('Eve Brown', 'eve.brown@example.com', POINT(37.7649, -122.4094));     -- San Francisco (Near Tenderloin)

-- Alice Green: Located at 37.7749, -122.4194 (This is downtown San Francisco).
-- Bob White: Located at 37.7849, -122.4194 (Mission District, around 1 km from Alice).
-- Charlie Black: Located at 37.7749, -122.3994 (North Beach, about 2 km from Alice).
-- Diana Blue: Located at 37.7649, -122.4194 (SOMA area, about 2 km from Alice).
-- Eve Brown: Located at 37.7649, -122.4094 (Tenderloin area, about 1 km from Alice).

INSERT INTO alumni_networks (name) 
VALUES 
('Computer Science Alumni Network'), 
('Business School Alumni Network'), 
('Engineering Alumni Network'), 
('Medical School Alumni Network');

-- Users and their associated alumni networks
INSERT INTO user_alumni_networks (user_id, network_id) 
VALUES 
(1, 1),  -- Alice Green is part of the Computer Science Alumni Network
(1, 2),  -- Alice Green is part of the Business School Alumni Network
(2, 2),  -- Bob White is part of the Business School Alumni Network
(2, 3),  -- Bob White is part of the Engineering Alumni Network
(3, 1),  -- Charlie Black is part of the Computer Science Alumni Network
(3, 4),  -- Charlie Black is part of the Medical School Alumni Network
(4, 3),  -- Diana Blue is part of the Engineering Alumni Network
(5, 4);  -- Eve Brown is part of the Medical School Alumni Network


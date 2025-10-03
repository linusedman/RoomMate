CREATE TABLE IF NOT EXISTS `floors` (
  `id` INT PRIMARY KEY
);

CREATE TABLE IF NOT EXISTS `rooms` (
  `id` INT PRIMARY KEY,
  `roomname` varchar(255),
  `floor_id` INT NOT NULL,
  CONSTRAINT fk_rooms_floor FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `username` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `admin` BOOLEAN DEFAULT FALSE
);

CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  `start_time` DATETIME NOT NULL,
  `end_time` DATETIME NOT NULL, 
  CONSTRAINT fk_bookings_user FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT fk_bookings_room FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `instrument_types` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `typename` varchar(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS `instruments` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `type_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  CONSTRAINT fk_instruments_instrument_types FOREIGN KEY (`type_id`) REFERENCES `instrument_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT fk_instruments_room FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `password_reset_temp` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,  
  `email` varchar(250) NOT NULL UNIQUE,
  `key` varchar(250) NOT NULL,
  `expDate` datetime NOT NULL,
  CONSTRAINT fk_email_user FOREIGN KEY (`email`) REFERENCES `users`(`email`) ON DELETE CASCADE ON UPDATE CASCADE
);

/* To backend team: 
the Insert statements for password reset shoul look like this:--- 
INSERT INTO password_reset_temp (email, reset_key, expDate)
VALUES ('user@example.com', 'reset', expiry date)
ON DUPLICATE KEY UPDATE
    reset_key = VALUES(reset_key),
    expDate = VALUES(expDate);
*/ 

ALTER TABLE `floors`
ADD COLUMN `path` VARCHAR(1000);

ALTER TABLE `rooms`
ADD COLUMN `path` VARCHAR(1000);

-- INSERT INTO users (username, password, email) VALUES ('test', '1', 'test@test.com');

-- INSERT INTO floors (id) VALUES (1);

-- INSERT INTO rooms (id, floor_id) VALUES (2, 1);

-- INSERT INTO instrument_types (typename) VALUES ('centrifuge');

-- INSERT INTO instruments (type_id, room_id) VALUES (1, 1);

-- INSERT INTO bookings (user_id, room_id, start_time, end_time) VALUES (1, 1, '2008-11-11 13:23:44', '2008-11-11 13:24:00');
-- INSERT INTO bookings (user_id, room_id, start_time, end_time) VALUES (2, 1, '2008-11-11 13:23:44', '2008-11-11 13:24:00');


-- create event that re,oves expired passwords--
-- 1. Enable the MySQL Event Scheduler
SET GLOBAL event_scheduler = ON;

-- 2. Create an automatic cleanup event
CREATE EVENT IF NOT EXISTS delete_expired_password_resets
ON SCHEDULE EVERY 1 MINUTE
STARTS CURRENT_TIMESTAMP
DO
  DELETE FROM password_reset_temp
  WHERE expDate < NOW();



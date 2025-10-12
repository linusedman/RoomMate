-- Drop existing tables in reverse dependency order to make updating simpler
DROP TABLE IF EXISTS `favorites`
DROP TABLE IF EXISTS `password_reset_temp`;
DROP TABLE IF EXISTS `instruments`;
DROP TABLE IF EXISTS `instrument_types`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `floors`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE IF NOT EXISTS `floors` (
  `id` INT PRIMARY KEY,
  `path` VARCHAR(1000) -- Can store a floor with ~50 corners
);

CREATE TABLE IF NOT EXISTS `rooms` (
  `id` INT PRIMARY KEY,
  `roomname` VARCHAR(30),
  `floor_id` INT NOT NULL,
  `path` VARCHAR(1000), -- Can store a room with ~50 corners
  CONSTRAINT fk_rooms_floor FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(20) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(50) NOT NULL UNIQUE,
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
  `typename` VARCHAR(50) NOT NULL
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
  `user_email` VARCHAR(50) NOT NULL UNIQUE,
  `key` VARCHAR(50) NOT NULL,
  `expDate` datetime NOT NULL,
  CONSTRAINT fk_email_user FOREIGN KEY (`user_email`) REFERENCES `users`(`email`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS 'favorites' (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `room_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  CONSTRAINT fk_id_room FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_id_room FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
);


-- create event that removes expired passwords--
-- 1. Enable the MySQL Event Scheduler
SET GLOBAL event_scheduler = ON;

-- 2. Create an automatic cleanup event
CREATE EVENT IF NOT EXISTS delete_expired_password_resets
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
  DELETE FROM password_reset_temp
  WHERE expDate < NOW();



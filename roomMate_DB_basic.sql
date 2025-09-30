CREATE TABLE `floors` (
  `id` INT PRIMARY KEY
);

CREATE TABLE `rooms` (
  `id` INT PRIMARY KEY,
  `roomname` varchar(255),
  `floor_id` INT NOT NULL,
  CONSTRAINT fk_rooms_floor FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON UPDATE CASCADE
);

CREATE TABLE `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `admin` BOOLEAN DEFAULT FALSE
);

CREATE TABLE `bookings` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  `start_time` DATETIME NOT NULL,
  `end_time` DATETIME NOT NULL, 
  CONSTRAINT fk_bookings_user FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT fk_bookings_room FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
);

CREATE TABLE `instrument_types` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `typename` varchar(255) NOT NULL
);

CREATE TABLE `instruments` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `type_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  CONSTRAINT fk_instruments_instrument_types FOREIGN KEY (`type_id`) REFERENCES `instrument_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT fk_instruments_room FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
);

CREATE TABLE `password_reset_temp` (
  `email` varchar(250) NOT NULL,
  `key` varchar(250) NOT NULL,
  `expDate` datetime NOT NULL
)

-- INSERT INTO users (username, password, email) VALUES ('test', '1', 'test@test.com');

-- INSERT INTO floors (id) VALUES (1);

-- INSERT INTO rooms (id, floor_id) VALUES (2, 1);

-- INSERT INTO instrument_types (typename) VALUES ('centrifuge');

-- INSERT INTO instruments (type_id, room_id) VALUES (1, 1);

-- INSERT INTO bookings (user_id, room_id, start_time, end_time) VALUES (1, 1, '2008-11-11 13:23:44', '2008-11-11 13:24:00');
-- INSERT INTO bookings (user_id, room_id, start_time, end_time) VALUES (2, 1, '2008-11-11 13:23:44', '2008-11-11 13:24:00');


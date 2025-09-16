CREATE TABLE `floors` (
  `id` INT PRIMARY KEY
);

CREATE TABLE `rooms` (
  `id` INT PRIMARY KEY,
  `roomname` varchar(255),
  `floor_id` INT NOT NULL,
  CONSTRAINT fk_rooms_floor FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`)
);

CREATE TABLE `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
);

CREATE TABLE `bookings` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  CONSTRAINT fk_bookings_user FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT fk_bookings_room FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`)
);


-- Alternative user table 
-- CREATE TABLE  users  (
--   id  int NOT NULL AUTO_INCREMENT,
--   username  varchar(45) NOT NULL,
--   email  varchar(45) NOT NULL,
--   password  varchar(45) NOT NULL,
--  PRIMARY KEY ( id ),
--  UNIQUE KEY  id_UNIQUE  ( id )
-- )


ALTER TABLE users
ADD COLUMN email VARCHAR(100) NOT NULL UNIQUE AFTER username;

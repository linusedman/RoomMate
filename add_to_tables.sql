-- Cannot add users this way since the passwords will not be hased!
-- INSERT INTO users (username, password, email)
-- VALUES
-- ('Tom_Sawyer', 'Password1', 'tom@example.com'),
-- ('Huck_Finn', 'Password2', 'huck@example.com'),
-- ('Sherlock_Holmes', 'Password3', 'sherlock@example.com'),
-- ('Oliver_Twist', 'password4', 'oliver@example.com');

-- Insert some floors
INSERT INTO floors (id) VALUES
(1),
(2),
(3);

-- Insert some rooms with numeric names for each floor
INSERT INTO rooms (id, roomname, floor_id) VALUES
(1, '101', 1),
(2, '102', 1),
(3, '201', 2),
(4, '202', 2),
(5, '301', 3),
(6, '302', 3);
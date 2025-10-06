-- The hashed password won't work if wee create users this way!

INSERT INTO users (username, password, email)
VALUES
('Tom_Sawyer', 'Password1', 'tom@example.com'),
('Huck_Finn', 'Password2', 'huck@example.com'),
('Sherlock_Holmes', 'Password3', 'sherlock@example.com'),
('Oliver_Twist', 'password4', 'oliver@example.com');
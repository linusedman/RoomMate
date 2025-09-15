CREATE TABLE Rooms(
    Room_nr VARCHAR(10),
    Floor INT,
    PRIMARY KEY (Room_nr)
);

CREATE TABLE Users(
    User_ID INT NOT NULL AUTO_INCREMENT,
    Username VARCHAR(10),
    Pswd VARCHAR(10),
    PRIMARY KEY (User_ID)
);

CREATE TABLE Bookings(
    Booking_ID INT NOT NULL AUTO_INCREMENT,
    User_ID INT,
    Room_nr VARCHAR(10),
    PRIMARY KEY (Booking_ID),
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID),
    FOREIGN KEY (Room_nr) REFERENCES Rooms(Room_nr)
);
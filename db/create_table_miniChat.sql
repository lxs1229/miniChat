CREATE TABLE users (
    num SERIAL PRIMARY KEY,
    mdp VARCHAR(20),
    pseudo VARCHAR(15) UNIQUE
);

CREATE TABLE rooms (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    created_by VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_room_creator
        FOREIGN KEY (created_by) REFERENCES users(pseudo)
);

CREATE TABLE messages (
    num SERIAL PRIMARY KEY,
    pseudo VARCHAR(15),
    message TEXT,
    time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    room_id INT,
    CONSTRAINT fk_users
        FOREIGN KEY (pseudo) REFERENCES users(pseudo),
    CONSTRAINT fk_room
        FOREIGN KEY (room_id) REFERENCES rooms(id)
);

CREATE TABLE connect_history (
    num SERIAL PRIMARY KEY,
    pseudo VARCHAR(15),
    time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_connection VARCHAR(45),
    CONSTRAINT fk_ip
        FOREIGN KEY (pseudo) REFERENCES users(pseudo)
);

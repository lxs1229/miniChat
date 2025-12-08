USE miniChat_db;

CREATE TABLE users(
    num INT AUTO_INCREMENT primary key,
    mdp varchar(20),
    pseudo varchar(15)
);

alter table users add UNIQUE (pseudo);

CREATE TABLE rooms(
    id INT AUTO_INCREMENT primary key,
    name varchar(50) UNIQUE,
    password varchar(255),
    created_by varchar(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_room_creator
        FOREIGN KEY (created_by)
            REFERENCES users(pseudo)
);

CREATE TABLE message(
    num INT AUTO_INCREMENT primary key,
    pseudo varchar(15),
    mesage text,
    time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    room_id INT,
    CONSTRAINT fk_users
        FOREIGN KEY (pseudo)
        REFERENCES users(pseudo),
    CONSTRAINT fk_room
        FOREIGN KEY (room_id)
        REFERENCES rooms(id)
);

CREATE TABLE Connect_Histoire
(
    num INT AUTO_INCREMENT primary key,
    pseudo varchar(15),
    time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ip
        FOREIGN KEY (pseudo)
            REFERENCES users(pseudo),
    ip_connection varchar(45)
);
ALTER TABLE Connect_Histoire MODIFY ip_connection VARCHAR(45);
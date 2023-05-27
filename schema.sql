CREATE TABLE
    tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        photo_name VARCHAR(255) NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        status VARCHAR(10) NOT NULL DEFAULT 'wait',
        result FLOAT
    );
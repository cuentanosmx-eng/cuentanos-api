USE u947809040_cuentanosbase;

CREATE TABLE IF NOT EXISTS businesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    rating DECIMAL(3,2) DEFAULT 0,
    location VARCHAR(255),
    image VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    user_id INT NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    comment TEXT,
    rating INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

INSERT INTO businesses (name, description, category, rating, location, image) VALUES
('Café Central', 'Un lugar increíble para disfrutar café artesanal.', 'Cafetería', 4.50, 'Ciudad de México', 'https://via.placeholder.com/300'),
('Taquería El Fuego', 'Las mejores tacos de la ciudad.', 'Restaurante', 4.80, 'Monterrey', 'https://via.placeholder.com/300');

INSERT INTO reviews (business_id, user_id, user_name, comment, rating) VALUES
(1, 1, 'Juan', 'Excelente café y ambiente muy tranquilo. 100% recomendado.', 5),
(2, 1, 'Carlos', 'Los mejores tacos al pastor que he probado en Monterrey.', 5);
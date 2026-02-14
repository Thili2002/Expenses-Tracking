CREATE DATABASE IF NOT EXISTS second_expness;
USE second_expness;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    icon VARCHAR(50) DEFAULT 'fas fa-tag'
);

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    category_id INT,
    transaction_date DATE NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Insert some default categories
INSERT INTO categories (name, type, icon) VALUES 
('Salary', 'income', 'fa-money-bill-wave'),
('Freelance', 'income', 'fa-laptop-code'),
('Investment', 'income', 'fa-chart-line'),
('Food', 'expense', 'fa-utensils'),
('Rent', 'expense', 'fa-home'),
('Transport', 'expense', 'fa-car'),
('Shopping', 'expense', 'fa-shopping-bag'),
('Entertainment', 'expense', 'fa-film'),
('Health', 'expense', 'fa-heartbeat');

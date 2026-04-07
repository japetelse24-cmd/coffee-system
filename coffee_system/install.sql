CREATE DATABASE IF NOT EXISTS coffee_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE coffee_shop;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(8,2) NOT NULL,
  image_url VARCHAR(255) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  category VARCHAR(100) NOT NULL DEFAULT 'Coffee',
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'Pending',
  slot_time VARCHAR(100) NOT NULL DEFAULT 'Anytime',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS slots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO slots (name) VALUES
('Anytime'),
('Morning 8:00 - 10:00'),
('Late Morning 11:00 - 13:00'),
('Afternoon 14:00 - 16:00'),
('Evening 17:00 - 19:00');

CREATE TABLE IF NOT EXISTS site_info (
  id INT PRIMARY KEY,
  system_name VARCHAR(255) NOT NULL DEFAULT 'ELSE Coffee',
  address VARCHAR(255) NOT NULL DEFAULT '123 Coffee Lane, Brewtown',
  social_media VARCHAR(255) NOT NULL DEFAULT 'Facebook: @ELSECoffee | Instagram: @ELSECoffee',
  description TEXT NOT NULL DEFAULT 'Your trusted local coffee shop serving fresh brews daily.'
);

INSERT IGNORE INTO site_info (id, system_name, address, social_media, description) VALUES
  (1, 'ELSE Coffee', '123 Coffee Lane, Brewtown', 'Facebook: @ELSECoffee | Instagram: @ELSECoffee', 'Your trusted local coffee shop serving fresh brews daily.');

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(8,2) NOT NULL,
  customizations TEXT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
);

INSERT IGNORE INTO users (name, email, password, role) VALUES
('Admin User', 'admin@coffee.com', '$2y$10$1joqiIRu368Y3MEKgY3IHOOkd.cQp4LTG9LnalsnBKvRlTiaUFwou', 'admin'),
('Guest Customer', 'customer@coffee.com', '$2y$10$uJtlerN4E2IzlQyI5C9DOeB5pXlTx6aQ0Rczr4M4pS1FDA7lXy3cO', 'customer');

INSERT IGNORE INTO products (name, description, price, image_url, stock, category) VALUES
('Espresso', 'Bold single shot espresso with a thick crema.', 2.50, 'https://images.unsplash.com/photo-1511920170033-f8396924c348?auto=format&fit=crop&w=800&q=80', 50, 'Espresso'),
('Cappuccino', 'Espresso with steamed milk and light foam.', 3.80, 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=800&q=80', 40, 'Milk Coffee'),
('Latte', 'Smooth latte with creamy milk and a mild coffee taste.', 4.20, 'https://images.unsplash.com/photo-1498804103079-a6351b050096?auto=format&fit=crop&w=800&q=80', 45, 'Milk Coffee'),
('Cold Brew', 'Chilled cold brew coffee, smooth and refreshing.', 4.00, 'https://images.unsplash.com/photo-1511920170033-f8396924c348?auto=format&fit=crop&w=800&q=80', 30, 'Cold Coffee');

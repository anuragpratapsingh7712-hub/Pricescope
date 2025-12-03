-- Database Schema for PriceScope
-- Matches user requirements: users, products, vendors, vendor_prices, price_history, watchlist, ratings

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS watchlist;
DROP TABLE IF EXISTS price_history;
DROP TABLE IF EXISTS vendor_prices;
DROP TABLE IF EXISTS vendors;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users
-- Stores user login details
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Products
-- The catalog of tracked items
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    base_price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    sku VARCHAR(50),
    asin VARCHAR(50)
);

-- 3. Vendors
-- The platforms you are tracking
CREATE TABLE vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- 4. Vendor Prices
-- Stores the current price from each vendor
CREATE TABLE vendor_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    vendor_id INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    source_url TEXT,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id)
);

-- 5. Price History
-- Stores time-series data for charting
CREATE TABLE price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    recorded_at DATE NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- 6. Watchlist
-- CRUD feature: Links users to products
CREATE TABLE watchlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    target_price DECIMAL(10, 2) DEFAULT NULL,
    alert_triggered BOOLEAN DEFAULT FALSE,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- 7. Ratings
-- Stores user sentiment (1=Yes, 0=No)
CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    vote TINYINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- SEED DATA --

-- Vendors
INSERT INTO vendors (name) VALUES 
('Amazon'), 
('Flipkart'), 
('Croma'), 
('Reliance Digital'), 
('Vijay Sales');

-- Products (Expanded Catalog)
INSERT INTO products (name, description, base_price, image_url) VALUES 
-- Electronics
('Sony WH-1000XM5', 'Wireless Noise Cancelling Headphones, 30hr Battery.', 24990, 'https://placehold.co/300x300/png?text=Sony+XM5'),
('MacBook Air M2', '13.6-inch Liquid Retina display, 8GB RAM, 256GB SSD.', 99900, 'https://placehold.co/300x300/png?text=MacBook+Air'),
('iPhone 15', 'Dynamic Island, 48MP Main Camera, USB-C.', 79900, 'https://placehold.co/300x300/png?text=iPhone+15'),
('Canon EOS R50', 'Mirrorless Camera with 18-45mm Lens, 4K Video.', 65990, 'https://placehold.co/300x300/png?text=Canon+R50'),
('Samsung Galaxy Watch 6', 'Smartwatch with Health Tracking, LTE.', 29999, 'https://placehold.co/300x300/png?text=Galaxy+Watch'),
('iPad Air 5th Gen', 'M1 Chip, 10.9-inch Liquid Retina Display.', 54900, 'https://placehold.co/300x300/png?text=iPad+Air'),
('Logitech MX Master 3S', 'Performance Wireless Mouse, Ultra-fast Scrolling.', 9995, 'https://placehold.co/300x300/png?text=MX+Master+3S'),
('Dell XPS 13', 'Intel Core i7, 16GB RAM, 512GB SSD, InfinityEdge.', 119990, 'https://placehold.co/300x300/png?text=Dell+XPS'),
('PlayStation 5', '825GB SSD, 4K 120Hz Gaming Console.', 54990, 'https://placehold.co/300x300/png?text=PS5'),
('GoPro Hero 12', 'Waterproof Action Camera, 5.3K60 Video.', 39990, 'https://placehold.co/300x300/png?text=GoPro+12'),

-- Home & Appliances
('Dyson V12 Detect', 'Cordless Vacuum Cleaner with Laser Detect.', 55900, 'https://placehold.co/300x300/png?text=Dyson+V12'),
('Philips Air Fryer', 'Digital Air Fryer HD9252/90, 4.1L.', 8999, 'https://placehold.co/300x300/png?text=Air+Fryer'),
('Nespresso Vertuo', 'Coffee and Espresso Machine by DeLonghi.', 18500, 'https://placehold.co/300x300/png?text=Nespresso'),
('Roborock S8', 'Robot Vacuum and Mop Cleaner, 6000Pa Suction.', 49999, 'https://placehold.co/300x300/png?text=Roborock'),
('Kindle Paperwhite', '6.8" display, Adjustable Warm Light.', 13999, 'https://placehold.co/300x300/png?text=Kindle'),

-- Fashion & Accessories
('Nike Air Jordan 1', 'High-top Sneakers, Chicago Colorway.', 16995, 'https://placehold.co/300x300/png?text=Jordan+1'),
('Ray-Ban Aviator', 'Classic Gold Frame, Green G-15 Lenses.', 8590, 'https://placehold.co/300x300/png?text=Ray-Ban'),
('Samsonite Trolley', 'Hard-sided Cabin Luggage, 55cm.', 11500, 'https://placehold.co/300x300/png?text=Samsonite'),
('Fossil Gen 6', 'Touchscreen Smartwatch, Brown Leather.', 18995, 'https://placehold.co/300x300/png?text=Fossil'),
('Adidas Ultraboost', 'Running Shoes, Light Boost Technology.', 14999, 'https://placehold.co/300x300/png?text=Ultraboost');

-- Vendor Prices (Current Snapshots)
INSERT INTO vendor_prices (product_id, vendor_id, price) VALUES 
(1, 1, 24990), (1, 2, 21990), (1, 3, 26990), -- Sony XM5
(2, 1, 92900), (2, 2, 99900), (2, 4, 94500), -- MacBook
(3, 1, 71999), (3, 2, 70500), -- iPhone
(4, 1, 65990), (4, 3, 67000), -- Canon
(5, 1, 29999), (5, 2, 26500); -- Watch

-- Price History (For Charts)
INSERT INTO price_history (product_id, price, recorded_at) VALUES 
-- Sony XM5 (Trending Down)
(1, 29990, '2025-01-01'), (1, 28990, '2025-01-05'), (1, 28000, '2025-01-10'), 
(1, 26990, '2025-01-15'), (1, 25990, '2025-01-20'), (1, 24990, '2025-01-25'), 
(1, 22990, '2025-01-28'), (1, 21990, '2025-01-30'),

-- MacBook (Stable)
(2, 99900, '2025-01-01'), (2, 99900, '2025-01-10'), (2, 98000, '2025-01-15'), 
(2, 99900, '2025-01-20'), (2, 95000, '2025-01-25'), (2, 92900, '2025-01-30'),

-- iPhone (Volatile)
(3, 82000, '2025-01-01'), (3, 79900, '2025-01-05'), (3, 75000, '2025-01-15'), 
(3, 78000, '2025-01-20'), (3, 72000, '2025-01-25'), (3, 70500, '2025-01-30'),

-- Canon (New)
(4, 69990, '2025-01-15'), (4, 68000, '2025-01-20'), (4, 65990, '2025-01-30');

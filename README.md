# PriceScope - Smart Shopping Companion

PriceScope is a price-comparison and tracking web application designed to help users make better shopping decisions. It features a friendly mascot "Blu" the penguin and a clean, light-blue themed interface.

## Features

-   **User Authentication**: Secure Login and Registration with CAPTCHA protection.
-   **Product Dashboard**: Overview of tracked items and potential savings.
-   **Price Comparison**: View prices from multiple vendors (Amazon, Flipkart, etc.).
-   **Price History**: Interactive charts showing price trends over time.
-   **Watchlist**: Add products to a watchlist, set target prices, and add personal notes.
-   **Deal Alerts**: Visual indicators for "Great Deals" or "High Prices".
-   **Responsive Design**: Works on desktop and mobile.

## Setup Instructions (XAMPP)

1.  **Copy Files**: Move the `pricescope` folder into your XAMPP `htdocs` directory.
    -   Example path: `C:\xampp\htdocs\pricescope`

2.  **Database Setup**:
    -   Open **phpMyAdmin** (usually at `http://localhost/phpmyadmin`).
    -   Create a new database named `pricescope` (or just import the file, it handles creation).
    -   Import the `db.sql` file located in the `pricescope` folder. This will create the necessary tables and seed them with demo data.

3.  **Configuration**:
    -   Open `config.php`.
    -   Ensure the database credentials match your XAMPP setup (default is usually User: `root`, Password: ``).

4.  **Run**:
    -   Open your browser and navigate to `http://localhost/pricescope/login.php`.

## Project Structure

-   `config.php`: Database connection settings.
-   `auth.php`: Authentication helper functions.
-   `header.php` / `footer.php`: Common layout elements.
-   `style.css`: Custom styling for the Light Blue theme and Blu mascot.
-   `login.php`: User login and registration.
-   `dashboard.php`: User dashboard with stats.
-   `product.php`: Product details, charts, and watchlist addition.
-   `watchlist.php`: Manage tracked items (CRUD).
-   `uploads/`: Directory for user-uploaded product images.

## Technologies Used

-   **Frontend**: HTML5, CSS3 (Custom + Bootstrap 5), JavaScript (Chart.js).
-   **Backend**: PHP.
-   **Database**: MySQL.

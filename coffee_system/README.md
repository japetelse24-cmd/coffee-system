# Coffee System Full Stack App

## System Overview
This is a small full-stack coffee shop ordering system built for XAMPP using PHP, MySQL, HTML, CSS, and JavaScript.
The system supports customer browsing, shopping cart checkout, user login, and an admin panel for managing products and orders.

## Features
- Home page with featured coffee products and site overview
- Product page listing all coffee products with add-to-cart functionality
- Cart page for updating quantities, removing items, and checkout
- Login page for customers and admin authentication
- User registration and password reset support
- Admin panel for managing products and reviewing orders
- MySQL database connection via XAMPP
- Responsive layout with CSS and interactive UI enhancements using JavaScript

## GUI Design
Main design components:
- Header navigation with links to Home, Products, Cart, Login, and Admin panel
- Hero banner with a coffee brand message on the home page
- Product cards showing image, description, price, and add button
- Cart summary table with dynamic quantity update and checkout panel
- Clean admin dashboard with product management and order history tables
- Consistent styling using a single `styles.css`

## Database Setup (XAMPP)
1. Start Apache and MySQL in XAMPP.
2. Open `phpMyAdmin` or run this SQL in MySQL.
3. Use the included `install.sql` to create the database, tables, and sample data.

## Files
- `index.php` — Home page
- `products.php` — Product catalog
- `cart.php` — Shopping cart and checkout
- `login.php` — Login form for customers and admin
- `logout.php` — Sign out
- `admin.php` — Admin dashboard
- `db.php` — MySQL database connection and session helpers
- `styles.css` — Shared styling
- `script.js` — Frontend cart interactions and responsive menu
- `install.sql` — Database initialization script

## Default Admin Login
- Email: `admin@coffee.com`
- Password: `admin123`

## Notes
- Place this folder inside XAMPP `htdocs` and access via `http://localhost/coffee_system/`.
- Use this direct link to open the system: http://localhost/coffee_system/
- Update `db.php` if your MySQL password or host is different.

## Run Without XAMPP
1. Install PHP and MySQL/MariaDB separately.
2. Import `install.sql` into your MySQL server to create the database and tables.
3. Update `db.php` with your MySQL connection details if needed.
4. From the project folder, start the built-in PHP server:
   - `php -S localhost:8000 -t .`
5. Open the app at `http://localhost:8000/`.

## Windows Quick Start
If PHP is in your PATH, run `start-local-server.bat` from the project folder.

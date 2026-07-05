# 🚚 Delivery Management System

A web-based **Delivery Management System** built with PHP and MySQL. It features a complete role-based workflow for Admins, Customers, and Delivery Partners to seamlessly manage, track, and update deliveries.

---

## 🌟 Key Features

### 1. 🔑 Multi-Role Authentication
- **Admin**: Full control over system operations, users, customers, delivery partners, and orders.
- **Customer**: Dedicated space to place orders, specify delivery details, and track shipment status.
- **Delivery Partner**: Tailored dashboard to view assigned shipments and update delivery statuses sequentially.

### 2. 🛡️ Admin Dashboard
- **Management**: Add, view, and manage Customers and Delivery Partners.
- **Order Dispatch**: Place new orders and assign them to active Delivery Partners.
- **Real-Time Tracking**: Monitor the live status of all active shipments.
- **Reporting**: Generate summaries and details of overall delivery stats and history.

### 3. 📦 Customer Dashboard
- **Place Orders**: Request a delivery by specifying items, category, and address.
- **Order Status History**: Track the step-by-step progress of your shipments.
- **Activity Log**: View details and history of past deliveries.

### 4. 🚴 Delivery Partner Dashboard
- **Active Deliveries**: View shipments currently assigned to you.
- **Sequential Status Updates**: Transition shipments sequentially through standard stages:
  1. `Order Preparing`
  2. `Order Ready`
  3. `Pick Order`
  4. `In Travel`
  5. `Order Reached`
  6. `Order Delivered`
- **History**: Keep track of completed deliveries.

---

## 🛠️ Tech Stack

- **Frontend**: HTML5, Vanilla CSS3 (styled with modern, premium glassmorphism elements & harmonies), JavaScript, FontAwesome Icons.
- **Backend**: PHP (Object-oriented PDO interface with secure transactions).
- **Database**: MySQL.

---

## 📂 Directory Structure

```text
├── admin/                  # Admin panel dashboards and controllers
├── assets/                 # CSS stylesheets, JS files, and images
├── config/                 # Database configuration (db.php)
├── customer/               # Customer portal pages
├── includes/               # Reusable page components (header, footer, sidebar)
├── partner/                # Delivery partner dashboard and status updates
├── index.php               # Unified login landing page
├── logout.php              # Session termination script
├── schema.sql              # SQL script to set up database tables
└── .gitignore              # Files and directories ignored by Git
```

---

## 🚀 Setup & Installation

Follow these steps to run the project locally:

### Prerequisites
- PHP 7.4 or higher
- MySQL database (e.g., via XAMPP, WAMP, or Local MySQL)

### Step 1: Clone the Repository
```bash
git clone https://github.com/khateebtuba04/Delivery-Management-System.git
cd Delivery-Management-System
```

### Step 2: Set Up the Database
1. Open your database administration tool (such as **phpMyAdmin**).
2. Create a new database named `delivery_db`.
3. Import the file [schema.sql](schema.sql) into the database.

### Step 3: Configure Database Credentials
Open [config/db.php](config/db.php) and configure your local MySQL credentials:
```php
$host = 'localhost';
$db   = 'delivery_db';
$user = 'YOUR_DB_USER';     // Default: 'root'
$pass = 'YOUR_DB_PASSWORD'; // Default: '' (empty string)
```

*Note: On first run, the database connection will automatically check if the admin account exists and auto-seed a default administrator if empty.*

### Step 4: Run the Application
You can run it on your local server:
- **XAMPP / WAMP**: Move the project folder to `htdocs` (or `www`), start Apache and MySQL from the control panel, and visit `http://localhost/Delivery-Management-System`.
- **PHP Built-in Server**: Run the following command inside the root folder:
  ```bash
  php -S localhost:8000
  ```
  Then, navigate to `http://localhost:8000` in your browser.

---

## 🔐 Default Credentials

For quick testing, use these default credentials:

- **Admin Account**:
  - **Username**: `admin`
  - **Password**: `123`
- **Customers & Partners**: Create them via the Admin Dashboard, then log in using their respective credentials.

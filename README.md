<div align="center">

# 🛒 Shopora

### A complete online shopping platform for customers, store managers, and administrators

[![PHP](https://img.shields.io/badge/PHP-8%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](#)
[![MySQL](https://img.shields.io/badge/MySQL-DATABASE-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](#)
[![HTML5](https://img.shields.io/badge/HTML5-FRONTEND-E34F26?style=for-the-badge&logo=html5&logoColor=white)](#)
[![CSS3](https://img.shields.io/badge/CSS3-RESPONSIVE-1572B6?style=for-the-badge&logo=css3&logoColor=white)](#)
[![JavaScript](https://img.shields.io/badge/JAVASCRIPT-INTERACTIVE-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](#)

[![Responsive](https://img.shields.io/badge/RESPONSIVE-DESKTOP%20%7C%20TABLET%20%7C%20MOBILE-2563EB?style=for-the-badge)](#)
[![Status](https://img.shields.io/badge/STATUS-ACTIVE-16A34A?style=for-the-badge)](#)

**Browse Products · Shopping Cart · Checkout · Orders · Reviews · Customer Accounts · Admin Panel · Store Management**

<br>

[![Live Website](https://img.shields.io/badge/🌐_LIVE_WEBSITE-VISIT_NOW-0EA5E9?style=for-the-badge)](https://shoporasite.xo.je/)
[![GitHub Repository](https://img.shields.io/badge/GITHUB-REPOSITORY-181717?style=for-the-badge&logo=github&logoColor=white)](https://github.com/mehedi77k/Shopora_Online-Shopping-Site)

</div>

---

## About Shopora

**Shopora** is a complete online shopping platform built to make both shopping and store management simple and organized.

Customers can explore products, search by category, add items to a cart, place orders, view previous purchases, submit reviews, and manage their accounts. Store administrators can manage products, categories, images, orders, and sales information from a dedicated Admin Panel.

A **Super Admin** role is also included for higher-level management of customer and administrator accounts.

---

## Main Features

### 🛍️ Customer Features

Customers can:

- Create an account and sign in
- Browse available products
- Search and filter products by category
- View product details
- Add products to the shopping cart
- Change quantities or remove items
- Keep cart items before and after login
- Complete checkout and place orders
- View order history and order details
- Submit reviews after eligible purchases
- Contact support
- View spending information in EUR and USD

### 🛠️ Admin Features

Store administrators can:

- View a simple sales dashboard
- Add, edit, and remove products
- Manage product images
- Create and manage categories
- Upload category images
- View customer orders
- Update order status
- Update payment status
- View sales information
- Check the current EUR/USD reference rate
- Change their own password

### 👑 Super Admin Features

The Super Admin includes all regular Admin features, plus:

- Create new Admin accounts
- Activate or deactivate Admin accounts
- Manage customer accounts
- Activate or deactivate customers
- View customer spending information
- Access currency information
- Manage the store at the highest level

> Regular Admins cannot create other Admins or manage customer and administrator accounts.

---

## How Shopora Works

```text
Customer Visits Shopora
        ↓
Browses or Searches Products
        ↓
Views Product Details
        ↓
Adds Products to Cart
        ↓
Reviews the Shopping Cart
        ↓
Completes Checkout
        ↓
Order is Created
        ↓
Customer Tracks the Order
        ↓
Admin Manages the Order
```

This keeps the shopping process simple for customers while giving store managers a clear way to handle daily store activities.

---

## User Roles

| User Type | Main Purpose |
|---|---|
| **Customer** | Browse products, shop, place orders, review purchases, and manage a personal account |
| **Admin** | Manage products, categories, images, orders, and store activities |
| **Super Admin** | Full store control, including Admin and customer account management |

---

## Product and Category Management

Shopora allows administrators to manage the store catalog directly from the Admin Panel.

### Product Management

- Add products
- Edit product information
- Delete products
- Set product prices
- Update stock
- Add product images
- Replace or remove images
- Organize products by category

### Category Management

- Add categories
- Edit categories
- Add category images
- Replace or remove category images

Supported image formats:

```text
JPG
JPEG
PNG
WEBP
```

Maximum image size:

```text
4 MB
```

---

## Shopping Cart

The shopping cart works for both visitors and registered customers.

A visitor can add products before signing in. After login, Shopora can continue the shopping process without forcing the customer to start again.

Customers can:

- Add products
- Increase or decrease quantities
- Remove products
- Review the total before checkout

---

## Orders

After checkout, Shopora creates an order that can be viewed from both the customer account and the Admin Panel.

Customers can see:

- Order number
- Ordered products
- Total amount
- Order status
- Payment information
- Previous orders

Admins can review orders and update their progress.

---

## Payment Options

The project currently includes:

- **Cash on Delivery**
- **Card**
- **Mobile Banking**

> **Note:** Card and Mobile Banking are currently available as payment choices in the system, but a real online payment gateway is not connected yet.

---

## EUR and USD Price Display

Shopora uses **EUR (Euro)** as its main store currency and also shows an approximate **USD (US Dollar)** value for reference.

Example:

```text
€100.00
≈ $115.92
```

The system can update the EUR/USD reference rate from its configured online source.

For previous orders, Shopora keeps the exchange rate that was used when the order was placed. This helps older order totals remain consistent even when the current exchange rate changes.

---

## Product Reviews

Registered customers can submit product reviews after an eligible purchase.

This helps future customers understand the experience of previous buyers while keeping reviews connected to actual purchases.

---

## Customer Support

Shopora includes support and contact options so customers can communicate with the store when they need help with:

- Orders
- Products
- Accounts
- General questions

---

## Account and Access Control

Shopora separates customer, Admin, and Super Admin access so that each user can only access the options intended for their role.

The project also includes basic protection for:

- User passwords
- Login sessions
- Admin-only pages
- Super Admin-only pages
- Product image uploads
- Customer and Admin account status
- Checkout and stock updates

---

## Project Structure

```text
Shopora_Online-Shopping-Site/
│
├── admin/              # Store management pages
├── api/                # Small background requests used by the website
├── assets/             # Design files, images, and browser scripts
├── config/             # Main website and database settings
├── includes/           # Shared page sections and common functions
├── realtime/           # Live-update related files
├── uploads/            # Uploaded product and category images
│
├── index.php           # Homepage
├── shop.php            # Main shopping page
├── product.php         # Product details
├── cart.php            # Shopping cart
├── checkout.php        # Checkout page
├── account.php         # Customer account
├── login.php           # Login page
├── register.php        # Registration page
├── support.php         # Customer support
│
└── online_shop.sql     # Fresh database setup file
```

---

# Running Shopora Locally

The easiest way to run Shopora on a computer is with **Laragon**.  
You can also use **XAMPP** or **WAMP**.

---

## Option A — Laragon

### 1. Start Laragon

Start:

```text
Apache
MySQL
```

### 2. Download the Project

Open PowerShell or Terminal inside:

```text
C:\laragon\www
```

Run:

```bash
git clone https://github.com/mehedi77k/Shopora_Online-Shopping-Site.git
```

Rename the downloaded folder to:

```text
online_shop
```

The project should then be located at:

```text
C:\laragon\www\online_shop
```

### 3. Create the Database

Open:

```text
http://localhost/phpmyadmin/
```

Then:

1. Select **Import**
2. Choose `online_shop.sql`
3. Start the import

> If you already have important Shopora data, create a backup before importing a fresh database file.

### 4. Check Database Settings

Open:

```text
config/config.php
```

For a normal Laragon or XAMPP setup:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'online_shop');
define('DB_USER', 'root');
define('DB_PASS', '');
```

If your MySQL account uses a password, update `DB_PASS`.

The project normally uses:

```php
define('BASE_URL', '/online_shop');
```

### 5. Open Shopora

Visit:

```text
http://localhost/online_shop/
```

---

## Option B — XAMPP

Place the project inside:

```text
C:\xampp\htdocs\online_shop
```

Start **Apache** and **MySQL** from XAMPP.

Import `online_shop.sql` through phpMyAdmin, then visit:

```text
http://localhost/online_shop/
```

---

## First Admin Setup

For a fresh installation, open:

```text
http://localhost/online_shop/register.php
```

If no administrator exists yet, the first Admin account can be created from the registration page.

The **first Admin automatically becomes the Super Admin**.

After that:

- Public users can register only as customers
- Additional Admin accounts can be created only by the Super Admin

---

## Login

All users use the same login page:

```text
http://localhost/online_shop/login.php
```

After login:

- Customers continue to the customer area
- Admins are taken to the Admin Panel
- The Super Admin receives the full management options

---

## Basic Testing Checklist

After setup, confirm that:

- [ ] Homepage opens correctly
- [ ] Shop page displays products
- [ ] Product search works
- [ ] Categories can be opened
- [ ] Product images appear correctly
- [ ] Customer registration works
- [ ] Customer login works
- [ ] Shopping cart works
- [ ] Checkout creates an order
- [ ] Order history appears in the customer account
- [ ] Product reviews work for eligible purchases
- [ ] Admin login works
- [ ] Admin can manage products
- [ ] Admin can manage categories
- [ ] Admin can manage orders
- [ ] Product and category image upload works
- [ ] Super Admin can manage customers
- [ ] Super Admin can create additional Admins
- [ ] EUR and USD values appear correctly

---

## Common Problems

| Problem | What to Check |
|---|---|
| Website does not open | Make sure Apache is running and the project is in the correct folder |
| Database connection fails | Make sure MySQL is running and check `config/config.php` |
| Products do not appear | Confirm that `online_shop.sql` was imported correctly |
| Images do not appear | Check the `uploads/products` and `uploads/categories` folders |
| Image upload fails | Use JPG, JPEG, PNG, or WEBP files under 4 MB |
| Page shows 404 | Confirm the folder name is `online_shop` and the base path is `/online_shop` |
| Admin page is unavailable | Make sure you are logged in with an Admin or Super Admin account |

---

## Updating Your Local Copy

If the project is already downloaded from GitHub, open PowerShell or Terminal inside the project folder and run:

```bash
git pull origin main
```

---

## Main Tools Used

| Tool | Purpose |
|---|---|
| **PHP** | Runs the main shopping and store-management features |
| **MySQL** | Stores customers, products, orders, and other shop information |
| **HTML** | Builds the website pages |
| **CSS** | Controls the website design and responsive layout |
| **JavaScript** | Adds interactive behavior |
| **Apache** | Runs the website locally or on a web server |
| **phpMyAdmin** | Helps set up and manage the database |

---

## Project Purpose

Shopora was created as a complete online shopping project that demonstrates how a store can manage both the **customer shopping experience** and the **store administration process** in one system.

```text
Product Display
      ↓
Customer Shopping
      ↓
Shopping Cart
      ↓
Checkout
      ↓
Order Creation
      ↓
Order Management
      ↓
Customer History and Reviews
```

It can be used as:

- An academic project
- A portfolio project
- A learning project
- A starting point for a larger online store

---

## Future Improvements

Possible future additions include:

- Real online payment gateway
- Order delivery tracking
- Email order notifications
- Wishlist
- Discount and coupon system
- More detailed sales reports
- Improved customer support tools
- Product recommendations
- Delivery charge calculation
- Multi-language support

---

## Important Notes

- EUR is the main store currency; USD is shown as a reference.
- Card and Mobile Banking are not connected to a real payment gateway yet.
- Back up important store data before replacing or changing the database.
- Do not publish real passwords or private account information in a public repository.
- Create your own test accounts for local testing instead of publishing login credentials.

---

## Links

| Resource | Link |
|---|---|
| 🌐 **Live Website** | [Visit Shopora](https://shoporasite.xo.je/) |
| 💻 **GitHub Repository** | [View Source Code](https://github.com/mehedi77k/Shopora_Online-Shopping-Site) |

Clone the project:

```bash
git clone https://github.com/mehedi77k/Shopora_Online-Shopping-Site.git
```

---

## Acknowledgement

Shopora was developed as a complete online shopping and store-management project covering customer shopping, order processing, product management, account management, and administration in a single platform.

---

<div align="center">

### 🛒 Shopora

**Simple shopping for customers. Organized management for store administrators.**

<br>

### Developed by **Mehedi Hasan**

</div>

# 🛒 Shopora — Online Shopping Management System

Shopora is a complete web-based **Online Shopping Management System** developed using **PHP, MySQL, HTML, CSS, and JavaScript**.

The system provides a customer-facing e-commerce website together with a role-based administration panel for **Customers, Admins, and Super Admins**.

It includes product and category management, shopping cart, checkout, order management, product reviews, image uploads, customer management, administrator management, and a dual-currency **EUR + USD** display system.

---

## 📌 Project Overview

Shopora provides three different user roles:

### 👤 Customer

Customers can:

- Create an account
- Login and logout
- Browse products
- Search and filter products
- View product details
- Add products to cart
- Update cart quantities
- Remove products from cart
- Checkout and place orders
- View order history
- View individual order details
- Submit product reviews after eligible purchases
- View total spending in both **EUR and USD**

### 🛠️ Admin

Normal Admins can:

- Access the Admin Dashboard
- View sales statistics
- Manage products
- Add, edit, and delete products
- Upload and manage product images
- Manage categories
- Upload and manage category images
- Manage orders
- Update order status
- Update payment status
- View the current USD exchange rate
- Change their own password

Normal Admins **cannot**:

- Create another Admin
- Manage Admin accounts
- Manage Customer accounts
- Access Super Admin-only account management pages

### 👑 Super Admin

The Super Admin has complete administrative control.

A Super Admin can:

- Perform all normal Admin operations
- Create new Admin accounts
- Activate or deactivate Admin accounts
- Manage Customer accounts
- Activate or deactivate Customers
- View customer lifetime spending
- View automatic EUR/USD rate details
- Access Currency Rate details
- Change their own password

---

# ✨ Main Features

## 🛍️ Shopping System

- Product catalog
- Product search
- Category filtering
- Product details
- Shopping cart
- Guest cart support
- Logged-in customer cart
- Cart merging after login
- Checkout system
- Order processing
- Stock validation
- Order history
- Order details

---

## 🔐 Authentication & Role Management

Shopora contains a three-level authentication system:

```text
Customer
Admin
Super Admin
```

### First-Time Registration

On a completely fresh installation, if no administrator exists, the registration page allows:

```text
Register as User
Register as Admin
```

The **first Admin account registered becomes the Super Admin**.

After the Super Admin has been created, public registration becomes:

```text
Register as User
```

only.

Additional Admin accounts can then only be created by the **Super Admin** from the Admin Dashboard.

All users use the same login page.

---

# 💰 Dual Currency System

Shopora uses:

```text
Base Currency      : EUR — Euro
Reference Currency : USD — US Dollar
```

Typical product price display:

```text
€ 100.00
≈ $ 115.92
```

EUR remains the main accounting and payment currency.

USD is displayed as a reference currency throughout the website.

Dual currency information is available in:

### Customer Area

- Homepage products
- Shop page
- Product details
- Shopping cart
- Checkout
- Order confirmation
- Order history
- Order details
- Lifetime spending

### Admin Area

- Product listing
- Dashboard sales statistics
- Total sales
- Today's sales
- Average order value
- Recent orders
- Order listing
- Order details

### Super Admin Area

Additionally includes:

- Customer lifetime spending
- Currency Settings

---

## 💱 Automatic EUR/USD Reference Rate

Shopora automatically retrieves the latest published EUR/USD reference rate from the configured online provider. No administrator needs to type the rate manually.

The browser checks Shopora every minute. Shopora uses a shared MySQL cache so only one upstream refresh is needed for all active accounts. If the internet/source is temporarily unavailable, the last successful rate remains active.

The Super Admin Currency Rate page is now read-only for the value and shows the source, publication date and last check time. A **Check source now** button is available only as a diagnostic/fallback action.

## 🧾 Historical Exchange Rate Protection

When an order is placed, Shopora stores the exchange rate that was active at the time of checkout.

For example:

```text
Order Date Rate:
1 EUR = $1.1592

Order Total:
€100.00
$115.92
```

If the Super Admin later changes the current rate to:

```text
1 EUR = $1.2000
```

the old order will still display:

```text
€100.00
$115.92
```

This prevents historical financial information from changing when exchange rates are updated.

---

# 🖼️ Product & Category Image Management

Both Admins and Super Admins can upload images while managing the catalog.

## Product Images

Navigate to:

```text
Admin Dashboard
→ Products
→ Add Product / Edit Product
```

Supported features:

- Upload image
- Live image preview
- Replace existing image
- Remove existing image
- Automatic fallback placeholder
- Secure randomly generated filenames

Supported formats:

```text
JPG
JPEG
PNG
WEBP
```

Maximum file size:

```text
4 MB
```

Recommended product image size:

```text
800 × 800 px or larger
```

---

## Category Images

Navigate to:

```text
Admin Dashboard
→ Categories
→ Add / Edit Category
```

Category images support:

- Upload
- Preview
- Replace
- Remove
- Automatic placeholder/fallback

Uploaded category images are displayed on the homepage under:

```text
Shop by Category
```

Recommended category image size:

```text
1200 × 800 px
```

Uploaded images are stored inside:

```text
uploads/products/
uploads/categories/
```

---

# 🛡️ Security Features

Shopora includes several security measures:

- PDO prepared statements
- Password hashing using `password_hash()`
- Password verification using `password_verify()`
- CSRF protection
- PHP session authentication
- Session ID regeneration
- Role-based authorization
- Super Admin-only protected routes
- Active account validation
- Server-side image validation
- MIME-type checking
- Random upload filenames
- Script execution protection inside upload directories
- Database transactions during checkout
- Stock locking and re-validation
- Historical currency-rate snapshots
- Public privileged registration disabled after first Super Admin creation

---

# 💳 Payment Methods

The current project includes:

```text
Cash on Delivery
Card
Mobile Banking
```

> **Note:** Card and Mobile Banking are currently database payment options. No real external payment gateway is connected.

EUR is the main recorded payment currency. USD is used for reference and reporting.

---

# 🗄️ Database

Database name:

```text
online_shop
```

Main database tables:

```text
users
categories
products
carts
cart_items
orders
order_items
payments
reviews
currency_rates
```

### Main Relationships

```text
users
 ├── carts
 │    └── cart_items ─── products
 │
 ├── orders
 │    ├── order_items ─── products
 │    └── payments
 │
 └── reviews ─────────── products

categories
 └── products

currency_rates
 └── automatically synchronized EUR/USD reference rate
```

---

# 🧰 Technologies Used

| Technology | Purpose |
|---|---|
| PHP 8+ | Backend development |
| MySQL 8+ | Database |
| PDO | Secure database communication |
| HTML5 | Page structure |
| CSS3 | User interface styling |
| JavaScript | Frontend interaction |
| Apache | Web server |
| PHP Sessions | Authentication |
| phpMyAdmin | Database management |

---

# 📁 Project Structure

```text
online_shop/
│
├── admin/
│   ├── includes/
│   ├── admins.php
│   ├── categories.php
│   ├── change_password.php
│   ├── currency_settings.php
│   ├── index.php
│   ├── order_view.php
│   ├── orders.php
│   ├── product_delete.php
│   ├── product_form.php
│   ├── products.php
│   └── users.php
│
├── assets/
│   ├── css/
│   ├── img/
│   └── js/
│
├── config/
│   └── config.php
│
├── includes/
│   ├── footer.php
│   ├── functions.php
│   └── header.php
│
├── uploads/
│   ├── categories/
│   ├── products/
│   └── .htaccess
│
├── about.php
├── account.php
├── add_to_cart.php
├── cart.php
├── checkout.php
├── contact.php
├── index.php
├── login.php
├── logout.php
├── order_details.php
├── order_success.php
├── privacy.php
├── product.php
├── register.php
├── remove_from_cart.php
├── review_submit.php
├── setup_admin.php
├── shop.php
├── update_cart.php
│
├── online_shop.sql
├── database_schema.sql
├── database_migration_super_admin.sql
├── database_migration_currency.sql
├── database_migration_images.sql
│
└── README.md
```

---

# ⚙️ Requirements

Before running the project, install one of the following local PHP environments:

- Laragon
- XAMPP
- WAMP

Recommended environment:

```text
PHP 8+
MySQL 8+
Apache
PDO MySQL extension
PHP Fileinfo extension
```

This guide uses **Laragon**.

---

# 🚀 How to Run the Project Using Laragon

## Step 1 — Install Laragon

Download and install Laragon.

Start:

```text
Apache
MySQL
```

from the Laragon control panel.

---

## Step 2 — Clone the Repository

Open PowerShell or Terminal inside:

```text
C:\laragon\www
```

Then run:

```bash
git clone https://github.com/mehedi77k/Shopora_Online-Shopping-Site.git
```

Rename the cloned folder to:

```text
online_shop
```

if necessary.

The final project location should be:

```text
C:\laragon\www\online_shop
```

---

## Step 3 — Create / Import the Database

Open phpMyAdmin:

```text
http://localhost/phpmyadmin/
```

For a completely fresh installation, import:

```text
online_shop.sql
```

The SQL file will create/configure the required:

```text
online_shop
```

database and database tables.

> Do not import the fresh SQL file over a database containing important existing data.

---

## Step 4 — Check Database Configuration

Open:

```text
config/config.php
```

The default Laragon/XAMPP-compatible configuration is:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'online_shop');
define('DB_USER', 'root');
define('DB_PASS', '');
```

If your MySQL username or password is different, update these values.

For example:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'online_shop');
define('DB_USER', 'root');
define('DB_PASS', 'your_mysql_password');
```

The project base URL is configured as:

```php
define('BASE_URL', '/online_shop');
```

Therefore, the folder should normally be named:

```text
online_shop
```

---

## Step 5 — Open the Website

After Apache and MySQL are running, open:

```text
http://localhost/online_shop/
```

---

# 👑 First Super Admin Setup

For a fresh database, open:

```text
http://localhost/online_shop/register.php
```

Because no administrator exists yet, you will see:

```text
Register as User
Register as Admin
```

Choose:

```text
Register as Admin
```

and complete the registration.

That first Admin account will automatically become the:

```text
SUPER ADMIN
```

After the Super Admin is created, public Admin registration is automatically disabled.

From that point onward, normal visitors can only create Customer accounts.

---

# 🔑 Login

All roles use the same login page:

```text
http://localhost/online_shop/login.php
```

### Customer Login

A Customer is redirected to the customer area.

### Admin Login

An Admin is redirected to:

```text
http://localhost/online_shop/admin/
```

### Super Admin Login

The Super Admin also enters through the same login page and receives access to the complete administration panel.

---

# 👨‍💼 Creating Additional Admins

A Normal Admin cannot create another Admin.

Only the **Super Admin** can create additional Admin accounts.

Login as Super Admin and navigate to:

```text
Admin Dashboard
→ Administrators
```

Create the new Admin using the required information.

The newly created Admin can then login through:

```text
http://localhost/online_shop/login.php
```

and change their own password from the Admin Dashboard.

---

# 🔄 Existing Database Upgrade

If you already have Shopora data such as:

- Customers
- Products
- Orders
- Reviews
- Admin accounts

do **not** import the fresh `online_shop.sql` file.

Instead, apply the migration files when required.

Recommended migration order:

```text
1. database_migration_super_admin.sql
2. database_migration_currency.sql
3. database_migration_images.sql
```

### Super Admin Migration

```text
database_migration_super_admin.sql
```

Adds the Super Admin role and permission structure.

### Currency Migration

```text
database_migration_currency.sql
```

Adds:

- USD currency support
- Currency rate storage
- Historical order exchange-rate information

### Image Migration

```text
database_migration_images.sql
```

Adds category image support without deleting existing catalog data.

> Always back up your database before running migrations.

---

# 📦 Updating the Project from GitHub

If the project is already cloned, open PowerShell inside:

```text
C:\laragon\www\online_shop
```

Then run:

```bash
git pull origin main
```

---

# 📤 Pushing Changes to GitHub

After making changes:

```bash
git add .
git commit -m "Describe your changes"
git push origin main
```

Example:

```bash
git add .
git commit -m "Improve product image management"
git push origin main
```

---

# 🧪 Basic Testing Checklist

After installation, verify the following:

```text
✓ Homepage opens
✓ Shop page loads products
✓ Product images are displayed
✓ Category images are displayed
✓ Customer registration works
✓ First Admin becomes Super Admin
✓ Public Admin signup disappears afterwards
✓ Customer login works
✓ Admin login works
✓ Super Admin login works
✓ Cart works
✓ Checkout creates an order
✓ Stock is updated after checkout
✓ Order history works
✓ EUR and USD amounts appear correctly
✓ Admin can manage products
✓ Admin can manage categories
✓ Product image upload works
✓ Category image upload works
✓ Normal Admin cannot manage accounts
✓ Super Admin can manage Customers
✓ Super Admin can create Admins
✓ Super Admin can update USD rate
✓ Admin can change own password
```

---

# 🛠️ Common Problems

## Database Connection Failed

If you see:

```text
Database connection failed
```

check:

```text
config/config.php
```

Make sure:

```text
MySQL is running
Database name = online_shop
Username = root
Password = correct
```

---

## Website Shows 404

Make sure the project is located at:

```text
C:\laragon\www\online_shop
```

and access it using:

```text
http://localhost/online_shop/
```

Also confirm:

```php
define('BASE_URL', '/online_shop');
```

inside `config/config.php`.

---

## Images Are Not Displaying

Check that these directories exist:

```text
uploads/products/
uploads/categories/
```

Also make sure Apache/PHP has permission to write files into those directories.

If an uploaded image is missing, Shopora automatically displays a placeholder instead of a broken-image icon.

---

## Image Upload Fails

Verify:

```text
File type: JPG, JPEG, PNG, or WEBP
Maximum size: 4 MB
```

Also ensure the PHP `fileinfo` extension is enabled.

---

## phpMyAdmin Cannot Import SQL

Make sure MySQL is running.

Then open:

```text
http://localhost/phpmyadmin/
```

Select:

```text
Import
→ Choose File
→ online_shop.sql
→ Import
```

---

# ⚠️ Important Notes

- Do not use the fresh SQL file if your database already contains important data.
- Keep database backups before running migration files.
- Do not commit production passwords or API credentials to a public GitHub repository.
- The current payment methods are database-recorded options and are not connected to a real payment gateway.
- USD values are reference values; EUR remains the application's primary accounting currency.
- The project is currently configured for the `/online_shop` local path.

---

# 🌐 Repository

GitHub Repository:

https://github.com/mehedi77k/Shopora_Online-Shopping-Site

Clone using:

```bash
git clone https://github.com/mehedi77k/Shopora_Online-Shopping-Site.git
```

---

# 👨‍💻 Development Environment

The project can be developed using:

```text
Visual Studio Code
Laragon
phpMyAdmin
Git
GitHub
```

Suggested local project path:

```text
C:\laragon\www\online_shop
```

---

# 📚 Project Purpose

Shopora was developed as a complete database-driven e-commerce web application covering:

- Frontend development
- Backend development
- Relational database design
- Authentication
- Authorization
- Role-based access control
- Shopping cart management
- Order processing
- Inventory management
- Image/file management
- Multi-currency reporting
- Secure PHP/MySQL programming

---

## Shopora

**A PHP & MySQL Online Shopping Management System with Customer, Admin, Super Admin, Product Management, Order Processing, Image Management, and EUR/USD Currency Support.**
## Support Center and User History

This build includes a database-backed internal support system.

- `contact.php` creates a real support conversation instead of showing a non-persistent success message.
- Logged-in Customer, Admin, and Super Admin accounts can see their own conversations and staff replies from `support.php` / `support_view.php`.
- Admin and Super Admin accounts can manage all conversations from `admin/support.php` and reply from `admin/support_view.php`.
- Admin and Super Admin accounts can search an exact registered email from `admin/user_history.php` to review stored orders, payments, reviews, current cart, support conversations, and the meaningful activity audit log.
- The audit log records meaningful actions after this update is installed (login/logout, cart changes, orders, reviews, support actions, account status changes, order/payment updates, and major administrator changes). Historical orders/reviews/cart records that existed before the audit migration remain available, but actions that were never stored by the old application cannot be reconstructed retroactively.

### Existing database upgrade

Import `database_migration_support_history.sql` into the existing `online_shop` database once. It only adds the support and audit tables; it does not delete existing store data.

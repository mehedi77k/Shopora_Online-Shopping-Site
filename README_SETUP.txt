SHOPORA - ONLINE SHOPPING MANAGEMENT SYSTEM
===========================================
Technology: PHP 8+, MySQL 8+, PDO, HTML/CSS/JavaScript
Database: online_shop
Default URL: http://localhost/online_shop/

1. INSTALL PROJECT
------------------
Copy the online_shop folder into:
    C:\xampp\htdocs\online_shop\

Start Apache and MySQL from XAMPP.

2. FRESH DATABASE INSTALLATION
------------------------------
Import:
    online_shop.sql

This fresh SQL recreates the database, inserts sample categories/products, adds
the Super Admin authentication structure, and enables BDT + EUR support.
No user/admin account is inserted, so first-time registration works correctly.

3. EXISTING DATABASE UPGRADE
----------------------------
If you already have important data, DO NOT import online_shop.sql.

Run these migrations instead:
    1) database_migration_super_admin.sql   (only if Super Admin migration was not run before)
    2) database_migration_currency.sql      (adds BDT/EUR support without deleting data)
    3) database_migration_images.sql        (adds Category image support without deleting data)

Legacy orders did not originally store an exchange-rate snapshot. The currency
migration backfills those old orders with the migration-time EUR rate. Every new
order after the upgrade stores its exact checkout-time rate.

4. DATABASE CONNECTION
----------------------
Default XAMPP configuration in config/config.php:
    Host: localhost
    Database: online_shop
    Username: root
    Password: [empty]

5. AUTHENTICATION ROLES
-----------------------
Customer/User:
    - Public signup
    - Shop/cart/checkout/order history/reviews

Admin:
    - Product/category/order management
    - Can view financial data and current exchange rate
    - Cannot create/control User/Admin accounts
    - Cannot change currency settings
    - Can change own password

Super Admin:
    - First Admin registered on a fresh installation
    - Full account control
    - Can create/deactivate Normal Admin accounts
    - Can activate/deactivate customer accounts
    - Can update EUR exchange rate
    - Can change own password

6. FIRST REGISTRATION
---------------------
Open:
    http://localhost/online_shop/register.php

When no Admin exists, signup shows:
    - Register as User
    - Register as Admin (becomes the one Super Admin)

After the first Super Admin exists, public signup becomes User-only.
All roles use the same login page:
    http://localhost/online_shop/login.php

7. DUAL CURRENCY SYSTEM
-----------------------
Base/accounting currency:
    BDT (Bangladeshi Taka)

Reference currency:
    EUR (Euro)

Typical display:
    ৳ 15,500.00
    ≈ € 108.12

Product, cart and checkout reference conversions use the CURRENT configured EUR
rate. New orders save a snapshot of that rate so historical order EUR totals do
not change when the Super Admin later updates the rate.

Order fields used for currency history:
    total_amount          -> BDT amount
    base_currency         -> BDT
    eur_exchange_rate     -> rate saved at checkout
    total_eur             -> EUR reference total saved at checkout

8. CURRENCY SETTINGS
--------------------
Super Admin only:
    Admin Dashboard -> Currency Settings

The Super Admin enters how many BDT equal 1 EUR.
Example:
    €1 = ৳143.50

Normal Admins can see the current rate on the dashboard but cannot edit it.

The supplied fresh/migration SQL starts with:
    €1 = ৳143.361
as the initial reference rate verified on 10 September 2026.

9. WHERE BDT + EUR APPEAR
-------------------------
Customer:
    - Home product cards
    - Shop
    - Product details
    - Cart
    - Checkout
    - Order confirmation
    - Order details
    - Order history
    - Lifetime spending summary

Admin / Super Admin:
    - Product list
    - Dashboard Total Sales
    - Dashboard Today's Sales
    - Dashboard Average Order
    - Recent orders
    - Order list
    - Order details

Super Admin additionally:
    - Customer lifetime spending table
    - Currency Settings

Cancelled orders are excluded from revenue/lifetime-spending totals.

10. HISTORICAL RATE PROTECTION
------------------------------
If an order was placed when:
    €1 = ৳143.00
and the Super Admin later changes the current rate to:
    €1 = ৳150.00
that old order continues to show the original saved EUR value based on ৳143.00.

11. ADMIN PANEL
---------------
URL:
    http://localhost/online_shop/admin/

Operational features:
    - Dashboard statistics
    - Products
    - Product image upload / preview / replace / remove
    - Categories
    - Category image upload / preview / replace / remove
    - Orders
    - Order/payment status update
    - Own password change

Super Admin-only features:
    - Customers
    - Administrators
    - Currency Settings

12. PAYMENT NOTE
----------------
Card and Mobile Banking are demo/database selections only. No real payment
gateway is connected. BDT remains the final recorded payment currency; EUR is a
reference value for display/reporting.

13. SECURITY
------------
Includes:
    - PDO prepared statements
    - password_hash/password_verify
    - CSRF tokens
    - Session regeneration
    - Role-based permissions
    - Active-account revalidation
    - Public privileged signup disabled after first Super Admin
    - Checkout database transaction
    - Stock locking/validation
    - Historical exchange-rate snapshot per new order
    - Safe product-image validation

14. PRODUCT & CATEGORY IMAGES
-----------------------------
Both Normal Admin and Super Admin can manage catalog images.

Products:
    Admin -> Products -> Add product / Edit
    - Choose JPG, PNG or WEBP image
    - Live preview before saving
    - Replace an existing image
    - Remove the image and use a professional placeholder

Categories:
    Admin -> Categories -> Add/Edit category
    - Choose JPG, PNG or WEBP image
    - Live preview before saving
    - Replace/remove an existing image
    - Uploaded category images appear in Home -> Shop by category

Image rules:
    - Maximum 4 MB per image
    - MIME type and real image content are validated server-side
    - Random filenames are generated to prevent filename collisions
    - Uploaded files are stored under uploads/products and uploads/categories
    - Script execution is blocked in the uploads directory
    - Missing/deleted image files automatically use a clean placeholder instead of a broken image icon
    - Replaced/deleted catalog images are cleaned up from storage

Recommended dimensions:
    Product: square, approximately 800x800 px or larger
    Category: landscape, approximately 1200x800 px

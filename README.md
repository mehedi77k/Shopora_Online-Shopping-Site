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


**---**

**## About Shopora**

**\*\*Shopora\*\*** is a complete online shopping website designed to make buying and managing products simple and organized.

Customers can browse products, search by category, add items to a cart, place orders, check previous purchases, submit reviews, and manage their accounts. Store managers can manage products, categories, orders, images, and sales information from a separate administration area.

The project also includes a **\*\*Super Admin\*\*** role for higher-level control over customer and administrator accounts.

**---**

**## Main Features**

**### 🛍️ Customer Shopping Experience**

Customers can:

\- Create an account and sign in

\- Browse available products

\- Search for products

\- Filter products by category

\- View product details

\- Add products to the shopping cart

\- Change product quantities or remove items

\- Keep cart items before and after login

\- Place an order through checkout

\- View order history

\- View individual order details

\- Submit product reviews after eligible purchases

\- Contact support

\- View spending information in EUR and USD

**---**

**### 🛠️ Admin Panel**

Store administrators can:

\- View a simple sales dashboard

\- Add new products

\- Edit existing products

\- Remove products

\- Upload and manage product images

\- Create and manage product categories

\- Upload category images

\- View customer orders

\- Update order status

\- Update payment status

\- View sales information

\- Check the current EUR/USD reference rate

\- Change their own password

**---**

**### 👑 Super Admin**

The Super Admin has all normal Admin features, along with additional account-management options.

A Super Admin can:

\- Create new Admin accounts

\- Activate or deactivate Admin accounts

\- Manage customer accounts

\- Activate or deactivate customers

\- View customer spending information

\- Access currency information

\- Manage the store at the highest level

\> Normal Admins cannot create other Admins or manage customer and administrator accounts.

**---**

**## How Shopora Works**

\`\`\`text

Customer visits Shopora

        ↓

Browses or searches products

        ↓

Views product details

        ↓

Adds products to cart

        ↓

Reviews the shopping cart

        ↓

Completes checkout

        ↓

Order is created

        ↓

Customer can track the order from the account page

        ↓

Admin manages the order from the Admin Panel

\`\`\`

This keeps the shopping process easy for customers while giving store managers a clear way to handle daily store activities.

**---**

**## User Roles**

\| User Type | Main Purpose |

\|---|---|

\| **\*\*Customer\*\*** | Browse products, shop, place orders, review purchases, and manage personal account |

\| **\*\*Admin\*\*** | Manage products, categories, images, orders, and store activity |

\| **\*\*Super Admin\*\*** | Full store control, including Admin and customer account management |

**---**

**## Product & Category Management**

Shopora allows store managers to organize the product catalog directly from the Admin Panel.

**### Product management includes:**

\- Add products

\- Edit product information

\- Delete products

\- Set product prices

\- Update stock

\- Add product images

\- Replace or remove images

\- Organize products by category

**### Category management includes:**

\- Add categories

\- Edit categories

\- Add category images

\- Replace or remove category images

Supported image formats include:

\`\`\`text

JPG

JPEG

PNG

WEBP

\`\`\`

The current upload limit is **\*\*4 MB per image\*\***.

**---**

**## Shopping Cart**

The shopping cart is designed to work for both visitors and registered customers.

A visitor can add products before signing in. After login, Shopora can keep the cart and continue the shopping process without forcing the customer to start again.

Customers can:

\- Add products

\- Increase or decrease quantities

\- Remove products

\- Review the total before checkout

**---**

**## Orders**

After checkout, Shopora creates an order that can be viewed from both the customer account and the Admin Panel.

Customers can see:

\- Order number

\- Ordered products

\- Total amount

\- Order status

\- Payment information

\- Previous orders

Admins can review orders and update their progress.

**---**

**## Payment Options**

The project currently includes the following payment choices:

\- **\*\*Cash on Delivery\*\***

\- **\*\*Card\*\***

\- **\*\*Mobile Banking\*\***

\> **\*\*Important:\*\*** Card and Mobile Banking are currently shown as payment choices inside the system. A real online payment gateway is not connected yet.

**---**

**## EUR & USD Price Display**

Shopora uses **\*\*EUR (Euro)\*\*** as its main store currency and also shows an approximate **\*\*USD (US Dollar)\*\*** value for easier reference.

Example:

\`\`\`text

€100.00

≈ $115.92

\`\`\`

The system can automatically update the EUR/USD reference rate from its configured online source.

For previous orders, Shopora keeps the rate that was used when the order was placed. This helps old order totals remain consistent even when the current exchange rate changes later.

**---**

**## Product Reviews**

Registered customers can submit reviews for products after an eligible purchase.

This helps future customers understand the experience of previous buyers while keeping reviews connected to actual purchases.

**---**

**## Customer Support**

Shopora includes customer support and contact options so users can communicate with the store when they need help with an order, product, or account-related issue.

**---**

**## Account and Access Control**

Shopora separates customer, Admin, and Super Admin access so that each user sees only the options intended for their role.

The project also includes basic protection for:

\- User passwords

\- Login sessions

\- Admin-only pages

\- Super Admin-only pages

\- Product image uploads

\- Customer and Admin account status

\- Checkout and stock updates

**---**

**## Main Project Folders**

\`\`\`text

Shopora\_Online-Shopping-Site/

│

├── admin/              # Store management pages

├── api/                # Small background requests used by the website

├── assets/             # Design files, images, and browser scripts

├── config/             # Main website and database settings

├── includes/           # Shared page sections and common functions

├── realtime/           # Live-update related files

├── uploads/            # Uploaded product and category images

│

├── index.php           # Homepage

├── shop.php            # Main shopping page

├── product.php         # Product details

├── cart.php            # Shopping cart

├── checkout.php        # Checkout page

├── account.php         # Customer account

├── login.php           # Login page

├── register.php        # Registration page

├── support.php         # Customer support

│

└── online\_shop.sql     # Fresh database setup file

\`\`\`

**---**

**# Running Shopora on Your Computer**

The easiest way to run the project locally is with **\*\*Laragon\*\***.  

You can also use **\*\*XAMPP\*\*** or **\*\*WAMP\*\***.

**---**

**## Option A — Run with Laragon**

**### 1. Install Laragon**

Install Laragon and start:

\`\`\`text

Apache

MySQL

\`\`\`

**---**

**### 2. Download the Project**

Open PowerShell or Terminal inside:

\`\`\`text

C:\laragon\www

\`\`\`

Run:

\`\`\`bash

git clone https\://github.com/mehedi77k/Shopora\_Online-Shopping-Site.git

\`\`\`

Rename the downloaded folder to:

\`\`\`text

online\_shop

\`\`\`

Your project should then be located at:

\`\`\`text

C:\laragon\www\online\_shop

\`\`\`

**---**

**### 3. Create the Database**

Open:

\`\`\`text

http\://localhost/phpmyadmin/

\`\`\`

Then:

1\. Go to **\*\*Import\*\***

2\. Select \`online\_shop.sql\`

3\. Start the import

This prepares the database required by Shopora.

\> If you already have important Shopora data, do not replace it with the fresh SQL file without creating a backup first.

**---**

**### 4. Check Database Settings**

Open:

\`\`\`text

config/config.php

\`\`\`

For a normal Laragon or XAMPP setup, the values are usually:

\`\`\`php

define('DB\_HOST', 'localhost');

define('DB\_NAME', 'online\_shop');

define('DB\_USER', 'root');

define('DB\_PASS', '');

\`\`\`

If your MySQL account uses a password, update \`DB\_PASS\`.

The project is normally configured to use:

\`\`\`php

define('BASE\_URL', '/online\_shop');

\`\`\`

**---**

**### 5. Open Shopora**

After Apache and MySQL are running, visit:

\`\`\`text

http\://localhost/online\_shop/

\`\`\`

Shopora should now open in your browser.

**---**

**## Option B — Run with XAMPP**

Place the project inside:

\`\`\`text

C:\xampp\htdocs\online\_shop

\`\`\`

Start **\*\*Apache\*\*** and **\*\*MySQL\*\*** from the XAMPP Control Panel.

Import \`online\_shop.sql\` through phpMyAdmin, then open:

\`\`\`text

http\://localhost/online\_shop/

\`\`\`

**---**

**## First Admin Setup**

For a fresh installation, open:

\`\`\`text

http\://localhost/online\_shop/register.php

\`\`\`

If no administrator exists yet, the registration page allows the first Admin account to be created.

The **\*\*first Admin automatically becomes the Super Admin\*\***.

After that:

\- Public users can register only as customers

\- Additional Admin accounts can be created only by the Super Admin

This prevents visitors from freely creating administrator accounts.

**---**

**## Login**

All users use the same login page:

\`\`\`text

http\://localhost/online\_shop/login.php

\`\`\`

After login:

\- Customers continue to the customer area

\- Admins are taken to the Admin Panel

\- The Super Admin receives the full management options

**---**

**## Test Credentials**

Use these credentials for testing the application:

\| Role | Email | Password |

\|---|---|---|

\| **\*\*SuperAdmin\*\*** | superadmin\@gmail.com | superadmin123 |

\| **\*\*Admin\*\*** | admin\@gmail.com | superadmin123 |

\| **\*\*User\*\*** | user\@gmail.com | superadmin123 |

**---**

**## Basic Testing Checklist**

After setup, check that:

\- [ ] Homepage opens correctly

\- [ ] Shop page displays products

\- [ ] Product search works

\- [ ] Categories can be opened

\- [ ] Product images appear correctly

\- [ ] Customer registration works

\- [ ] Customer login works

\- [ ] Shopping cart works

\- [ ] Checkout creates an order

\- [ ] Order history appears in the customer account

\- [ ] Product reviews work for eligible purchases

\- [ ] Admin login works

\- [ ] Admin can manage products

\- [ ] Admin can manage categories

\- [ ] Admin can manage orders

\- [ ] Product and category image upload works

\- [ ] Super Admin can manage customers

\- [ ] Super Admin can create additional Admins

\- [ ] EUR and USD values appear correctly

**---**

**## Common Problems**

\| Problem | What to Check |

\|---|---|

\| Website does not open | Make sure Apache is running and the project is inside the correct folder |

\| Database connection fails | Make sure MySQL is running and check \`config/config.php\` |

\| Products do not appear | Confirm that \`online\_shop.sql\` was imported correctly |

\| Images do not appear | Check the \`uploads/products\` and \`uploads/categories\` folders |

\| Image upload fails | Use JPG, JPEG, PNG, or WEBP and keep the file within 4 MB |

\| Page shows 404 | Confirm the folder name is \`online\_shop\` and the base path is \`/online\_shop\` |

\| Admin page is unavailable | Make sure you are logged in with an Admin or Super Admin account |

**---**

**## Updating Your Local Copy**

If the project is already downloaded from GitHub, open PowerShell or Terminal inside the project folder and run:

\`\`\`bash

git pull origin main

\`\`\`

**---**

**## Main Tools Used**

The project is built with commonly used web tools:

\| Tool | Used For |

\|---|---|

\| **\*\*PHP\*\*** | Runs the main shopping and store-management features |

\| **\*\*MySQL\*\*** | Stores customers, products, orders, and other shop information |

\| **\*\*HTML\*\*** | Creates the website pages |

\| **\*\*CSS\*\*** | Controls the website design and responsive layout |

\| **\*\*JavaScript\*\*** | Adds interactive behavior |

\| **\*\*Apache\*\*** | Runs the website locally or on a web server |

\| **\*\*phpMyAdmin\*\*** | Makes database setup and management easier |

**---**

**## Project Purpose**

Shopora was created as a complete online shopping project that demonstrates how a real store can manage both the **\*\*customer shopping experience\*\*** and the **\*\*store administration process\*\*** in one system.

The project covers the full journey from:

\`\`\`text

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

Customer History & Reviews

\`\`\`

It is suitable as an academic project, portfolio project, learning project, or starting point for a larger online store.

**---**

**## Future Improvements**

Possible future additions include:

\- Real online payment gateway

\- Order delivery tracking

\- Email order notifications

\- Wishlist

\- Product discount and coupon system

\- More detailed sales reports

\- Improved customer support tools

\- Product recommendation features

\- Delivery charge calculation

\- Multi-language support

**---**

**## Important Notes**

\- EUR is the main store currency; USD is shown as a reference.

\- Card and Mobile Banking are not connected to a real payment gateway yet.

\- Back up important store data before replacing or changing the database.

\- Do not publish real passwords or private account information in a public repository.

\- For security, login credentials are intentionally not included in this README.

**---**

**## Repository**

**\*\*GitHub:\*\***  

https\://github.com/mehedi77k/Shopora\_Online-Shopping-Site

Clone the project with:

\`\`\`bash

git clone https\://github.com/mehedi77k/Shopora\_Online-Shopping-Site.git

\`\`\`

**---**

**## Live Website**

Shopora can currently be viewed at:

**\*\*https\://shoporasite.xo.je/\*\***

**---**



**## Acknowledgement**

Shopora was developed as a complete online shopping and store-management project covering customer shopping, order processing, product management, account management, and administration in a single website.

**---**

\

---

<div align="center">

### 🛒 Shopora

**Simple shopping for customers. Organized management for store administrators.**

<br>

**Developed by Mehedi Hasan**

</div>

# ANUGERAH3D MASTER BUILD PLAN

## 1. Project Vision

Anugerah3D is a Laravel-based business platform for 3D printing operations.

The system will support:

- Public website
- Admin portal
- Agent portal
- Customer portal
- Product catalog
- Stock tracking
- Quotation
- Order
- Simple invoice
- Activities log
- S3 content storage
- AI-assisted development using Codex and Laravel Boost

---

## 2. Core Architecture

Single Laravel application.

Repository:

/var/www/anugerah3d-core

Laravel source:

/var/www/anugerah3d-core/src

Domains:

- anugerah3d.com
- admin.anugerah3d.com
- agent.anugerah3d.com
- customer.anugerah3d.com

Database:

MariaDB

Storage:

AWS S3 bucket:

anugerah3d-content

AI tools:

- Codex
- Laravel Boost

---

## 3. Development Constitution

Codex must follow these rules:

1. Do not change architecture without updating documentation.
2. Do not create database table without migration and documentation.
3. Do not commit `.env`.
4. Do not store uploaded file URLs directly; store S3 object keys.
5. Do not bypass activities logging for major actions.
6. Do not over-engineer MVP.
7. Keep beginner-friendly structure.
8. Keep all Laravel source code inside `/src`.
9. Use GitHub for version control.
10. Use small commits with clear messages.

---

## 4. Naming Convention

Use these field names:

- admins.adm_name
- agents.agt_name
- customers.cust_name
- products.prd_name

Use:

- last_login_at

Do not use:

- lastlogin_at

Picture fields:

- admins.picture
- agents.picture
- customers.picture
- products.picture

All picture fields store S3 object keys only.

Example:

products/clicker-black.jpg

---

## 5. MVP Tables

Required tables:

1. admins
2. agents
3. customers
4. categories
5. products
6. quotations
7. quotation_items
8. orders
9. order_items
10. invoices
11. activities
12. settings

---

## 6. MVP Build Order

Build in this order:

1. Install Laravel Boost
2. Install Filament
3. Setup domain routing
4. Create MVP migrations
5. Create models
6. Create admin authentication
7. Create admin dashboard
8. Create Admins CRUD
9. Create Agents CRUD
10. Create Customers CRUD
11. Create Categories CRUD
12. Create Products CRUD
13. Create Quotations
14. Create Quotation Items
15. Convert Quotation to Order
16. Create Orders
17. Create Simple Invoices
18. Create Activities Log
19. Create Basic Reports

---

## 7. Portals

### Public Website

Domain:

anugerah3d.com

Purpose:

- Company introduction
- Product display
- Contact
- Customer registration
- Agent registration

### Admin Portal

Domain:

admin.anugerah3d.com

Purpose:

- Manage admins
- Manage agents
- Manage customers
- Manage products
- Manage quotations
- Manage orders
- Manage invoices
- View activities
- View reports

### Agent Portal

Domain:

agent.anugerah3d.com

Purpose:

- Agent login
- View product catalog
- Create quotation
- Create customer order
- View own commission later

### Customer Portal

Domain:

customer.anugerah3d.com

Purpose:

- Customer login
- View quotation
- View order
- View invoice

---

## 8. S3 Storage Rules

Bucket:

anugerah3d-content

Folder structure:

- admins/
- agents/
- customers/
- products/
- company/
- quotations/
- invoices/
- payments/
- temp/

All uploaded files must go to S3.

Database stores object key only.

---

## 9. Activities Log

Every important action must write to activities table.

Examples:

- admin_login
- agent_login
- customer_login
- create_product
- update_product
- create_quotation
- convert_quotation_to_order
- create_invoice
- update_payment_status

---

## 10. What Not To Build Yet

Do not build these in MVP:

- Payment gateway
- Advanced accounting
- Advanced commission
- Multi-warehouse inventory
- Mobile app
- Complex REST API
- CloudFront
- RDS
- CI/CD
- Advanced manufacturing module

---

## 11. Codex Working Rule

Before coding, Codex must read:

1. README.md
2. MASTER_BUILD_PLAN.md
3. TASK.md
4. .ai/project_rules.md
5. .ai/architecture.md
6. .ai/database.md
7. .ai/laravel_rules.md
8. .codex/instructions.md
9. docs/specs/*

After coding, Codex must:

1. Run relevant artisan commands
2. Run tests if available
3. Update documentation
4. Commit to GitHub
5. Explain what changed

---

## 12. Current Sprint

Current objective:

Prepare Laravel AI foundation.

Next steps:

1. Install Laravel Boost
2. Install Filament
3. Configure database
4. Configure S3
5. Create MVP migrations


# Orchestra

![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)
![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20.svg)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4.svg)
![Version](https://img.shields.io/badge/version-1.0.0-blue)

**Open-source distribution operations platform for wholesalers, distributors, FMCG businesses, and multi-product enterprises.**

Orchestra connects business administration, role-based access, product and catalog management, field-oriented staff operations, inventory transactions, batches, sales, returns, customer ledgers, collections, digital shopfront ordering, subscriptions, and operational reporting in one system.

It is designed around a simple idea:

```text
people → products → transactions → inventory → money → customers
                         │
                         ▼
                    operations
                         │
                         ▼
                     Orchestra
```

## Why Orchestra

Distribution businesses operate across many connected workflows.

A single organization may manage thousands of products, multiple product categories, staff with different responsibilities, stock movements, sales transactions, returned and damaged goods, customer balances, collections, digital orders, and administrative controls at the same time.

Orchestra brings those workflows together around a shared business and transaction model rather than treating each function as an isolated application.

```text
                         Orchestra
                             │
         ┌───────────────────┼───────────────────┐
         │                   │                   │
      Commerce           Operations           Control
         │                   │                   │
     Customers            Products             Businesses
     Shopfronts            Inventory            Roles
     Orders                Batches              Permissions
     Sales                 Returns              Staff
         │                   │                   │
         └───────────────────┼───────────────────┘
                             │
                         Financial
                             │
                 Ledgers · Payments · Deposits
```

## Core capabilities

### Business administration

* Business management
* Business administrators
* Super-admin controls
* Role management
* Permission management
* Business subscriptions
* Subscription plans
* Payment transaction records
* Business geographic/sub-district management

### Product and catalog management

* Products
* Product categories
* Common products
* Common categories
* Units of measurement
* Product batches
* Product images
* Supplier relationships
* Product import workflows
* Global/default product data

### Staff and access control

* Staff management
* Role-based access control
* Granular permissions
* Admin and staff separation
* DSR-oriented staff assignments
* Staff/category relationships
* Staff pricing controls
* Salary-head management

### Sales and transactions

* Sales transactions
* Transaction line items
* Customer transactions
* Inventory-linked transactions
* Dealer pricing
* Paid and due amounts
* Transaction status
* Invoice references
* Invoice printing
* Shopfront order integration

### Inventory

* Inventory transactions
* Inventory transaction line items
* Current stock tracking
* Product batches
* Batch quantities
* Inventory contributors
* Contributor quantities
* Damaged goods transactions
* Returned products
* Return dates
* Inventory-linked damage records
* Inventory-linked sales summaries

### Customer financial operations

* Customer ledgers
* Default ledgers
* Ledger status
* Ledger audits
* Customer ledger relationships
* Payment transactions
* Deposit slips
* Collection totals
* Due collections
* Damage-related deposit information

### Digital shopfronts

* Business shopfronts
* Public product catalogues
* Categories
* Category images
* Product images
* Shopping cart
* Customer orders
* Order line items
* Order invoices
* Due-status tracking
* Shopfront-to-transaction integration

### Data import and data quality

* Customer imports
* Product imports
* Import templates
* Import history
* Import conflict tracking
* Duplicate detection
* Duplicate detection logs
* Duplicate detection performance tracking
* Customer merge history
* Customer data-quality records

### Reporting and operational visibility

* Super-admin dashboard
* Business administration dashboards
* Inventory summaries
* Category sales summaries
* Profit and loss reporting
* Customer ledger visibility
* Collection information
* Transaction status reporting

## Role model

Orchestra separates platform-level administration from business-level operations.

```text
Super Admin
    │
    ├── Businesses
    ├── Business Administrators
    ├── Common Products
    ├── Common Categories
    ├── Common Units
    ├── Default Ledgers
    ├── Location Data
    ├── Roles
    └── Permissions
            │
            ▼
     Business Admin
            │
            ├── Staff
            ├── Products
            ├── Categories
            ├── Inventory
            ├── Transactions
            ├── Ledgers
            ├── Shopfront
            ├── Orders
            ├── Imports
            └── Operations
```

Authorization is implemented through Laravel middleware and Spatie Laravel Permission.

## Distribution data model

The core data model connects business identity, products, transactions, stock, customers, staff, and financial records.

```text
Business
   │
   ├── Users
   ├── Staff
   ├── Products
   │    ├── Categories
   │    ├── Units
   │    └── Batches
   │
   ├── Transactions
   │    └── Transaction Lines
   │
   ├── Inventory Transactions
   │    └── Inventory Transaction Lines
   │
   ├── Returns
   ├── Damage Transactions
   ├── Customer Ledgers
   │    └── Ledger Audits
   │
   ├── Payment Transactions
   ├── Deposit Slips
   │
   └── Shopfront
        └── Orders
             └── Order Lines
```

The repository's migration history shows this model evolving around businesses, products, transactions, inventory, staff, returns, damage, deposits, shopfront orders, subscriptions, DSR assignments, and salary-head functionality.

## Data quality

A distribution platform becomes difficult to operate when master data becomes inconsistent.

Orchestra therefore includes dedicated data-quality infrastructure rather than treating imports as a simple file upload.

```text
Import
  │
  ├── History
  ├── Conflict detection
  ├── Duplicate detection
  ├── Data quality records
  └── Merge history
```

Duplicate-detection functionality is implemented through dedicated service and model layers.

## Shopfront workflow

Orchestra includes a public-facing shopfront layer for digital ordering.

```text
Customer
   │
   ▼
Shopfront
   │
   ├── Browse categories
   ├── Browse products
   ├── Cart
   │
   ▼
Order
   │
   ├── Order lines
   ├── Due status
   └── Invoice
          │
          ▼
      Transaction
```

This allows a business to expose its catalog and accept digital orders while keeping those orders connected to its internal transaction model.

## Technology

Orchestra is built on the Laravel ecosystem.

### Backend

* PHP 8.2+
* Laravel 11
* Laravel Tinker
* League CSV
* Laravel Excel
* Intervention Image
* Simple QrCode
* ESC/POS PHP
* Spatie Laravel Permission

### Frontend

* Vite
* Tailwind CSS
* Alpine.js
* Axios
* PostCSS
* Autoprefixer
* Laravel Vite Plugin

### Development and testing

* Pest
* Pest Laravel plugin
* PHPUnit
* Laravel Pint
* Laravel Sail
* Laravel Debugbar
* Faker
* Mockery
* Collision

## Under the hood

Orchestra follows Laravel's conventional application architecture while separating administrative controllers, business controllers, domain models, services, imports, jobs, and database migrations.

```text
app/
├── Http/
│   └── Controllers/
│       ├── Admin/
│       ├── Auth/
│       └── SuperAdmin/
│
├── Models/
├── Services/
├── Imports/
├── Jobs/
├── Notifications/
└── Providers/

database/
├── factories/
├── migrations/
└── seeders/

resources/
├── css/
├── js/
└── views/

routes/
└── web.php

tests/
├── Feature/
└── Unit/
```

Current service-layer functionality includes customer-oriented services and duplicate-detection processing.

## Installation

### Requirements

* PHP 8.2 or later
* Composer
* Node.js
* npm
* MySQL or another Laravel-supported database
* Git

### Clone

```bash
git clone https://github.com/nasimubd/orchestra.git
cd orchestra
```

### Install PHP dependencies

```bash
composer install
```

### Install frontend dependencies

```bash
npm install
```

### Configure the environment

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure the database connection and other environment values in `.env`.

### Run migrations

```bash
php artisan migrate
```

### Seed the database

```bash
php artisan db:seed
```

### Build frontend assets

```bash
npm run build
```

### Start the application

```bash
php artisan serve
```

For frontend development:

```bash
npm run dev
```

## Development

Orchestra includes a Composer development command that runs the Laravel server, queue listener, log viewer, and Vite development server together:

```bash
composer run dev
```

The development workflow is equivalent to:

```text
Laravel server
      │
      ├── Queue listener
      ├── Pail logging
      └── Vite
```

## Testing

Run the test suite with:

```bash
php artisan test
```

or:

```bash
./vendor/bin/pest
```

Tests are organized into:

```text
tests/
├── Feature/
└── Unit/
```

## Versioning

Orchestra follows Semantic Versioning.

Current release:

```text
v1.0.0
```

Release numbering follows:

```text
MAJOR.MINOR.PATCH
```

## Project status

Orchestra v1.0.0 establishes the initial public distribution-platform baseline.

The current release focuses on the core operational foundation:

* business administration
* access control
* products
* inventory
* sales transactions
* customer ledgers
* collections
* returns and damage
* shopfront ordering
* subscriptions
* imports and data quality
* operational reporting

Future development can build on this foundation with additional field operations, mobile workflows, advanced logistics, integrations, automation, and intelligence.

## Contributing

Contributions are welcome.

When contributing:

1. Create a focused branch.
2. Keep changes scoped to the problem being solved.
3. Add or update tests where appropriate.
4. Run the test suite.
5. Verify frontend assets build successfully.
6. Explain the problem and implementation clearly in the pull request.

## Security

Do not commit:

* production credentials
* API keys
* payment credentials
* database passwords
* private environment files
* other secrets

For vulnerabilities that could affect users or deployments, use a private disclosure process rather than publishing exploitable details in a public issue.

## Roadmap

The architecture is intended to provide a foundation for a broader distribution operating platform.

Potential future areas include:

```text
Field Operations
      │
      ├── DSR workflows
      ├── Route operations
      ├── Delivery workflows
      └── Collection workflows

Operations
      │
      ├── Advanced warehouse workflows
      ├── Fulfillment
      ├── Stock planning
      └── Operational automation

Platform
      │
      ├── APIs
      ├── Integrations
      ├── Mobile applications
      └── Webhooks

Intelligence
      │
      ├── Advanced analytics
      ├── Forecasting
      ├── Optimization
      └── AI-assisted operations
```

These are roadmap directions, not claims about functionality currently included in `v1.0.0`.

## License

[MIT](./LICENSE) © 2026 ePATNER

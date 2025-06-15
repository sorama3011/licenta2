# Database Structure for Gusturi Românești E-commerce Platform

This directory contains the MySQL database schema for the Gusturi Românești e-commerce platform specializing in traditional Romanian products.

## Overview

The database is designed to support a complete e-commerce solution with the following features:

- User management with client and administrator roles
- Product catalog organized by categories and regions
- Shopping cart functionality
- Order processing and tracking
- Voucher and discount system
- Loyalty points program
- Product reviews
- Comprehensive logging system

## Main Tables

### User Management
- `utilizatori` - Stores user information with roles (Client, Administrator)

### Product Catalog
- `categorii` - Product categories
- `regiuni` - Romanian regions where products originate
- `produse` - Main product table with details
- `etichete` - Product tags (e.g., "artizanal", "fara-aditivi")
- `produse_etichete` - Many-to-many relationship between products and tags
- `informatii_nutritionale` - Nutritional information for products
- `imagini_produse` - Product images

### Shopping & Orders
- `cos_cumparaturi` - Shopping cart items
- `favorite` - Wishlist items
- `comenzi` - Order information
- `comenzi_produse` - Order items
- `vouchere` - Discount vouchers

### Customer Loyalty
- `puncte_fidelitate` - Loyalty points balance
- `tranzactii_puncte` - Loyalty points transactions

### Additional Features
- `recenzii` - Product reviews
- `jurnalizare` - System logging
- `newsletter_abonati` - Newsletter subscribers
- `contacte` - Contact form submissions
- `istoric_preturi` - Price change history
- `setari_site` - Site settings

## Views

The schema includes several useful views:
- `produse_active` - Active products with category and region
- `produse_cu_etichete` - Products with their tags
- `comenzi_recente` - Recent orders with customer info
- `stoc_produse` - Product stock status

## Stored Procedures

Key procedures include:
- `adauga_in_cos` - Add product to cart
- `plaseaza_comanda` - Place an order
- `actualizeaza_status_comanda` - Update order status

## Installation

To install the database:

1. Make sure you have MySQL 5.7+ installed
2. Run the schema.sql file:
   ```
   mysql -u your_username -p < schema.sql
   ```

## Entity Relationship Diagram

A simplified representation of the database relationships:

```
utilizatori 1──┐
               │
               ├──n comenzi 1──n comenzi_produse n──1 produse 1──┐
               │                                                  │
               ├──n cos_cumparaturi n──1 produse n──1 categorii   │
               │                                                  │
               ├──n favorite n──1 produse n──1 regiuni            │
               │                                                  │
               ├──n puncte_fidelitate                             │
               │                                                  │
               ├──n tranzactii_puncte                             │
               │                                                  │
               └──n recenzii n──1 produse n──n etichete           │
                                                                  │
                                                                  │
informatii_nutritionale 1──1 produse n──n imagini_produse         │
                                                                  │
                                                                  │
vouchere 1──n comenzi                                             │
                                                                  │
                                                                  │
jurnalizare n──1 utilizatori                                      │
                                                                  │
                                                                  │
istoric_preturi n──1 produse                                      │
```

## Notes

- All tables use InnoDB storage engine for transaction support
- UTF8MB4 character set is used for proper Romanian character support
- Foreign key constraints ensure data integrity
- Indexes are created for frequently queried columns
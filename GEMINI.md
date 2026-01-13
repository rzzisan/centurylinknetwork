# Project Context: ONU Stock Management System

## Overview
This project is a custom web-based application designed for an ISP (Internet Service Provider) to manage their inventory, staff, and customer connections. It is built using native PHP and MySQL, focusing on tracking stock (ONUs, Cables, Other Products), employee attendance/payroll, and new client connections.

## Technical Architecture
- **Language:** PHP (Native/Vanilla)
- **Database:** MySQL (accessed via PDO)
- **Frontend:** HTML5, CSS3, Bootstrap 5 (likely), JavaScript (jQuery + Vanilla)
- **Structure:**
  - Root: View files (pages) and configuration.
  - `api/`: Backend logic handling AJAX requests (CRUD operations).
  - `assets/`: Static resources (JS, CSS).

## Key Features & Modules

### 1. User & Role Management
- **Employees:** Manage staff accounts, salaries, and system access.
- **Roles:** Dynamic role-based access control (RBAC). Roles can be created and assigned to employees.
- **Authentication:** Session-based login/logout protection.

### 2. Inventory Management
- **ONU Stock:**
  - **Brands:** Manage supported ONU brands and prices.
  - **Stock Entries:** Track incoming ONU shipments.
  - **Assignments:** Track ONUs given to customers (links to specific MAC addresses and staff).
- **Cable (Fiber) Stock:**
  - **Drums:** Track fiber drums (Total meters, Current meters).
  - **Usage Logs:** Record cable usage per employee/day, broken down by customer.
- **Other Stock:**
  - General product inventory with 'In' (Purchase) and 'Out' (Usage/Sale) logs.

### 3. HR & Payroll
- **Attendance:** Daily attendance marking for employees.
- **Overtime:** Track overtime work, specifically when one employee covers for another.
- **Payroll:** Generate reports based on salary and attendance/overtime data.

### 4. Sales & CRM
- **New Connections:** Register new client installations, calculate costs, materials used, and track payments (Deposit/Due).
- **Client List:** View existing customer database (likely synced from a core ISP billing system).
- **Due Payments:** Track and collect outstanding balances for connections.

## Database
The database schema is documented in `database.md`. It relies on relational data connecting Employees, Inventory Items, and Customers.

## Directory Structure Highlights
- `config.php`: Database connection settings and global constants.
- `api/`: Contains specific API handlers like `connection_api.php`, `attendance_api.php`, `cable_api.php`, etc.
- `assets/`: JS files correspond 1:1 with main modules (e.g., `cable.js` for `cable_management.php`).

## Recent Changes
- Migrated role management from a hardcoded/column-based system to a dedicated `roles` table.
- Removed deprecated `is_admin()` checks in favor of the new role system (though some legacy code might still be in transition).

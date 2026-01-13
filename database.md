# Database Schema

## Authentication & Users

### `employees`
Stores user accounts for the system (staff/admins).
- `id` (INT, PK, AUTO_INCREMENT)
- `full_name` (VARCHAR)
- `username` (VARCHAR, Unique)
- `password` (VARCHAR, Hashed)
- `role_id` (INT, FK -> `roles.id`)
- `salary` (DECIMAL/INT)
- `include_in_attendance` (TINYINT/BOOLEAN) - Whether this user tracks attendance.
- `status` (TINYINT/BOOLEAN) - Active status?

### `roles`
Defines user roles and permissions.
- `id` (INT, PK, AUTO_INCREMENT)
- `name` (VARCHAR) - e.g., 'Admin', 'Manager', 'Technician'.

---

## Inventory Management (ONU)

### `brands`
Catalog of ONU brands.
- `id` (INT, PK, AUTO_INCREMENT)
- `name` (VARCHAR)
- `price` (DECIMAL)
- `created_at` (DATETIME)

### `stock_entries`
Records incoming stock of ONUs.
- `id` (INT, PK, AUTO_INCREMENT)
- `brand_id` (INT, FK -> `brands.id`)
- `quantity` (INT)
- `purchase_date` (DATE)

### `onu_assignments`
Records ONUs assigned/sold to customers.
- `id` (INT, PK, AUTO_INCREMENT)
- `assignment_date` (DATETIME)
- `customer_id` (INT) - Links to `clients` (likely `CustomerId`).
- `brand_name` (VARCHAR) - Denormalized brand name.
- `mac_address` (VARCHAR)
- `purpose` (VARCHAR) - Usage reason (e.g., 'New Connection', 'Replacement').
- `assigned_to` (VARCHAR/TEXT) - Employee ID(s) handling the assignment (used with FIND_IN_SET).
- `created_by` (INT, FK -> `employees.id`)

---

## Inventory Management (Cable/Fiber)

### `fiber_drums`
Tracks fiber cable drums.
- `id` (INT, PK, AUTO_INCREMENT)
- `drum_code` (VARCHAR)
- `year` (INT)
- `fiber_core` (INT)
- `company` (VARCHAR)
- `total_meter` (DECIMAL)
- `current_meter` (DECIMAL)
- `metering_direction` (VARCHAR)
- `start_meter_mark` (DECIMAL)
- `end_meter_mark` (DECIMAL)
- `created_at` (DATETIME)

### `cable_usage_logs`
Header table for cable usage sessions.
- `id` (INT, PK, AUTO_INCREMENT)
- `drum_id` (INT, FK -> `fiber_drums.id`)
- `employee_id` (INT, FK -> `employees.id`)
- `usage_date` (DATE)
- `meter_at_start` (DECIMAL)
- `meter_at_end` (DECIMAL)
- `meter_used` (DECIMAL)

### `cable_usage_details`
Breakdown of cable usage per customer.
- `id` (INT, PK, AUTO_INCREMENT)
- `usage_log_id` (INT, FK -> `cable_usage_logs.id`)
- `customer_id` (VARCHAR/INT)
- `meter_used` (DECIMAL)

---

## Inventory Management (General/Other)

### `products`
General products catalog.
- `id` (INT, PK, AUTO_INCREMENT)
- `name` (VARCHAR)
- `company` (VARCHAR)
- `current_stock` (INT)

### `product_stock_logs`
Transaction history for general products.
- `id` (INT, PK, AUTO_INCREMENT)
- `product_id` (INT, FK -> `products.id`)
- `log_date` (DATE)
- `log_type` (ENUM: 'in', 'out')
- `quantity` (INT)
- `unit_price` (DECIMAL, NULL for 'out')
- `total_price` (DECIMAL, NULL for 'out')
- `employee_id` (INT, FK -> `employees.id`, NULL for 'in')
- `reference_customer_id` (VARCHAR, NULL for 'in')

---

## HR & Attendance

### `attendance`
Daily attendance records.
- `id` (INT, PK, AUTO_INCREMENT)
- `employee_id` (INT, FK -> `employees.id`)
- `attendance_date` (DATE)
- `status` (VARCHAR) - e.g., 'Present', 'Absent', 'Leave'.

### `overtime`
Overtime tracking, often covering for absent employees.
- `id` (INT, PK, AUTO_INCREMENT)
- `overtime_date` (DATE)
- `absent_employee_id` (INT, FK -> `employees.id`) - The employee being covered.
- `overtime_employee_id` (INT, FK -> `employees.id`) - The employee working overtime.
- `amount` (DECIMAL)

---

## CRM & Sales

### `clients`
Read-only view or sync of ISP customers.
- `CustomerHeaderId` (INT, PK?)
- `CustomerId` (INT, Display ID)
- `CustomerName` (VARCHAR)
- `UserName` (VARCHAR)
- `MobileNumber` (VARCHAR)
- `ZoneName` (VARCHAR)
- `Package` (VARCHAR)
- `MonthlyBill` (DECIMAL)

### `new_connections`
Tracks new installation orders/sales.
- `id` (INT, PK, AUTO_INCREMENT)
- `connection_date` (DATE)
- `customer_id_code` (VARCHAR)
- `customer_name` (VARCHAR)
- `mobile_number` (VARCHAR)
- `address` (TEXT)
- `connection_type` (VARCHAR)
- `materials_used` (TEXT)
- `total_price` (DECIMAL)
- `deposit_amount` (DECIMAL)
- `due_amount` (DECIMAL)
- `order_taker_id` (INT, FK -> `employees.id`)
- `money_with_id` (INT, FK -> `employees.id`)

### `due_payments`
Payment history for connections.
- `id` (INT, PK, AUTO_INCREMENT)
- `connection_id` (INT, FK -> `new_connections.id`)
- `payment_date` (DATE)
- `paid_amount` (DECIMAL)
- `discount_amount` (DECIMAL)
- `collected_by_id` (INT, FK -> `employees.id`)

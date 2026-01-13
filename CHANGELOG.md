# Changelog

## [Unreleased]

### Added
- Created `roles` table in the database.
- Created `role_management.php` page to manage roles.
- Created `api/roles_api.php` to handle role management API requests.
- Created `assets/roles.js` to handle role management frontend logic.

### Changed
- Updated `add_employee.php` to use the `roles` table to populate the roles dropdown.
- Updated `employee_settings.php` to use the `roles` table to populate the roles dropdown.
- Refactored the role management feature to use the `roles` table instead of the `employees` table.
- Created a migration script to migrate the existing roles from the `employees` table to the `roles` table.

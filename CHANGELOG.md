# Changelog

## [Unreleased]

### Added
- **New Connection:** Display ONU Brand and MAC Address in the main list and search results (under 'materials used' column).

### Added (Previous)
- Created `roles` table in the database.
- Created `role_management.php` page to manage roles.
- Created `api/roles_api.php` to handle role management API requests.
- Created `assets/roles.js` to handle role management frontend logic.

### Changed
- Updated `add_employee.php` to use the `roles` table to populate the roles dropdown.
- Updated `employee_settings.php` to use the `roles` table to populate the roles dropdown.
- Refactored the role management feature to use the `roles` table instead of the `employees` table.
- Created a migration script to migrate the existing roles from the `employees` table to the `roles` table.

## [2026-01-12]

### Added
- **New Connection:** 'All' option in pagination dropdown to show all records at once.
- **Roles:** Added `roles` table and migrated permission logic to `api/roles_api.php` and `role_management.php`.

### Fixed
- **New Connection:** Fixed issue where pagination limit was applied even when 'All' was selected.

## [2025-01-10]
- Initial system setup and migration to Git.
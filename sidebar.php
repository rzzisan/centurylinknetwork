<?php
// Determine which menu is active
$current_page = basename($_SERVER['PHP_SELF']);
$employee_pages = ['employee_settings.php', 'add_employee.php', 'role_management.php'];
$is_employee_page_active = in_array($current_page, $employee_pages);
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3>ONU ম্যানেজমেন্ট</h3>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i> ড্যাশবোর্ড
            </a>
        </li>
        
        <li>
            <a href="#onuSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo $is_onu_page_active ? 'true' : 'false'; ?>" class="dropdown-toggle <?php echo $is_onu_page_active ? '' : 'collapsed'; ?>">
                <i class="bi bi-hdd-rack-fill"></i> অনু ম্যানেজমেন্ট
            </a>
            <ul class="collapse list-unstyled <?php echo $is_onu_page_active ? 'show' : ''; ?>" id="onuSubmenu">
                <li>
                    <a href="onu_management.php" class="<?php echo $current_page == 'onu_management.php' ? 'active' : ''; ?>"><span><i class="bi bi-plus-circle-fill"></i> ONU বরাদ্দ</span></a>
                </li>
                <li>
                    <a href="brands.php" class="<?php echo $current_page == 'brands.php' ? 'active' : ''; ?>"><span><i class="bi bi-tags-fill"></i> ONU ব্র্যান্ড</span></a>
                </li>
                <li>
                    <a href="stock.php" class="<?php echo $current_page == 'stock.php' ? 'active' : ''; ?>"><span><i class="bi bi-box-seam-fill"></i> ONU স্টক</span></a>
                </li>
            </ul>
        </li>

        <li>
            <a href="new_connection.php" class="<?php echo $current_page == 'new_connection.php' ? 'active' : ''; ?>">
                <i class="bi bi-person-plus-fill"></i> নতুন কানেকশন
            </a>
        </li>
        <li>
            <a href="cable_management.php" class="<?php echo $current_page == 'cable_management.php' ? 'active' : ''; ?>">
                <i class="bi bi-bezier"></i> ক্যাবল ম্যানেজমেন্ট
            </a>
        </li>
        <li>
            <a href="other_stock.php" class="<?php echo $current_page == 'other_stock.php' ? 'active' : ''; ?>">
                <i class="bi bi-hdd-stack-fill"></i> অন্যান্য স্টক
            </a>
        </li>
        <li>
            <a href="attendance_dashboard.php" class="<?php echo $current_page == 'attendance_dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-calendar-check-fill"></i> হাজিরা ও বেতন
            </a>
        </li>
        
        
        <li>
            <a href="#employeeSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo $is_employee_page_active ? 'true' : 'false'; ?>" class="dropdown-toggle <?php echo $is_employee_page_active ? '' : 'collapsed'; ?>">
                <i class="bi bi-people-fill"></i> কর্মচারী ম্যানেজমেন্ট
            </a>
            <ul class="collapse list-unstyled <?php echo $is_employee_page_active ? 'show' : ''; ?>" id="employeeSubmenu">
                <li>
                    <a href="employee_settings.php" class="<?php echo $current_page == 'employee_settings.php' ? 'active' : ''; ?>"><span><i class="bi bi-gear-fill"></i> কর্মচারী সেটিংস</span></a>
                </li>
                <li>
                    <a href="add_employee.php" class="<?php echo $current_page == 'add_employee.php' ? 'active' : ''; ?>"><span><i class="bi bi-person-plus-fill"></i> নতুন কর্মচারী</span></a>
                </li>
                <li>
                    <a href="role_management.php" class="<?php echo $current_page == 'role_management.php' ? 'active' : ''; ?>"><span><i class="bi bi-person-badge-fill"></i> ভূমিকা ব্যবস্থাপনা</span></a>
                </li>
            </ul>
        </li>
        
		   <li>
            <a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="bi bi-person-circle"></i> প্রোফাইল
            </a>
        </li>

    </ul>
</div>
<div class="content-wrapper">
    <header class="header">
        <i class="bi bi-list sidebar-toggle" id="sidebar-toggle"></i>
        <div class="user-info">
            <span>স্বাগতম, <?php echo htmlspecialchars($_SESSION['employee_name']); ?>!</span>
            <a href="logout.php" class="btn btn-danger btn-sm">লগ আউট</a>
        </div>
    </header>
    <main class="main-content">
        <style>
            .sidebar-menu ul a {
                padding-left: 45px !important;
                background-color: #212f3d;
            }
            .sidebar-menu ul a:hover,
            .sidebar-menu ul a.active {
                background-color: #1a2531 !important;
            }
            .sidebar-menu a.dropdown-toggle::after {
                float: right;
                margin-top: 8px;
            }
             .sidebar-menu a.dropdown-toggle.collapsed::after {
                transform: rotate(-90deg);
            }
        </style>
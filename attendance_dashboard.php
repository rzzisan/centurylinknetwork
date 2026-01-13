<?php
require_once 'config.php';
redirect_if_not_logged_in();

$page_title = "হাজিরা ও বেতন ম্যানেজমেন্ট";
include 'header.php';
include 'sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-calendar-check"></i> হাজিরা ও বেতন ম্যানেজমেন্ট</h2>
    </div>

    <div class="row g-4">

        <div class="col-md-4">
            <div class="card text-center h-100 shadow-sm">
                <div class="card-body">
                    <i class="bi bi-people-fill fs-1 text-primary"></i>
                    <h5 class="card-title mt-3">কর্মচারী ম্যানেজমেন্ট</h5>
                    <p class="card-text">কর্মচারীদের তালিকা, বেতন এবং হাজিরা স্ট্যাটাস ম্যানেজ করুন।</p>
                    <a href="employee_settings.php" class="btn btn-primary">ম্যানেজ করুন</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center h-100 shadow-sm">
                <div class="card-body">
                    <i class="bi bi-calendar-plus-fill fs-1 text-success"></i>
                    <h5 class="card-title mt-3">দৈনিক হাজিরা দিন</h5>
                    <p class="card-text">আজকের তারিখের জন্য কর্মচারীদের হাজিরা এন্ট্রি করুন।</p>
                    <a href="attendance_entry.php" class="btn btn-success">হাজিরা এন্ট্রি</a>
                </div>
            </div>
        </div>
         <div class="col-md-4">
            <div class="card text-center h-100 shadow-sm">
                <div class="card-body">
                    <i class="bi bi-person-plus-fill fs-1 text-warning"></i>
                    <h5 class="card-title mt-3">ওভারটাইম ও অন্যান্য</h5>
                    <p class="card-text">ওভারটাইম এবং হাফ-ডিউটি এন্ট্রি করুন।</p>
                    <a href="overtime_entry.php" class="btn btn-warning">এন্ট্রি করুন</a>
                </div>
            </div>
        </div>
         <div class="col-md-12 mt-4">
            <div class="card text-center h-100 shadow-sm">
                <div class="card-body">
                    <i class="bi bi-file-earmark-bar-graph-fill fs-1 text-info"></i>
                    <h5 class="card-title mt-3">রিপোর্ট ও বেতন শিট</h5>
                    <p class="card-text">মাসিক রিপোর্ট, বেতনের হিসাব এবং বেতন শিট তৈরি করুন।</p>
                    <a href="payroll_report.php" class="btn btn-info">রিপোর্ট দেখুন</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php';
?>
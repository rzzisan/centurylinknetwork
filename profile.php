<?php
require_once 'config.php';
redirect_if_not_logged_in();

$page_title = "প্রোফাইল ম্যানেজমেন্ট";
include 'header.php';
include 'sidebar.php';

?>

<div class="container-fluid">
    <h2><i class="bi bi-person-circle"></i> প্রোফাইল ম্যানেজমেন্ট</h2>
    <p class="text-muted">এখান থেকে আপনি আপনার লগইন পাসওয়ার্ড পরিবর্তন করতে পারবেন।</p>
    
    <div class="card shadow-sm">
        <div class="card-header">
            <h4>পাসওয়ার্ড পরিবর্তন করুন</h4>
        </div>
        <div class="card-body">
            <form id="change-password-form">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="current_password" class="form-label"><b>বর্তমান পাসওয়ার্ড</b></label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="col-md-6">
                        <label for="new_password" class="form-label"><b>নতুন পাসওয়ার্ড</b></label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label"><b>নতুন পাসওয়ার্ড নিশ্চিত করুন</b></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary">পাসওয়ার্ড পরিবর্তন করুন</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/profile.js"></script>

<?php
include 'footer.php';
?>
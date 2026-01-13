document.addEventListener('DOMContentLoaded', function() {
    const api_url = 'profile_api.php'; // Use the new API file
    const changePasswordForm = document.getElementById('change-password-form');

    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword !== confirmPassword) {
                alert('নতুন পাসওয়ার্ড এবং কনফার্ম পাসওয়ার্ড মিলছে না।');
                return;
            }

            const formData = new FormData(this);
            formData.append('action', 'change_password');

            fetch(api_url, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        changePasswordForm.reset();
                    }
                });
        });
    }
});
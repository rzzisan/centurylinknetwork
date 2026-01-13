document.addEventListener('DOMContentLoaded', function() {
    const api_url = 'api/roles_api.php';

    const rolesTable = document.getElementById('roles-table');
    if (rolesTable) {
        const addRoleForm = document.getElementById('add-role-form');
        const editRoleModalElement = document.getElementById('edit-role-modal');
        const editRoleModal = new bootstrap.Modal(editRoleModalElement);
        const editRoleForm = document.getElementById('edit-role-form');

        addRoleForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'add_role');

            fetch(api_url, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) window.location.reload();
                });
        });

        rolesTable.addEventListener('click', function(e) {
            const target = e.target.closest('.btn-edit, .btn-delete');
            if (!target) return;

            const id = target.dataset.id;
            const name = target.dataset.name;

            if (target.classList.contains('btn-edit')) {
                document.getElementById('edit_role_id').value = id;
                document.getElementById('edit_role_name').value = name;
                editRoleModal.show();
            }

            if (target.classList.contains('btn-delete')) {
                if (confirm(`আপনি কি "${name}" ভূমিকাটি ডিলিট করতে নিশ্চিত?`)) {
                    const formData = new FormData();
                    formData.append('action', 'delete_role');
                    formData.append('id', id);

                    fetch(api_url, { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            alert(data.message);
                            if (data.success) window.location.reload();
                        });
                }
            }
        });

        editRoleForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'edit_role');
            fetch(api_url, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) window.location.reload();
                });
        });
    }
});
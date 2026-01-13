/* =================================================================
 * File: assets/management.js (UPDATED FOR BOOTSTRAP)
 * Description: JavaScript logic for brand and stock management pages.
 * ================================================================= */

document.addEventListener('DOMContentLoaded', function() {
    const api_url = 'api.php';

    // --- Brand Management Logic ---
    const brandsTable = document.getElementById('brands-table');
    if (brandsTable) {
        const addBrandForm = document.getElementById('add-brand-form');
        // Bootstrap Modal for editing brand
        const editBrandModalElement = document.getElementById('edit-brand-modal');
        const editBrandModal = new bootstrap.Modal(editBrandModalElement);
        const editBrandForm = document.getElementById('edit-brand-form');

        // Handle Add Brand
        addBrandForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'save_brand');

            fetch(api_url, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) window.location.reload();
                });
        });

        // Handle Edit/Delete clicks
        brandsTable.addEventListener('click', function(e) {
            const target = e.target.closest('.btn-edit, .btn-delete');
            if (!target) return;

            // if (!is_admin) return; // This check is removed

            const id = target.dataset.id;

            // Edit button
            if (target.classList.contains('btn-edit')) {
                const formData = new FormData();
                formData.append('action', 'get_brand');
                formData.append('id', id);

                fetch(api_url, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('edit_brand_id').value = data.data.id;
                            document.getElementById('edit_brand_name').value = data.data.name;
                            document.getElementById('edit_brand_price').value = data.data.price;
                            editBrandModal.show();
                        } else {
                            alert(data.message);
                        }
                    });
            }

            // Delete button
            if (target.classList.contains('btn-delete')) {
                if (confirm('আপনি কি এই ব্র্যান্ডটি ডিলিট করতে নিশ্চিত?')) {
                    const formData = new FormData();
                    formData.append('action', 'delete_brand');
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

        // Handle Edit Brand Form Submission
        editBrandForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'save_brand');
            fetch(api_url, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) window.location.reload();
                });
        });
    }


    // --- Stock Entry Management Logic ---
    const stockTable = document.getElementById('stock-table');
    if (stockTable) {
        const addStockForm = document.getElementById('add-stock-form');
        // Bootstrap modal for editing stock
        const editStockModalElement = document.getElementById('edit-stock-modal');
        const editStockModal = new bootstrap.Modal(editStockModalElement);
        const editStockForm = document.getElementById('edit-stock-form');

        // Handle Add Stock Entry
        addStockForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'save_stock_entry');
            fetch(api_url, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) window.location.reload();
                });
        });

        // Handle Edit/Delete clicks
        stockTable.addEventListener('click', function(e) {
            const target = e.target.closest('.btn-edit, .btn-delete');
            if (!target) return;

            // if (!is_admin) return; // This check is removed

            const id = target.dataset.id;

            // Edit button
            if (target.classList.contains('btn-edit')) {
                const formData = new FormData();
                formData.append('action', 'get_stock_entry');
                formData.append('id', id);

                fetch(api_url, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('edit_stock_id').value = data.data.id;
                            document.getElementById('edit_stock_brand_id').value = data.data.brand_id;
                            document.getElementById('edit_stock_quantity').value = data.data.quantity;
                            document.getElementById('edit_stock_purchase_date').value = data.data.purchase_date;
                            editStockModal.show();
                        } else {
                            alert(data.message);
                        }
                    });
            }

            // Delete button
            if (target.classList.contains('btn-delete')) {
                if (confirm('আপনি কি এই স্টক এন্ট্রিটি ডিলিট করতে নিশ্চিত?')) {
                    const formData = new FormData();
                    formData.append('action', 'delete_stock_entry');
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

        // Handle Edit Stock Form Submission
        editStockForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'save_stock_entry');
            fetch(api_url, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) window.location.reload();
                });
        });
    }
});
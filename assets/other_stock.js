document.addEventListener('DOMContentLoaded', function() {
    const api_url = 'api/other_stock_api.php';

    // Generic form submission handler
    function handleFormSubmit(formId, action, modalInstance) {
        const form = document.getElementById(formId);
        const submitButton = document.querySelector(`button[type="submit"][form="${formId}"]`);

        if (form && submitButton) {
            const originalButtonHTML = submitButton.innerHTML; // Store original button text

            submitButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (!form.checkValidity()) {
                    // This is a trick to trigger the browser's built-in validation UI
                    const tempSubmit = document.createElement('button');
                    tempSubmit.type = 'submit';
                    tempSubmit.style.display = 'none';
                    form.appendChild(tempSubmit);
                    tempSubmit.click();
                    form.removeChild(tempSubmit);
                    return;
                }

                const formData = new FormData(form);
                formData.append('action', action);

                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> প্রসেস হচ্ছে...';

                fetch(api_url, { method: 'POST', body: formData })
                    .then(res => {
                        if (!res.ok) {
                            throw new Error(`HTTP error! status: ${res.status}`);
                        }
                        return res.json();
                    })
                    .then(data => {
                        if(data.message) {
                            alert(data.message);
                        }
                        
                        if (data.success) {
                            if (modalInstance) {
                                // Hide the modal and reload the page once it's hidden
                                modalInstance.hide();
                                const modalEl = document.getElementById(modalInstance._element.id);
                                modalEl.addEventListener('hidden.bs.modal', function () {
                                    window.location.reload();
                                }, { once: true });
                            } else {
                                window.location.reload();
                            }
                        } else {
                            // Re-enable button on API-level failure
                            submitButton.disabled = false;
                            submitButton.innerHTML = originalButtonHTML;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('একটি ত্রুটি ঘটেছে। অনুগ্রহ করে কনসোল চেক করুন।');
                        // Re-enable button on network/parsing failure
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonHTML;
                    });
            });
        }
    }

    // Initialize Modals safely
    const addProductModalEl = document.getElementById('add-product-modal');
    const stockInModalEl = document.getElementById('stock-in-modal');
    const stockOutModalEl = document.getElementById('stock-out-modal');
    const editLogModalEl = document.getElementById('edit-log-modal');

    const addProductModal = addProductModalEl ? new bootstrap.Modal(addProductModalEl) : null;
    const stockInModal = stockInModalEl ? new bootstrap.Modal(stockInModalEl) : null;
    const stockOutModal = stockOutModalEl ? new bootstrap.Modal(stockOutModalEl) : null;
    const editLogModal = editLogModalEl ? new bootstrap.Modal(editLogModalEl) : null;

    // Initialize Form Handlers if modals exist
    if (addProductModal) handleFormSubmit('add-product-form', 'save_product', addProductModal);
    if (stockInModal) handleFormSubmit('stock-in-form', 'save_stock_in', stockInModal);
    if (stockOutModal) handleFormSubmit('stock-out-form', 'save_stock_out', stockOutModal);
    if (editLogModal) handleFormSubmit('edit-log-form', 'update_stock_log', editLogModal);


    // Auto-calculate total price for stock-in
    const inQuantity = document.getElementById('in_quantity');
    const inUnitPrice = document.getElementById('in_unit_price');
    const inTotalPrice = document.getElementById('in_total_price');

    function calculateTotal() {
        if (!inQuantity || !inUnitPrice || !inTotalPrice) return;
        const quantity = parseFloat(inQuantity.value) || 0;
        const unitPrice = parseFloat(inUnitPrice.value) || 0;
        inTotalPrice.value = (quantity * unitPrice).toFixed(2);
    }
    if (inQuantity) inQuantity.addEventListener('input', calculateTotal);
    if (inUnitPrice) inUnitPrice.addEventListener('input', calculateTotal);

    // Edit and Delete Logic
    const logTableBody = document.getElementById('log-table-body');
    if (logTableBody) {
        logTableBody.addEventListener('click', function(e) {
            const target = e.target.closest('.btn-edit, .btn-delete');
            if (!target) return;

            const logId = target.dataset.id;

            if (target.classList.contains('btn-edit')) {
                const formData = new FormData();
                formData.append('action', 'get_stock_log');
                formData.append('id', logId);

                fetch(api_url, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const log = data.data;
                            document.getElementById('edit_log_id').value = log.id;
                            document.getElementById('edit_log_date').value = log.log_date;
                            document.getElementById('edit_product_id').value = log.product_id;
                            document.getElementById('edit_quantity').value = log.quantity;

                            const stockInFields = document.getElementById('edit-stock-in-fields');
                            const stockOutFields = document.getElementById('edit-stock-out-fields');

                            if (log.log_type === 'in') {
                                stockInFields.style.display = 'block';
                                stockOutFields.style.display = 'none';
                                document.getElementById('edit_unit_price').value = log.unit_price;
                            } else {
                                stockInFields.style.display = 'none';
                                stockOutFields.style.display = 'block';
                                document.getElementById('edit_employee_id').value = log.employee_id;
                                document.getElementById('edit_reference_customer_id').value = log.reference_customer_id;
                            }
                            if(editLogModal) editLogModal.show();
                        } else {
                            alert(data.message);
                        }
                    });
            }

            if (target.classList.contains('btn-delete')) {
                if (confirm('আপনি কি এই লগটি ডিলিট করতে নিশ্চিত? এটি স্টকের পরিমাণ পরিবর্তন করে দেবে।')) {
                    const formData = new FormData();
                    formData.append('action', 'delete_stock_log');
                    formData.append('id', logId);

                    fetch(api_url, { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            alert(data.message);
                            if (data.success) {
                                window.location.reload();
                            }
                        });
                }
            }
        });
    }
});
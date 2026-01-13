/* =================================================================
 * File: assets/main.js (UPDATED FOR BOOTSTRAP)
 * Description: JavaScript logic for the main dashboard.
 * ================================================================= */

document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap Modal instance
    const formModalElement = document.getElementById('form-modal');
    if (!formModalElement) return;
    const formModal = new bootstrap.Modal(formModalElement);

    const modalTitle = document.getElementById('modal-title');
    const addNewBtn = document.getElementById('add-new-btn');
    const onuForm = document.getElementById('onu-form');
    const recordIdField = document.getElementById('record_id');
    const customerIdField = document.getElementById('customer_id');
    const dateField = document.getElementById('assignment_date');
    const brandSelect = document.getElementById('brand_name');
    const tableBody = document.querySelector('table tbody');

    const api_url = 'api.php';

    function loadBrandsForModal(selectedBrand = '') {
        fetch(`${api_url}?action=get_stock_brands`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    brandSelect.innerHTML = '<option value="">ব্র্যান্ড নির্বাচন করুন</option>';
                    // If editing, and the original brand is out of stock, add it to the list
                    if (selectedBrand && !data.data.includes(selectedBrand)) {
                        const option = document.createElement('option');
                        option.value = selectedBrand;
                        option.textContent = `${selectedBrand} (স্টকে নেই)`;
                        brandSelect.appendChild(option);
                    }
                    data.data.forEach(brand => {
                        const option = document.createElement('option');
                        option.value = brand;
                        option.textContent = brand;
                        brandSelect.appendChild(option);
                    });
                    if (selectedBrand) {
                        brandSelect.value = selectedBrand;
                    }
                }
            });
    }

    addNewBtn.onclick = function() {
        onuForm.reset();
        recordIdField.value = '';
        modalTitle.textContent = 'নতুন ONU বরাদ্দ করুন';
        loadBrandsForModal();
        // Set current date and time
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        dateField.value = now.toISOString().slice(0, 16);
        formModal.show();
    }

    onuForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'save_record');
        fetch(api_url, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('ত্রুটি: ' + data.message);
                }
            });
    });

    tableBody.addEventListener('click', function(e) {
        const target = e.target.closest('.btn-edit, .btn-delete');
        if (!target) return;

        if (!is_admin) return;

        const id = target.dataset.id;

        if (target.classList.contains('btn-edit')) {
            const formData = new FormData();
            formData.append('action', 'get_record');
            formData.append('id', id);
            fetch(api_url, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        onuForm.reset();
                        const record = data.data;
                        loadBrandsForModal(record.brand_name);
                        recordIdField.value = record.id;
                        dateField.value = record.assignment_date.slice(0, 16);
                        customerIdField.value = record.customer_id;
                        document.getElementById('mac_address').value = record.mac_address;
                        document.getElementById('purpose').value = record.purpose;

                        // Reset checkboxes
                        document.querySelectorAll('input[name="assigned_to[]"]').forEach(checkbox => checkbox.checked = false);

                        // Check assigned employees
                        const assigned = record.assigned_to.split(', ');
                        document.querySelectorAll('input[name="assigned_to[]"]').forEach(checkbox => {
                            if (assigned.includes(checkbox.value)) {
                                checkbox.checked = true;
                            }
                        });
                        modalTitle.textContent = 'তথ্য এডিট করুন';
                        formModal.show();
                    }
                });
        }

        if (target.classList.contains('btn-delete')) {
            if (confirm('আপনি কি এই রেকর্ডটি ডিলিট করতে নিশ্চিত?')) {
                const formData = new FormData();
                formData.append('action', 'delete_record');
                formData.append('id', id);
                fetch(api_url, { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            window.location.reload();
                        } else {
                            alert('ত্রুটি: ' + data.message);
                        }
                    });
            }
        }
    });

    // Duplicate Customer ID Check
    customerIdField.addEventListener('blur', function() {
        const customerId = this.value;
        if (customerId && /^\d{4}$/.test(customerId)) {
            const formData = new FormData();
            formData.append('action', 'check_customer');
            formData.append('customer_id', customerId);
            formData.append('record_id', recordIdField.value);

            fetch(api_url, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.found) {
                        const prevData = data.data;
                        const assignedDate = new Date(prevData.assignment_date).toLocaleString('bn-BD', {
                            year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
                        });

                        let warningMessage = '⚠️ সতর্কবার্তা!\n\n';
                        warningMessage += `এই কাস্টমার আইডি (${customerId}) দিয়ে ஏற்கனவே একটি ONU বরাদ্দ করা হয়েছে।\n\n`;
                        warningMessage += '--- পূর্বের বিবরণ ---\n';
                        warningMessage += `ব্র্যান্ড: ${prevData.brand_name}\n`;
                        warningMessage += `MAC Address: ${prevData.mac_address}\n`;
                        warningMessage += `বরাদ্দ গ্রহীতা: ${prevData.assigned_to}\n`;
                        warningMessage += `তারিখ: ${assignedDate}\n`;

                        alert(warningMessage);
                        customerIdField.classList.add('is-invalid');
                    } else {
                         customerIdField.classList.remove('is-invalid');
                    }
                });
        }
    });

     customerIdField.addEventListener('focus', function() {
        this.classList.remove('is-invalid');
    });
});
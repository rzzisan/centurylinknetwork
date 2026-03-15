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
    const purposeField = document.getElementById('purpose');
    const newConnectionSection = document.getElementById('new-connection-section');
    const tableBody = document.querySelector('table tbody');
    const ncDateField = document.getElementById('nc_connection_date');
    const ncCustomerIdField = document.getElementById('nc_customer_id_code');
    const ncTotalField = document.getElementById('nc_total_price');
    const ncDepositField = document.getElementById('nc_deposit_amount');
    const ncDueDisplay = document.getElementById('nc_due_display');

    function setNewConnectionRequired(isRequired) {
        const requiredIds = [
            'nc_connection_date',
            'nc_customer_id_code',
            'nc_customer_name',
            'nc_mobile_number',
            'nc_address',
            'nc_total_price',
            'nc_deposit_amount',
            'nc_order_taker_id',
            'nc_money_with_id'
        ];

        requiredIds.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.required = isRequired;
        });
    }

    function calculateNewConnectionDue() {
        const total = parseFloat(ncTotalField.value) || 0;
        const deposit = parseFloat(ncDepositField.value) || 0;
        ncDueDisplay.value = (total - deposit).toFixed(2);
    }

    function toggleNewConnectionSection() {
        const isNewConnectionPurpose = purposeField.value === 'New Connection';
        newConnectionSection.style.display = isNewConnectionPurpose ? 'block' : 'none';
        setNewConnectionRequired(isNewConnectionPurpose);
    }

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
        ncDateField.value = now.toISOString().slice(0, 10);
        ncCustomerIdField.value = '';
        ncDueDisplay.value = '';
        toggleNewConnectionSection();
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

    purposeField.addEventListener('change', toggleNewConnectionSection);

    customerIdField.addEventListener('input', function() {
        ncCustomerIdField.value = this.value;
    });

    dateField.addEventListener('change', function() {
        if (!this.value) return;
        ncDateField.value = this.value.slice(0, 10);
    });

    ncTotalField.addEventListener('input', calculateNewConnectionDue);
    ncDepositField.addEventListener('input', calculateNewConnectionDue);

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
                        ncCustomerIdField.value = record.customer_id;
                        ncDateField.value = record.assignment_date ? record.assignment_date.slice(0, 10) : '';

                        // Reset checkboxes
                        document.querySelectorAll('input[name="assigned_to[]"]').forEach(checkbox => checkbox.checked = false);

                        // Check assigned employees
                        const assigned = record.assigned_to.split(', ');
                        document.querySelectorAll('input[name="assigned_to[]"]').forEach(checkbox => {
                            if (assigned.includes(checkbox.value)) {
                                checkbox.checked = true;
                            }
                        });

                        document.querySelectorAll('input[name="nc_materials[]"]').forEach(checkbox => checkbox.checked = false);
                        if (record.new_connection) {
                            document.getElementById('nc_connection_date').value = record.new_connection.connection_date || '';
                            document.getElementById('nc_customer_id_code').value = record.new_connection.customer_id_code || record.customer_id || '';
                            document.getElementById('nc_customer_name').value = record.new_connection.customer_name || '';
                            document.getElementById('nc_mobile_number').value = record.new_connection.mobile_number || '';
                            document.getElementById('nc_address').value = record.new_connection.address || '';
                            document.getElementById('nc_connection_type').value = record.new_connection.connection_type || 'নতুন লাইন';
                            document.getElementById('nc_total_price').value = record.new_connection.total_price || '';
                            document.getElementById('nc_deposit_amount').value = record.new_connection.deposit_amount || '';
                            document.getElementById('nc_order_taker_id').value = record.new_connection.order_taker_id || '';
                            document.getElementById('nc_money_with_id').value = record.new_connection.money_with_id || '';

                            if (record.new_connection.materials_used) {
                                record.new_connection.materials_used.split(',').map(v => v.trim()).forEach(val => {
                                    const cb = document.querySelector(`input[name="nc_materials[]"][value="${val}"]`);
                                    if (cb) cb.checked = true;
                                });
                            }
                        }

                        toggleNewConnectionSection();
                        calculateNewConnectionDue();
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

    toggleNewConnectionSection();
});
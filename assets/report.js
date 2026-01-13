document.addEventListener('DOMContentLoaded', function() {
    const api_url = 'api.php';
    
    const usageAccordion = document.getElementById('usageAccordion');
    if (!usageAccordion) return;

    const editLogModalEl = document.getElementById('edit-log-modal');
    const editLogModal = new bootstrap.Modal(editLogModalEl);
    const editLogForm = document.getElementById('edit-log-form');
    const editDetailsContainer = document.getElementById('edit-details-container');
    const editAddDetailBtn = document.getElementById('edit-add-detail-btn');

    // Function to add a new detail row in the edit modal
    function addEditDetailRow(customerId = '', meterUsed = '') {
        const row = document.createElement('div');
        row.className = 'row g-3 mb-2 align-items-center';
        row.innerHTML = `
            <div class="col-md-5">
                <input type="text" name="customer_id[]" class="form-control" placeholder="কাস্টমার আইডি" value="${customerId}" required>
            </div>
            <div class="col-md-5">
                <input type="number" name="meter_used_detail[]" class="form-control" placeholder="খরচ (মিটার)" value="${meterUsed}" min="1" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-sm btn-danger remove-detail-btn w-100">মুছুন</button>
            </div>
        `;
        editDetailsContainer.appendChild(row);
    }

    // Add new detail row in modal
    editAddDetailBtn.addEventListener('click', () => addEditDetailRow());

    // Remove detail row from modal
    editDetailsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-detail-btn')) {
            e.target.closest('.row').remove();
        }
    });


    // Event listener for Edit and Delete buttons
    usageAccordion.addEventListener('click', function(e) {
        const target = e.target.closest('.btn-edit-log, .btn-delete-log');
        if (!target) return;

        const logId = target.dataset.logId;

        // Handle Edit button click
        if (target.classList.contains('btn-edit-log')) {
            const formData = new FormData();
            formData.append('action', 'get_cable_usage_log');
            formData.append('log_id', logId);

            fetch(api_url, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const log = data.data;
                        document.getElementById('edit_log_id').value = log.id;
                        document.getElementById('edit_usage_date').value = log.usage_date;
                        document.getElementById('edit_employee_id').value = log.employee_id;
                        document.getElementById('edit_meter_at_start').value = log.meter_at_start;

                        editDetailsContainer.innerHTML = ''; // Clear previous details
                        if (log.details.length > 0) {
                            log.details.forEach(detail => {
                                addEditDetailRow(detail.customer_id, detail.meter_used);
                            });
                        } else {
                             addEditDetailRow(); // Add one empty row if no details exist
                        }
                        
                        editLogModal.show();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        }

        // Handle Delete button click
        if (target.classList.contains('btn-delete-log')) {
            if (confirm('আপনি কি এই খরচের হিসাবটি ডিলিট করতে নিশ্চিত? ডিলিট করলে ড্রামের স্টক আবার বেড়ে যাবে।')) {
                const formData = new FormData();
                formData.append('action', 'delete_cable_usage_log');
                formData.append('log_id', logId);

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

    // Handle Edit Form Submission
    editLogForm.addEventListener('submit', function(e){
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'update_cable_usage_log');

        fetch(api_url, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    editLogModal.hide();
                    window.location.reload();
                }
            });
    });
});
document.addEventListener('DOMContentLoaded', function() {
    const api_url = 'api/cable_api.php';

    // --- Add New Drum Modal Logic ---
    const addDrumForm = document.getElementById('add-drum-form');
    if (addDrumForm) {
        // ... (The existing code for Add Drum form remains unchanged)
        const totalMeterInput = addDrumForm.querySelector('[name="total_meter"]');
        const startMarkInput = addDrumForm.querySelector('[name="start_meter_mark"]');
        const directionSelect = addDrumForm.querySelector('[name="metering_direction"]');
        const endMarkDisplay = document.getElementById('end_meter_mark_display');

        function calculateEndMark() {
            const total = parseInt(totalMeterInput.value) || 0;
            const start = parseInt(startMarkInput.value) || 0;
            const direction = directionSelect.value;
            let endMark = 0;

            if (total > 0) {
                if (direction === 'desc') {
                    endMark = start - total + 1;
                } else {
                    endMark = start + total - 1;
                }
                endMarkDisplay.value = endMark;
            } else {
                endMarkDisplay.value = '';
            }
        }

        totalMeterInput.addEventListener('input', calculateEndMark);
        startMarkInput.addEventListener('input', calculateEndMark);
        directionSelect.addEventListener('change', calculateEndMark);

        addDrumForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'save_fiber_drum');

            fetch(api_url, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        window.location.reload();
                    }
                });
        });
    }

    // --- Edit Drum Modal Logic ---
    const editDrumModalEl = document.getElementById('edit-drum-modal');
    if (editDrumModalEl) {
        const editDrumModal = new bootstrap.Modal(editDrumModalEl);
        const editDrumForm = document.getElementById('edit-drum-form');
        const editTotalMeterInput = editDrumForm.querySelector('[name="total_meter"]');
        const editStartMarkInput = editDrumForm.querySelector('[name="start_meter_mark"]');
        const editDirectionSelect = editDrumForm.querySelector('[name="metering_direction"]');
        const editEndMarkDisplay = document.getElementById('edit_end_meter_mark_display');

        function calculateEditEndMark() {
            const total = parseInt(editTotalMeterInput.value) || 0;
            const start = parseInt(editStartMarkInput.value) || 0;
            const direction = editDirectionSelect.value;
            if (total > 0) {
                const endMark = (direction === 'desc') ? (start - total + 1) : (start + total - 1);
                editEndMarkDisplay.value = endMark;
            } else {
                editEndMarkDisplay.value = '';
            }
        }
        editTotalMeterInput.addEventListener('input', calculateEditEndMark);
        editStartMarkInput.addEventListener('input', calculateEditEndMark);
        editDirectionSelect.addEventListener('change', calculateEditEndMark);
        
        editDrumForm.addEventListener('submit', function(e){
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update_drum');
            fetch(api_url, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        editDrumModal.hide();
                        window.location.reload();
                    }
                });
        });
    }


    // --- Log Cable Usage Form Logic ---
    const logUsageForm = document.getElementById('log-usage-form');
    if (logUsageForm) {
        // ... (The existing code for Log Usage form remains unchanged)
        const drumSelect = document.getElementById('drum_id');
        const detailsContainer = document.getElementById('usage-details-container');
        const addDetailBtn = document.getElementById('add-detail-btn');
        const totalUsedDisplay = document.getElementById('total_meter_used_display');
        const totalUsedInput = document.getElementById('total_meter_used_hidden');
        
        let selectedDrumInfo = {};

        drumSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                selectedDrumInfo = {
                    meter: parseInt(selectedOption.dataset.meter) || 0,
                    direction: selectedOption.dataset.direction
                };
                 document.getElementById('current_meter_display').value = `${selectedDrumInfo.meter}m`;
            } else {
                selectedDrumInfo = {};
                 document.getElementById('current_meter_display').value = '';
            }
            detailsContainer.innerHTML = '';
            addDetailRow();
            updateTotalUsage();
        });

        function addDetailRow() {
            const row = document.createElement('div');
            row.className = 'row g-3 mb-2 align-items-center detail-row';
            row.innerHTML = `
                <div class="col-md-4"><input type="text" name="customer_id[]" class="form-control" placeholder="কাস্টমার আইডি" required></div>
                <div class="col-md-3"><input type="number" class="form-control start-mark" placeholder="শুরুর মার্ক" required></div>
                <div class="col-md-3"><input type="number" class="form-control end-mark" placeholder="শেষের মার্ক" required></div>
                <div class="col-md-1"><input type="text" name="meter_used_detail[]" class="form-control meter-used-detail" readonly></div>
                <div class="col-md-1"><button type="button" class="btn btn-sm btn-danger remove-detail-btn w-100">X</button></div>
            `;
            detailsContainer.appendChild(row);
        }

        detailsContainer.addEventListener('input', function(e) {
            if (e.target.classList.contains('start-mark') || e.target.classList.contains('end-mark')) {
                const row = e.target.closest('.detail-row');
                const startMark = parseInt(row.querySelector('.start-mark').value) || 0;
                const endMark = parseInt(row.querySelector('.end-mark').value) || 0;
                const resultInput = row.querySelector('.meter-used-detail');
                
                if (startMark > 0 && endMark > 0) {
                    resultInput.value = Math.abs(startMark - endMark) + 1;
                } else {
                    resultInput.value = 0;
                }
                updateTotalUsage();
            }
        });
        
        detailsContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-detail-btn')) {
                e.target.closest('.detail-row').remove();
                updateTotalUsage();
            }
        });
        
        addDetailBtn.addEventListener('click', addDetailRow);

        function updateTotalUsage() {
            let total = 0;
            detailsContainer.querySelectorAll('.meter-used-detail').forEach(input => {
                total += parseInt(input.value) || 0;
            });
            totalUsedDisplay.value = `${total}m`;
            totalUsedInput.value = total;
        }
        
        addDetailRow();

        logUsageForm.addEventListener('submit', function(e){
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'save_cable_usage');
            
            fetch(api_url, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        window.location.reload();
                    }
                });
        });
    }

    // --- Event listener for Edit/Delete buttons on the main table ---
    const drumStockTable = document.getElementById('drum-stock-table');
    if (drumStockTable) {
        drumStockTable.addEventListener('click', function(e) {
            const target = e.target.closest('.btn-edit-drum, .btn-delete-drum');
            if (!target) return;

            // if (!is_admin) return; // This check is removed

            const drumId = target.dataset.id;
            const editDrumModal = new bootstrap.Modal(document.getElementById('edit-drum-modal'));

            if (target.classList.contains('btn-edit-drum')) {
                const formData = new FormData();
                formData.append('action', 'get_drum_details');
                formData.append('id', drumId);

                fetch(api_url, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const drum = data.data;
                            const form = document.getElementById('edit-drum-form');
                            form.querySelector('[name="id"]').value = drum.id;
                            form.querySelector('[name="drum_code"]').value = drum.drum_code;
                            form.querySelector('[name="year"]').value = drum.year;
                            form.querySelector('[name="fiber_core"]').value = drum.fiber_core;
                            form.querySelector('[name="company"]').value = drum.company;
                            form.querySelector('[name="metering_direction"]').value = drum.metering_direction;
                            form.querySelector('[name="total_meter"]').value = drum.total_meter;
                            form.querySelector('[name="start_meter_mark"]').value = drum.start_meter_mark;
                            document.getElementById('edit_end_meter_mark_display').value = drum.end_meter_mark;
                            
                            editDrumModal.show();
                        } else {
                            alert(data.message);
                        }
                    });
            }

            if (target.classList.contains('btn-delete-drum')) {
                if (confirm('আপনি কি এই ড্রামটি ডিলিট করতে নিশ্চিত?')) {
                    const formData = new FormData();
                    formData.append('action', 'delete_drum');
                    formData.append('id', drumId);

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
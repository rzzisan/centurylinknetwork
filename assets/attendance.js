document.addEventListener('DOMContentLoaded', function() {
    const api_url = 'api/attendance_api.php';
    const attendanceDatePicker = document.getElementById('attendance_date_picker');
    const attendanceForm = document.getElementById('attendance-form');
    const monthlySummaryContainer = document.getElementById('monthly-summary-container');

    if (attendanceDatePicker) {
        // 1. Handle date change to reload the page with the new date
        attendanceDatePicker.addEventListener('change', function() {
            const selectedDate = this.value;
            window.location.href = `attendance_entry.php?date=${selectedDate}`;
        });

        // 2. Handle form submission for saving/updating attendance
        if (attendanceForm) {
            /* This check is removed, so the form is always active
            if (!is_admin) {
                const submitBtn = document.getElementById('submit-btn');
                if (submitBtn) {
                    submitBtn.style.display = 'none';
                }
                const inputs = attendanceForm.querySelectorAll('input, select, button');
                inputs.forEach(input => {
                    input.disabled = true;
                });
            }
            */
            
            attendanceForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const submitBtn = document.getElementById('submit-btn');
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> সেভ হচ্ছে...';

                const formData = new FormData(this);
                formData.append('action', 'save_daily_attendance');

                fetch(api_url, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        alert(data.message);
                        if (data.success) {
                            loadMonthlySummary(); // Refresh summary table
                            submitBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> আপডেট করুন';
                        } else {
                           submitBtn.innerHTML = originalText;
                        }
                        submitBtn.disabled = false;
                    });
            });
        }

        // 3. Load monthly summary table via AJAX
        function loadMonthlySummary() {
            if (!monthlySummaryContainer) return;
            
            const selectedDate = new Date(attendanceDatePicker.value);
            const year = selectedDate.getFullYear();
            const month = selectedDate.getMonth() + 1;

            fetch(`${api_url}?action=get_monthly_summary&year=${year}&month=${month}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderSummaryTable(data.data);
                    } else {
                        monthlySummaryContainer.innerHTML = `<p class="text-danger text-center">${data.message}</p>`;
                    }
                });
        }

        function renderSummaryTable(data) {
            const { employees, attendance_data, days_in_month } = data;
            
            let tableHTML = `<table class="table table-bordered table-sm text-center" style="font-size: 0.8rem;">`;
            
            // Table Header
            tableHTML += `<thead class="table-light"><tr><th style="min-width: 150px; vertical-align: middle;">কর্মচারী</th>`;
            for (let day = 1; day <= days_in_month; day++) {
                tableHTML += `<th>${day}</th>`;
            }
            tableHTML += `</tr></thead>`;

            // Table Body
            tableHTML += `<tbody>`;
            employees.forEach(emp => {
                tableHTML += `<tr><td class="text-start fw-bold">${emp.full_name}</td>`;
                for (let day = 1; day <= days_in_month; day++) {
                    const status = attendance_data[emp.id] && attendance_data[emp.id][day] ? attendance_data[emp.id][day] : '';
                    let symbol = '';
                    let cssClass = '';
                    let title = '';

                    switch (status) {
                        case 'present':  symbol = 'P'; cssClass = 'bg-success text-white'; title = 'Present'; break;
                        case 'absent':   symbol = 'A'; cssClass = 'bg-danger text-white'; title = 'Absent'; break;
                        case 'leave':    symbol = 'L'; cssClass = 'bg-info text-white'; title = 'Leave'; break;
                        case 'half_day': symbol = 'H'; cssClass = 'bg-warning text-dark'; title = 'Half-day'; break;
                        default:         symbol = '-'; cssClass = 'text-muted'; title = 'No Data';
                    }
                    tableHTML += `<td class="${cssClass}" title="${title}">${symbol}</td>`;
                }
                tableHTML += `</tr>`;
            });
            tableHTML += `</tbody></table>`;
            
            monthlySummaryContainer.innerHTML = tableHTML;
        }

        // Initial load of the summary table on page load
        loadMonthlySummary();
    }
});
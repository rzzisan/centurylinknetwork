document.addEventListener('DOMContentLoaded', function() {
    const api_url = '../api/attendance_api.php';

    // --- Payroll Report Form ---
    const reportForm = document.getElementById('payroll-report-form');
    if(reportForm) {
        const resultContainer = document.getElementById('report-result-container');
        const editModalEl = document.getElementById('edit-attendance-modal');
        const editModal = new bootstrap.Modal(editModalEl);
        const editForm = document.getElementById('edit-attendance-form');
        
        reportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'get_payroll_report');
            resultContainer.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary"></div><p class="mt-2">রিপোর্ট তৈরি হচ্ছে...</p></div>';

            fetch(api_url, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        renderReport(data.data);
                    } else {
                        resultContainer.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                });
        });

        function renderReport(data) {
            const { employee_name, month_name, summary, details } = data;
            let html = `
                <div class="payslip mb-4">
                    <h4>বেতন শিট - ${month_name}</h4>
                    <h5>কর্মচারী: ${employee_name}</h5>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <table class="table table-sm table-bordered">
                                <tr><th>মূল বেতন</th><td>${summary.base_salary} টাকা</td></tr>
                                <tr><th>উপস্থিত</th><td>${summary.present_days} দিন</td></tr>
                                <tr><th>অনুপস্থিত (বেতন কর্তন)</th><td>${summary.deductible_days} দিন</td></tr>
                                <tr><th>ছুটি (বেতন সহ)</th><td>${Math.min(summary.leave_days, summary.paid_leave)} দিন</td></tr>
                                <tr><th>হাফ ডিউটি</th><td>${summary.half_days} দিন</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                             <table class="table table-sm table-bordered">
                                <tr><th>অনুপস্থিতি কর্তন</th><td class="text-danger">(-) ${summary.absent_deduction} টাকা</td></tr>
                                <tr><th>হাফ ডিউটি কর্তন</th><td class="text-danger">(-) ${summary.half_day_deduction} টাকা</td></tr>
                                <tr><th>মোট কর্তন</th><td class="text-danger">(-) ${summary.total_deduction} টাকা</td></tr>
                                <tr><th>ওভারটাইম আয়</th><td class="text-success">(+) ${summary.overtime_amount} টাকা</td></tr>
                                <tr class="table-primary"><th>সর্বমোট বেতন</th><td><b>${summary.net_salary} টাকা</b></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header"><h5>বিস্তারিত দৈনিক লগ</h5></div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark"><tr><th>তারিখ</th><th>দিন</th><th>স্ট্যাটাস</th>
                            <th>অ্যাকশন</th>
                            </tr></thead>
                            <tbody>`;

            details.forEach(rec => {
                let statusBadge = '';
                switch(rec.status) {
                    case 'present': statusBadge = '<span class="badge bg-success">উপস্থিত</span>'; break;
                    case 'absent': statusBadge = '<span class="badge bg-danger">অনুপস্থিত</span>'; break;
                    case 'half_day': statusBadge = '<span class="badge bg-warning text-dark">হাফ ডিউটি</span>'; break;
                    case 'leave': statusBadge = '<span class="badge bg-info">ছুটি</span>'; break;
                }
                const date = new Date(rec.attendance_date);
                const dayName = date.toLocaleDateString('bn-BD', { weekday: 'long' });

                html += `<tr>
                            <td>${date.toLocaleDateString('bn-BD', {day: '2-digit', month: '2-digit', year: 'numeric'})}</td>
                            <td>${dayName}</td>
                            <td>${statusBadge}</td>
                            <td><button class="btn btn-sm btn-outline-primary btn-edit-att" data-id="${rec.id}" data-date="${rec.attendance_date}" data-name="${employee_name}" data-current-status="${rec.status}">এডিট</button></td>
                         </tr>`;
            });

            html += `</tbody></table></div></div>`;
            resultContainer.innerHTML = html;
        }
        
        resultContainer.addEventListener('click', function(e){
            if(e.target.classList.contains('btn-edit-att')){
                const button = e.target;
                document.getElementById('edit_attendance_id').value = button.dataset.id;
                document.getElementById('modal_employee_name').innerText = button.dataset.name;
                const date = new Date(button.dataset.date).toLocaleDateString('bn-BD', {day: '2-digit', month: '2-digit', year: 'numeric'});
                document.getElementById('modal_attendance_date').innerText = date;
                // Set the current status in the dropdown
                document.getElementById('edit_status').value = button.dataset.currentStatus;
                editModal.show();
            }
        });

        editForm.addEventListener('submit', function(e){
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update_single_attendance');
            
            fetch(api_url, { method: 'POST', body: formData})
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if(data.success){
                        editModal.hide();
                        reportForm.dispatchEvent(new Event('submit')); // Refresh report
                    }
                });
        });
    }
});
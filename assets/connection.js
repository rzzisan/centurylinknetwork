document.addEventListener('DOMContentLoaded', function () {
    const connectionModal = document.getElementById('connection-modal');
    const connectionForm = document.getElementById('connection-form');
    const dueForm = document.getElementById('due-form');
    const dueModalElement = document.getElementById('due-modal');
    const dueModal = new bootstrap.Modal(dueModalElement);
    const bsConnectionModal = new bootstrap.Modal(connectionModal);

    // ONU Section Toggle Logic
    const matOnu = document.getElementById('mat_onu');
    const matRouter = document.getElementById('mat_router');
    const onuSection = document.getElementById('onu_assignment_section');

    function toggleOnuSection() {
        if (matOnu.checked || matRouter.checked) {
            onuSection.style.display = 'block';
        } else {
            onuSection.style.display = 'none';
            // Clear values when hidden
            document.getElementById('onu_brand').value = '';
            document.getElementById('onu_mac').value = '';
        }
    }

    matOnu.addEventListener('change', toggleOnuSection);
    matRouter.addEventListener('change', toggleOnuSection);

    // Address Suggestion Logic
    const addressInput = document.getElementById('address');
    const addressList = document.getElementById('address-suggestions');

    addressInput.addEventListener('input', function() {
        const query = this.value;
        if(query.length > 2) {
            fetch(`api/connection_api.php?action=get_address_suggestions&query=${query}`)
            .then(res => res.json())
            .then(data => {
                addressList.innerHTML = '';
                if(data.success) {
                    data.data.forEach(item => {
                        let option = document.createElement('option');
                        option.value = item.address;
                        addressList.appendChild(option);
                    });
                }
            });
        }
    });

    // Auto-calculate Due Amount
    const totalPriceInput = document.getElementById('total_price');
    const depositAmountInput = document.getElementById('deposit_amount');
    const dueAmountDisplay = document.getElementById('due_amount_display');

    function calculateDue() {
        const total = parseFloat(totalPriceInput.value) || 0;
        const deposit = parseFloat(depositAmountInput.value) || 0;
        const due = total - deposit;
        dueAmountDisplay.value = due;
    }

    totalPriceInput.addEventListener('input', calculateDue);
    depositAmountInput.addEventListener('input', calculateDue);

    // Save/Update Connection
    connectionForm.addEventListener('submit', function (e) {
        e.preventDefault();
        console.log('Form submission triggered'); // Debug log

        const formData = new FormData(this);
        const action = formData.get('connection_id') ? 'update_connection' : 'save_new_connection';
        formData.append('action', action);

        // Debug: Log form data
        // for (var pair of formData.entries()) { console.log(pair[0]+ ', ' + pair[1]); }

        fetch('api/connection_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Server response:', data); // Debug log
            if (data.success) {
                alert(data.message);
                // Use the existing instance if possible, or hide via DOM
                const modalEl = document.getElementById('connection-modal');
                const modal = bootstrap.Modal.getInstance(modalEl); 
                if (modal) {
                    modal.hide();
                } else {
                    bsConnectionModal.hide();
                }
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Check console for details.');
        });
    });

    // Reset form on modal open (for new entry)
    connectionModal.addEventListener('show.bs.modal', function (event) {
        // Check if relatedTarget exists and has the class
        if (event.relatedTarget && !event.relatedTarget.classList.contains('btn-edit')) {
            document.getElementById('connection-modal-title').innerText = 'নতুন কানেকশন';
            connectionForm.reset();
            document.getElementById('connection_id').value = '';
            document.getElementById('due_amount_display').value = '';
            document.getElementById('connection_date').value = new Date().toISOString().split('T')[0];
            try {
                toggleOnuSection();
            } catch (err) {
                console.error('Error toggling ONU section:', err);
            }
        }
    });

    // Edit Connection Logic (Event Delegation)
    document.addEventListener('click', function (e) {
        const target = e.target.closest('.btn-edit');
        if (target) {
            const id = target.getAttribute('data-id');
            document.getElementById('connection-modal-title').innerText = 'কানেকশন এডিট করুন';
            document.getElementById('connection_id').value = id;

            fetch('api/connection_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_connection_details&id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const conn = data.data;
                    document.getElementById('connection_date').value = conn.connection_date;
                    document.getElementById('customer_id_code').value = conn.customer_id_code;
                    document.getElementById('customer_name').value = conn.customer_name;
                    document.getElementById('mobile_number').value = conn.mobile_number;
                    document.getElementById('address').value = conn.address;
                    document.getElementById('connection_type').value = conn.connection_type;
                    document.getElementById('total_price').value = conn.total_price;
                    document.getElementById('deposit_amount').value = conn.deposit_amount;
                    document.getElementById('order_taker_id').value = conn.order_taker_id;
                    document.getElementById('money_with_id').value = conn.money_with_id;
                    calculateDue();

                    // Materials Checkboxes
                    document.querySelectorAll('input[name="materials[]"]').forEach(cb => cb.checked = false);
                    if (conn.materials_used) {
                        const mats = conn.materials_used.split(',');
                        mats.forEach(m => {
                            const cb = document.querySelector(`input[value="${m.trim()}"]`);
                            if (cb) cb.checked = true;
                        });
                    }

                    // ONU Data Population
                    document.getElementById('onu_brand').value = conn.onu_brand || '';
                    document.getElementById('onu_mac').value = conn.onu_mac || '';

                    // Reset Assigned To Checkboxes
                    document.querySelectorAll('input[name="onu_assigned_to[]"]').forEach(cb => cb.checked = false);
                    
                    if (conn.onu_assigned_to) {
                        // The DB stores names like "Name1, Name2"
                        const assignedNames = conn.onu_assigned_to.split(',').map(n => n.trim());
                        assignedNames.forEach(name => {
                            // Find checkbox with this value (name)
                            const cb = document.querySelector(`input[name="onu_assigned_to[]"][value="${name}"]`);
                            if (cb) cb.checked = true;
                        });
                    }

                    // If ONU data exists or ONU materials selected, show the section
                    if (conn.onu_brand || conn.onu_mac || (conn.materials_used && (conn.materials_used.includes('অনু') || conn.materials_used.includes('অনু-রাউটার')))) {
                         document.getElementById('onu_assignment_section').style.display = 'block';
                    } else {
                         document.getElementById('onu_assignment_section').style.display = 'none';
                    }
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error fetching details:', error));
        }
    });


    // Delete Connection
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function () {
            if (confirm('আপনি কি নিশ্চিত যে আপনি এই কানেকশনটি ডিলিট করতে চান?')) {
                const id = this.getAttribute('data-id');
                fetch('api/connection_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete_connection&id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            }
        });
    });

    // Due Payment Logic
    document.querySelectorAll('.btn-due').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const row = this.closest('tr');
            const dueAmount = row.cells[9].innerText.replace(/,/g, ''); // 9th column is Due
            const moneyWithName = row.cells[11].innerText;

            document.getElementById('due_connection_id').value = id;
            document.getElementById('due_total_display').innerText = dueAmount;
            document.getElementById('due_money_with_name').innerText = moneyWithName;
            
            dueModal.show();
        });
    });

    dueForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'save_due_payment');

        fetch('api/connection_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert(data.message);
                dueModal.hide();
                location.reload();
            } else {
                alert(data.message);
            }
        });
    });

    // Connection Search
    const searchInput = document.getElementById('connectionSearchInput');
    const tableBody = document.getElementById('connectionTableBody');
    const paginationContainer = document.getElementById('connectionPaginationContainer');
    let originalTableHTML = tableBody.innerHTML;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        if (query.length > 2) {
             if (paginationContainer) paginationContainer.style.display = 'none';

            fetch(`api/connection_api.php?action=search_connections&query=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    tableBody.innerHTML = '';
                    data.data.forEach((conn, index) => {
                        let materialsContent = conn.materials_used;
                        if (conn.onu_brand || conn.onu_mac) {
                            materialsContent += `<br><small class="text-muted">
                                ${conn.onu_brand || ''}
                                ${(conn.onu_brand && conn.onu_mac) ? '-' : ''}
                                ${conn.onu_mac || ''}
                            </small>`;
                        }

                        const row = `<tr>
                            <td>${index + 1}</td>
                            <td>${conn.customer_id_code}</td>
                            <td>${new Date(conn.connection_date).toLocaleDateString('en-GB')}</td>
                            <td><strong>${conn.customer_name}</strong><br><small>${conn.address}</small></td>
                            <td>${conn.mobile_number}</td>
                            <td>${conn.connection_type}</td>
                            <td>${materialsContent}</td>
                            <td>${conn.total_price}</td>
                            <td>${conn.deposit_amount}</td>
                            <td class="${conn.due_amount > 0 ? 'text-danger fw-bold' : ''}">${conn.due_amount}</td>
                            <td>${conn.order_taker_name || ''}</td>
                            <td>${conn.money_with_name || ''}</td>
                            <td>
                                ${conn.due_amount > 0 ? `<button class="btn btn-danger btn-sm btn-due" data-id="${conn.id}" title="বকেয়া পরিশোধ করুন">Due</button>` : ''}
                                <button class="btn btn-warning btn-sm btn-edit" data-id="${conn.id}" title="এডিট করুন" data-bs-toggle="modal" data-bs-target="#connection-modal"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-danger btn-sm btn-delete" data-id="${conn.id}" title="ডিলিট করুন"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>`;
                        tableBody.innerHTML += row;
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="13" class="text-center">কোনো তথ্য পাওয়া যায়নি।</td></tr>';
                }
            });
        } else if (query.length === 0) {
            tableBody.innerHTML = originalTableHTML;
             if (paginationContainer) paginationContainer.style.display = 'block';
        }
    });

});
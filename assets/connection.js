document.addEventListener('DOMContentLoaded', function() {
    const api_url = 'connection_api.php';
    const connectionModalEl = document.getElementById('connection-modal');
    const connectionForm = document.getElementById('connection-form');
    const dueModalEl = document.getElementById('due-modal');
    const dueModal = new bootstrap.Modal(dueModalEl);
    const dueForm = document.getElementById('due-form');
    const tableBody = document.getElementById('connectionTableBody'); // tbody ID corrected

    // --- Auto calculate due amount ---
    const totalPriceEl = document.getElementById('total_price');
    const depositAmountEl = document.getElementById('deposit_amount');
    const dueDisplayEl = document.getElementById('due_amount_display');

    function calculateDue() {
        const total = parseFloat(totalPriceEl.value) || 0;
        const deposit = parseFloat(depositAmountEl.value) || 0;
        dueDisplayEl.value = (total - deposit).toFixed(2);
    }

    if(totalPriceEl) totalPriceEl.addEventListener('input', calculateDue);
    if(depositAmountEl) depositAmountEl.addEventListener('input', calculateDue);

    // --- Address Suggestions ---
    const addressInput = document.getElementById('address');
    const suggestionsDatalist = document.getElementById('address-suggestions');
    if(addressInput) {
        addressInput.addEventListener('keyup', function() {
            const query = this.value;
            if (query.length < 3) return;
            fetch(`${api_url}?action=get_address_suggestions&query=${query}`)
                .then(res => res.json())
                .then(data => {
                    if (suggestionsDatalist) {
                        suggestionsDatalist.innerHTML = '';
                        if (data.success) {
                            data.data.forEach(item => {
                                suggestionsDatalist.innerHTML += `<option value="${item.address}">`;
                            });
                        }
                    }
                });
        });
    }

    // --- Modal Show Event Listener (Handles both Add and Edit) ---
    if(connectionModalEl) {
        connectionModalEl.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const isEditButton = button && button.classList.contains('btn-edit');

            if (isEditButton) {
                // --- EDIT MODE ---
                document.getElementById('connection-modal-title').textContent = 'কানেকশন এডিট করুন';
                const id = button.dataset.id;
                
                const formData = new FormData();
                formData.append('action', 'get_connection_details');
                formData.append('id', id);

                fetch(api_url, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const conn = data.data;
                            document.getElementById('connection_id').value = conn.id;
                            document.getElementById('connection_date').value = conn.connection_date;
                            document.getElementById('customer_id_code').value = conn.customer_id_code;
                            document.getElementById('customer_name').value = conn.customer_name;
                            document.getElementById('mobile_number').value = conn.mobile_number;
                            document.getElementById('address').value = conn.address;
                            document.getElementById('connection_type').value = conn.connection_type;
                            totalPriceEl.value = conn.total_price;
                            depositAmountEl.value = conn.deposit_amount;
                            document.getElementById('order_taker_id').value = conn.order_taker_id;
                            document.getElementById('money_with_id').value = conn.money_with_id;
                            
                            document.querySelectorAll('input[name="materials[]"]').forEach(cb => cb.checked = false);
                            if (conn.materials_used) {
                                conn.materials_used.split(',').forEach(mat => {
                                   const cb = document.querySelector(`input[name="materials[]"][value="${mat.trim()}"]`);
                                   if(cb) cb.checked = true;
                                });
                            }
                            calculateDue();
                        } else {
                            alert(data.message);
                        }
                    });

            } else {
                // --- ADD MODE ---
                connectionForm.reset();
                document.getElementById('connection_id').value = '';
                document.getElementById('connection-modal-title').textContent = 'নতুন কানেকশন যোগ করুন';
                document.getElementById('connection_date').valueAsDate = new Date();
                calculateDue();
            }
        });
    }

    // --- Form Submission (Handles both Save and Update) ---
    if(connectionForm) {
        connectionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const action = document.getElementById('connection_id').value ? 'update_connection' : 'save_new_connection';
            formData.append('action', action);
            
            const materials = Array.from(document.querySelectorAll('input[name="materials[]"]:checked')).map(cb => cb.value);
            formData.delete('materials[]'); // Remove old materials array if exists
            formData.append('materials_used', materials.join(','));

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

    // --- Table Button Click Listener (for Delete and Due) using EVENT DELEGATION ---
    if(tableBody) {
        tableBody.addEventListener('click', function(e) {
            const target = e.target.closest('.btn-delete, .btn-due');
            if (!target) return;
            
            const id = target.dataset.id;

            // --- DELETE ---
            if (target.classList.contains('btn-delete')) {
                if (confirm('আপনি কি এই কানেকশনটি ডিলিট করতে নিশ্চিত?')) {
                    const formData = new FormData();
                    formData.append('action', 'delete_connection');
                    formData.append('id', id);
                    fetch(api_url, { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            alert(data.message);
                            if(data.success) window.location.reload();
                        });
                }
            }

            // --- DUE ---
            if (target.classList.contains('btn-due')) {
                const formData = new FormData();
                formData.append('action', 'get_connection_details');
                formData.append('id', id);

                fetch(api_url, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const conn = data.data;
                            dueForm.reset();
                            document.getElementById('due_connection_id').value = conn.id;
                            document.getElementById('due_total_display').textContent = conn.due_amount;
                            document.getElementById('due_money_with_name').textContent = conn.money_with_name;
                            dueModal.show();
                        } else {
                            alert(data.message);
                        }
                    });
            }
        });
    }

    // --- Due form submission ---
    if(dueForm) {
        dueForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'save_due_payment');
            fetch(api_url, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        dueModal.hide();
                        window.location.reload();
                    }
                });
        });
    }

    // --- Live Search Logic ---
    const searchInput = document.getElementById('connectionSearchInput');
    const paginationContainer = document.getElementById('connectionPaginationContainer');
    let originalTableContent = tableBody ? tableBody.innerHTML : '';
    let originalPaginationContent = paginationContainer ? paginationContainer.innerHTML : '';
    let typingTimer;
    const doneTypingInterval = 300; // 300ms

    function performSearch() {
        const query = searchInput.value.trim();

        if (query.length > 2) {
            if(paginationContainer) paginationContainer.style.display = 'none';
            if(tableBody) tableBody.innerHTML = '<tr><td colspan="12" class="text-center">সার্চ করা হচ্ছে...</td></tr>';

            fetch(`connection_api.php?action=search_connections&query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if(!tableBody) return;
                    tableBody.innerHTML = '';
                    if (data.success && data.data.length > 0) {
                        data.data.forEach(conn => {
                            const dueAmountClass = conn.due_amount > 0 ? 'text-danger fw-bold' : '';
                            const dueButton = conn.due_amount > 0 ? `<button class="btn btn-danger btn-sm btn-due" data-id="${conn.id}" title="বকেয়া পরিশোধ করুন">Due</button>` : '';
                            const date = new Date(conn.connection_date);
                            const formattedDate = `${date.getDate().toString().padStart(2, '0')}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date.getFullYear()}`;
                            const materials = conn.materials_used ? conn.materials_used.replace(/,/g, ', ') : '';
                            
                            // Removed the 'if (is_admin)' check here
                            let actionButtons = `
                                ${dueButton}
                                <button class="btn btn-warning btn-sm btn-edit" data-id="${conn.id}" title="এডিট করুন" data-bs-toggle="modal" data-bs-target="#connection-modal"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-danger btn-sm btn-delete" data-id="${conn.id}" title="ডিলিট করুন"><i class="bi bi-trash"></i></button>
                            `;

                            const row = `<tr>
                                <td>${conn.customer_id_code}</td>
                                <td>${formattedDate}</td>
                                <td><strong>${conn.customer_name}</strong><br><small>${conn.address}</small></td>
                                <td>${conn.mobile_number}</td>
                                <td>${conn.connection_type}</td>
                                <td>${materials}</td>
                                <td>${Number(conn.total_price).toLocaleString('en-IN')}</td>
                                <td>${Number(conn.deposit_amount).toLocaleString('en-IN')}</td>
                                <td class="${dueAmountClass}">${Number(conn.due_amount).toLocaleString('en-IN')}</td>
                                <td>${conn.order_taker_name}</td>
                                <td>${conn.money_with_name}</td>
                                <td>
                                    ${actionButtons}
                                </td>
                            </tr>`;
                            tableBody.innerHTML += row;
                        });
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="12" class="text-center">কোনো ফলাফল পাওয়া যায়নি।</td></tr>';
                    }
                });
        } else if (query.length === 0) {
            tableBody.innerHTML = originalTableContent;
            if(paginationContainer) {
                paginationContainer.innerHTML = originalPaginationContent;
                paginationContainer.style.display = 'block';
            }
        }
    }
    
    if(searchInput) {
        searchInput.addEventListener('keyup', () => {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(performSearch, doneTypingInterval);
        });

        searchInput.addEventListener('keydown', () => {
            clearTimeout(typingTimer);
        });
    }
});
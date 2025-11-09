    <?php if (isLoggedIn()): ?>
                </div>
            </main>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Initialize DataTables
        $(document).ready(function() {
            $('.data-table').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[ 0, 'desc' ]],
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "No entries available",
                    infoFiltered: "(filtered from _MAX_ total entries)"
                }
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
            
            // Confirm delete actions
            $('.delete-btn').click(function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                const itemName = $(this).data('name') || 'this item';
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: `Do you want to delete ${itemName}? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
            
            // Form validation
            $('.needs-validation').submit(function(e) {
                if (!this.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                $(this).addClass('was-validated');
            });
            
            // Auto-refresh notifications
            setInterval(function() {
                // Fetch new notifications
                $.get('includes/get_notifications.php', function(data) {
                    if (data.count > 0) {
                        $('#notification-count').text(data.count).show();
                        $('#notification-list').html(data.html);
                    } else {
                        $('#notification-count').hide();
                    }
                });
            }, 30000); // Check every 30 seconds
            
            // Live search
            $('.live-search').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                const target = $(this).data('target');
                
                $(target + ' tr').each(function() {
                    const text = $(this).text().toLowerCase();
                    if (text.indexOf(searchTerm) === -1) {
                        $(this).hide();
                    } else {
                        $(this).show();
                    }
                });
            });
            
            // Tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // File upload preview
            $('.file-input').change(function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    const preview = $(this).siblings('.file-preview');
                    
                    reader.onload = function(e) {
                        if (file.type.startsWith('image/')) {
                            preview.html(`<img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px;">`);
                        } else {
                            preview.html(`<p><i class="fas fa-file"></i> ${file.name}</p>`);
                        }
                    };
                    
                    reader.readAsDataURL(file);
                }
            });
            
            // Auto-calculate fields
            $('.auto-calculate').on('input', function() {
                const row = $(this).closest('tr');
                const quantity = parseFloat(row.find('.quantity').val()) || 0;
                const price = parseFloat(row.find('.price').val()) || 0;
                const total = quantity * price;
                row.find('.total').val(total.toFixed(2));
                
                // Update grand total if needed
                updateGrandTotal();
            });
            
            function updateGrandTotal() {
                let grandTotal = 0;
                $('.total').each(function() {
                    grandTotal += parseFloat($(this).val()) || 0;
                });
                $('#grand-total').text(grandTotal.toFixed(2));
            }
        });
        
        // Global functions
        function showLoading() {
            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading()
                }
            });
        }
        
        function hideLoading() {
            Swal.close();
        }
        
        function showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        }
        
        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: message
            });
        }
        
        // Print function
        function printDiv(divName) {
            const printContents = document.getElementById(divName).innerHTML;
            const originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            location.reload();
        }
        
        // Export to CSV
        function exportTableToCSV(filename) {
            const csv = [];
            const rows = document.querySelectorAll('.data-table tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [];
                const cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    let data = cols[j].innerText.replace(/"/g, '""');
                    row.push('"' + data + '"');
                }
                csv.push(row.join(','));
            }
            
            const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
            const downloadLink = document.createElement('a');
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
        }
    </script>
    
    <!-- Page specific scripts -->
    <?php if (isset($page_scripts)): ?>
        <?php echo $page_scripts; ?>
    <?php endif; ?>
    
</body>
</html>
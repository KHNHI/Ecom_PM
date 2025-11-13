// Customer management functions
function filterCustomers(type) {
    console.log('Filtering customers by:', type);
    const rows = document.querySelectorAll('#customersTable tbody tr');
    
    rows.forEach(row => {
        const status = row.querySelector('.badge')?.textContent.trim().toLowerCase();
        
        if (type === 'all') {
            row.style.display = '';
        } else if (type === 'active' && status.includes('hoạt động')) {
            row.style.display = '';
        } else if (type === 'inactive' && status.includes('tạm dừng')) {
            row.style.display = '';
        } else if (type === 'vip' && row.textContent.toLowerCase().includes('vip')) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function toggleCustomerStatus(customerId) {
    if (confirm('Bạn có chắc chắn muốn thay đổi trạng thái của khách hàng này?')) {
        console.log('Toggling status for customer:', customerId);
        alert('Đã thay đổi trạng thái!');
        location.reload();
    }
}

function deleteCustomer(customerId) {
    if (confirm('Bạn có chắc chắn muốn xóa khách hàng này? Hành động này không thể hoàn tác!')) {
        console.log('Deleting customer:', customerId);
        alert('Đã xóa khách hàng!');
        location.reload();
    }
}

function viewCustomer(customerId) {
    window.location.href = 'customer-details.html?id=' + customerId;
}

function editCustomer(customerId) {
    window.location.href = 'add-customer.html?id=' + customerId + '&edit=true';
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('customerSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#customersTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Select all checkbox functionality
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    // Initialize pagination
    const paginationContainer = document.getElementById('pagination-container');
    if (paginationContainer && window.ComponentManager) {
        window.ComponentManager.renderPagination(paginationContainer, {
            ariaLabel: 'Customers pagination'
        });
    }
});

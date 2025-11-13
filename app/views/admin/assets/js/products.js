// Products page specific JavaScript
window.pageConfig = window.pageConfig || {};
window.pageConfig.sidebar = window.pageConfig.sidebar || {};
window.pageConfig.sidebar.activePage = 'products';

document.addEventListener('DOMContentLoaded', function() {
    console.log('Products page JavaScript loaded');
    
    // Initialize products page functionality
    initializeProductsPage();
});

function initializeProductsPage() {
    // Search functionality
    const searchInput = document.getElementById('productSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const productName = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
                const sku = row.querySelector('td:nth-child(6)')?.textContent.toLowerCase() || '';
                
                if (productName.includes(searchTerm) || sku.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Category filter functionality
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            const selectedCategory = this.value;
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const categoryBadge = row.querySelector('td:nth-child(3) .badge');
                const categoryText = categoryBadge?.textContent.toLowerCase() || '';
                
                if (selectedCategory === 'all') {
                    row.style.display = '';
                } else if (categoryText.includes(selectedCategory.toLowerCase())) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Status filter functionality
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            const selectedStatus = this.value;
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const statusBadge = row.querySelector('td:nth-child(7) .badge');
                const statusText = statusBadge?.textContent.toLowerCase() || '';
                
                if (selectedStatus === 'all') {
                    row.style.display = '';
                } else if (statusText.includes(selectedStatus)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Bulk actions
    const bulkActions = document.querySelectorAll('.bulk-action');
    bulkActions.forEach(action => {
        action.addEventListener('click', function() {
            const selectedRows = document.querySelectorAll('tbody input[type="checkbox"]:checked');
            
            if (selectedRows.length === 0) {
                alert('Vui lòng chọn ít nhất một sản phẩm!');
                return;
            }
            
            const actionType = this.dataset.action;
            if (confirm(`Bạn có chắc chắn muốn ${actionType} ${selectedRows.length} sản phẩm?`)) {
                // Implement bulk action logic here
                console.log(`Bulk ${actionType} for ${selectedRows.length} products`);
            }
        });
    });
}

// Product management functions
function viewProduct(productId) {
    window.location.href = `index.php?url=product-details&id=${productId}`;
}

function editProduct(productId) {
    window.location.href = `index.php?url=edit-product&id=${productId}`;
}

function toggleProduct(productId, newStatus) {
    const action = newStatus === 'true' ? 'kích hoạt' : 'ngừng bán';
    if (confirm(`Bạn có chắc chắn muốn ${action} sản phẩm này?`)) {
        // Implement toggle logic
        console.log(`Toggling product ${productId} to status: ${newStatus}`);
    }
}

function deleteProduct(productId) {
    if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này? Hành động này không thể hoàn tác!')) {
        // Implement delete logic
        console.log(`Deleting product ${productId}`);
    }
}

// Filter functions
function filterProducts(type) {
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const statusBadge = row.querySelector('td:nth-child(7) .badge');
        const statusText = statusBadge?.textContent.toLowerCase() || '';
        
        if (type === 'all') {
            row.style.display = '';
        } else if (type === 'active' && statusText.includes('đang bán')) {
            row.style.display = '';
        } else if (type === 'inactive' && statusText.includes('ngừng bán')) {
            row.style.display = '';
        } else if (type === 'best-seller') {
            // Implement best seller logic based on sales data
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

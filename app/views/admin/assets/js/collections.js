// Collections page initialization
document.addEventListener('DOMContentLoaded', function() {
    // Select All Checkbox
    const selectAll = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        });
    }

    // Collection Search
    const searchInput = document.getElementById('collectionSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#collectionsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});

// Collection management functions
function addNewCollection() {
    window.location.href = 'add-collection.html';
}

function viewCollection(collectionId) {
    alert('Xem chi tiết bộ sưu tập: ' + collectionId);
}

function editCollection(collectionId) {
    alert('Chỉnh sửa bộ sưu tập: ' + collectionId);
}

function viewProducts(collectionId) {
    window.location.href = 'products.html?collection=' + collectionId;
}

function deleteCollection(collectionId) {
    if (confirm('Bạn có chắc chắn muốn xóa bộ sưu tập này? Hành động này không thể hoàn tác!')) {
        console.log('Deleting collection:', collectionId);
        alert('Đã xóa bộ sưu tập thành công!');
        location.reload();
    }
}

function toggleCollectionStatus(collectionId) {
    if (confirm('Bạn có chắc chắn muốn thay đổi trạng thái của bộ sưu tập này?')) {
        console.log('Toggling collection status:', collectionId);
        alert('Đã thay đổi trạng thái!');
        location.reload();
    }
}

// Filter collections
function filterCollections(type) {
    const rows = document.querySelectorAll('#collectionsTable tbody tr');
    rows.forEach(row => {
        const status = row.querySelector('.badge')?.textContent.trim().toLowerCase();
        
        if (type === 'all') {
            row.style.display = '';
        } else if (type === 'active' && status.includes('hoạt động')) {
            row.style.display = '';
        } else if (type === 'inactive' && status.includes('tạm dừng')) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Bulk actions
function bulkDelete() {
    const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Vui lòng chọn ít nhất một bộ sưu tập!');
        return;
    }
    
    if (confirm(`Bạn có chắc chắn muốn xóa ${checkedBoxes.length} bộ sưu tập đã chọn?`)) {
        console.log('Bulk deleting collections');
        alert('Đã xóa các bộ sưu tập đã chọn!');
        location.reload();
    }
}

function bulkActivate() {
    const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Vui lòng chọn ít nhất một bộ sưu tập!');
        return;
    }
    
    console.log('Bulk activating collections');
    alert(`Đã kích hoạt ${checkedBoxes.length} bộ sưu tập!`);
    location.reload();
}

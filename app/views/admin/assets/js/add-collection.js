/**
 * Add Collection JavaScript - Client-side functionality
 * Tuân thủ nguyên tắc Separation of Concerns và OOP
 * - Chỉ xử lý UI/UX và client-side validation
 * - Business logic ở Server-side (Controller/Model)
 */

// Class để quản lý Collection Form
class CollectionFormManager {
    constructor() {
        this.form = document.getElementById('addCollectionForm');
        this.nameInput = document.getElementById('name');
        this.slugInput = document.getElementById('slug');
        this.coverImageInput = document.getElementById('cover_image');
        
        this.init();
    }

    init() {
        if (this.form) {
            // Form submit validation (client-side only)
            this.form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }

        // Auto-generate slug from name
        if (this.nameInput && this.slugInput) {
            this.nameInput.addEventListener('input', () => this.updateSlug());
        }
    }

    /**
     * Xử lý form submit - Client-side validation only
     */
    handleFormSubmit(e) {
        const name = this.nameInput ? this.nameInput.value.trim() : '';

        let errors = [];

        if (name.length < 3) {
            errors.push('Tên bộ sưu tập phải có ít nhất 3 ký tự');
        }

        if (errors.length > 0) {
            e.preventDefault();
            alert('Vui lòng kiểm tra lại:\n' + errors.join('\n'));
            return false;
        }

        // Hiển thị loading indicator
        const submitBtn = this.form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang tạo...';
        }

        // Let form submit naturally to server
        return true;
    }

    /**
     * Auto-generate slug from collection name
     */
    updateSlug() {
        const name = this.nameInput.value;
        const slug = this.generateSlug(name);
        if (this.slugInput) {
            this.slugInput.value = slug;
        }
    }

    /**
     * Generate slug from text
     */
    generateSlug(text) {
        return text
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
}

// =================== COVER IMAGE UPLOAD HANDLING ===================

/**
 * Handle cover image selection
 */
function handleCollectionCoverSelect(input) {
    const file = input.files[0];
    const previewContainer = document.getElementById('coverPreview');
    const placeholder = document.getElementById('coverUploadPlaceholder');
    const previewImg = document.getElementById('coverPreviewImg');
    
    if (!file) {
        if (previewContainer) previewContainer.style.display = 'none';
        if (placeholder) placeholder.style.display = 'block';
        return;
    }

    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        alert('Chỉ chấp nhận file ảnh: JPG, PNG, GIF, WEBP');
        input.value = '';
        return;
    }

    // Validate file size (5MB)
    if (file.size > 5242880) {
        alert('Kích thước file không được vượt quá 5MB');
        input.value = '';
        return;
    }

    // Show preview
    const reader = new FileReader();
    reader.onload = function(e) {
        if (previewImg) previewImg.src = e.target.result;
        if (previewContainer) previewContainer.style.display = 'block';
        if (placeholder) placeholder.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

/**
 * Remove cover image
 */
function removeCollectionCover() {
    const fileInput = document.getElementById('cover_image');
    const previewContainer = document.getElementById('coverPreview');
    const placeholder = document.getElementById('coverUploadPlaceholder');
    
    if (fileInput) fileInput.value = '';
    if (previewContainer) previewContainer.style.display = 'none';
    if (placeholder) placeholder.style.display = 'block';
}

// =================== DRAG & DROP HANDLING ===================

/**
 * Handle drag over
 */
function handleCollectionDragOver(event) {
    event.preventDefault();
    event.currentTarget.style.borderColor = 'var(--accent-color, #28a745)';
    event.currentTarget.style.backgroundColor = 'rgba(40, 167, 69, 0.1)';
}

/**
 * Handle drag leave
 */
function handleCollectionDragLeave(event) {
    event.currentTarget.style.borderColor = 'var(--border-color, #ddd)';
    event.currentTarget.style.backgroundColor = 'transparent';
}

/**
 * Handle drop
 */
function handleCollectionDrop(event) {
    event.preventDefault();
    event.currentTarget.style.borderColor = 'var(--border-color, #ddd)';
    event.currentTarget.style.backgroundColor = 'transparent';
    
    const files = event.dataTransfer.files;
    const fileInput = document.getElementById('cover_image');
    
    if (files.length > 0) {
        fileInput.files = files;
        handleCollectionCoverSelect(fileInput);
    }
}

// =================== INITIALIZE ===================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Collection Form Manager
    const collectionFormManager = new CollectionFormManager();
    
    console.log('Add Collection page initialized - MVC/OOP compliant');
});

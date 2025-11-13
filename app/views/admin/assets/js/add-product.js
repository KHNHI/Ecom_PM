/**
 * Add Product JavaScript - Production Ready
 */

// Class để quản lý Product Form
class ProductFormManager {
  constructor() {
    // DOM elements
    this.form = document.getElementById("addProductForm");
    this.fileInput = document.getElementById("product_images");
    this.imageList = document.getElementById("imageList");
    this.imagePreviewContainer = document.getElementById(
      "imagePreviewContainer"
    );
    this.uploadArea = document.getElementById("uploadArea");

    this.init();
  }

  init() {
    if (this.form) {
      this.form.addEventListener("submit", (e) => this.handleFormSubmit(e));
    }

    if (this.fileInput) {
      this.fileInput.addEventListener("change", (e) => {
        this.handleFileSelect(e.target);
      });
    }

    this.setupSKUGenerator();
  }

  /**
   * Handle form submit - Client-side validation
   */
  handleFormSubmit(e) {
    const name = document.getElementById("name").value.trim();
    const basePrice = document.getElementById("base_price").value;

    let errors = [];

    if (name.length < 3) {
      errors.push("Tên sản phẩm phải có ít nhất 3 ký tự");
    }

    if (!basePrice || parseFloat(basePrice) <= 0) {
      errors.push("Giá sản phẩm phải lớn hơn 0");
    }

    if (errors.length > 0) {
      e.preventDefault();
      alert("Lỗi:\n" + errors.join("\n"));
      return false;
    }

    const submitBtn = this.form.querySelector('button[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu...';
    }

    return true;
  }

  /**
   * Setup auto SKU generator
   */
  setupSKUGenerator() {
    const nameInput = document.getElementById("name");
    const skuInput = document.getElementById("sku");

    if (nameInput && skuInput) {
      nameInput.addEventListener("input", function () {
        if (!skuInput.value) {
          const name = this.value.trim();
          if (name) {
            const sku = generateSKU(name);
            skuInput.value = sku;
          }
        }
      });
    }
  }

  /**
   * Handle file selection
   */
  handleFileSelect(input) {
    const files = input.files;
    const imageList = this.imageList;
    const previewContainer = this.imagePreviewContainer;

    if (!files || files.length === 0) {
      if (previewContainer) previewContainer.style.display = "none";
      return;
    }

    if (imageList) imageList.innerHTML = "";
    if (previewContainer) previewContainer.style.display = "block";

    Array.from(files).forEach((file, index) => {
      if (file.type.startsWith("image/")) {
        if (file.size > 5242880) {
          alert(`File ${file.name} quá lớn. Kích thước tối đa: 5MB`);
          return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
          const imageItem = createImagePreviewItem(
            e.target.result,
            file.name,
            index
          );
          if (imageList) {
            imageList.appendChild(imageItem);
          }
        };
        reader.onerror = (e) => {
          console.error("FileReader error:", e);
        };
        reader.readAsDataURL(file);
      }
    });
  }
}

// =================== HELPER FUNCTIONS ===================

/**
 * Create image preview item
 */
function createImagePreviewItem(imageSrc, fileName, index) {
  const div = document.createElement("div");
  div.className =
    "d-flex align-items-center justify-content-between mb-2 p-2 border rounded";
  div.setAttribute("data-index", index);

  div.innerHTML = `
        <div class="d-flex align-items-center">
            <img src="${imageSrc}" alt="Preview" width="40" height="40" class="rounded me-2" style="object-fit: cover;">
            <div>
                <div class="small fw-bold">${truncateFileName(
                  fileName,
                  20
                )}</div>
                ${
                  index === 0
                    ? '<small class="text-primary">Ảnh chính</small>'
                    : ""
                }
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImagePreview(this)">
            <i class="fas fa-times"></i>
        </button>
    `;

  return div;
}

/**
 * Remove image preview
 */
function removeImagePreview(button) {
  const imageItem = button.closest("[data-index]");
  if (imageItem) {
    imageItem.remove();

    const container = document.getElementById("imagePreviewContainer");
    const imageList = document.getElementById("imageList");
    if (container && imageList && imageList.children.length === 0) {
      container.style.display = "none";
    }
  }
}

/**
 * Generate SKU from product name
 */
function generateSKU(name) {
  return name
    .toUpperCase()
    .replace(/[^A-Z0-9\s]/g, "")
    .replace(/\s+/g, "-")
    .substring(0, 20);
}

/**
 * Truncate file name for display
 */
function truncateFileName(fileName, maxLength) {
  if (fileName.length <= maxLength) {
    return fileName;
  }

  const ext = fileName.split(".").pop();
  const nameWithoutExt = fileName.substring(
    0,
    fileName.length - ext.length - 1
  );
  const truncated =
    nameWithoutExt.substring(0, maxLength - ext.length - 4) + "...";

  return truncated + "." + ext;
}

// =================== INITIALIZE ===================

document.addEventListener("DOMContentLoaded", function () {
  const productFormManager = new ProductFormManager();
});

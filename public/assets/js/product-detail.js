// ==================== IMAGE GALLERY ====================
function changeImage(element, imageUrl) {
  const mainImage = document.getElementById("mainImage");
  if (mainImage) {
    mainImage.src = imageUrl;
  }

  // Bỏ active ở tất cả thumbnail
  document.querySelectorAll(".thumbnail-item").forEach((item) => {
    item.classList.remove("active");
  });
  // Active thumbnail được chọn
  element.classList.add("active");
}

// ==================== PRODUCT OPTIONS ====================
function selectOption(element) {
  let parent = element.parentNode;
  // Bỏ active ở nhóm hiện tại
  parent.querySelectorAll(".option-btn").forEach((btn) => {
    btn.classList.remove("active");
  });
  // Active option được chọn
  element.classList.add("active");
}

// ==================== TAB SWITCHING ====================
function switchTab(element, tabId) {
  console.log("🔄 switchTab called with:", tabId);

  // Reset button active
  document.querySelectorAll(".tab-btn").forEach((btn) => {
    btn.classList.remove("active");
  });
  element.classList.add("active");

  // Reset content active
  document.querySelectorAll(".tab-content").forEach((content) => {
    content.classList.remove("active");
  });

  const targetTab = document.getElementById(tabId);
  if (targetTab) {
    targetTab.classList.add("active");
    console.log("✅ Tab switched to:", tabId);
  } else {
    console.error("❌ Tab not found:", tabId);
  }
}

// ==================== REVIEW FORM ====================
function submitReview(formData) {
  console.log("📝 Submitting review:", formData);

  // For now, just show success message
  // Later you can implement AJAX call to submit review
  alert(
    "Cảm ơn bạn đã đánh giá! Đánh giá của bạn sẽ được xem xét và phê duyệt sớm."
  );

  // Reset form
  const reviewForm = document.getElementById("reviewForm");
  if (reviewForm) {
    reviewForm.reset();
    document
      .querySelectorAll('input[name="rating"]')
      .forEach((input) => (input.checked = false));
    reviewForm.classList.remove("was-validated");
  }

  console.log("✅ Review form reset");
}

// ==================== INITIALIZATION ====================
document.addEventListener("DOMContentLoaded", function () {
  console.log("🚀 Product Detail Page Initialized");

  // Initialize tabs - show description by default
  const descTab = document.getElementById("description");
  if (descTab) {
    descTab.classList.add("active");
    console.log("✅ Description tab activated by default");
  }

  // Review Form Event Listener
  const reviewForm = document.getElementById("reviewForm");
  if (reviewForm) {
    console.log("📝 Review form found, attaching events");

    reviewForm.addEventListener("submit", function (e) {
      e.preventDefault();
      console.log("📝 Review form submitted");

      // Validate form
      if (!reviewForm.checkValidity()) {
        e.stopPropagation();
        reviewForm.classList.add("was-validated");
        console.log("❌ Form validation failed");
        return;
      }

      // Check if rating is selected
      const rating = document.querySelector('input[name="rating"]:checked');
      if (!rating) {
        alert("Vui lòng chọn số sao đánh giá!");
        console.log("❌ No rating selected");
        return;
      }

      // Collect form data
      const formData = {
        product_id: document.getElementById("productId")?.value,
        rating: rating.value,
        title: document.getElementById("reviewTitle")?.value,
        comment: document.getElementById("reviewComment")?.value,
        reviewer_name: document.getElementById("reviewerName")?.value,
        reviewer_email: document.getElementById("reviewerEmail")?.value,
      };

      // Submit review
      submitReview(formData);
    });
  } else {
    console.log("ℹ️ No review form found on this page");
  }

  // Search functionality
  const searchBtn = document.querySelector(".search-btn");
  if (searchBtn) {
    console.log("🔍 Search functionality initialized");

    searchBtn.addEventListener("click", function () {
      const searchTerm = document.querySelector(".search-input").value;
      if (searchTerm.trim()) {
        window.location.href = `/Ecom_website/customer/products?search=${encodeURIComponent(
          searchTerm.trim()
        )}`;
      }
    });
  }

  // Handle Enter key in search
  const searchInput = document.querySelector(".search-input");
  if (searchInput) {
    searchInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        const searchBtn = document.querySelector(".search-btn");
        if (searchBtn) searchBtn.click();
      }
    });
  }

  console.log("✅ Product Detail JS Fully Loaded");
});

// Material Filter Functions
function applyFilters() {
  try {
    const url = new URL(window.location);

    // Get category filters (support multiple)
    const categories = Array.from(
      document.querySelectorAll('input[type=checkbox][id^="category_"]')
    )
      .filter((cb) => cb.checked)
      .map((cb) => cb.value);

    if (categories.length > 0) {
      url.searchParams.set("categories", categories.join(","));
    } else {
      url.searchParams.delete("categories");
      url.searchParams.delete("category"); // Also remove old single category
    }

    // Get material filters - use value selector for consistency
    const materials = Array.from(
      document.querySelectorAll(
        'input[type=checkbox][value="gold"], input[type=checkbox][value="silver"], input[type=checkbox][value="diamond"], input[type=checkbox][value="pearl"]'
      )
    )
      .filter((cb) => cb.checked)
      .map((cb) => cb.value);

    if (materials.length > 0) {
      url.searchParams.set("materials", materials.join(","));
    } else {
      url.searchParams.delete("materials");
    }

    // Get price range
    const priceRange = document.querySelector(
      'input[name="priceRange"]:checked'
    )?.value;
    if (priceRange && priceRange !== "all") {
      if (priceRange === "custom") {
        const minPrice = document.getElementById("minPrice")?.value;
        const maxPrice = document.getElementById("maxPrice")?.value;
        if (minPrice) url.searchParams.set("min_price", minPrice);
        if (maxPrice) url.searchParams.set("max_price", maxPrice);
      } else {
        const [min, max] = priceRange.split("-");
        if (min) url.searchParams.set("min_price", min);
        if (max && max !== "up") url.searchParams.set("max_price", max);
      }
    } else {
      url.searchParams.delete("min_price");
      url.searchParams.delete("max_price");
    }

    // Get sort
    const sort = document.getElementById("sortSelect")?.value;
    if (sort) {
      url.searchParams.set("sort", sort);
    }

    // Reset to page 1 when filtering
    url.searchParams.set("page", "1");

    // Show loading state
    const btn = document.getElementById("applyFiltersBtn");
    if (btn) {
      btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang lọc...';
      btn.disabled = true;
    }

    // Redirect to new URL
    window.location.href = url.toString();
  } catch (error) {
    alert("Có lỗi xảy ra: " + error.message);
  }
}

// Clear All Filters Function
function clearAllFilters() {
  try {
    const url = new URL(window.location.origin + window.location.pathname);

    // Show loading state
    const btn = document.getElementById("clearFiltersBtn");
    if (btn) {
      btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xóa...';
      btn.disabled = true;
    }

    // Redirect to clean URL
    window.location.href = url.toString();
  } catch (error) {
    alert("Có lỗi xảy ra: " + error.message);
  }
}

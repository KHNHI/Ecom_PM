// Add Customer page initialization
document.addEventListener('DOMContentLoaded', function() {
    // Real-time preview updates
    const customerNameInput = document.getElementById('customerName');
    if (customerNameInput) {
        customerNameInput.addEventListener('input', function() {
            const name = this.value || 'Tên Khách Hàng';
            const previewName = document.getElementById('previewName');
            if (previewName) {
                previewName.textContent = name;
            }
        });
    }

    const customerEmailInput = document.getElementById('customerEmail');
    if (customerEmailInput) {
        customerEmailInput.addEventListener('input', function() {
            const email = this.value || 'email@example.com';
            const previewEmail = document.getElementById('previewEmail');
            if (previewEmail) {
                previewEmail.textContent = email;
            }
        });
    }

    const customerPhoneInput = document.getElementById('customerPhone');
    if (customerPhoneInput) {
        customerPhoneInput.addEventListener('input', function() {
            const phone = this.value || '0901234567';
            const previewPhone = document.getElementById('previewPhone');
            if (previewPhone) {
                previewPhone.textContent = phone;
            }
        });
    }

    const customerRoleSelect = document.getElementById('customerRole');
    if (customerRoleSelect) {
        customerRoleSelect.addEventListener('change', function() {
            const role = this.value || 'customer';
            const roleNames = {
                'customer': 'Customer',
                'vip_customer': 'VIP Customer',
                'admin': 'Admin',
                'manager': 'Manager'
            };
            const previewRole = document.getElementById('previewRole');
            if (previewRole) {
                previewRole.textContent = roleNames[role];
            }
        });
    }

    const isActiveCheckbox = document.getElementById('isActive');
    if (isActiveCheckbox) {
        isActiveCheckbox.addEventListener('change', function() {
            const statusBadge = document.getElementById('previewStatus');
            if (statusBadge) {
                if (this.checked) {
                    statusBadge.textContent = 'Hoạt động';
                    statusBadge.style.backgroundColor = 'var(--success-color)';
                } else {
                    statusBadge.textContent = 'Không hoạt động';
                    statusBadge.style.backgroundColor = 'var(--danger-color)';
                }
            }
        });
    }

    // Profile Image Upload Handler
    const profileImageInput = document.getElementById('profileImageInput');
    if (profileImageInput) {
        profileImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('Ảnh vượt quá 2MB!');
                    this.value = '';
                    return;
                }
                
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    alert('Vui lòng chọn file ảnh!');
                    this.value = '';
                    return;
                }
                
                // Preview image
                const reader = new FileReader();
                reader.onload = function(event) {
                    const profileImagePreview = document.getElementById('profileImagePreview');
                    const previewAvatar = document.getElementById('previewAvatar');
                    if (profileImagePreview) {
                        profileImagePreview.src = event.target.result;
                    }
                    if (previewAvatar) {
                        previewAvatar.src = event.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Load provinces on page load
    loadProvinces();

    // Form submission
    const addCustomerForm = document.getElementById('addCustomerForm');
    if (addCustomerForm) {
        addCustomerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            let isValid = true;

            // Basic validation
            const requiredFields = ['customerName', 'customerEmail', 'customerPhone'];
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field && !field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else if (field) {
                    field.classList.remove('is-invalid');
                }
            });

            // Email validation
            const email = document.getElementById('customerEmail');
            if (email && email.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email.value)) {
                    email.classList.add('is-invalid');
                    isValid = false;
                }
            }

            // Password validation
            const password = document.getElementById('customerPassword');
            if (password && password.value.length < 8) {
                password.classList.add('is-invalid');
                isValid = false;
            }

            if (isValid) {
                alert('Khách hàng đã được tạo thành công!');
                window.location.href = 'customers.html';
            } else {
                alert('Vui lòng kiểm tra và điền đúng thông tin bắt buộc!');
            }
        });
    }
});

// Vietnam Address API - Using Vietnam Provinces Open API
// API Documentation: https://provinces.open-api.vn/api/
const API_BASE_URL = 'https://provinces.open-api.vn/api';

// Load Provinces from API when country changes
async function loadProvinces() {
    const countrySelect = document.getElementById('addressCountry');
    const provinceSelect = document.getElementById('addressProvince');
    
    if (!provinceSelect) return;
    
    const selectedCountry = countrySelect ? countrySelect.value : 'VN';
    
    // Only load Vietnam provinces from API
    if (selectedCountry !== 'VN') {
        provinceSelect.innerHTML = '<option value="">Không có dữ liệu</option>';
        provinceSelect.disabled = true;
        
        // Reset district and ward
        const districtSelect = document.getElementById('addressDistrict');
        const wardSelect = document.getElementById('addressWard');
        if (districtSelect) {
            districtSelect.innerHTML = '<option value="">Chọn quận/huyện</option>';
            districtSelect.disabled = true;
        }
        if (wardSelect) {
            wardSelect.innerHTML = '<option value="">Chọn phường/xã</option>';
            wardSelect.disabled = true;
        }
        return;
    }

    try {
        // Show loading state
        provinceSelect.innerHTML = '<option value="">Đang tải dữ liệu...</option>';
        provinceSelect.disabled = true;

        // Fetch provinces from API
        const response = await fetch(`${API_BASE_URL}/p/`);
        if (!response.ok) throw new Error('Failed to fetch provinces');
        
        const provinces = await response.json();
        
        // Clear and populate provinces
        provinceSelect.innerHTML = '<option value="">Chọn tỉnh/thành phố</option>';
        provinces.forEach(province => {
            const option = document.createElement('option');
            option.value = province.code;
            option.textContent = province.name;
            option.dataset.provinceName = province.name;
            provinceSelect.appendChild(option);
        });
        
        provinceSelect.disabled = false;
        
        console.log(`✓ Đã tải ${provinces.length} tỉnh/thành phố từ API`);
    } catch (error) {
        console.error('Error loading provinces:', error);
        provinceSelect.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
        provinceSelect.disabled = false;
        alert('Không thể tải danh sách tỉnh/thành phố. Vui lòng kiểm tra kết nối internet và thử lại!');
    }
}

// Load Districts based on Province
async function loadDistricts() {
    const provinceSelect = document.getElementById('addressProvince');
    const districtSelect = document.getElementById('addressDistrict');
    const wardSelect = document.getElementById('addressWard');
    
    if (!districtSelect || !provinceSelect) return;
    
    const provinceCode = provinceSelect.value;
    
    // Reset district and ward if no province selected
    if (!provinceCode) {
        districtSelect.innerHTML = '<option value="">Chọn quận/huyện</option>';
        districtSelect.disabled = true;
        if (wardSelect) {
            wardSelect.innerHTML = '<option value="">Chọn phường/xã</option>';
            wardSelect.disabled = true;
        }
        return;
    }

    try {
        // Show loading state
        districtSelect.innerHTML = '<option value="">Đang tải dữ liệu...</option>';
        districtSelect.disabled = true;
        
        // Reset ward
        if (wardSelect) {
            wardSelect.innerHTML = '<option value="">Chọn phường/xã</option>';
            wardSelect.disabled = true;
        }

        // Fetch province with districts from API
        const response = await fetch(`${API_BASE_URL}/p/${provinceCode}?depth=2`);
        if (!response.ok) throw new Error('Failed to fetch districts');
        
        const provinceData = await response.json();
        const districts = provinceData.districts || [];
        
        // Clear and populate districts
        districtSelect.innerHTML = '<option value="">Chọn quận/huyện</option>';
        districts.forEach(district => {
            const option = document.createElement('option');
            option.value = district.code;
            option.textContent = district.name;
            option.dataset.districtName = district.name;
            districtSelect.appendChild(option);
        });
        
        districtSelect.disabled = false;
        
        console.log(`✓ Đã tải ${districts.length} quận/huyện`);
    } catch (error) {
        console.error('Error loading districts:', error);
        districtSelect.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
        districtSelect.disabled = false;
        alert('Không thể tải danh sách quận/huyện. Vui lòng thử lại!');
    }
}

// Load Wards based on District
async function loadWards() {
    const districtSelect = document.getElementById('addressDistrict');
    const wardSelect = document.getElementById('addressWard');
    
    if (!wardSelect || !districtSelect) return;
    
    const districtCode = districtSelect.value;
    
    // Reset ward if no district selected
    if (!districtCode) {
        wardSelect.innerHTML = '<option value="">Chọn phường/xã</option>';
        wardSelect.disabled = true;
        return;
    }

    try {
        // Show loading state
        wardSelect.innerHTML = '<option value="">Đang tải dữ liệu...</option>';
        wardSelect.disabled = true;

        // Fetch district with wards from API
        const response = await fetch(`${API_BASE_URL}/d/${districtCode}?depth=2`);
        if (!response.ok) throw new Error('Failed to fetch wards');
        
        const districtData = await response.json();
        const wards = districtData.wards || [];
        
        // Clear and populate wards
        wardSelect.innerHTML = '<option value="">Chọn phường/xã</option>';
        wards.forEach(ward => {
            const option = document.createElement('option');
            option.value = ward.code;
            option.textContent = ward.name;
            option.dataset.wardName = ward.name;
            wardSelect.appendChild(option);
        });
        
        wardSelect.disabled = false;
        
        console.log(`✓ Đã tải ${wards.length} phường/xã`);
    } catch (error) {
        console.error('Error loading wards:', error);
        wardSelect.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
        wardSelect.disabled = false;
        alert('Không thể tải danh sách phường/xã. Vui lòng thử lại!');
    }
}

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = event.target;
    
    if (field && icon) {
        if (field.type === 'password') {
            field.type = 'text';
            icon.src = 'https://cdn-icons-png.flaticon.com/512/159/159604.png';
        } else {
            field.type = 'password';
            icon.src = 'https://cdn-icons-png.flaticon.com/512/159/159666.png';
        }
    }
}

function saveDraft() {
    console.log('Saving draft...');
    alert('Đã lưu nháp!');
}

function cancelAdd() {
    if (confirm('Bạn có chắc chắn muốn hủy? Tất cả thông tin sẽ bị mất.')) {
        window.location.href = 'customers.html';
    }
}

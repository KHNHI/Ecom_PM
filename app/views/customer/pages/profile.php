<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Thông tin cá nhân'; ?> - Jewelry Store</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <html xmlns:th="http://www.thymeleaf.org">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="<?= asset('css/css.css?v=' . time()) ?>" rel="stylesheet">  
    
    <style>
        :root {
            --gold: #d4af37;
            --dark-gold: #b8941f;
            --light-gold: #f0e68c;
            --cream: #f8f6f0;
            --dark-brown: #3a2f28;
            --light-gray: #f5f5f5;
        }
        
        .profile-sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px 25px;
            box-shadow: 0 5px 25px rgba(212, 175, 55, 0.2);
            border: 1px solid rgba(212, 175, 55, 0.1);
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--gold);
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
        }
        
        .profile-avatar:hover {
            transform: scale(1.05);
            border-color: var(--dark-gold);
        }
        
        .profile-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 25px rgba(212, 175, 55, 0.2);
            border: 1px solid rgba(212, 175, 55, 0.1);
        }
        
        .nav-pills .nav-link {
            border-radius: 10px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            color: var(--dark-brown);
            font-weight: 500;
        }
        
        .nav-pills .nav-link:hover {
            background-color: var(--cream);
            color: var(--gold);
        }
        
        .nav-pills .nav-link.active {
            background: var(--gold);
            color: white;
        }
        
        .nav-pills .nav-link.active:hover {
            background: var(--dark-gold);
            color: white;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.25);
            background: white;
        }
        
        .btn-primary {
            background: var(--gold);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-primary:hover {
            background: var(--dark-gold);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(212, 175, 55, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(212, 175, 55, 0.7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            cursor: pointer;
        }
        
        .avatar-container:hover .upload-overlay {
            opacity: 1;
        }
        
        .badge.bg-success {
            background-color: var(--gold) !important;
        }
        
        .text-primary {
            color: var(--gold) !important;
        }
        
        h5.fw-bold {
            color: var(--dark-brown);
        }
        
        .form-label {
            color: var(--dark-brown);
        }
        
        select.form-control {
            background-image: linear-gradient(45deg, transparent 50%, var(--dark-brown) 50%), 
                            linear-gradient(135deg, var(--dark-brown) 50%, transparent 50%);
            background-position: calc(100% - 20px) calc(1em + 2px), 
                               calc(100% - 15px) calc(1em + 2px);
            background-size: 5px 5px, 5px 5px;
            background-repeat: no-repeat;
            appearance: none;
        }
        
        select.form-control:focus {
            background-image: linear-gradient(45deg, var(--gold) 50%, transparent 50%), 
                            linear-gradient(135deg, transparent 50%, var(--gold) 50%);
        }
        
        body {
            background: linear-gradient(rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.7)),
                        url("https://images.unsplash.com/photo-1608042314453-ae338d80c427") center/cover fixed;
        }
        
        .wishlist-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .wishlist-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .wishlist-card img {
            height: 200px;
            object-fit: cover;
        }
        
        .wishlist-card .card-body {
            padding: 20px;
        }
        
        .price {
            color: #667eea;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .btn-remove-wishlist {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.9);
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #dc3545;
            transition: all 0.3s ease;
        }
        
        .btn-remove-wishlist:hover {
            background: #dc3545;
            color: white;
        }
        
        /* Orders Management Styles */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .order-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            border: 1px solid rgba(212, 175, 55, 0.2);
            padding: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.15);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
        }
        
        .order-id-date {
            flex: 1;
        }
        
        .order-id {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .order-id strong {
            color: var(--gold);
            font-weight: 600;
        }
        
        .order-date {
            font-size: 0.85rem;
            color: #999;
        }
        
        .order-status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .order-status-badge.pending {
            background: rgba(255, 193, 7, 0.2);
            color: #FFC107;
        }
        
        .order-status-badge.paid {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }
        
        .order-status-badge.shipped {
            background: rgba(33, 150, 243, 0.2);
            color: #2196F3;
        }
        
        .order-status-badge.delivered {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }
        
        .order-status-badge.cancelled {
            background: rgba(244, 67, 54, 0.2);
            color: #F44336;
        }
        
        .order-items-list {
            margin-bottom: 15px;
        }
        
        .order-item {
            display: flex;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(212, 175, 55, 0.05);
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            background: var(--light-gray);
        }
        
        .order-item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .order-item-name {
            font-weight: 500;
            color: var(--dark-brown);
            margin-bottom: 3px;
            font-size: 0.95rem;
        }
        
        .order-item-meta {
            font-size: 0.8rem;
            color: #999;
        }
        
        .order-item-price {
            text-align: right;
            font-weight: 600;
            color: var(--gold);
        }
        
        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid rgba(212, 175, 55, 0.1);
        }
        
        .order-total {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }
        
        .order-total-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 3px;
        }
        
        .order-total-amount {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--gold);
        }
        
        .order-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-cancel-order {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
            border: 1px solid #F44336;
        }
        
        .btn-cancel-order:hover {
            background: #F44336;
            color: white;
        }
        
        .btn-view-detail {
            background: var(--gold);
            color: white;
            border: 1px solid var(--gold);
        }
        
        .btn-view-detail:hover {
            background: var(--dark-gold);
            border-color: var(--dark-gold);
        }
        
        .btn-review {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            border: 1px solid #4CAF50;
        }
        
        .btn-review:hover {
            background: #4CAF50;
            color: white;
        }
        
        .empty-orders {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-orders i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 15px;
        }
        
        .empty-orders h6 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .empty-orders p {
            color: #999;
            margin-bottom: 20px;
        }
        
        /* Filter buttons */
        .orders-list + .text-center button.btn-outline-primary,
        [data-filter] {
            transition: all 0.3s ease;
        }
        
        [data-filter].active {
            background: var(--gold);
            color: white;
            border-color: var(--gold);
        }
        
        /* Rating Input Styles */
        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 8px;
        }
        
        .rating-input input[type="radio"] {
            display: none;
        }
        
        .rating-input label {
            color: #ddd;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .rating-input label:hover,
        .rating-input label:hover ~ label,
        .rating-input input[type="radio"]:checked ~ label {
            color: #ffc107;
        }
        
        .rating-input label:hover {
            transform: scale(1.1);
        }
        
        /* Review Modal Styles */
        #reviewModal .modal-content {
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 15px;
        }
        
        #reviewModal .modal-header {
            border-bottom: 2px solid rgba(212, 175, 55, 0.1);
            background: rgba(248, 246, 240, 0.5);
        }
        
        #reviewModal .nav-tabs .nav-link {
            color: var(--dark-brown);
            border: none;
            border-bottom: 2px solid transparent;
        }
        
        #reviewModal .nav-tabs .nav-link:hover {
            border-bottom-color: var(--gold);
            color: var(--gold);
        }
        
        #reviewModal .nav-tabs .nav-link.active {
            border-bottom-color: var(--gold);
            color: var(--gold);
            background: none;
        }
    </style>
</head>
<body class="bg-light">
    
    <!-- Include Header -->
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <div class="container mt-5 pt-4">
        <div class="row">
            <!-- Profile Sidebar -->
            <div class="col-lg-4">
                <div class="profile-sidebar text-center">
                    <!-- Avatar -->
                    <div class="avatar-container position-relative d-inline-block mb-4">
                        <img id="profileAvatar" 
                             src="<?php echo !empty($user->avatar) ? $user->avatar : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=667eea&color=fff&size=120'; ?>" 
                             alt="Avatar" 
                             class="profile-avatar">
                        <div class="upload-overlay" onclick="document.getElementById('avatarInput').click()">
                            <i class="fas fa-camera fa-2x text-white"></i>
                        </div>
                        <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                    </div>
                    
                    <!-- User Info -->
                    <h4 class="fw-bold mb-2"><?php echo htmlspecialchars($user->name); ?></h4>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($user->email); ?></p>
                    <span class="badge bg-success mb-4">
                        <i class="fas fa-check-circle me-1"></i>
                        Đã xác thực
                    </span>
                    
                    <!-- Navigation Pills -->
                    <ul class="nav nav-pills flex-column" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active w-100 text-start" id="info-tab" data-bs-toggle="pill" data-bs-target="#info" type="button" role="tab">
                                <i class="fas fa-user me-2"></i>Thông tin cá nhân
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link w-100 text-start" id="password-tab" data-bs-toggle="pill" data-bs-target="#password" type="button" role="tab">
                                <i class="fas fa-key me-2"></i>Đổi mật khẩu
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link w-100 text-start" id="wishlist-tab" data-bs-toggle="pill" data-bs-target="#wishlist" type="button" role="tab">
                                <i class="fas fa-heart me-2"></i>Danh sách yêu thích
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link w-100 text-start" id="orders-tab" data-bs-toggle="pill" data-bs-target="#orders" type="button" role="tab">
                                <i class="fas fa-shopping-bag me-2"></i>Đơn hàng của tôi
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link w-100 text-start" id="settings-tab" data-bs-toggle="pill" data-bs-target="#settings" type="button" role="tab">
                                <i class="fas fa-cog me-2"></i>Cài đặt
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Profile Content -->
            <div class="col-lg-8">
                <div class="profile-content">
                    <div class="tab-content" id="profileTabContent">
                        <!-- Personal Info Tab -->
                        <div class="tab-pane fade show active" id="info" role="tabpanel">
                            <h5 class="fw-bold mb-4">
                                <i class="fas fa-user text-primary me-2"></i>
                                Thông tin cá nhân
                            </h5>
                            
                            <div id="alertContainer"></div>
                            
                            <form id="profileForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Họ và tên *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user->name); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Email</label>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user->email); ?>" readonly>
                                        <small class="text-muted">Email không thể thay đổi</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Số điện thoại</label>
                                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user->phone ?? ''); ?>">
                                        <!-- Debug: <?php echo "DEBUG: phone = '" . ($user->phone ?? 'NULL') . "'"; ?> -->
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Ngày sinh</label>
                                        <input type="date" class="form-control" name="date_of_birth" value="<?php echo $user->date_of_birth ?? ''; ?>">
                                        <!-- Debug: <?php echo "DEBUG: date_of_birth = '" . ($user->date_of_birth ?? 'NULL') . "'"; ?> -->
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Giới tính</label>
                                        <select class="form-control" name="gender">
                                            <option value="">Chọn giới tính</option>
                                            <option value="male" <?php echo ($user->gender ?? '') === 'male' ? 'selected' : ''; ?>>Nam</option>
                                            <option value="female" <?php echo ($user->gender ?? '') === 'female' ? 'selected' : ''; ?>>Nữ</option>
                                            <option value="other" <?php echo ($user->gender ?? '') === 'other' ? 'selected' : ''; ?>>Khác</option>
                                        </select>
                                        <!-- Debug: <?php echo "DEBUG: gender = '" . ($user->gender ?? 'NULL') . "'"; ?> -->
                                    </div>
                                </div>
                                
                                <!-- Address Section with UX -->
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Địa chỉ</label>
                                    
                                    <!-- Country (Fixed) -->
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Quốc gia</label>
                                        <input type="text" class="form-control" value="Việt Nam" readonly>
                                        <input type="hidden" name="country" value="Vietnam">
                                    </div>
                                    
                                    <!-- Province/City -->
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Tỉnh/Thành phố <span class="text-danger">*</span></label>
                                        <select class="form-select" id="provinceSelect" name="province" required>
                                            <option value="">Chọn tỉnh/thành phố...</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Ward (directly under Province) -->
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Phường/Xã/Thị trấn <span class="text-danger">*</span></label>
                                        <select class="form-select" id="wardSelect" name="ward" required disabled>
                                            <option value="">Chọn phường/xã/thị trấn...</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Street Address -->
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Số nhà, tên đường <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="street" 
                                               placeholder="Ví dụ: 123 Nguyễn Du" 
                                               value="<?php echo htmlspecialchars(($defaultAddress && isset($defaultAddress->street)) ? $defaultAddress->street : ''); ?>" required>
                                        <div class="form-text">Nhập số nhà và tên đường chi tiết</div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    Cập nhật thông tin
                                </button>
                            </form>
                        </div>
                        
                        <!-- Change Password Tab -->
                        <div class="tab-pane fade" id="password" role="tabpanel">
                            <h5 class="fw-bold mb-4">
                                <i class="fas fa-key text-primary me-2"></i>
                                Đổi mật khẩu
                            </h5>
                            
                            <div id="passwordAlertContainer"></div>
                            
                            <form id="passwordForm">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Mật khẩu hiện tại *</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Mật khẩu mới *</label>
                                    <input type="password" class="form-control" name="new_password" minlength="6" required>
                                    <small class="text-muted">Mật khẩu phải có ít nhất 6 ký tự</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Xác nhận mật khẩu mới *</label>
                                    <input type="password" class="form-control" name="confirm_password" minlength="6" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key me-2"></i>
                                    Đổi mật khẩu
                                </button>
                            </form>
                        </div>
                        
                        <!-- Wishlist Tab -->
                        <div class="tab-pane fade" id="wishlist" role="tabpanel">
                            <h5 class="fw-bold mb-4">
                                <i class="fas fa-heart text-primary me-2"></i>
                                Danh sách yêu thích
                            </h5>
                            
                            <div id="wishlistContainer">
                                <!-- Wishlist items sẽ được load bằng JavaScript -->
                                <div class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Đang tải...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Orders Tab -->
                        <div class="tab-pane fade" id="orders" role="tabpanel">
                            <h5 class="fw-bold mb-4">
                                <i class="fas fa-shopping-bag text-primary me-2"></i>
                                Đơn hàng của tôi
                            </h5>
                            
                            <!-- Alert Container -->
                            <div id="ordersAlertContainer"></div>
                            
                            <!-- Filter & Sort Buttons -->
                            <div class="mb-4 d-flex gap-2 flex-wrap">
                                <button class="btn btn-sm btn-outline-primary active" data-filter="all">
                                    <i class="fas fa-list me-1"></i>Tất cả
                                </button>
                                <button class="btn btn-sm btn-outline-primary" data-filter="pending">
                                    <i class="fas fa-clock me-1"></i>Chờ xác nhận
                                </button>
                                <button class="btn btn-sm btn-outline-primary" data-filter="paid">
                                    <i class="fas fa-check-circle me-1"></i>Đã thanh toán
                                </button>
                                <button class="btn btn-sm btn-outline-primary" data-filter="shipped">
                                    <i class="fas fa-truck me-1"></i>Đang giao
                                </button>
                                <button class="btn btn-sm btn-outline-primary" data-filter="delivered">
                                    <i class="fas fa-box-open me-1"></i>Đã giao
                                </button>
                                <button class="btn btn-sm btn-outline-danger" data-filter="cancelled">
                                    <i class="fas fa-times-circle me-1"></i>Hủy
                                </button>
                            </div>
                            
                            <!-- Orders Container -->
                            <div id="ordersContainer" class="orders-list">
                                <div class="text-center py-5">
                                    <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                                    <p class="text-muted">Đang tải đơn hàng...</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Settings Tab -->
                        <div class="tab-pane fade" id="settings" role="tabpanel">
                            <h5 class="fw-bold mb-4">
                                <i class="fas fa-cog text-primary me-2"></i>
                                Cài đặt
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <i class="fas fa-bell me-2"></i>
                                                Thông báo
                                            </h6>
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                                                <label class="form-check-label" for="emailNotifications">
                                                    Nhận thông báo qua email
                                                </label>
                                            </div>
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" id="orderUpdates" checked>
                                                <label class="form-check-label" for="orderUpdates">
                                                    Cập nhật đơn hàng
                                                </label>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="promotions">
                                                <label class="form-check-label" for="promotions">
                                                    Khuyến mãi và ưu đãi
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <i class="fas fa-shield-alt me-2"></i>
                                                Bảo mật
                                            </h6>
                                            <div class="mb-3">
                                                <label class="form-label">Xác thực 2 bước</label>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-success me-2">Đã bật</span>
                                                    <button class="btn btn-sm btn-outline-secondary">Cấu hình</button>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Phiên đăng nhập</label>
                                                <div>
                                                    <small class="text-muted">Thiết bị: Chrome trên Windows</small><br>
                                                    <small class="text-muted">Lần cuối: Hôm nay lúc 14:30</small>
                                                </div>
                                            </div>
                                            <button class="btn btn-sm btn-outline-danger">Đăng xuất tất cả thiết bị</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title text-danger">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                Vùng nguy hiểm
                                            </h6>
                                            <p class="text-muted">Các hành động này không thể hoàn tác. Hãy cân nhắc kỹ trước khi thực hiện.</p>
                                            <button class="btn btn-outline-danger">
                                                <i class="fas fa-trash me-2"></i>
                                                Xóa tài khoản
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Profile form submission
        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const alertContainer = document.getElementById('alertContainer');
            
            try {
                const response = await fetch('/Ecom_PM/profile/update', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                showAlert(alertContainer, result.success, result.message);
                
                if (result.success) {
                    // Update user name in header if changed
                    const nameInput = document.querySelector('input[name="name"]');
                    if (nameInput.value !== '<?php echo addslashes($user->name); ?>') {
                        setTimeout(() => location.reload(), 1500);
                    }
                }
                
            } catch (error) {
                showAlert(alertContainer, false, 'Có lỗi xảy ra khi kết nối đến server!');
            }
        });
        
        // Password form submission
        document.getElementById('passwordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const alertContainer = document.getElementById('passwordAlertContainer');
            
            // Validate password confirmation
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');
            
            if (newPassword !== confirmPassword) {
                showAlert(alertContainer, false, 'Mật khẩu xác nhận không khớp!');
                return;
            }
            
            try {
                const response = await fetch('/Ecom_PM/profile/change-password', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                showAlert(alertContainer, result.success, result.message);
                
                if (result.success) {
                    this.reset();
                }
                
            } catch (error) {
                showAlert(alertContainer, false, 'Có lỗi xảy ra khi kết nối đến server!');
            }
        });
        
        // Avatar upload
        document.getElementById('avatarInput').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('avatar', file);
            
            try {
                const response = await fetch('/Ecom_PM/profile/upload-avatar', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('profileAvatar').src = result.data.avatar_url + '?t=' + Date.now();
                    showAlert(document.getElementById('alertContainer'), true, result.message);
                } else {
                    showAlert(document.getElementById('alertContainer'), false, result.message);
                }
                
            } catch (error) {
                showAlert(document.getElementById('alertContainer'), false, 'Có lỗi xảy ra khi upload avatar!');
            }
        });
        
        // Show alert function
        function showAlert(container, success, message) {
            container.innerHTML = `
                <div class="alert alert-${success ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
                    <i class="fas fa-${success ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }
        
        // Load wishlist when tab is shown
        document.getElementById('wishlist-tab').addEventListener('shown.bs.tab', function () {
            loadWishlist();
        });
        
        // Load wishlist function
        async function loadWishlist() {
            const container = document.getElementById('wishlistContainer');
            
            try {
                const response = await fetch('/Ecom_PM/wishlist', {
                    method: 'GET',
                    credentials: 'same-origin',  // Include cookies/session
                    headers: {
                        'Cache-Control': 'no-cache'
                    }
                });
                
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                // Get the HTML content
                const html = await response.text();
                
                // DEBUG: Log response details
                console.log('=== WISHLIST DEBUG ===');
                console.log('Response status:', response.status);
                console.log('HTML length:', html.length);
                console.log('HTML contains "wishlist":', html.includes('wishlist'));
                console.log('HTML contains "error":', html.includes('error'));
                console.log('HTML first 500 chars:', html.substring(0, 500));
                
                // Parse the HTML to extract wishlist items
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // DEBUG: Check different selectors
                console.log('Elements found:');
                console.log('- #wishlistItemsContainer:', doc.querySelector('#wishlistItemsContainer') ? 'YES' : 'NO');
                console.log('- #wishlistGrid:', doc.querySelector('#wishlistGrid') ? 'YES' : 'NO');
                console.log('- .col-lg-3:', doc.querySelectorAll('.col-lg-3').length);
                console.log('- .product-card:', doc.querySelectorAll('.product-card').length);
                console.log('- .empty-wishlist:', doc.querySelector('.empty-wishlist') ? 'YES' : 'NO');
                
                // If response is redirect (login page), try API instead
                if (html.includes('signin') || html.includes('login') || response.status === 302) {
                    console.log('Detected redirect to login, trying API approach...');
                    await loadWishlistViaAPI();
                    return;
                }
                
                // Find wishlist items in the response
                const wishlistItemsContainer = doc.querySelector('#wishlistItemsContainer');
                
                if (wishlistItemsContainer && wishlistItemsContainer.children.length > 0) {
                    // Convert wishlist items to profile format
                    const items = Array.from(wishlistItemsContainer.children);
                    let wishlistHTML = '<div class="row">';
                    
                    items.forEach(item => {
                        const name = item.querySelector('.product-name')?.textContent?.trim() || 'Sản phẩm';
                        const price = item.querySelector('.price')?.textContent?.trim() || '0₫';
                        const img = item.querySelector('img')?.src || '/Ecom_PM/public/assets/images/placeholder.svg';
                        const productId = item.dataset.productId || '';
                        const href = item.querySelector('a')?.href || '#';
                        
                        wishlistHTML += `
                            <div class="col-md-4 col-sm-6 mb-4">
                                <div class="card wishlist-card position-relative">
                                    <button class="btn-remove-wishlist" onclick="removeFromWishlist(${productId})">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <a href="${href}" style="text-decoration: none; color: inherit;">
                                        <img src="${img}" class="card-img-top" alt="${name}">
                                        <div class="card-body">
                                            <h6 class="card-title">${name}</h6>
                                            <p class="price mb-0">${price}</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        `;
                    });
                    
                    wishlistHTML += '</div>';
                    container.innerHTML = wishlistHTML;
                } else {
                    // Try API approach if no items found in HTML
                    console.log('No items found in HTML, trying API...');
                    await loadWishlistViaAPI();
                }
                
            } catch (error) {
                console.error('Error loading wishlist:', error);
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h6 class="text-muted">Có lỗi xảy ra khi tải danh sách yêu thích</h6>
                        <button class="btn btn-outline-primary" onclick="loadWishlist()">
                            <i class="fas fa-redo me-2"></i>
                            Thử lại
                        </button>
                    </div>
                `;
            }
        }

        // Alternative method using API endpoint
        async function loadWishlistViaAPI() {
            const container = document.getElementById('wishlistContainer');
            
            try {
                console.log('Loading wishlist via API...');
                const response = await fetch('/Ecom_PM/api/wishlist/status', {
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                console.log('API Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`API Error: ${response.status}`);
                }

                const data = await response.json();
                console.log('API Response data:', data);
                
                if (data.success && data.count > 0) {
                    container.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fas fa-heart fa-3x text-primary mb-3"></i>
                            <h6>Bạn có ${data.count} sản phẩm yêu thích</h6>
                            <p class="text-muted">Đang tải chi tiết sản phẩm...</p>
                            <a href="/Ecom_PM/wishlist" class="btn btn-primary">
                                <i class="fas fa-eye me-2"></i>
                                Xem danh sách đầy đủ
                            </a>
                        </div>
                    `;
                } else {
                    // Empty wishlist
                    container.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">Danh sách yêu thích trống</h6>
                            <p class="text-muted">Hãy thêm những sản phẩm bạn yêu thích để xem chúng tại đây</p>
                            <a href="/Ecom_PM/products" class="btn btn-primary">
                                <i class="fas fa-shopping-cart me-2"></i>
                                Khám phá sản phẩm
                            </a>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('API Error loading wishlist:', error);
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h6 class="text-muted">Không thể tải danh sách yêu thích</h6>
                        <p class="text-muted">Vui lòng thử lại sau hoặc đăng nhập lại</p>
                        <button class="btn btn-outline-primary" onclick="loadWishlist()">
                            <i class="fas fa-redo me-2"></i>
                            Thử lại
                        </button>
                        <a href="/Ecom_PM/auth/signin" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Đăng nhập lại
                        </a>
                    </div>
                `;
            }
        }
        
        // Remove from wishlist function
        async function removeFromWishlist(productId) {
            try {
                const response = await fetch('/Ecom_PM/wishlist/remove', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Reload wishlist to reflect changes
                    loadWishlist();
                    showAlert(document.getElementById('alertContainer'), true, result.message);
                } else {
                    showAlert(document.getElementById('alertContainer'), false, result.message);
                }
                
            } catch (error) {
                console.error('Error removing from wishlist:', error);
                showAlert(document.getElementById('alertContainer'), false, 'Có lỗi xảy ra khi xóa sản phẩm!');
            }
        }
        
        // ================ ADDRESS LOCATION HANDLERS ================
        
        // Load provinces on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadProvinces();
        });
        
        // Load provinces
        async function loadProvinces() {
            try {
                const response = await fetch('/Ecom_PM/api/locations/provinces');
                const result = await response.json();
                
                if (result.success && result.data) {
                    const provinceSelect = document.getElementById('provinceSelect');
                    provinceSelect.innerHTML = '<option value="">Chọn tỉnh/thành phố...</option>';
                    
                    result.data.forEach(province => {
                        const option = document.createElement('option');
                        option.value = province.name; // Thay đổi: lưu tên thay vì code
                        option.textContent = province.name;
                        option.dataset.code = province.code; // Giữ lại code để load wards
                        option.dataset.fullName = province.full_name || province.name;
                        provinceSelect.appendChild(option);
                    });
                    
                    // Set current province if exists
                    const currentProvince = "<?php echo ($defaultAddress && isset($defaultAddress->province)) ? $defaultAddress->province : ''; ?>";
                    if (currentProvince) {
                        // Try to match by code first, then by name
                        let foundOption = Array.from(provinceSelect.options).find(option => 
                            option.value === currentProvince || 
                            option.textContent === currentProvince ||
                            (option.dataset.fullName && option.dataset.fullName === currentProvince)
                        );
                        
                        if (foundOption) {
                            provinceSelect.value = foundOption.value;
                            loadWardsByProvince(foundOption.value);
                        }
                    }
                }
            } catch (error) {
                console.error('Error loading provinces:', error);
            }
        }
        
        // Province change handler
        document.getElementById('provinceSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const provinceCode = selectedOption ? selectedOption.dataset.code : null; // Lấy code từ dataset
            const wardSelect = document.getElementById('wardSelect');
            
            // Reset wards
            wardSelect.innerHTML = '<option value="">Chọn phường/xã/thị trấn...</option>';
            
            if (provinceCode) {
                wardSelect.disabled = false;
                loadWardsByProvince(provinceCode);
            } else {
                wardSelect.disabled = true;
            }
        });
        
        // Load wards directly by province code (modern structure)
        async function loadWardsByProvince(provinceCode) {
            try {
                const response = await fetch(`/Ecom_PM/api/locations/wards?province_code=${provinceCode}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const wardSelect = document.getElementById('wardSelect');
                    wardSelect.innerHTML = '<option value="">Chọn phường/xã/thị trấn...</option>';
                    
                    result.data.forEach(ward => {
                        const option = document.createElement('option');
                        const wardName = ward.district_name ? `${ward.name} (${ward.district_name})` : ward.name;
                        option.value = wardName; // Thay đổi: lưu tên thay vì code
                        option.textContent = wardName;
                        option.dataset.code = ward.code; // Giữ lại code nếu cần
                        option.dataset.fullName = ward.full_name || ward.name;
                        option.dataset.districtName = ward.district_name || '';
                        wardSelect.appendChild(option);
                    });
                    
                    // Set current ward if exists
                    const currentWard = "<?php echo ($defaultAddress && isset($defaultAddress->ward)) ? $defaultAddress->ward : ''; ?>";
                    if (currentWard) {
                        // Try to match by code first, then by name
                        setTimeout(() => {
                            let foundWardOption = Array.from(wardSelect.options).find(option => 
                                option.value === currentWard || 
                                option.textContent === currentWard ||
                                (option.dataset.fullName && option.dataset.fullName === currentWard)
                            );
                            
                            if (foundWardOption) {
                                wardSelect.value = foundWardOption.value;
                            }
                        }, 500); // Wait for wards to load
                    }
                }
            } catch (error) {
                console.error('Error loading wards:', error);
            }
        }
    </script>
    
    <!-- Orders Management Script -->
    <script>
        // Store orders data globally
        let allOrders = [];
        let currentFilter = 'all';
        
        // Load orders when tab is shown
        document.getElementById('orders-tab').addEventListener('shown.bs.tab', function() {
            loadOrders();
        });
        
        // Load orders from server
        async function loadOrders() {
            const container = document.getElementById('ordersContainer');
            
            try {
                const response = await fetch('/Ecom_PM/api/orders/list', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success && result.data) {
                    allOrders = result.data;
                    displayOrders(allOrders);
                } else {
                    showEmptyOrders(container);
                }
                
            } catch (error) {
                console.error('Error loading orders:', error);
                container.innerHTML = `
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Không thể tải danh sách đơn hàng. Vui lòng thử lại.
                    </div>
                `;
            }
        }
        
        // Display orders
        function displayOrders(orders) {
            const container = document.getElementById('ordersContainer');
            
            if (!orders || orders.length === 0) {
                showEmptyOrders(container);
                return;
            }
            
            let html = '<div class="orders-list">';
            
            orders.forEach(order => {
                const statusClass = getStatusClass(order.order_status);
                const statusText = getStatusText(order.order_status);
                const orderDate = new Date(order.created_at).toLocaleDateString('vi-VN');
                
                html += `
                    <div class="order-card" data-order-id="${order.order_id}" data-status="${order.order_status}">
                        <div class="order-header">
                            <div class="order-id-date">
                                <div class="order-id">
                                    Đơn hàng <strong>#${order.order_id}</strong>
                                </div>
                                <div class="order-date">
                                    <i class="fas fa-calendar me-1"></i>${orderDate}
                                </div>
                            </div>
                            <span class="order-status-badge ${statusClass}">
                                ${statusText}
                            </span>
                        </div>
                        
                        <div class="order-items-list">
                            ${renderOrderItems(order.items)}
                        </div>
                        
                        <div class="order-footer">
                            <div class="order-actions">
                                ${getOrderActions(order)}
                            </div>
                            <div class="order-total">
                                <div class="order-total-label">Tổng cộng:</div>
                                <div class="order-total-amount">
                                    ${formatPrice(order.total_amount)}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
            attachOrderEventListeners();
        }
        
        // Show empty orders message
        function showEmptyOrders(container) {
            container.innerHTML = `
                <div class="empty-orders">
                    <i class="fas fa-shopping-bag"></i>
                    <h6>Bạn chưa có đơn hàng nào</h6>
                    <p>Hãy bắt đầu mua sắm để xem đơn hàng tại đây</p>
                    <a href="/Ecom_PM/products" class="btn btn-primary">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Mua sắm ngay
                    </a>
                </div>
            `;
        }
        
        // Render order items
        function renderOrderItems(items) {
            if (!items || items.length === 0) {
                return '<div class="text-muted text-center py-2">Không có sản phẩm</div>';
            }
            
            return items.map(item => `
                <div class="order-item">
                    <img src="${item.product_image || '/Ecom_PM/public/uploads/products/placeholder.png'}" 
                         alt="${item.product_name}" class="order-item-image">
                    <div class="order-item-details">
                        <div class="order-item-name">${item.product_name}</div>
                        <div class="order-item-meta">
                            <span class="me-2">SL: ${item.quantity}</span>
                            ${item.color ? `<span class="me-2">Màu: ${item.color}</span>` : ''}
                            ${item.size ? `<span class="me-2">Size: ${item.size}</span>` : ''}
                        </div>
                    </div>
                    <div class="order-item-price">${formatPrice(item.total_price)}</div>
                </div>
            `).join('');
        }
        
        // Get order status text and styling
        function getStatusClass(status) {
            const statusMap = {
                'pending': 'pending',
                'paid': 'paid',
                'shipped': 'shipped',
                'delivered': 'delivered',
                'cancelled': 'cancelled'
            };
            return statusMap[status] || 'pending';
        }
        
        function getStatusText(status) {
            const statusMap = {
                'pending': '⏳ Chờ xác nhận',
                'paid': '✓ Đã thanh toán',
                'shipped': '🚚 Đang giao',
                'delivered': '📦 Đã giao',
                'cancelled': '✕ Hủy đơn'
            };
            return statusMap[status] || 'Không xác định';
        }
        
        // Get available actions for order
        function getOrderActions(order) {
            let actions = `<button class="btn-action btn-view-detail" onclick="viewOrderDetail(${order.order_id})">
                <i class="fas fa-eye me-1"></i>Chi tiết
            </button>`;
            
            // Show cancel button for pending orders
            if (order.order_status === 'pending') {
                actions += `<button class="btn-action btn-cancel-order" onclick="cancelOrder(${order.order_id})">
                    <i class="fas fa-times me-1"></i>Hủy
                </button>`;
            }
            
            // Show review button for delivered orders
            if (order.order_status === 'delivered') {
                actions += `<button class="btn-action btn-review" onclick="reviewOrder(${order.order_id})">
                    <i class="fas fa-star me-1"></i>Đánh giá
                </button>`;
            }
            
            return actions;
        }
        
        // Format price to Vietnamese Dong
        function formatPrice(price) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND',
                maximumFractionDigits: 0
            }).format(price);
        }
        
        // View order detail
        function viewOrderDetail(orderId) {
            // Redirect to order detail page
            window.location.href = `/Ecom_PM/order-detail?id=${orderId}`;
        }
        
        // Cancel order
        async function cancelOrder(orderId) {
            if (!confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')) {
                return;
            }
            
            try {
                const response = await fetch(`/Ecom_PM/api/orders/${orderId}/cancel`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('ordersAlertContainer', true, 'Đơn hàng đã được hủy thành công');
                    loadOrders();
                } else {
                    showAlert('ordersAlertContainer', false, result.message || 'Không thể hủy đơn hàng');
                }
            } catch (error) {
                console.error('Error cancelling order:', error);
                showAlert('ordersAlertContainer', false, 'Có lỗi xảy ra khi hủy đơn hàng');
            }
        }
        
        // Review order
        function reviewOrder(orderId) {
            // Convert to number if needed
            orderId = parseInt(orderId);
            console.log('Review order ID:', orderId, 'Type:', typeof orderId);
            console.log('All orders:', allOrders);
            
            // Find the order and get its items
            let order = allOrders.find(o => parseInt(o.order_id) === orderId);
            console.log('Found order:', order);
            
            if (order && order.items && order.items.length > 0) {
                // Create review modal for items in this order
                showReviewModal(order);
            } else {
                // If order not found in allOrders, try to fetch it
                console.warn('Order not found in allOrders, attempting to fetch...');
                fetchAndReviewOrder(orderId);
            }
        }
        
        // Fetch a specific order and show review modal
        async function fetchAndReviewOrder(orderId) {
            try {
                const response = await fetch('/Ecom_PM/api/orders/detail?order_id=' + orderId, {
                    method: 'GET',
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    throw new Error('Order not found');
                }
                
                const result = await response.json();
                
                if (result.success && result.data) {
                    // Add to allOrders for future reference
                    allOrders.push(result.data);
                    showReviewModal(result.data);
                } else {
                    alert('Không tìm thấy đơn hàng: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error fetching order:', error);
                alert('Không thể tải thông tin đơn hàng. Vui lòng thử lại.');
            }
        }
        
        // Show review modal for order items
        function showReviewModal(order) {
            const items = order.items || [];
            
            if (items.length === 0) {
                alert('Không có sản phẩm để đánh giá');
                return;
            }
            
            console.log('Creating review modal with', items.length, 'items');
            
            // Create modal HTML
            let modalHtml = `
                <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="reviewModalLabel">Đánh giá sản phẩm từ đơn hàng #${order.order_id}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="review-items-tabs">
                                    <ul class="nav nav-tabs mb-3">
            `;
            
            // Create tabs for each item
            items.forEach((item, index) => {
                modalHtml += `
                    <li class="nav-item">
                        <button class="nav-link ${index === 0 ? 'active' : ''}" data-bs-toggle="tab" data-bs-target="#review-item-${index}" type="button">
                            ${item.product_name}
                        </button>
                    </li>
                `;
            });
            
            modalHtml += `
                                    </ul>
                                    <div class="tab-content">
            `;
            
            // Create review forms for each item
            items.forEach((item, index) => {
                // Ensure product_image is set
                const productImage = item.product_image || '/Ecom_PM/public/uploads/products/placeholder.png';
                
                modalHtml += `
                    <div class="tab-pane fade ${index === 0 ? 'show active' : ''}" id="review-item-${index}" role="tabpanel">
                        <form class="review-form" data-product-id="${item.product_id}">
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <img src="${productImage}" alt="${item.product_name}" 
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;"
                                         onerror="this.src='/Ecom_PM/public/uploads/products/placeholder.png'">
                                    <div>
                                        <h6 class="mb-0">${item.product_name}</h6>
                                        <small class="text-muted">SL: ${item.quantity}</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Đánh giá:</label>
                                <div class="rating-input">
                                    <input type="radio" name="rating" value="5" id="star5-${index}" required>
                                    <label for="star5-${index}"><i class="fas fa-star"></i></label>
                                    
                                    <input type="radio" name="rating" value="4" id="star4-${index}">
                                    <label for="star4-${index}"><i class="fas fa-star"></i></label>
                                    
                                    <input type="radio" name="rating" value="3" id="star3-${index}">
                                    <label for="star3-${index}"><i class="fas fa-star"></i></label>
                                    
                                    <input type="radio" name="rating" value="2" id="star2-${index}">
                                    <label for="star2-${index}"><i class="fas fa-star"></i></label>
                                    
                                    <input type="radio" name="rating" value="1" id="star1-${index}">
                                    <label for="star1-${index}"><i class="fas fa-star"></i></label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Tiêu đề đánh giá:</label>
                                <input type="text" class="form-control" name="title" 
                                       placeholder="Ví dụ: Sản phẩm tuyệt vời" required maxlength="255">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nội dung đánh giá:</label>
                                <textarea class="form-control" name="comment" rows="4" 
                                          placeholder="Chia sẻ ý kiến của bạn về sản phẩm này..." 
                                          required maxlength="1000"></textarea>
                                <small class="text-muted d-block mt-1">Tối đa 1000 ký tự</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-send me-2"></i>Gửi đánh giá
                            </button>
                        </form>
                    </div>
                `;
            });
            
            modalHtml += `
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove old modal if exists
            const oldModal = document.getElementById('reviewModal');
            if (oldModal) {
                oldModal.remove();
            }
            
            // Add modal to page
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Show modal with a small delay to ensure DOM is ready
            setTimeout(() => {
                const reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'));
                reviewModal.show();
                console.log('Modal shown');
                
                // Attach form submission handlers
                attachReviewFormHandlers();
            }, 100);
        }
        
        // Attach review form handlers
        function attachReviewFormHandlers() {
            document.querySelectorAll('.review-form').forEach(form => {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const productId = this.dataset.productId;
                    const rating = this.querySelector('input[name="rating"]:checked');
                    
                    if (!rating) {
                        alert('Vui lòng chọn mức đánh giá');
                        return;
                    }
                    
                    const title = this.querySelector('input[name="title"]').value.trim();
                    const comment = this.querySelector('textarea[name="comment"]').value.trim();
                    
                    // Validate
                    if (!title || !comment) {
                        alert('Vui lòng điền đầy đủ thông tin');
                        return;
                    }
                    
                    console.log('Submitting review for product', productId);
                    
                    // Submit review
                    try {
                        const response = await fetch('/Ecom_PM/api/reviews/add', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: new URLSearchParams({
                                product_id: productId,
                                rating: rating.value,
                                title: title,
                                comment: comment
                            })
                        });
                        
                        const result = await response.json();
                        console.log('Review response:', result);
                        
                        if (result.success) {
                            showAlert('ordersAlertContainer', true, 'Đánh giá của bạn đã được gửi và chờ duyệt');
                            const modal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
                            modal.hide();
                            setTimeout(() => {
                                modal.dispose();
                                document.getElementById('reviewModal').remove();
                            }, 500);
                        } else {
                            showAlert('ordersAlertContainer', false, result.message || 'Có lỗi khi gửi đánh giá');
                        }
                    } catch (error) {
                        console.error('Error submitting review:', error);
                        showAlert('ordersAlertContainer', false, 'Có lỗi xảy ra khi gửi đánh giá: ' + error.message);
                    }
                });
            });
        }
        
        // Filter orders
        document.querySelectorAll('[data-filter]').forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.dataset.filter;
                currentFilter = filter;
                
                // Update active button
                document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Filter and display orders
                let filtered = allOrders;
                if (filter !== 'all') {
                    filtered = allOrders.filter(o => o.order_status === filter);
                }
                
                displayOrders(filtered);
            });
        });
        
        // Attach event listeners to order cards
        function attachOrderEventListeners() {
            // Event listeners are attached via onclick handlers in buttons
            // You can add more interactive features here if needed
        }
        
        // Show alert function (reuse from profile)
        function showAlert(container, success, message) {
            const alertContainer = document.getElementById(container);
            alertContainer.innerHTML = `
                <div class="alert alert-${success ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
                    <i class="fas fa-${success ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Chi tiết đơn hàng'; ?> - Jewelry Store</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        body {
            background: linear-gradient(rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.7)),
                        url("https://images.unsplash.com/photo-1608042314453-ae338d80c427") center/cover fixed;
            padding-top: 120px;
        }

        .order-detail-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .order-status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
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

        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .info-item {
            padding: 15px;
            background: var(--cream);
            border-radius: 10px;
            border-left: 4px solid var(--gold);
        }

        .info-label {
            font-size: 0.85rem;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .info-value {
            font-size: 1.1rem;
            color: var(--dark-brown);
            font-weight: 500;
        }

        .order-items-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .order-item {
            display: flex;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
            align-items: flex-start;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            object-fit: cover;
            background: var(--light-gray);
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: var(--dark-brown);
            margin-bottom: 8px;
            font-size: 1.05rem;
        }

        .item-meta {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }

        .item-meta span {
            margin-right: 20px;
        }

        .item-price-section {
            text-align: right;
        }

        .item-unit-price {
            font-size: 0.9rem;
            color: #999;
            margin-bottom: 5px;
        }

        .item-total {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--gold);
        }

        .order-summary {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            max-width: 400px;
            margin-left: auto;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
            color: var(--dark-brown);
        }

        .summary-row.total {
            border-top: 2px solid var(--gold);
            border-bottom: none;
            padding-top: 15px;
            margin-top: 15px;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .summary-row.total .value {
            color: var(--gold);
            font-size: 1.3rem;
        }

        .address-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .address-title {
            font-weight: 600;
            color: var(--dark-brown);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .address-content {
            color: #666;
            line-height: 1.8;
        }

        .btn-back {
            background: transparent;
            border: 2px solid var(--gold);
            color: var(--gold);
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: var(--gold);
            color: white;
        }

        .btn-cancel {
            background: rgba(244, 67, 54, 0.1);
            border: 2px solid #F44336;
            color: #F44336;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-cancel:hover {
            background: #F44336;
            color: white;
        }

        .btn-review {
            background: rgba(76, 175, 80, 0.1);
            border: 2px solid #4CAF50;
            color: #4CAF50;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-review:hover {
            background: #4CAF50;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .breadcrumb {
            background: transparent;
            padding: 0 0 20px 0;
        }

        .breadcrumb-item {
            color: var(--gold);
        }

        .breadcrumb-item.active {
            color: var(--dark-brown);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/Ecom_website/">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="/Ecom_website/profile">Thông tin cá nhân</a></li>
                <li class="breadcrumb-item active">Chi tiết đơn hàng</li>
            </ol>
        </nav>

        <!-- Order Header -->
        <div class="order-detail-header">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="mb-3">
                        <i class="fas fa-shopping-bag me-2 text-gold"></i>
                        Đơn hàng #<?php echo $order->order_id; ?>
                    </h2>
                    <span class="order-status-badge <?php echo $order->order_status; ?>">
                        <?php
                        $statusMap = [
                            'pending' => '⏳ Chờ xác nhận',
                            'paid' => '✓ Đã thanh toán',
                            'shipped' => '🚚 Đang giao',
                            'delivered' => '📦 Đã giao',
                            'cancelled' => '✕ Hủy đơn'
                        ];
                        echo $statusMap[$order->order_status] ?? 'Không xác định';
                        ?>
                    </span>
                </div>
                <div class="text-end">
                    <small class="text-muted d-block">Ngày đặt hàng</small>
                    <strong><?php echo date('d/m/Y H:i', strtotime($order->created_at)); ?></strong>
                </div>
            </div>

            <!-- Order Info Grid -->
            <div class="order-info-grid">
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-envelope me-1"></i>Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($order->email); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-phone me-1"></i>Số điện thoại</div>
                    <div class="info-value"><?php echo htmlspecialchars($order->phone); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-credit-card me-1"></i>Phương thức thanh toán</div>
                    <div class="info-value"><?php echo $payment ? ($payment->payment_method === 'BANK_TRANSFER_HOME' ? 'Chuyển khoản' : 'Thanh toán tại cửa hàng') : 'Chưa rõ'; ?></div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="/Ecom_website/profile?tab=orders" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
                <?php if ($order->order_status === 'pending'): ?>
                    <button class="btn-cancel" onclick="cancelOrder(<?php echo $order->order_id; ?>)">
                        <i class="fas fa-times"></i> Hủy đơn hàng
                    </button>
                <?php endif; ?>
                <?php if ($order->order_status === 'delivered'): ?>
                    <button class="btn-review" onclick="reviewOrder(<?php echo $order->order_id; ?>)">
                        <i class="fas fa-star"></i> Đánh giá sản phẩm
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <!-- Order Items -->
            <div class="col-lg-8">
                <div class="order-items-card">
                    <h4 class="mb-4">
                        <i class="fas fa-box me-2"></i>
                        Sản phẩm trong đơn hàng
                    </h4>

                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $item): ?>
                            <div class="order-item">
                                <img src="<?php echo $item['product_image']; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="item-image">
                                
                                <div class="item-details">
                                    <div class="item-name">
                                        <a href="/Ecom_website/product/<?php echo $item['product_id']; ?>" target="_blank">
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                        </a>
                                    </div>
                                    <div class="item-meta">
                                        <span><strong>Số lượng:</strong> <?php echo $item['quantity']; ?></span>
                                        <?php if ($item['color']): ?>
                                            <span><strong>Màu:</strong> <?php echo htmlspecialchars($item['color']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($item['size']): ?>
                                            <span><strong>Size:</strong> <?php echo htmlspecialchars($item['size']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="item-price-section">
                                    <div class="item-unit-price">
                                        <?php echo number_format($item['unit_price'], 0, ',', '.'); ?> ₫ / cái
                                    </div>
                                    <div class="item-total">
                                        <?php echo number_format($item['total_price'], 0, ',', '.'); ?> ₫
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Không có sản phẩm trong đơn hàng
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Shipping Address -->
                <div class="address-card">
                    <div class="address-title">
                        <i class="fas fa-map-marker-alt"></i>
                        Địa chỉ giao hàng
                    </div>
                    <div class="address-content">
                        <strong><?php echo htmlspecialchars($order->full_name); ?></strong><br>
                        <?php if ($order->street): ?>
                            <?php echo htmlspecialchars($order->street); ?><br>
                        <?php endif; ?>
                        <?php if ($order->ward): ?>
                            <?php echo htmlspecialchars($order->ward); ?><br>
                        <?php endif; ?>
                        <?php if ($order->province): ?>
                            <?php echo htmlspecialchars($order->province); ?><br>
                        <?php endif; ?>
                        <?php if ($order->country): ?>
                            <?php echo htmlspecialchars($order->country); ?><br>
                        <?php endif; ?>
                        <br>
                        <strong>Điện thoại:</strong> <?php echo htmlspecialchars($order->phone); ?>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="order-summary">
                    <h5 class="mb-4">
                        <i class="fas fa-calculator me-2"></i>
                        Tóm tắt đơn hàng
                    </h5>

                    <div class="summary-row">
                        <span>Tạm tính:</span>
                        <span class="value"><?php echo number_format($order->total_amount - ($order->shipping_fee ?? 0) - ($order->discount_amount ?? 0), 0, ',', '.'); ?> ₫</span>
                    </div>

                    <?php if ($order->discount_amount > 0): ?>
                        <div class="summary-row">
                            <span>
                                <i class="fas fa-tag me-1"></i>
                                Giảm giá
                            </span>
                            <span class="value text-danger">-<?php echo number_format($order->discount_amount, 0, ',', '.'); ?> ₫</span>
                        </div>
                    <?php endif; ?>

                    <div class="summary-row">
                        <span>
                            <i class="fas fa-truck me-1"></i>
                            Phí vận chuyển
                        </span>
                        <span class="value"><?php echo number_format($order->shipping_fee ?? 0, 0, ',', '.'); ?> ₫</span>
                    </div>

                    <div class="summary-row total">
                        <span>Tổng cộng:</span>
                        <span class="value"><?php echo number_format($order->total_amount, 0, ',', '.'); ?> ₫</span>
                    </div>

                    <?php if ($payment): ?>
                        <div class="alert alert-info mt-4">
                            <small>
                                <strong>Trạng thái thanh toán:</strong><br>
                                <?php
                                $paymentStatusMap = [
                                    'pending' => '⏳ Chờ thanh toán',
                                    'completed' => '✓ Đã thanh toán',
                                    'failed' => '✕ Thanh toán thất bại',
                                    'refunded' => '↩ Đã hoàn tiền'
                                ];
                                echo $paymentStatusMap[$payment->payment_status] ?? 'Không xác định';
                                ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include __DIR__ . '/../components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <script>
        function cancelOrder(orderId) {
            if (!confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')) {
                return;
            }

            Swal.fire({
                title: 'Đang xử lý...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`/Ecom_website/api/orders/${orderId}/cancel`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: 'Đơn hàng đã được hủy',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: data.message || 'Không thể hủy đơn hàng'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Có lỗi xảy ra khi hủy đơn hàng'
                });
            });
        }

        function reviewOrder(orderId) {
            // Redirect to profile with order ID for review
            window.location.href = `/Ecom_website/profile?review_order=${orderId}`;
        }
    </script>
</body>
</html>

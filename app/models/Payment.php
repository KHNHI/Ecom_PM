<?php

class Payment extends BaseModel
{
    protected $table = 'payments';
    protected $primaryKey = 'payment_id';

    // Các payment methods có sẵn
    const PAYMENT_METHODS = [
        'COD' => 'Thanh toán khi nhận hàng',
        'BANK_TRANSFER' => 'Chuyển khoản ngân hàng',
        'CREDIT_CARD' => 'Thẻ tín dụng',
        'E_WALLET' => 'Ví điện tử'
    ];

    // Các payment status
    const PAYMENT_STATUS = [
        'pending' => 'Chờ thanh toán',
        'completed' => 'Đã thanh toán',
        'failed' => 'Thanh toán thất bại',
        'refunded' => 'Đã hoàn tiền'
    ];

    // Tạo payment record mới
    public function createPayment($orderId, $paymentMethod, $amount, $transactionCode = null, $transactionId = null)
    {
        try {
            // Validate payment method
            if (!array_key_exists($paymentMethod, self::PAYMENT_METHODS)) {
                throw new Exception("Invalid payment method: $paymentMethod");
            }

            $sql = "INSERT INTO {$this->table} (order_id, payment_method, payment_status, amount, 
                                               transaction_code, transaction_id, created_at) 
                    VALUES (:order_id, :payment_method, 'pending', :amount, :transaction_code, 
                            :transaction_id, NOW())";
            
            $this->db->query($sql);
            $this->db->bind(':order_id', $orderId);
            $this->db->bind(':payment_method', $paymentMethod);
            $this->db->bind(':amount', $amount);
            $this->db->bind(':transaction_code', $transactionCode);
            $this->db->bind(':transaction_id', $transactionId);
            
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log("Error creating payment: " . $e->getMessage());
            return false;
        }
    }

    // Lấy payment theo order ID
    public function getPaymentByOrderId($orderId)
    {
        try {
            $sql = "SELECT p.*, o.total_amount as order_total, o.order_status, 
                           o.full_name, o.email
                    FROM {$this->table} p
                    JOIN orders o ON p.order_id = o.order_id
                    WHERE p.order_id = :order_id";
            
            $this->db->query($sql);
            $this->db->bind(':order_id', $orderId);
            
            return $this->db->single();
        } catch (Exception $e) {
            error_log("Error getting payment by order ID: " . $e->getMessage());
            return false;
        }
    }

    // Lấy payment theo ID
    public function getPaymentById($paymentId)
    {
        try {
            $sql = "SELECT p.*, o.total_amount as order_total, o.order_status, 
                           o.full_name, o.email, o.created_at as order_date
                    FROM {$this->table} p
                    JOIN orders o ON p.order_id = o.order_id
                    WHERE p.payment_id = :payment_id";
            
            $this->db->query($sql);
            $this->db->bind(':payment_id', $paymentId);
            
            return $this->db->single();
        } catch (Exception $e) {
            error_log("Error getting payment by ID: " . $e->getMessage());
            return false;
        }
    }

    // Cập nhật trạng thái payment
    public function updatePaymentStatus($paymentId, $status, $transactionId = null, $paidAt = null)
    {
        try {
            // Validate status
            if (!array_key_exists($status, self::PAYMENT_STATUS)) {
                throw new Exception("Invalid payment status: $status");
            }

            $sql = "UPDATE {$this->table} SET payment_status = :status";
            
            if ($transactionId) {
                $sql .= ", transaction_id = :transaction_id";
            }
            
            if ($status === 'completed' && !$paidAt) {
                $sql .= ", paid_at = NOW()";
            } elseif ($paidAt) {
                $sql .= ", paid_at = :paid_at";
            }
            
            $sql .= " WHERE payment_id = :payment_id";
            
            $this->db->query($sql);
            $this->db->bind(':status', $status);
            $this->db->bind(':payment_id', $paymentId);
            
            if ($transactionId) {
                $this->db->bind(':transaction_id', $transactionId);
            }
            
            if ($paidAt && $status !== 'completed') {
                $this->db->bind(':paid_at', $paidAt);
            }
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Error updating payment status: " . $e->getMessage());
            return false;
        }
    }

    // Xác nhận thanh toán thành công
    public function confirmPayment($paymentId, $transactionId = null)
    {
        try {
            $result = $this->updatePaymentStatus($paymentId, 'completed', $transactionId);
            
            if ($result) {
                // Cập nhật order status thành 'paid'
                $payment = $this->getPaymentById($paymentId);
                if ($payment) {
                    $orderSql = "UPDATE orders SET order_status = 'paid' WHERE order_id = :order_id";
                    $this->db->query($orderSql);
                    $this->db->bind(':order_id', $payment['order_id']);
                    $this->db->execute();
                }
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error confirming payment: " . $e->getMessage());
            return false;
        }
    }

    // Hủy thanh toán
    public function cancelPayment($paymentId, $reason = null)
    {
        try {
            $result = $this->updatePaymentStatus($paymentId, 'failed');
            
            if ($result) {
                // Cập nhật order status thành 'cancelled'
                $payment = $this->getPaymentById($paymentId);
                if ($payment) {
                    $orderSql = "UPDATE orders SET order_status = 'cancelled' WHERE order_id = :order_id";
                    $this->db->query($orderSql);
                    $this->db->bind(':order_id', $payment['order_id']);
                    $this->db->execute();
                }
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error cancelling payment: " . $e->getMessage());
            return false;
        }
    }

    // Hoàn tiền
    public function refundPayment($paymentId, $refundAmount = null, $transactionId = null)
    {
        try {
            $payment = $this->getPaymentById($paymentId);
            if (!$payment) {
                throw new Exception("Payment not found");
            }

            if ($payment['payment_status'] !== 'completed') {
                throw new Exception("Cannot refund payment that is not completed");
            }

            // Nếu không có refund amount, hoàn toàn bộ
            if (!$refundAmount) {
                $refundAmount = $payment['amount'];
            }

            // Validate refund amount
            if ($refundAmount > $payment['amount']) {
                throw new Exception("Refund amount cannot exceed payment amount");
            }

            $result = $this->updatePaymentStatus($paymentId, 'refunded', $transactionId);
            
            if ($result) {
                // Log refund transaction
                $this->logRefundTransaction($paymentId, $refundAmount, $transactionId);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error refunding payment: " . $e->getMessage());
            return false;
        }
    }

    // Log refund transaction (có thể mở rộng thành bảng riêng)
    private function logRefundTransaction($paymentId, $amount, $transactionId)
    {
        // Placeholder - có thể tạo bảng refunds riêng để track chi tiết
        error_log("Refund logged: Payment ID $paymentId, Amount: $amount, Transaction: $transactionId");
    }

    // Lấy danh sách payments theo user
    public function getPaymentsByUser($userId, $limit = 10, $offset = 0)
    {
        try {
            $sql = "SELECT p.*, o.total_amount as order_total, o.order_status, 
                           o.full_name, o.created_at as order_date
                    FROM {$this->table} p
                    JOIN orders o ON p.order_id = o.order_id
                    WHERE o.user_id = :user_id
                    ORDER BY p.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $this->db->query($sql);
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':limit', $limit);
            $this->db->bind(':offset', $offset);
            
            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log("Error getting payments by user: " . $e->getMessage());
            return [];
        }
    }

    // Lấy thống kê payments
    public function getPaymentStats($dateFrom = null, $dateTo = null)
    {
        try {
            $sql = "SELECT 
                        payment_status,
                        payment_method,
                        COUNT(*) as count,
                        SUM(amount) as total_amount
                    FROM {$this->table}";
            
            $conditions = [];
            $params = [];
            
            if ($dateFrom) {
                $conditions[] = "created_at >= :date_from";
                $params[':date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $conditions[] = "created_at <= :date_to";
                $params[':date_to'] = $dateTo;
            }
            
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $sql .= " GROUP BY payment_status, payment_method 
                      ORDER BY payment_status, payment_method";
            
            $this->db->query($sql);
            
            foreach ($params as $param => $value) {
                $this->db->bind($param, $value);
            }
            
            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log("Error getting payment stats: " . $e->getMessage());
            return [];
        }
    }

    // Kiểm tra payment có tồn tại cho order
    public function paymentExistsForOrder($orderId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE order_id = :order_id";
            $this->db->query($sql);
            $this->db->bind(':order_id', $orderId);
            
            $result = $this->db->single();
            return $result && $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Error checking payment exists: " . $e->getMessage());
            return false;
        }
    }

    // Lấy payments pending cần xử lý
    public function getPendingPayments($limit = 50)
    {
        try {
            $sql = "SELECT p.*, o.total_amount as order_total, o.full_name, 
                           o.email, o.created_at as order_date
                    FROM {$this->table} p
                    JOIN orders o ON p.order_id = o.order_id
                    WHERE p.payment_status = 'pending'
                    ORDER BY p.created_at ASC
                    LIMIT :limit";
            
            $this->db->query($sql);
            $this->db->bind(':limit', $limit);
            
            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log("Error getting pending payments: " . $e->getMessage());
            return [];
        }
    }

    // Tìm payment theo transaction code
    public function findByTransactionCode($transactionCode)
    {
        try {
            $sql = "SELECT p.*, o.total_amount as order_total, o.order_status
                    FROM {$this->table} p
                    JOIN orders o ON p.order_id = o.order_id
                    WHERE p.transaction_code = :transaction_code";
            
            $this->db->query($sql);
            $this->db->bind(':transaction_code', $transactionCode);
            
            return $this->db->single();
        } catch (Exception $e) {
            error_log("Error finding payment by transaction code: " . $e->getMessage());
            return false;
        }
    }
}
?>
<?php

class OrderController extends BaseController {
    private $productModel;

    public function __construct() {
        $this->productModel = new Product();
        SessionHelper::start();
    }

    /**
     * Handle Buy Now - validate stock and store for direct checkout
     */
    public function buyNow() {
        // For AJAX requests, suppress all output except JSON
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            ini_set('display_errors', 0);
            if (ob_get_level()) {
                ob_clean();
            }
        }
        
        try {
            $productId = (int)$_POST['product_id'];
            $quantity = (int)($_POST['quantity'] ?? 1);
            $size = $_POST['size'] ?? null;
            $color = $_POST['color'] ?? null;

            // Debug logging
            error_log("OrderController BuyNow Debug - Product ID: $productId, Size: '$size', Color: '$color', Quantity: $quantity");

            if ($productId <= 0 || $quantity <= 0) {
                $this->jsonResponse(false, 'Dữ liệu không hợp lệ');
                return;
            }

            // Get product info
            $product = $this->productModel->findById($productId);
            if (!$product) {
                $this->jsonResponse(false, 'Sản phẩm không tồn tại');
                return;
            }

            // Check stock - MUST pass for buyNow
            $hasStock = $this->productModel->checkStock($productId, $size, $color, $quantity);
            if (!$hasStock) {
                $this->jsonResponse(false, 'Số lượng hàng trong kho không đủ hoặc biến thể không tồn tại');
                return;
            }

            // Store buy now item in separate session (not main cart)
            $_SESSION['buy_now_item'] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'size' => $size,
                'color' => $color,
                'price' => $product->base_price,
                'product' => $product
            ];

            error_log("OrderController - Buy now item stored successfully");
            $this->jsonResponse(true, 'Sản phẩm hợp lệ, chuyển đến thanh toán');

        } catch (Exception $e) {
            error_log("OrderController BuyNow Error: " . $e->getMessage());
            $this->jsonResponse(false, 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Send JSON response
     */
//     private function jsonResponse($success, $message, $data = null) {
//         // Clear any previous output
//         if (ob_get_level()) {
//             ob_clean();
//         }
        
//         // Set JSON header
//         header('Content-Type: application/json; charset=utf-8');
        
//         $response = [
//             'success' => $success,
//             'message' => $message
//         ];
        
//         if ($data !== null) {
//             $response['data'] = $data;
//         }
        
//         echo json_encode($response, JSON_UNESCAPED_UNICODE);
//         exit;
//     }

    /**
     * API: Get user's orders list
     */
    public function listUserOrders() {
        try {
            // Check if user is logged in
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                $this->jsonResponse(false, 'Vui lòng đăng nhập');
                return;
            }

            // Load Order model
            $orderModel = new Order();
            
            // Get orders by user ID
            $orders = $orderModel->getOrdersByUserId($userId);
            
            if (!$orders) {
                $this->jsonResponse(true, 'Không có đơn hàng', null);
                return;
            }

            // Enrich orders with items and payment info
            $ordersData = [];
            foreach ($orders as $order) {
                $orderData = (array)$order;
                
                // Get order items
                $items = $orderModel->getOrderItems($order->order_id);
                $orderData['items'] = array_map(function($item) {
                    // Get variant info if available (color, size)
                    $variantInfo = $this->getVariantInfo($item->variant_id);
                    
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price_snapshot,
                        'total_price' => $item->total_price,
                        'color' => $variantInfo['color'] ?? null,
                        'size' => $variantInfo['size'] ?? null,
                        'product_image' => $this->getProductImage($item->product_id)
                    ];
                }, $items);
                
                // Get payment info
                $payment = $orderModel->getPaymentInfo($order->order_id);
                $orderData['payment'] = $payment ? (array)$payment : null;
                
                $ordersData[] = $orderData;
            }

            $this->jsonResponse(true, 'Lấy danh sách đơn hàng thành công', $ordersData);
            
        } catch (Exception $e) {
            error_log("OrderController listUserOrders Error: " . $e->getMessage());
            $this->jsonResponse(false, 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * API: Get order detail
     */
    public function getOrderDetail($orderId = null) {
        try {
            // Get order ID from URL param or POST
            $orderId = $orderId ?? $_POST['order_id'] ?? $_GET['order_id'] ?? null;
            
            if (!$orderId) {
                $this->jsonResponse(false, 'Thiếu ID đơn hàng');
                return;
            }

            // Check if user is logged in
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                $this->jsonResponse(false, 'Vui lòng đăng nhập');
                return;
            }

            // Load Order model
            $orderModel = new Order();
            
            // Get order detail
            $order = $orderModel->getOrderDetails($orderId);
            
            if (!$order) {
                $this->jsonResponse(false, 'Không tìm thấy đơn hàng');
                return;
            }

            // Check if order belongs to user
            if ($order->user_id != $userId) {
                http_response_code(403);
                $this->jsonResponse(false, 'Không có quyền xem đơn hàng này');
                return;
            }

            // Convert to array
            $orderData = (array)$order;
            
            // Get order items
            $items = $orderModel->getOrderItems($order->order_id);
            $orderData['items'] = array_map(function($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price_snapshot,
                    'total_price' => $item->total_price,
                    'color' => $item->color ?? null,
                    'size' => $item->size ?? null,
                    'product_image' => $this->getProductImage($item->product_id)
                ];
            }, $items);

            $this->jsonResponse(true, 'Lấy chi tiết đơn hàng thành công', $orderData);
            
        } catch (Exception $e) {
            error_log("OrderController getOrderDetail Error: " . $e->getMessage());
            $this->jsonResponse(false, 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * API: Cancel order
     */
    public function cancelOrder($orderId = null) {
        try {
            // Get order ID
            $orderId = $orderId ?? $_POST['order_id'] ?? $_GET['order_id'] ?? null;
            
            if (!$orderId) {
                $this->jsonResponse(false, 'Thiếu ID đơn hàng');
                return;
            }

            // Check if user is logged in
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                $this->jsonResponse(false, 'Vui lòng đăng nhập');
                return;
            }

            // Load Order model
            $orderModel = new Order();
            
            // Get order
            $order = $orderModel->findByOrderId($orderId);
            
            if (!$order) {
                $this->jsonResponse(false, 'Không tìm thấy đơn hàng');
                return;
            }

            // Check if order belongs to user
            if ($order->user_id != $userId) {
                http_response_code(403);
                $this->jsonResponse(false, 'Không có quyền hủy đơn hàng này');
                return;
            }

            // Check if order can be cancelled (only pending orders)
            if ($order->order_status !== 'pending') {
                $this->jsonResponse(false, 'Chỉ có thể hủy đơn hàng chờ xác nhận');
                return;
            }

            // Cancel order
            $result = $orderModel->updateStatus($orderId, 'cancelled');
            
            if ($result) {
                $this->jsonResponse(true, 'Đơn hàng đã được hủy thành công');
            } else {
                $this->jsonResponse(false, 'Không thể hủy đơn hàng');
            }
            
        } catch (Exception $e) {
            error_log("OrderController cancelOrder Error: " . $e->getMessage());
            $this->jsonResponse(false, 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Helper: Get product primary image
     */
    private function getProductImage($productId) {
        try {
            $productModel = new Product();
            $image = $productModel->getProductPrimaryImage($productId);
            
            if ($image) {
                // The image object has 'file_path' field
                $filePath = $image->file_path ?? null;
                if ($filePath) {
                    // Check if it's already a full URL (http/https)
                    if (strpos($filePath, 'http://') === 0 || strpos($filePath, 'https://') === 0) {
                        return $filePath;
                    }
                    
                    // Check if it's already a complete relative path that includes directory
                    if (strpos($filePath, 'public/uploads/products/') === 0) {
                        return '/Ecom_PM/' . $filePath;
                    }
                    
                    // Otherwise, it's just a filename - prepend the full path
                    return '/Ecom_PM/public/uploads/products/' . $filePath;
                }
            }
            
            return '/Ecom_PM/public/uploads/products/placeholder.png';
        } catch (Exception $e) {
            error_log("Error getting product image for product {$productId}: " . $e->getMessage());
            return '/Ecom_PM/public/uploads/products/placeholder.png';
        }
    }

    /**
     * Show order detail page
     */
    public function showOrderDetail($orderId = null) {
        try {
            // Get order ID from URL param or GET
            $orderId = $orderId ?? $_GET['id'] ?? $_GET['order_id'] ?? null;
            
            if (!$orderId) {
                $this->view('errors/404', ['message' => 'Không tìm thấy đơn hàng']);
                return;
            }

            // Check if user is logged in
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                header('Location: /Ecom_PM/signin');
                exit;
            }

            // Load Order model
            $orderModel = new Order();
            
            // Get order detail
            $order = $orderModel->getOrderDetails($orderId);
            
            if (!$order) {
                $this->view('errors/404', ['message' => 'Không tìm thấy đơn hàng']);
                return;
            }

            // Check if order belongs to user
            if ($order->user_id != $userId) {
                $this->view('errors/403', ['message' => 'Không có quyền xem đơn hàng này']);
                return;
            }

            // Get order items
            $items = $orderModel->getOrderItems($order->order_id);
            $itemsData = [];
            
            foreach ($items as $item) {
                $variantInfo = $this->getVariantInfo($item->variant_id);
                $itemsData[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price_snapshot,
                    'total_price' => $item->total_price,
                    'color' => $variantInfo['color'] ?? null,
                    'size' => $variantInfo['size'] ?? null,
                    'product_image' => $this->getProductImage($item->product_id)
                ];
            }

            // Get payment info
            $payment = $orderModel->getPaymentInfo($order->order_id);

            // Prepare data for view
            $data = [
                'title' => 'Chi tiết đơn hàng #' . $order->order_id,
                'order' => $order,
                'items' => $itemsData,
                'payment' => $payment
            ];

            // Render view
            $this->view('customer/pages/order-detail', $data);
            
        } catch (Exception $e) {
            error_log("OrderController showOrderDetail Error: " . $e->getMessage());
            $this->view('errors/500', ['message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Helper: Get variant color and size
     */
    private function getVariantInfo($variantId) {
        try {
            if (!$variantId) {
                return ['color' => null, 'size' => null];
            }

            $productModel = new Product();
            $query = "SELECT color, size FROM product_variants WHERE variant_id = :variant_id LIMIT 1";
            
            // Use direct database query
            $db = Database::getInstance();
            $db->query($query);
            $db->bind(':variant_id', $variantId);
            $result = $db->single();
            
            if ($result) {
                return [
                    'color' => $result->color ?? null,
                    'size' => $result->size ?? null
                ];
            }
            
            return ['color' => null, 'size' => null];
        } catch (Exception $e) {
            return ['color' => null, 'size' => null];
        }
    }
}
?>
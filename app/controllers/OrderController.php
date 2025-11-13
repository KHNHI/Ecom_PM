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
}
?>
<?php
// All dependencies are loaded via index.php

class CartController extends BaseController {
    private $productModel;
    private $cartService;

    public function __construct() {
        $this->productModel = new Product();
        $this->cartService = new CartService();
        SessionHelper::start();
    }

    /**
     * Show cart page
     */
    public function index() {
        $cartItems = $this->getCartItems();
        $cartSummary = $this->calculateCartSummary($cartItems);

        $this->view('customer/pages/cart', [
            'title' => 'Giỏ hàng',
            'cartItems' => $cartItems,
            'cartSummary' => $cartSummary
        ]);
    }

    /**
     * Add product to cart (AJAX)
     */
    public function add() {
        // For AJAX requests, suppress all output except JSON
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            // Turn off error display completely for AJAX
            ini_set('display_errors', 0);
            // Clear any previous output
            if (ob_get_level()) {
                ob_clean();
            }
        }
        
        try {
            $productId = (int)$_POST['product_id'];
            $quantity = (int)($_POST['quantity'] ?? 1);
            $size = trim($_POST['size'] ?? '') ?: null;
            $color = trim($_POST['color'] ?? '') ?: null;

            // Debug logging
            error_log("Cart Add Debug - Product ID: $productId, Size: '$size', Color: '$color', Quantity: $quantity");

            if ($productId <= 0 || $quantity <= 0) {
                $this->jsonResponse(false, 'Dữ liệu không hợp lệ');
                return;
            }

            // Check if product exists
            $product = $this->productModel->findById($productId);
            if (!$product) {
                $this->jsonResponse(false, 'Sản phẩm không tồn tại');
                return;
            }

            // Check stock from variants
            if (!$this->productModel->checkStock($productId, $size, $color, $quantity)) {
                // Log chi tiết để debug
                error_log("Stock check failed - Product ID: $productId, Size: '$size', Color: '$color', Quantity: $quantity");
                
                $this->jsonResponse(false, "Biến thể sản phẩm (Size: $size, Color: $color) không tồn tại hoặc không đủ hàng trong kho");
                return;
            }

            // Add to cart
            $this->addToCart($productId, $quantity, $size, $color);

            // Get updated cart info
            $cartItems = $this->getCartItems();
            $cartSummary = $this->calculateCartSummary($cartItems);

            $this->jsonResponse(true, 'Thêm sản phẩm vào giỏ hàng thành công!', [
                'cartCount' => count($cartItems),
                'cartTotal' => $cartSummary['total'],
                'cartSubtotal' => $cartSummary['subtotal']
            ]);

        } catch (Exception $e) {
            error_log("Add to Cart Error: " . $e->getMessage());
            $this->jsonResponse(false, 'Có lỗi hệ thống xảy ra');
        }
    }

    /**
     * Update cart item quantity (AJAX)
     */
    public function update() {
        try {
            $productId = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];
            $size = trim($_POST['size'] ?? '') ?: null;
            $color = trim($_POST['color'] ?? '') ?: null;

            if ($productId <= 0) {
                $this->jsonResponse(false, 'Dữ liệu không hợp lệ');
                return;
            }

            if ($quantity <= 0) {
                // Remove item if quantity is 0
                $this->removeFromCart($productId, $size, $color);
            } else {
                // Check stock from variants
                if (!$this->productModel->checkStock($productId, $size, $color, $quantity)) {
                    $this->jsonResponse(false, 'Số lượng hàng trong kho không đủ');
                    return;
                }

                $this->updateCartItem($productId, $quantity, $size, $color);
            }

            // Get updated cart info
            $cartItems = $this->getCartItems();
            $cartSummary = $this->calculateCartSummary($cartItems);
            
  
            $this->jsonResponse(true, 'Cập nhật giỏ hàng thành công!', [
                'cartCount' => count($cartItems),
                'cartTotal' => $cartSummary['total'],
                'cartSubtotal' => $cartSummary['subtotal'],
            ]);

        } catch (Exception $e) {
            error_log("Update Cart Error: " . $e->getMessage());
            $this->jsonResponse(false, 'Có lỗi hệ thống xảy ra');
        }
    }

    /**
     * Remove item from cart (AJAX)
     */
    public function remove() {
        try {
            $productId = (int)$_POST['product_id'];
            $size = trim($_POST['size'] ?? '') ?: null;
            $color = trim($_POST['color'] ?? '') ?: null;

            if ($productId <= 0) {
                $this->jsonResponse(false, 'Dữ liệu không hợp lệ');
                return;
            }

            $this->removeFromCart($productId, $size, $color);

            // Get updated cart info
            $cartItems = $this->getCartItems();
            $cartSummary = $this->calculateCartSummary($cartItems);

            $this->jsonResponse(true, 'Xóa sản phẩm khỏi giỏ hàng thành công!', [
                'cartCount' => count($cartItems),
                'cartTotal' => $cartSummary['total'],
                'cartSubtotal' => $cartSummary['subtotal']
            ]);

        } catch (Exception $e) {
            error_log("Remove from Cart Error: " . $e->getMessage());
            $this->jsonResponse(false, 'Có lỗi hệ thống xảy ra');
        }
    }

    /**
     * Clear entire cart (AJAX)
     */
    public function clear() {
        try {
            // Clear session cart
            unset($_SESSION['cart']);

            // Clear database if user is logged in
            if (SessionHelper::isLoggedIn()) {
                $this->clearDatabaseCart();
            }

            $this->jsonResponse(true, 'Đã xóa tất cả sản phẩm trong giỏ hàng!', [
                'cartCount' => 0,
                'cartTotal' => 0,
                'cartSubtotal' => 0
            ]);

        } catch (Exception $e) {
            error_log("Cart Clear Error: " . $e->getMessage());
            $this->jsonResponse(false, 'Có lỗi hệ thống xảy ra');
        }
    }

    /**
     * Get cart count (AJAX)
     */
    public function getCount() {
        $cartItems = $this->getCartItems();
        $this->jsonResponse(true, '', ['cartCount' => count($cartItems)]);
    }

    /**
     * Add product to cart (internal method)
     */
    private function addToCart($productId, $quantity, $size = null, $color = null) {
        $cartKey = $this->generateCartKey($productId, $size, $color);
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$cartKey])) {
            $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$cartKey] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'size' => $size,
                'color' => $color,
                'added_at' => time()
            ];
        }

        // Save to database if user is logged in (for persistence)
        if (SessionHelper::isLoggedIn()) {
            $this->saveCartToDatabase();
        }
    }



    /**
     * Update cart item quantity
     */
    private function updateCartItem($productId, $quantity, $size = null, $color = null) {
        $cartKey = $this->generateCartKey($productId, $size, $color);
        
        if (isset($_SESSION['cart'][$cartKey])) {
            $_SESSION['cart'][$cartKey]['quantity'] = $quantity;
            
            // Save to database if user is logged in
            if (SessionHelper::isLoggedIn()) {
                $this->saveCartToDatabase();
            }
        }
    }

    /**
     * Remove item from cart
     */
    private function removeFromCart($productId, $size = null, $color = null) {
        $cartKey = $this->generateCartKey($productId, $size, $color);
        
        if (isset($_SESSION['cart'][$cartKey])) {
            unset($_SESSION['cart'][$cartKey]);
            
            // Save to database if user is logged in
            if (SessionHelper::isLoggedIn()) {
                $this->saveCartToDatabase();
            }
        }
    }

    /**
     * Get cart items with product details
     */
    private function getCartItems() {
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            return [];
        }

        $cartItems = [];
        foreach ($_SESSION['cart'] as $cartKey => $cartItem) {
            $product = $this->productModel->findById($cartItem['product_id']);
            if ($product) {
                $cartItems[] = [
                    'cart_key' => $cartKey,
                    'product' => $product,
                    'quantity' => $cartItem['quantity'],
                    'size' => $cartItem['size'],
                    'color' => $cartItem['color'],
                    'subtotal' => $product->base_price * $cartItem['quantity'],
                    'added_at' => $cartItem['added_at']
                ];
            }
        }

        return $cartItems;
    }

    /**
     * Calculate cart summary
     */
    private function calculateCartSummary($cartItems) {
        $subtotal = 0;
        $totalQuantity = 0;

        foreach ($cartItems as $item) {
            $subtotal += $item['subtotal'];
            $totalQuantity += $item['quantity'];
        }

        $shipping = $subtotal >= 500000 ? 0 : 30000; // Free shipping over 500k VND
        $tax = $subtotal * 0.1; // 10% tax
        $total = $subtotal + $shipping + $tax;

        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total,
            'totalQuantity' => $totalQuantity
        ];
    }

    /**
     * Generate unique cart key for product with variants
     */
    private function generateCartKey($productId, $size = null, $color = null) {
        return $productId . '_' . ($size ?? 'default') . '_' . ($color ?? 'default');
    }



    /**
     * Buy now - validate stock and store for direct checkout
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
            $size = trim($_POST['size'] ?? '') ?: null;
            $color = trim($_POST['color'] ?? '') ?: null;

            // Debug logging
            error_log("Cart BuyNow Debug - Product ID: $productId, Size: '$size', Color: '$color', Quantity: $quantity");

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

            // Check stock from variants
            if (!$this->productModel->checkStock($productId, $size, $color, $quantity)) {
                error_log("BuyNow stock check failed - Product ID: $productId, Size: '$size', Color: '$color', Quantity: $quantity");
                $this->jsonResponse(false, "Biến thể sản phẩm (Size: $size, Color: $color) không tồn tại hoặc không đủ hàng trong kho");
                return;
            }

            // Store buy now item in separate session
            $_SESSION['buy_now_item'] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'size' => $size,
                'color' => $color,
                'price' => $product->base_price,
                'product' => $product
            ];

            error_log("Cart BuyNow - Item stored successfully");
            $this->jsonResponse(true, 'Sản phẩm hợp lệ, chuyển đến thanh toán');

        } catch (Exception $e) {
            error_log("Cart BuyNow Error: " . $e->getMessage());
            $this->jsonResponse(false, 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Get cart count for badge
     */
    public function count() {
        $totalQuantity = 0;
        
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $totalQuantity += $item['quantity'];
            }
        }
        
        $this->jsonResponse(true, 'Cart count retrieved', ['count' => $totalQuantity]);
    }

    /**
     * Get cart summary for AJAX updates
     */
    public function summary() {
        try {
            $cartItems = $this->getCartItems();
            $cartSummary = $this->calculateCartSummary($cartItems);
            
            $this->jsonResponse(true, 'Cart summary retrieved', [
                'summary' => $cartSummary,
                'totalItems' => count($cartItems)
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Lỗi khi lấy thông tin giỏ hàng: ' . $e->getMessage());
        }
    }

    /**
     * Get cart summary for AJAX updates
     */
    public function getSummary() {
        try {
            $cartItems = $this->getCartItems();
            $summary = $this->calculateCartSummary($cartItems);
            
            $this->jsonResponse(true, 'Cart summary retrieved', [
                'subtotal' => $summary['subtotal'],
                'shipping' => $summary['shipping'],
                'tax' => $summary['tax'],
                'total' => $summary['total'],
                'count' => count($cartItems)
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Lỗi khi lấy thông tin giỏ hàng: ' . $e->getMessage());
        }
    }

    /**
     * Save cart to database (simple backup)
     */
    private function saveCartToDatabase() {
        // Simple method to save cart state for logged in users
        if (SessionHelper::isLoggedIn()) {
            $userId = SessionHelper::getUserId();
            $cartData = $_SESSION['cart'] ?? [];
            
            // Save as JSON in user profile or separate table
            // For now, just log - can implement database storage later if needed
            error_log("Cart saved for user $userId: " . json_encode($cartData));
        }
    }

    /**
     * Clear database cart
     */
    private function clearDatabaseCart() {
        if (SessionHelper::isLoggedIn()) {
            $userId = SessionHelper::getUserId();
            error_log("Database cart cleared for user $userId");
        }
    }

    /**
     * Load cart from database when user logs in
     */
    public function loadCartFromDatabase($userId) {
        // Simple method to restore cart for returning users
        // For now, just maintain session cart - can enhance later if needed
        return true;
    }
}
?>
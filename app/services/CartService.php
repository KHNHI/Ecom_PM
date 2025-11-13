<?php

class CartService {
    private $productModel;
    private $db;

    public function __construct() {
        $this->productModel = new Product();
        $this->db = Database::getInstance();
    }

    /**
     * Validate đủ stock cho toàn bộ giỏ hàng
     * @param array $cartItems Array of cart items with product_id, quantity, size, color
     * @return array Array of error messages if any
     * @throws Exception
     */
    public function validateCartStock($cartItems) {
        try {
            $errors = [];
            foreach ($cartItems as $item) {
                // Get fresh product data
                $product = $this->productModel->findById($item['product_id']);
                if (!$product) {
                    $errors[] = "Sản phẩm ID #{$item['product_id']} không tồn tại.";
                    continue;
                }

                // Check variant stock
                $variantStock = $this->getVariantStock(
                    $item['product_id'], 
                    $item['size'] ?? null, 
                    $item['color'] ?? null
                );

                if ($variantStock === false) {
                    $errors[] = "Biến thể của sản phẩm '{$product->name}' không tồn tại.";
                    continue;
                }

                if ($variantStock < $item['quantity']) {
                    $errors[] = "Sản phẩm '{$product->name}' chỉ còn {$variantStock} sản phẩm trong kho.";
                }
            }
            return $errors;
        } catch (Exception $e) {
            error_log("Validate Cart Stock Error: " . $e->getMessage());
            throw new Exception("Lỗi kiểm tra tồn kho: " . $e->getMessage());
        }
    }

    /**
     * Lấy số lượng tồn kho của một variant cụ thể
     * Nếu size và color là null, sẽ lấy variant mặc định
     * @param int $productId
     * @param string|null $size
     * @param string|null $color
     * @return int|false Số lượng tồn kho hoặc false nếu variant không tồn tại
     */
    public function getVariantStock($productId, $size = null, $color = null) {
        try {
            // Query thông tin variant và sản phẩm
            $sql = "SELECT pv.*, p.name as product_name 
                   FROM product_variants pv
                   JOIN products p ON p.product_id = pv.product_id
                   WHERE pv.product_id = :product_id 
                   AND (pv.size = :size OR (:size IS NULL AND pv.size IS NULL))
                   AND (pv.color = :color OR (:color IS NULL AND pv.color IS NULL))";

            $this->db->query($sql);
            $this->db->bind(':product_id', $productId);
            $this->db->bind(':size', $size);
            $this->db->bind(':color', $color);

            $variant = $this->db->single();
            
            // Log kết quả để debug
            if (!$variant) {
                error_log("No variant found for product #$productId with size: " . ($size ?? 'NULL') . ", color: " . ($color ?? 'NULL'));
                return false;
            }

            error_log("Found variant for '{$variant->product_name}' - Stock: {$variant->stock}");
            return (int)$variant->stock;

        } catch (Exception $e) {
            error_log("Get Variant Stock Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Giảm số lượng tồn kho cho một variant
     * @param int $productId
     * @param int $quantity
     * @param string|null $size
     * @param string|null $color
     * @return bool
     */
    public function decrementStock($productId, $quantity, $size = null, $color = null) {
        try {
            // Check current stock first
            $currentStock = $this->getVariantStock($productId, $size, $color);
            if ($currentStock === false || $currentStock < $quantity) {
                return false;
            }

            $sql = "UPDATE product_variants 
                    SET stock = stock - :quantity 
                    WHERE product_id = :product_id 
                    AND size = :size 
                    AND color = :color 
                    AND stock >= :quantity";

            $this->db->query($sql);
            $this->db->bind(':product_id', $productId);
            $this->db->bind(':size', $size);
            $this->db->bind(':color', $color);
            $this->db->bind(':quantity', $quantity);

            return $this->db->execute();

        } catch (Exception $e) {
            error_log("Decrement Stock Error: " . $e->getMessage());
            return false;
        }
    }
}
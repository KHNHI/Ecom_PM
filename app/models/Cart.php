<?php

class Cart extends BaseModel
{
    protected $table = 'carts';
    protected $primaryKey = 'cart_id';

    // Tạo giỏ hàng mới cho user
    public function createCart($userId = null, $sessionId = null)
    {
        try {
            $sql = "INSERT INTO {$this->table} (user_id, session_id, created_at, updated_at) 
                    VALUES (:user_id, :session_id, NOW(), NOW())";
            
            $this->db->query($sql);
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':session_id', $sessionId);
            
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log("Error creating cart: " . $e->getMessage());
            return false;
        }
    }

    // Lấy giỏ hàng theo user_id hoặc session_id
    public function getCartByUser($userId = null, $sessionId = null)
    {
        try {
            if ($userId) {
                $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
                $this->db->query($sql);
                $this->db->bind(':user_id', $userId);
            } else if ($sessionId) {
                $sql = "SELECT * FROM {$this->table} WHERE session_id = :session_id";
                $this->db->query($sql);
                $this->db->bind(':session_id', $sessionId);
            } else {
                return false;
            }
            
            return $this->db->single();
        } catch (Exception $e) {
            error_log("Error getting cart: " . $e->getMessage());
            return false;
        }
    }

    // Gán user_id cho cart khi guest login
    // public function assignUserToCart($cartId, $userId)
    // {
    //     try {
    //         $sql = "UPDATE {$this->table} SET user_id = :user_id, session_id = NULL, updated_at = NOW() 
    //                 WHERE cart_id = :cart_id";
            
    //         $this->db->query($sql);
    //         $this->db->bind(':user_id', $userId);
    //         $this->db->bind(':cart_id', $cartId);
            
    //         return $this->db->execute();
    //     } catch (Exception $e) {
    //         error_log("Error assigning user to cart: " . $e->getMessage());
    //         return false;
    //     }
    // }

    // Lấy tất cả items trong giỏ hàng
    public function getCartItems($cartId)
    {
        try {
            $sql = "SELECT ci.*, p.name as product_name, p.base_price, p.slug,
                           pv.variant_id, pv.color, pv.size, pv.price as variant_price
                    FROM cart_items ci
                    JOIN products p ON ci.product_id = p.product_id
                    LEFT JOIN product_variants pv ON ci.variant_id = pv.variant_id
                    WHERE ci.cart_id = :cart_id
                    ORDER BY ci.added_at DESC";
            
            $this->db->query($sql);
            $this->db->bind(':cart_id', $cartId);
            
            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log("Error getting cart items: " . $e->getMessage());
            return [];
        }
    }

    // Thêm sản phẩm vào giỏ hàng
    // public function addToCart($cartId, $productId, $price, $variantId = null, $quantity = 1)
    // {
    //     try {
    //         // Kiểm tra sản phẩm đã có trong giỏ chưa
    //         $existingItem = $this->getCartItem($cartId, $productId, $variantId);
            
    //         if ($existingItem) {
    //             // Cập nhật số lượng
    //             return $this->updateCartItemQuantity($existingItem['cart_item_id'], 
    //                                                $existingItem['quantity'] + $quantity);
    //         } else {
    //             // Thêm item mới
    //             $sql = "INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, price, added_at, created_at, updated_at) 
    //                     VALUES (:cart_id, :product_id, :variant_id, :quantity, :price, NOW(), NOW(), NOW())";
                
    //             $this->db->query($sql);
    //             $this->db->bind(':cart_id', $cartId);
    //             $this->db->bind(':product_id', $productId);
    //             $this->db->bind(':variant_id', $variantId);
    //             $this->db->bind(':quantity', $quantity);
    //             $this->db->bind(':price', $price);
                
    //             if ($this->db->execute()) {
    //                 $this->updateCartTimestamp($cartId);
    //                 return $this->db->lastInsertId();
    //             }
    //         }
    //         return false;
    //     } catch (Exception $e) {
    //         error_log("Error adding to cart: " . $e->getMessage());
    //         return false;
    //     }
    // }

    // Lấy một cart item cụ thể
    // private function getCartItem($cartId, $productId, $variantId = null)
    // {
    //     try {
    //         $sql = "SELECT * FROM cart_items 
    //                 WHERE cart_id = :cart_id AND product_id = :product_id";
            
    //         if ($variantId) {
    //             $sql .= " AND variant_id = :variant_id";
    //         } else {
    //             $sql .= " AND variant_id IS NULL";
    //         }
            
    //         $this->db->query($sql);
    //         $this->db->bind(':cart_id', $cartId);
    //         $this->db->bind(':product_id', $productId);
            
    //         if ($variantId) {
    //             $this->db->bind(':variant_id', $variantId);
    //         }
            
    //         return $this->db->single();
    //     } catch (Exception $e) {
    //         error_log("Error getting cart item: " . $e->getMessage());
    //         return false;
    //     }
    // }

    // Cập nhật số lượng sản phẩm trong giỏ
    // public function updateCartItemQuantity($cartItemId, $quantity)
    // {
    //     try {
    //         if ($quantity <= 0) {
    //             return $this->removeCartItem($cartItemId);
    //         }
            
    //         $sql = "UPDATE cart_items SET quantity = :quantity, updated_at = NOW() 
    //                 WHERE cart_item_id = :cart_item_id";
            
    //         $this->db->query($sql);
    //         $this->db->bind(':quantity', $quantity);
    //         $this->db->bind(':cart_item_id', $cartItemId);
            
    //         if ($this->db->execute()) {
    //             // Cập nhật timestamp của cart
    //             $cartItem = $this->getCartItemById($cartItemId);
    //             if ($cartItem) {
    //                 $this->updateCartTimestamp($cartItem['cart_id']);
    //             }
    //             return true;
    //         }
    //         return false;
    //     } catch (Exception $e) {
    //         error_log("Error updating cart item quantity: " . $e->getMessage());
    //         return false;
    //     }
    // }

    // Xóa sản phẩm khỏi giỏ hàng
    // public function removeCartItem($cartItemId)
    // {
    //     try {
    //         // Lấy cart_id trước khi xóa
    //         $cartItem = $this->getCartItemById($cartItemId);
            
    //         $sql = "DELETE FROM cart_items WHERE cart_item_id = :cart_item_id";
    //         $this->db->query($sql);
    //         $this->db->bind(':cart_item_id', $cartItemId);
            
    //         if ($this->db->execute()) {
    //             if ($cartItem) {
    //                 $this->updateCartTimestamp($cartItem['cart_id']);
    //             }
    //             return true;
    //         }
    //         return false;
    //     } catch (Exception $e) {
    //         error_log("Error removing cart item: " . $e->getMessage());
    //         return false;
    //     }
    // }

    // Lấy cart item theo ID
    // private function getCartItemById($cartItemId)
    // {
    //     try {
    //         $sql = "SELECT * FROM cart_items WHERE cart_item_id = :cart_item_id";
    //         $this->db->query($sql);
    //         $this->db->bind(':cart_item_id', $cartItemId);
            
    //         return $this->db->single();
    //     } catch (Exception $e) {
    //         error_log("Error getting cart item by ID: " . $e->getMessage());
    //         return false;
    //     }
    // }

    // Xóa toàn bộ giỏ hàng
    public function clearCart($cartId)
    {
        try {
            $sql = "DELETE FROM cart_items WHERE cart_id = :cart_id";
            $this->db->query($sql);
            $this->db->bind(':cart_id', $cartId);
            
            if ($this->db->execute()) {
                $this->updateCartTimestamp($cartId);
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error clearing cart: " . $e->getMessage());
            return false;
        }
    }

    // Tính tổng giá trị giỏ hàng
    // public function getCartTotal($cartId)
    // {
    //     try {
    //         $sql = "SELECT SUM(quantity * price) as total 
    //                 FROM cart_items WHERE cart_id = :cart_id";
            
    //         $this->db->query($sql);
    //         $this->db->bind(':cart_id', $cartId);
            
    //         $result = $this->db->single();
    //         return $result ? (float)$result['total'] : 0;
    //     } catch (Exception $e) {
    //         error_log("Error calculating cart total: " . $e->getMessage());
    //         return 0;
    //     }
    // }

    // Đếm số lượng items trong giỏ
    // public function getCartItemCount($cartId)
    // {
    //     try {
    //         $sql = "SELECT SUM(quantity) as count FROM cart_items WHERE cart_id = :cart_id";
    //         $this->db->query($sql);
    //         $this->db->bind(':cart_id', $cartId);
            
    //         $result = $this->db->single();
    //         return $result ? (int)$result['count'] : 0;
    //     } catch (Exception $e) {
    //         error_log("Error counting cart items: " . $e->getMessage());
    //         return 0;
    //     }
    // }

    // Cập nhật timestamp của cart
    private function updateCartTimestamp($cartId)
    {
        try {
            $sql = "UPDATE {$this->table} SET updated_at = NOW() WHERE cart_id = :cart_id";
            $this->db->query($sql);
            $this->db->bind(':cart_id', $cartId);
            $this->db->execute();
        } catch (Exception $e) {
            error_log("Error updating cart timestamp: " . $e->getMessage());
        }
    }

    // Merge guest cart với user cart khi login
    // public function mergeGuestCartToUser($guestCartId, $userCartId)
    // {
    //     try {
    //         $guestItems = $this->getCartItems($guestCartId);
            
    //         foreach ($guestItems as $item) {
    //             $existingItem = $this->getCartItem($userCartId, $item['product_id'], $item['variant_id']);
                
    //             if ($existingItem) {
    //                 // Cập nhật số lượng
    //                 $this->updateCartItemQuantity($existingItem['cart_item_id'], 
    //                                             $existingItem['quantity'] + $item['quantity']);
    //             } else {
    //                 // Thêm item mới
    //                 $this->addToCart($userCartId, $item['product_id'], $item['variant_id'], 
    //                                $item['quantity'], $item['price']);
    //             }
    //         }
            
    //         // Xóa guest cart
    //         $this->deleteCart($guestCartId);
            
    //         return true;
    //     } catch (Exception $e) {
    //         error_log("Error merging guest cart: " . $e->getMessage());
    //         return false;
    //     }
    // }

    // Xóa cart hoàn toàn
    // public function deleteCart($cartId)
    // {
    //     try {
    //         // Xóa cart items trước
    //         $this->clearCart($cartId);
            
    //         // Xóa cart
    //         $sql = "DELETE FROM {$this->table} WHERE cart_id = :cart_id";
    //         $this->db->query($sql);
    //         $this->db->bind(':cart_id', $cartId);
            
    //         return $this->db->execute();
    //     } catch (Exception $e) {
    //         error_log("Error deleting cart: " . $e->getMessage());
    //         return false;
    //     }
    // }
}
?>
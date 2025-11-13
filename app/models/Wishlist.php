<?php

class Wishlist extends BaseModel
{
    protected $table = 'wishlists';
    protected $primaryKey = 'wishlist_id';

    // Tạo wishlist mới cho user
    public function createWishlist($userId, $name = 'Wishlist của tôi')
    {
        try {
            $sql = "INSERT INTO {$this->table} (user_id, name, created_at) 
                    VALUES (:user_id, :name, NOW())";
            
            $this->db->query($sql);
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':name', $name);
            
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log("Error creating wishlist: " . $e->getMessage());
            return false;
        }
    }

    // Lấy wishlist của user (tự động tạo nếu chưa có)
    public function getUserWishlist($userId)
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
            $this->db->query($sql);
            $this->db->bind(':user_id', $userId);
            
            $wishlist = $this->db->single();
            
            // Nếu chưa có wishlist, tạo mới
            if (!$wishlist) {
                $wishlistId = $this->createWishlist($userId);
                if ($wishlistId) {
                    return $this->getWishlistById($wishlistId);
                }
                return false;
            }
            
            return $wishlist;
        } catch (Exception $e) {
            error_log("Error getting user wishlist: " . $e->getMessage());
            return false;
        }
    }

    // Lấy wishlist theo ID
    public function getWishlistById($wishlistId)
    {
        try {
            $sql = "SELECT w.*, u.name as user_name, u.email
                    FROM {$this->table} w
                    JOIN users u ON w.user_id = u.user_id
                    WHERE w.wishlist_id = :wishlist_id";
            
            $this->db->query($sql);
            $this->db->bind(':wishlist_id', $wishlistId);
            
            return $this->db->single();
        } catch (Exception $e) {
            error_log("Error getting wishlist by ID: " . $e->getMessage());
            return false;
        }
    }

    // Lấy tất cả items trong wishlist
    public function getWishlistItems($wishlistId)
    {
        try {
            $sql = "SELECT wi.*, p.name as product_name, p.description, 
                           p.base_price, p.slug, p.is_active,
                           c.collection_name,
                           (SELECT img.file_path FROM images img 
                            JOIN image_usages iu ON img.image_id = iu.image_id 
                            WHERE iu.ref_id = p.product_id AND iu.ref_type = 'product' 
                            AND iu.is_primary = 1 LIMIT 1) as primary_image
                    FROM wishlist_items wi
                    JOIN products p ON wi.product_id = p.product_id
                    LEFT JOIN collection c ON p.collection_id = c.collection_id
                    WHERE wi.wishlist_id = :wishlist_id AND p.is_active = 1
                    ORDER BY wi.added_at DESC";
            
            $this->db->query($sql);
            $this->db->bind(':wishlist_id', $wishlistId);
            
            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log("Error getting wishlist items: " . $e->getMessage());
            return [];
        }
    }

    // Thêm sản phẩm vào wishlist
    public function addToWishlist($userId, $productId)
    {
        try {
            // Lấy hoặc tạo wishlist
            $wishlist = $this->getUserWishlist($userId);
            if (!$wishlist) {
                throw new Exception("Cannot create or get wishlist for user");
            }

            // Kiểm tra sản phẩm đã có trong wishlist chưa
            if ($this->isProductInWishlist($wishlist->wishlist_id, $productId)) {
                return false; // Đã có trong wishlist
            }

            // Kiểm tra sản phẩm có tồn tại và active không
            if (!$this->isProductValid($productId)) {
                throw new Exception("Product not found or inactive");
            }

            $sql = "INSERT INTO wishlist_items (wishlist_id, product_id, added_at) 
                    VALUES (:wishlist_id, :product_id, NOW())";
            
            $this->db->query($sql);
            $this->db->bind(':wishlist_id', $wishlist->wishlist_id);
            $this->db->bind(':product_id', $productId);
            
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log("Error adding to wishlist: " . $e->getMessage());
            return false;
        }
    }

    // Kiểm tra sản phẩm có trong wishlist không
    public function isProductInWishlist($wishlistId, $productId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM wishlist_items 
                    WHERE wishlist_id = :wishlist_id AND product_id = :product_id";
            
            $this->db->query($sql);
            $this->db->bind(':wishlist_id', $wishlistId);
            $this->db->bind(':product_id', $productId);
            
            $result = $this->db->single();
            return $result && $result->count > 0;
        } catch (Exception $e) {
            error_log("Error checking product in wishlist: " . $e->getMessage());
            return false;
        }
    }

    // Kiểm tra user có sản phẩm trong wishlist không
    public function isProductInUserWishlist($userId, $productId)
    {
        try {
            $wishlist = $this->getUserWishlist($userId);
            if (!$wishlist) {
                return false;
            }
            
            return $this->isProductInWishlist($wishlist->wishlist_id, $productId);
        } catch (Exception $e) {
            error_log("Error checking product in user wishlist: " . $e->getMessage());
            return false;
        }
    }

    // Kiểm tra sản phẩm có hợp lệ không
    private function isProductValid($productId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM products 
                    WHERE product_id = :product_id AND is_active = 1";
            
            $this->db->query($sql);
            $this->db->bind(':product_id', $productId);
            
            $result = $this->db->single();
            return $result && $result->count > 0;
        } catch (Exception $e) {
            error_log("Error validating product: " . $e->getMessage());
            return false;
        }
    }

    // Xóa sản phẩm khỏi wishlist
    public function removeFromWishlist($userId, $productId)
    {
        try {
            $wishlist = $this->getUserWishlist($userId);
            if (!$wishlist) {
                return false;
            }

            $sql = "DELETE FROM wishlist_items 
                    WHERE wishlist_id = :wishlist_id AND product_id = :product_id";
            
            $this->db->query($sql);
            $this->db->bind(':wishlist_id', $wishlist->wishlist_id);
            $this->db->bind(':product_id', $productId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Error removing from wishlist: " . $e->getMessage());
            return false;
        }
    }

    // Xóa wishlist item theo ID
    // public function removeWishlistItem($wishlistItemId, $userId = null)
    // {
    //     try {
    //         $sql = "DELETE wi FROM wishlist_items wi";
            
    //         if ($userId) {
    //             $sql .= " JOIN wishlists w ON wi.wishlist_id = w.wishlist_id
    //                       WHERE wi.wishlist_item_id = :item_id AND w.user_id = :user_id";
                
    //             $this->db->query($sql);
    //             $this->db->bind(':item_id', $wishlistItemId);
    //             $this->db->bind(':user_id', $userId);
    //         } else {
    //             $sql .= " WHERE wi.wishlist_item_id = :item_id";
                
    //             $this->db->query($sql);
    //             $this->db->bind(':item_id', $wishlistItemId);
    //         }
            
    //         return $this->db->execute();
    //     } catch (Exception $e) {
    //         error_log("Error removing wishlist item: " . $e->getMessage());
    //         return false;
    //     }
    // }

    // Xóa toàn bộ wishlist
    public function clearWishlist($userId)
    {
        try {
            $wishlist = $this->getUserWishlist($userId);
            if (!$wishlist) {
                return false;
            }

            $sql = "DELETE FROM wishlist_items WHERE wishlist_id = :wishlist_id";
            $this->db->query($sql);
            $this->db->bind(':wishlist_id', $wishlist->wishlist_id);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Error clearing wishlist: " . $e->getMessage());
            return false;
        }
    }

    // Đếm số lượng items trong wishlist
    public function getWishlistItemCount($userId)
    {
        try {
            $wishlist = $this->getUserWishlist($userId);
            if (!$wishlist) {
                return 0;
            }

            $sql = "SELECT COUNT(*) as count FROM wishlist_items wi
                    JOIN products p ON wi.product_id = p.product_id
                    WHERE wi.wishlist_id = :wishlist_id AND p.is_active = 1";
            
            $this->db->query($sql);
            $this->db->bind(':wishlist_id', $wishlist->wishlist_id);
            
            $result = $this->db->single();
            return $result ? (int)$result->count : 0;
        } catch (Exception $e) {
            error_log("Error counting wishlist items: " . $e->getMessage());
            return 0;
        }
    }

    // Lấy wishlist items của user với phân trang
    // public function getUserWishlistItems($userId, $limit = 12, $offset = 0)
    // {
    //     try {
    //         $wishlist = $this->getUserWishlist($userId);
    //         if (!$wishlist) {
    //             return [];
    //         }

    //         $sql = "SELECT wi.*, p.name as product_name, p.description, 
    //                        p.base_price, p.slug, p.is_active,
    //                        c.collection_name,
    //                        (SELECT img.file_path FROM images img 
    //                         JOIN image_usages iu ON img.image_id = iu.image_id 
    //                         WHERE iu.ref_id = p.product_id AND iu.ref_type = 'product' 
    //                         AND iu.is_primary = 1 LIMIT 1) as primary_image
    //                 FROM wishlist_items wi
    //                 JOIN products p ON wi.product_id = p.product_id
    //                 LEFT JOIN collection c ON p.collection_id = c.collection_id
    //                 WHERE wi.wishlist_id = :wishlist_id AND p.is_active = 1
    //                 ORDER BY wi.added_at DESC
    //                 LIMIT :limit OFFSET :offset";
            
    //         $this->db->query($sql);
    //         $this->db->bind(':wishlist_id', $wishlist->wishlist_id);
    //         $this->db->bind(':limit', $limit);
    //         $this->db->bind(':offset', $offset);
            
    //         return $this->db->resultSet();
    //     } catch (Exception $e) {
    //         error_log("Error getting user wishlist items: " . $e->getMessage());
    //         return [];
    //     }
    // }

    // Cập nhật tên wishlist
    // public function updateWishlistName($userId, $name)
    // {
    //     try {
    //         $wishlist = $this->getUserWishlist($userId);
    //         if (!$wishlist) {
    //             return false;
    //         }

    //         $sql = "UPDATE {$this->table} SET name = :name WHERE wishlist_id = :wishlist_id";
    //         $this->db->query($sql);
    //         $this->db->bind(':name', $name);
    //         $this->db->bind(':wishlist_id', $wishlist->wishlist_id);
            
    //         return $this->db->execute();
    //     } catch (Exception $e) {
    //         error_log("Error updating wishlist name: " . $e->getMessage());
    //         return false;
    //     }
    // }

    // // Chuyển sản phẩm từ wishlist sang cart
    // public function moveToCart($userId, $productId, $cartModel)
    // {
    //     try {
    //         // Lấy thông tin sản phẩm
    //         $productSql = "SELECT base_price FROM products WHERE product_id = :product_id AND is_active = 1";
    //         $this->db->query($productSql);
    //         $this->db->bind(':product_id', $productId);
    //         $product = $this->db->single();
            
    //         if (!$product) {
    //             throw new Exception("Product not found or inactive");
    //         }

    //         // Lấy hoặc tạo cart
    //         $cart = $cartModel->getCartByUser($userId);
    //         if (!$cart) {
    //             $cartId = $cartModel->createCart($userId);
    //             if (!$cartId) {
    //                 throw new Exception("Cannot create cart");
    //             }
    //         } else {
    //             $cartId = $cart['cart_id'];
    //         }

    //         // Thêm vào cart
    //         $addResult = $cartModel->addToCart($cartId, $productId, null, 1, $product['base_price']);
            
    //         if ($addResult) {
    //             // Xóa khỏi wishlist
    //             $this->removeFromWishlist($userId, $productId);
    //             return true;
    //         }
            
    //         return false;
    //     } catch (Exception $e) {
    //         error_log("Error moving to cart: " . $e->getMessage());
    //         return false;
    //     }
    // }

    // Lấy danh sách product IDs trong wishlist của user
    public function getUserWishlistProductIds($userId)
    {
        try {
            $wishlist = $this->getUserWishlist($userId);
            if (!$wishlist) {
                return [];
            }

            $sql = "SELECT wi.product_id 
                    FROM wishlist_items wi 
                    JOIN products p ON wi.product_id = p.product_id 
                    WHERE wi.wishlist_id = :wishlist_id AND p.is_active = 1";
            
            $this->db->query($sql);
            $this->db->bind(':wishlist_id', $wishlist->wishlist_id);
            $items = $this->db->resultSet();
            
            return array_map(function($item) {
                return (int)$item->product_id;
            }, $items);
            
        } catch (Exception $e) {
            error_log("Error getting user wishlist product IDs: " . $e->getMessage());
            return [];
        }
    }

    // Lấy sản phẩm được yêu thích nhiều nhất
    // public function getMostWishedProducts($limit = 10)
    // {
    //     try {
    //         $sql = "SELECT p.*, c.collection_name, COUNT(wi.product_id) as wishlist_count,
    //                        (SELECT img.file_path FROM images img 
    //                         JOIN image_usages iu ON img.image_id = iu.image_id 
    //                         WHERE iu.ref_id = p.product_id AND iu.ref_type = 'product' 
    //                         AND iu.is_primary = 1 LIMIT 1) as primary_image
    //                 FROM products p
    //                 JOIN wishlist_items wi ON p.product_id = wi.product_id
    //                 LEFT JOIN collection c ON p.collection_id = c.collection_id
    //                 WHERE p.is_active = 1
    //                 GROUP BY p.product_id
    //                 ORDER BY wishlist_count DESC, p.created_at DESC
    //                 LIMIT :limit";
            
    //         $this->db->query($sql);
    //         $this->db->bind(':limit', $limit);
            
    //         return $this->db->resultSet();
    //     } catch (Exception $e) {
    //         error_log("Error getting most wished products: " . $e->getMessage());
    //         return [];
    //     }
    // }
}
?>
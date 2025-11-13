<?php

// Load required models
require_once __DIR__ . '/../../core/BaseModel.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Collection.php';
require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/ReviewService.php';

/**
 * ProductService - Business Logic Layer
 * Xử lý tất cả logic phức tạp liên quan đến products
 */
class ProductService extends BaseModel {
    protected $table = 'products'; // Add table name
    private $productModel;
    private $categoryModel;
    private $collectionModel;
    private $reviewModel;
    private $reviewService;
    
    public function __construct() {
        parent::__construct(); // Initialize database connection from BaseModel
        
        // Initialize models
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->collectionModel = new Collection(); 
        $this->reviewModel = new Review();
        $this->reviewService = new ReviewService();

    }
    
    /**
     * Lấy sản phẩm với filters phức tạp
     */
    public function getProductsWithFilters($filters = [], $page = 1, $limit = 12) {
        // Xây dựng SQL động dựa trên filters
        $sql = "SELECT p.*, c.collection_name 
                FROM products p 
                LEFT JOIN collection c ON p.collection_id = c.collection_id 
                WHERE p.is_active = 1";
        
        $params = [];
        
        // Multiple categories filter
        if (!empty($filters['category_ids']) && is_array($filters['category_ids'])) {
            $placeholders = [];
            foreach ($filters['category_ids'] as $index => $categoryId) {
                $placeholder = ":category_id_$index";
                $placeholders[] = $placeholder;
                $params[$placeholder] = $categoryId;
            }
            $categoryPlaceholders = implode(',', $placeholders);
            
            $sql .= " AND EXISTS (
                        SELECT 1 FROM product_categories pc 
                        WHERE pc.product_id = p.product_id 
                        AND pc.category_id IN ($categoryPlaceholders)
                      )";
        }
        // Single category filter (backward compatibility)
        elseif (!empty($filters['category_id'])) {
            $sql .= " AND EXISTS (
                        SELECT 1 FROM product_categories pc 
                        WHERE pc.product_id = p.product_id 
                        AND pc.category_id = :category_id
                      )";
            $params[':category_id'] = $filters['category_id'];
        }
        
        // Materials filter - using new material column
        if (!empty($filters['materials']) && is_array($filters['materials'])) {
            $placeholders = [];
            foreach ($filters['materials'] as $index => $material) {
                $placeholder = ":material_$index";
                $placeholders[] = $placeholder;
                $params[$placeholder] = $material;
            }
            $materialPlaceholders = implode(',', $placeholders);
            $sql .= " AND p.material IN ($materialPlaceholders)";
        }
        
        // Brands filter - search in product name and description only
        if (!empty($filters['brands']) && is_array($filters['brands'])) {
            $brandConditions = [];
            foreach ($filters['brands'] as $index => $brand) {
                $placeholder = ":brand_$index";
                // Convert slug to readable format for search
                $searchTerm = str_replace('-', ' ', $brand);
                $brandConditions[] = "(p.name LIKE $placeholder OR p.description LIKE $placeholder)";
                $params[$placeholder] = '%' . $searchTerm . '%';
            }
            if (!empty($brandConditions)) {
                $sql .= " AND (" . implode(' OR ', $brandConditions) . ")";
            }
        }
        
        // Price filter
        if (!empty($filters['min_price'])) {
            $sql .= " AND p.base_price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $sql .= " AND p.base_price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE :search OR p.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // Sorting
        switch ($filters['sort_by'] ?? 'popular') {
            case 'price_asc':
                $sql .= " ORDER BY p.base_price ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY p.base_price DESC";
                break;
            case 'name_asc':
                $sql .= " ORDER BY p.name ASC";
                break;
            case 'name_desc':
                $sql .= " ORDER BY p.name DESC";
                break;
            case 'rating':
                $sql .= " ORDER BY average_rating DESC, review_count DESC";
                break;
            case 'newest':
                $sql .= " ORDER BY p.created_at DESC";
                break;
            case 'popular':
            default:
                // Sort by most sold products - use subquery to maintain filter logic
                $sql .= " ORDER BY (
                    SELECT COALESCE(SUM(oi.quantity), 0) 
                    FROM order_items oi 
                    JOIN orders o ON oi.order_id = o.order_id 
                    WHERE oi.product_id = p.product_id 
                    AND o.order_status IN ('paid', 'shipped', 'delivered')
                ) DESC, p.created_at DESC";
                break;
        }
        
        // Pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT $limit OFFSET $offset";

        
        // Execute query
        $this->productModel->db->query($sql);
        foreach ($params as $key => $value) {
            $this->productModel->db->bind($key, $value);
        }
        
        $products = $this->productModel->db->resultSet();
        
        // Attach images và rating data cho mỗi sản phẩm
        foreach ($products as $product) {
            $product->images = $this->getProductImages($product->product_id);
            $product->primary_image = $this->getProductPrimaryImage($product->product_id);
            
            // Attach rating data sử dụng ReviewService
            $ratingStats = $this->reviewService->getProductReviewStats($product->product_id);
            $product->average_rating = $ratingStats ? (float)$ratingStats->average_rating : 0;
            $product->review_count = $ratingStats ? (int)$ratingStats->total_reviews : 0;
        }
        
        // Get total count for pagination
        $total = $this->countProductsWithFilters($filters);
        
        return [
            'products' => $products,
            'total' => $total
        ];
    }
    
    /**
     * Đếm tổng số sản phẩm với filters
     */
    public function countProductsWithFilters($filters = []) {
        // Tương tự getProductsWithFilters nhưng chỉ COUNT
        $sql = "SELECT COUNT(*) as total 
                FROM products p 
                LEFT JOIN collection c ON p.collection_id = c.collection_id 
                WHERE p.is_active = 1";
        
        $params = [];
        
        // Multiple categories filter
        if (!empty($filters['category_ids']) && is_array($filters['category_ids'])) {
            $placeholders = [];
            foreach ($filters['category_ids'] as $index => $categoryId) {
                $placeholder = ":category_id_$index";
                $placeholders[] = $placeholder;
                $params[$placeholder] = $categoryId;
            }
            $categoryPlaceholders = implode(',', $placeholders);
            
            $sql .= " AND EXISTS (
                        SELECT 1 FROM product_categories pc 
                        WHERE pc.product_id = p.product_id 
                        AND pc.category_id IN ($categoryPlaceholders)
                      )";
        }
        // Single category filter (backward compatibility)
        elseif (!empty($filters['category_id'])) {
            $sql .= " AND EXISTS (
                        SELECT 1 FROM product_categories pc 
                        WHERE pc.product_id = p.product_id 
                        AND pc.category_id = :category_id
                      )";
            $params[':category_id'] = $filters['category_id'];
        }
        // Category filter - using relationship table
        elseif (!empty($filters['category_slug'])) {
            // If slug is provided, try to find category by first available text column
            $sql .= " AND EXISTS (
                        SELECT 1 FROM product_categories pc 
                        INNER JOIN categories cat ON pc.category_id = cat.category_id 
                        WHERE pc.product_id = p.product_id 
                        AND cat.category_id = :category_slug
                        AND cat.is_active = 1
                      )";
            $params[':category_slug'] = $filters['category_slug'];
        }
        
        // Materials filter - using new material column
        if (!empty($filters['materials']) && is_array($filters['materials'])) {
            $placeholders = [];
            foreach ($filters['materials'] as $index => $material) {
                $placeholder = ":material_$index";
                $placeholders[] = $placeholder;
                $params[$placeholder] = $material;
            }
            $materialPlaceholders = implode(',', $placeholders);
            $sql .= " AND p.material IN ($materialPlaceholders)";
        }
        
        // Brands filter - search in product name and description only
        if (!empty($filters['brands']) && is_array($filters['brands'])) {
            $brandConditions = [];
            foreach ($filters['brands'] as $index => $brand) {
                $placeholder = ":brand_$index";
                // Convert slug to readable format for search
                $searchTerm = str_replace('-', ' ', $brand);
                $brandConditions[] = "(p.name LIKE $placeholder OR p.description LIKE $placeholder)";
                $params[$placeholder] = '%' . $searchTerm . '%';
            }
            if (!empty($brandConditions)) {
                $sql .= " AND (" . implode(' OR ', $brandConditions) . ")";
            }
        }
        
        // Collection filter
        if (!empty($filters['slug'])) {
            $sql .= " AND c.slug = :slug";
            $params[':slug'] = $filters['slug'];
        }
        
        // Price filters
        if (!empty($filters['min_price'])) {
            $sql .= " AND p.base_price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $sql .= " AND p.base_price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE :search OR p.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $result = $this->db->single();
        return $result->total ?? 0;
    }
    
    /**
     * Lấy sản phẩm với đầy đủ thông tin (images, variants, etc.)
     */
    public function getProductWithFullDetails($slugOrId) {
        // Try to find by slug first, then by ID if slug fails
        $product = $this->productModel->findBySlug($slugOrId);
        
        if (!$product && is_numeric($slugOrId)) {
            // If slug not found and input is numeric, try by ID
            $product = $this->productModel->findById($slugOrId);
        }
        
        if (!$product) {
            return null;
        }
        
        // Attach additional data
        $product->images = $this->getProductImages($product->product_id);
        $product->primary_image = $this->getProductPrimaryImage($product->product_id);
        $product->categories = $this->getProductCategories($product->product_id);
        
        // Attach variant data
        $product->variants = $this->productModel->getAvailableVariants($product->product_id);
        $product->variant_options = $this->getVariantOptions($product->product_id);
        
        // Attach review data
        $product->reviews = $this->reviewService->getProductReviews($product->product_id, 10);
        $product->review_stats = $this->reviewService->getProductReviewStats($product->product_id);
        
        return $product;
    }
    
    /**
     * Lấy sản phẩm liên quan
     */
    public function getRelatedProducts($productId, $limit = 4) {
        $product = $this->productModel->findById($productId);
        
        if (!$product || !$product->collection_id) {
            return [];
        }
        
        // Sanitize limit parameter
        $limit = (int) $limit;
        if ($limit <= 0) $limit = 4;
        
        // Lấy sản phẩm cùng collection, trừ sản phẩm hiện tại
        $sql = "SELECT * FROM products 
                WHERE collection_id = :collection_id 
                AND product_id != :product_id 
                AND is_active = 1 
                ORDER BY created_at DESC 
                LIMIT $limit";
        
        $this->productModel->db->query($sql);
        $this->productModel->db->bind(':collection_id', $product->collection_id);
        $this->productModel->db->bind(':product_id', $productId);
        
        $relatedProducts = $this->productModel->db->resultSet();
        
        // Attach images for each related product
        foreach ($relatedProducts as $relatedProduct) {
            $relatedProduct->images = $this->getProductImages($relatedProduct->product_id);
            $relatedProduct->primary_image = $this->getProductPrimaryImage($relatedProduct->product_id);
        }
        
        return $relatedProducts;
    }
    
    // =================== IMAGE HELPER METHODS ===================
    
    /**
     * Get all images for a product
     */
    private function getProductImages($productId) {
        $sql = "SELECT i.*, iu.usage_id, iu.is_primary
                FROM images i 
                JOIN image_usages iu ON i.image_id = iu.image_id 
                WHERE iu.ref_type = 'product' 
                AND iu.ref_id = :product_id 
                ORDER BY iu.is_primary DESC, iu.created_at ASC";
        
        $this->db->query($sql);
        $this->db->bind(':product_id', $productId);
        
        return $this->db->resultSet();
    }
    
    /**
     * Get primary image for a product
     */
    private function getProductPrimaryImage($productId) {
        $sql = "SELECT i.*, iu.usage_id, iu.is_primary
                FROM images i 
                JOIN image_usages iu ON i.image_id = iu.image_id 
                WHERE iu.ref_type = 'product' 
                AND iu.ref_id = :product_id 
                AND iu.is_primary = 1
                LIMIT 1";
        
        $this->db->query($sql);
        $this->db->bind(':product_id', $productId);
        
        $primaryImage = $this->db->single();
        
        // If no primary image, get first available image
        if (!$primaryImage) {
            $images = $this->getProductImages($productId);
            return $images ? $images[0] : null;
        }
        
        return $primaryImage;
    }
    
    // =================== ADDITIONAL BUSINESS LOGIC METHODS ===================
    
    /**
     * Get new arrival products
     */
    public function getNewArrivals($limit = 8) {
        // Sanitize limit parameter to prevent SQL injection
        $limit = (int) $limit;
        if ($limit <= 0) $limit = 8;
        
        $sql = "SELECT p.*, c.collection_name
                FROM " . $this->table . " p 
                LEFT JOIN collection c ON p.collection_id = c.collection_id 
                WHERE p.is_active = 1 
                ORDER BY p.created_at DESC
                LIMIT $limit";
        $this->db->query($sql);
        $products = $this->db->resultSet();
        
        // Add images for each product
        foreach ($products as $product) {
            $product->images = $this->getProductImages($product->product_id);
            $product->primary_image = $this->getProductPrimaryImage($product->product_id);
        }
        
        return $products;
    }
    
    /**
     * Get popular products (by sales count)
     */
    public function getPopularProducts($limit = 8) {
        // Sanitize limit parameter to prevent SQL injection
        $limit = (int) $limit;
        if ($limit <= 0) $limit = 8;
        
        $sql = "SELECT p.*, c.collection_name 
                FROM products p 
                LEFT JOIN collection c ON p.collection_id = c.collection_id 
                WHERE p.is_active = 1 
                ORDER BY (
                    SELECT COALESCE(SUM(oi.quantity), 0) 
                    FROM order_items oi 
                    JOIN orders o ON oi.order_id = o.order_id 
                    WHERE oi.product_id = p.product_id 
                    AND o.order_status IN ('paid', 'shipped', 'delivered')
                ) DESC, p.created_at DESC 
                LIMIT $limit";
        
        $this->db->query($sql);
        $products = $this->db->resultSet();
        
        // Attach images cho mỗi sản phẩm
        foreach ($products as $product) {
            $product->images = $this->getProductImages($product->product_id);
            $product->primary_image = $this->getProductPrimaryImage($product->product_id);
        }
        
        return $products;
    }
    
    /**
     * Get active categories
     */
    public function getActiveCategories() {
        $sql = "SELECT * FROM categories WHERE is_active = 1 ORDER BY category_id ASC";
        $this->db->query($sql);
        return $this->db->resultSet();
    }
    
    /**
     * Get active collections
     */
    public function getActiveCollections() {
        $sql = "SELECT * FROM collection WHERE is_active = 1 ORDER BY collection_id ASC";
        $this->db->query($sql);
        return $this->db->resultSet();
    }
    
    /**
     * Get categories for a specific product
     */
    public function getProductCategories($productId) {
        $sql = "SELECT c.* FROM categories c 
                INNER JOIN product_categories pc ON c.category_id = pc.category_id 
                WHERE pc.product_id = :product_id 
                AND c.is_active = 1 
                ORDER BY c.category_id ASC";
        $this->db->query($sql);
        $this->db->bind(':product_id', $productId);
        return $this->db->resultSet();
    }
    
    /**
     * Lấy danh sách materials có trong database
     */
    public function getAvailableMaterials() {
        $sql = "SELECT DISTINCT material, COUNT(*) as product_count 
                FROM products 
                WHERE is_active = 1 AND material IS NOT NULL 
                GROUP BY material 
                ORDER BY product_count DESC";
        $this->db->query($sql);
        return $this->db->resultSet();
    }
    
    /**
     * Lấy các tùy chọn biến thể cho sản phẩm (color, size)
     */
    public function getVariantOptions($productId) {
        $sql = "SELECT DISTINCT color, size FROM product_variants 
                WHERE product_id = :product_id AND stock > 0 
                ORDER BY color ASC, size ASC";
        $this->db->query($sql);
        $this->db->bind(':product_id', $productId);
        $variants = $this->db->resultSet();
        
        $options = [
            'colors' => [],
            'sizes' => []
        ];
        
        foreach ($variants as $variant) {
            if ($variant->color && !in_array($variant->color, $options['colors'])) {
                $options['colors'][] = $variant->color;
            }
            if ($variant->size && !in_array($variant->size, $options['sizes'])) {
                $options['sizes'][] = $variant->size;
            }
        }
        
        return $options;
    }
}
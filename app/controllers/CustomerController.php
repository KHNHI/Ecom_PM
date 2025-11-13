<?php

// Load required classes
require_once __DIR__ . '/../services/ProductService.php';

/**
 * CustomerControllerRefactored - Thin Controller with Clean Architecture
 * Chỉ xử lý HTTP requests và responses, logic nghiệp vụ được chuyển sang Service Layer
 */
class CustomerController extends BaseController {
    private $productService;
    private $sliderService;
    

    public function __construct() {
        $this->productService = new ProductService();
    }
    
    /**
     * Homepage - hiển thị sản phẩm nổi bật
     */
    public function index() {
        try {
            // Get data from Service Layer
            $newArrivals = $this->productService->getNewArrivals(8);
            $popularProducts = $this->productService->getPopularProducts(8);
            
            // Get categories with banner images
            $categoryModel = new Category();
            $categories = $categoryModel->getAllCategoriesWithBanners();
            
            // Pass data to view
            $data = [
                'title' => 'Trang chủ',
                'newArrivals' => $newArrivals,
                'popularProducts' => $popularProducts,
                'categories' => $categories
            ];
            
            $this->view('customer/pages/home', $data);
            
        } catch (Exception $e) {
            // Handle error
            $this->view('customer/pages/error', ['message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Product listing page với filter
     */
    public function products() {
        try {
            // Get parameters from request
            $categoryParam = $_GET['category'] ?? null;
            $categoriesParam = $_GET['categories'] ?? null; // Multiple categories
            $materialsParam = $_GET['materials'] ?? null;
            $collectionSlug = $_GET['collection'] ?? null;
            $search = $_GET['search'] ?? null;
            $sortBy = $_GET['sort'] ?? 'newest';
            $minPrice = $_GET['min_price'] ?? null;
            $maxPrice = $_GET['max_price'] ?? null;
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 12;
            
            // Build filter array - handle both category ID and slug
            $filters = [
                'slug' => $collectionSlug,
                'search' => $search,
                'sort_by' => $sortBy,
                'is_active' => 1
            ];
            
            // Handle multiple categories
            if ($categoriesParam) {
                $categoryIds = explode(',', $categoriesParam);
                $filters['category_ids'] = array_map('intval', $categoryIds);
            } elseif ($categoryParam) {
                // Backward compatibility for single category
                if (is_numeric($categoryParam)) {
                    $filters['category_id'] = (int) $categoryParam;
                } else {
                    $filters['category_slug'] = $categoryParam;
                }
            }
            
            // Handle materials filter
            if ($materialsParam) {
                $materials = explode(',', $materialsParam);
                $filters['materials'] = $materials;
            }
            

            
            // Handle price range
            if ($minPrice !== null && $minPrice !== '') {
                $filters['min_price'] = (float) $minPrice;
            }
            if ($maxPrice !== null && $maxPrice !== '') {
                $filters['max_price'] = (float) $maxPrice;
            }
            
            // Get data from Service Layer
            $result = $this->productService->getProductsWithFilters($filters, $page, $limit);
            $categories = $this->productService->getActiveCategories();
            $collections = $this->productService->getActiveCollections();
            $materials = $this->productService->getAvailableMaterials();
            
            // Calculate pagination
            $totalPages = ceil($result['total'] / $limit);
            
            // Determine page title based on filters
            $pageTitle = 'Sản phẩm';
            if ($sortBy === 'newest') {
                $pageTitle = 'Sản phẩm mới nhất';
            } elseif ($sortBy === 'popular') {
                $pageTitle = 'Sản phẩm bán chạy';
            } elseif ($search) {
                $pageTitle = 'Tìm kiếm: ' . $search;
            }
            
            // Pass data to view
            $data = [
                'title' => $pageTitle,
                'products' => $result['products'],
                'categories' => $categories,
                'collections' => $collections,
                'materials' => $materials,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'filters' => $filters,
                'total' => $result['total']
            ];
            
            $this->view('customer/pages/list-product', $data);
            
        } catch (Exception $e) {
            $this->view('customer/pages/error', ['message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Product detail page
     */
    public function productDetail($id) {
        try {
            // Get product from Service Layer
            $product = $this->productService->getProductWithFullDetails($id);

            if (!$product) {
                $this->view('customer/pages/404', ['message' => 'Không tìm thấy sản phẩm']);
                return;
            }
            
            // Get related products
            $relatedProducts = $this->productService->getRelatedProducts($product->product_id, 4);
            
            // Pass data to view
            $data = [
                'title' => $product->name,
                'product' => $product,
                'relatedProducts' => $relatedProducts
            ];
            
            $this->view('customer/pages/details-product', $data);
            
        } catch (Exception $e) {
            $this->view('customer/pages/error', ['message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Search products AJAX
     */
    public function searchProducts() {
        try {
            $search = $_POST['search'] ?? '';
            $limit = $_POST['limit'] ?? 10;
            
            if (strlen($search) < 2) {
                echo json_encode(['success' => false, 'message' => 'Từ khóa quá ngắn']);
                return;
            }
            
            $filters = [
                'search' => $search,
                'is_active' => 1
            ];
            
            $result = $this->productService->getProductsWithFilters($filters, 1, $limit);
            
            echo json_encode([
                'success' => true,
                'products' => $result['products'],
                'total' => $result['total']
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Get products by category AJAX
     */
    public function getProductsByCategory() {
        try {
            $categorySlug = $_POST['category_slug'] ?? '';
            $page = $_POST['page'] ?? 1;
            $limit = $_POST['limit'] ?? 12;
            
            $filters = [
                'category_slug' => $categorySlug,
                'is_active' => 1
            ];
            
            $result = $this->productService->getProductsWithFilters($filters, $page, $limit);
            
            echo json_encode([
                'success' => true,
                'products' => $result['products'],
                'total' => $result['total'],
                'totalPages' => ceil($result['total'] / $limit)
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * API - Get new arrivals products
     */
    public function getNewArrivals() {
        header('Content-Type: application/json');
        
        try {
            $limit = $_GET['limit'] ?? 8;
            $products = $this->productService->getNewArrivals($limit);
            
            echo json_encode([
                'success' => true,
                'data' => $products,
                'count' => count($products),
                'debug' => 'API is working'
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * API - Get popular products  
     */
    public function getPopularProducts() {
        header('Content-Type: application/json');
        
        try {
            $limit = $_GET['limit'] ?? 8;
            $products = $this->productService->getPopularProducts($limit);
            
            echo json_encode([
                'success' => true,
                'data' => $products
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * About Us page
     */
    public function about() {
        try {
            $data = [
                'title' => 'Giới thiệu'
            ];
            
            $this->view('customer/pages/about', $data);
            
        } catch (Exception $e) {
            $this->view('customer/pages/error', ['message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }
    
    /**
     * API - Get categories for dropdown
     */
    public function getCategories() {
        header('Content-Type: application/json');
        
        try {
            $categories = $this->productService->getActiveCategories();
            
            echo json_encode([
                'success' => true,
                'data' => $categories
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * API - Get collections for dropdown
     */
    public function getCollections() {
        header('Content-Type: application/json');
        
        try {
            $collections = $this->productService->getActiveCollections();
            
            echo json_encode([
                'success' => true,
                'data' => $collections
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
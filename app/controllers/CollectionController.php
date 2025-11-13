<?php
require_once __DIR__ . '/../models/Collection.php';
require_once __DIR__ . '/../models/Product.php';

class CollectionController extends BaseController {
    private $collectionModel;
    private $productModel;

    public function __construct() {
        $this->collectionModel = new Collection();
        $this->productModel = new Product();
    }

    private function redirect($url) {
        header("Location: $url");
        exit;
    }

    /**
     * Display all collections
     */
    public function index() {
        try {
            $collections = $this->collectionModel->getAllCollectionsWithCovers(true);
            
            $data = [
                'title' => 'Collections',
                'collections' => $collections
            ];

            $this->view('customer/pages/collections', $data);
        } catch (Exception $e) {
            error_log("Collection index error: " . $e->getMessage());
            $this->redirect('/404');
        }
    }

    /**
     * Display specific collection with products
     */
    public function show($slug = null) {
        try {
            // Nếu không có slug trong parameter, kiểm tra trong $_GET
            if (!$slug && isset($_GET['slug'])) {
                $slug = $_GET['slug'];
            }
            
            if (!$slug) {
                $this->redirect('/collections');
                return;
            }

            // Get collection by slug
            $collection = $this->collectionModel->getCollectionBySlugWithCover($slug);
            
            if (!$collection) {
                $this->redirect('/404');
                return;
            }

            // Get products in this collection
            $products = $this->collectionModel->getCollectionProductsWithImages($collection->collection_id);
            
            // Process product images
            foreach ($products as $product) {
                if (!empty($product->images)) {
                    $product->image_array = explode(',', $product->images);
                    $product->main_image = $product->image_array[0];
                } else {
                    $product->image_array = [];
                    $product->main_image = 'assets/images/placeholder.svg';
                }
            }

            $data = [
                'title' => $collection->collection_name,
                'collection' => $collection,
                'products' => $products,
                'product_count' => count($products)
            ];

            $this->view('customer/pages/collection', $data);
        } catch (Exception $e) {
            error_log("Collection show error: " . $e->getMessage());
            $this->redirect('/404');
        }
    }

    /**
     * API endpoint to get collection products (for AJAX)
     */
    public function getProducts($collectionId = null) {
        header('Content-Type: application/json');
        
        try {
            if (!$collectionId) {
                echo json_encode(['success' => false, 'message' => 'Collection ID required']);
                return;
            }

            $products = $this->collectionModel->getCollectionProductsWithImages($collectionId);
            
            // Process products for JSON response
            $processedProducts = [];
            foreach ($products as $product) {
                $images = [];
                if (!empty($product->images)) {
                    $images = explode(',', $product->images);
                }
                
                $processedProducts[] = [
                    'product_id' => $product->product_id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'description' => $product->description,
                    'category_name' => $product->category_name,
                    'images' => $images,
                    'main_image' => !empty($images) ? $images[0] : 'assets/images/placeholder.svg'
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $processedProducts,
                'count' => count($processedProducts)
            ]);

        } catch (Exception $e) {
            error_log("Collection getProducts error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
    }

    /**
     * Get collections for dropdown (API)
     */
    public function getCollectionsApi() {
        header('Content-Type: application/json');
        
        try {
            $collections = $this->collectionModel->getAllCollections(true);
            
            $data = [];
            foreach ($collections as $collection) {
                $data[] = [
                    'collection_id' => $collection->collection_id,
                    'collection_name' => $collection->collection_name,
                    'slug' => $collection->slug,
                    'description' => $collection->description
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $data
            ]);

        } catch (Exception $e) {
            error_log("Collection API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
    }
}
?>
<?php
/**
 * Test product image loading
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
require_once 'configs/env.php';
require_once 'configs/config.php';
require_once 'configs/database.php';
require_once 'core/BaseModel.php';
require_once 'app/models/Product.php';

echo "=== Product Image Test ===\n\n";

try {
    $db = Database::getInstance();
    
    // 1. Get a product from an order
    echo "1. Getting products from user 11's orders:\n";
    $db->query("SELECT DISTINCT oi.product_id 
               FROM order_items oi
               JOIN orders o ON oi.order_id = o.order_id
               WHERE o.user_id = 11
               LIMIT 3");
    $db->execute();
    $products = $db->resultSet();
    
    foreach ($products as $prod) {
        echo "\n   Product ID: " . $prod->product_id . "\n";
        
        // 2. Check if images exist for this product
        $db->query("SELECT i.*, iu.is_primary
                   FROM images i
                   JOIN image_usages iu ON i.image_id = iu.image_id
                   WHERE iu.ref_type = 'product' AND iu.ref_id = :product_id");
        $db->bind(':product_id', $prod->product_id);
        $db->execute();
        $images = $db->resultSet();
        
        if ($images) {
            echo "   Images found: " . count($images) . "\n";
            foreach ($images as $img) {
                echo "     - file_path: " . $img->file_path . "\n";
                echo "     - is_primary: " . $img->is_primary . "\n";
            }
        } else {
            echo "   No images found!\n";
        }
        
        // 3. Test getProductPrimaryImage method
        $productModel = new Product();
        $primaryImage = $productModel->getProductPrimaryImage($prod->product_id);
        
        if ($primaryImage) {
            echo "   Primary image file_path: " . $primaryImage->file_path . "\n";
            $imageUrl = $productModel->getPrimaryImageUrl($prod->product_id);
            echo "   Final URL: " . $imageUrl . "\n";
        } else {
            echo "   No primary image method result\n";
        }
    }
    
    echo "\n=== Test Complete ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

<?php
/**
 * CategoryService - Business Logic for Category Management
 * Xử lý tất cả logic phức tạp liên quan đến categories
 */
class CategoryService {
    private $categoryModel;
    
    public function __construct() {
        $this->categoryModel = new Category();
    }
    
    /**
     * Tạo category mới với xử lý file upload
     */
    public function createCategoryWithImage($data, $files = []) {
        try {
            // Handle file upload
            $imageUrl = '';
            if (isset($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
                $imageUrl = $this->handleImageUpload($files['image']);
            }

            $categoryData = [
                'name' => trim($data['name']),
                'description' => trim($data['description'] ?? ''),
                'image_url' => $imageUrl,
                'icon_url' => trim($data['icon_url'] ?? ''),
                'parent_id' => null,  // Always null for now
                'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
            ];

            return $this->categoryModel->create($categoryData);
            
        } catch (Exception $e) {
            throw new Exception('Lỗi tạo danh mục: ' . $e->getMessage());
        }
    }
    
    /**
     * Xử lý upload hình ảnh
     */
    private function handleImageUpload($file) {
        $uploadDir = __DIR__ . '/../../public/uploads/categories/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = uniqid('category_') . '_' . time() . '_' . $file['name'];
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return 'public/uploads/categories/' . $fileName;
        }
        
        return '';
    }
    
    /**
     * Lấy tất cả categories với thống kê
     */
    public function getAllCategoriesWithStats() {
        return $this->categoryModel->getAllWithProductCount(false);
    }
    
    /**
     * Cập nhật category
     */
    public function updateCategory($id, $data, $files = []) {
        try {
            $updateData = [
                'name' => trim($data['name']),
                'description' => trim($data['description'] ?? ''),
                'icon_url' => trim($data['icon_url'] ?? ''),
                'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
            ];
            
            // Handle new image upload if provided
            if (isset($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
                $updateData['image_url'] = $this->handleImageUpload($files['image']);
            }
            
            return $this->categoryModel->update($id, $updateData);
            
        } catch (Exception $e) {
            throw new Exception('Lỗi cập nhật danh mục: ' . $e->getMessage());
        }
    }
    
    /**
     * Xóa category (soft delete)
     */
    public function deleteCategory($id) {
        try {
            return $this->categoryModel->deleteById($id);
        } catch (Exception $e) {
            throw new Exception('Lỗi xóa danh mục: ' . $e->getMessage());
        }
    }
}

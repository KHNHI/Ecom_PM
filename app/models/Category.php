<?php
class Category extends BaseModel {
    protected $table = 'categories';
    protected $primaryKey = 'category_id';

    public function createCategory($data) {
        $this->db->query("INSERT INTO " . $this->table . " (name, parent_id, slug, is_active, created_at) VALUES (:name, :parent_id, :slug, 1, NOW())");
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':parent_id', $data['parent_id'] ?? null);
        $this->db->bind(':slug', $this->generateSlug($data['name']));
        return $this->db->execute();
    }

    public function updateCategory($id, $data) {
        $this->db->query("UPDATE " . $this->table . " SET name = :name, parent_id = :parent_id, slug = :slug, is_active = :is_active WHERE category_id = :id");
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':parent_id', $data['parent_id'] ?? null);
        $this->db->bind(':slug', $data['slug'] ?? $this->generateSlug($data['name']));
        $this->db->bind(':is_active', $data['is_active'] ?? 1);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function findById($id) {
        $this->db->query("SELECT * FROM " . $this->table . " WHERE category_id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function findBySlug($slug) {
        $this->db->query("SELECT * FROM " . $this->table . " WHERE slug = :slug AND is_active = 1");
        $this->db->bind(':slug', $slug);
        return $this->db->single();
    }

    public function deleteById($id) {
        // Soft delete by deactivating
        $this->db->query("UPDATE " . $this->table . " SET is_active = 0 WHERE category_id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function getAllCategories($onlyActive = true) {
        $sql = "SELECT * FROM " . $this->table;
        if ($onlyActive) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY name ASC";
        $this->db->query($sql);
        return $this->db->resultSet();
    }


    private function generateSlug($name) {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Check for duplicates and add number if needed
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug, $id)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    private function slugExists($slug, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE slug = :slug";
        if ($excludeId) {
            $sql .= " AND category_id != :id";
        }
        
        $this->db->query($sql);
        $this->db->bind(':slug', $slug);
        if ($excludeId) {
            $this->db->bind(':id', $excludeId);
        }
        
        $result = $this->db->single();
        return $result && $result->count > 0;
    }

    // =================== CATEGORY IMAGE METHODS ===================
    
    /**
     * Get category banner image URL
     */
    public function getCategoryBanner($categoryId) {
        $sql = "SELECT i.file_path
                FROM images i 
                JOIN image_usages iu ON i.image_id = iu.image_id 
                WHERE iu.ref_type = 'banner' 
                AND iu.ref_id = :category_id 
                ORDER BY iu.is_primary DESC, iu.created_at ASC
                LIMIT 1";
        
        $this->db->query($sql);
        $this->db->bind(':category_id', $categoryId);
        $result = $this->db->single();
        
        return $result ? $result->file_path : null;
    }
    
    /**
     * Get categories with their banner images (for display purposes)
     */
    public function getAllCategoriesWithBanners($onlyActive = true) {
        $categories = $this->getAllCategories($onlyActive);
        
        foreach ($categories as $category) {
            $category->banner_image = $this->getCategoryBanner($category->category_id);
        }
        
        return $categories;
    }
     public function getTotalCount() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $this->db->query($sql);
        $result = $this->db->single();
        return $result ? (int)$result->total : 0;
    }
}
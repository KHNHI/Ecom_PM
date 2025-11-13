<?php

class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll(){
        $this->db->query("SELECT * FROM " . $this->table);
        return $this->db->resultSet();
    }
    //đếm số dòng
    //  public function findById($id){
    //     $this->db->query("SELECT * FROM " . $this->table . " WHERE id = :id");
    //     $this->db->bind(':id', $id);
    //     return $this->db->single();
    // }
    public function deleteById($id){
        $primaryKey = $this->primaryKey ?? 'id';
        $this->db->query("DELETE FROM " . $this->table . " WHERE " . $primaryKey . " = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function getById($id) {
        $primaryKey = $this->primaryKey ?? 'id';
        $this->db->query("SELECT * FROM " . $this->table . " WHERE " . $primaryKey . " = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Generic create method - can be overridden in child classes
     */
    public function create($data) {
        $fields = array_keys($data);
        $placeholders = array_map(function($field) { return ':' . $field; }, $fields);
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $this->db->query($sql);
        
        foreach ($data as $key => $value) {
            $this->db->bind(':' . $key, $value);
        }
        
        return $this->db->execute() ? $this->db->lastInsertId() : false;
    }

    /**
     * Generic update method - can be overridden in child classes
     */
    public function update($id, $data) {
        $primaryKey = $this->primaryKey ?? 'id';
        $fields = array_keys($data);
        $setClause = array_map(function($field) { return $field . ' = :' . $field; }, $fields);
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE {$primaryKey} = :id";
        $this->db->query($sql);
        
        foreach ($data as $key => $value) {
            $this->db->bind(':' . $key, $value);
        }
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
}
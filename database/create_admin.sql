-- Tạo admin user cho login
-- Password mặc định: password
-- role_id = 1 (admin), role_id = 2 (customer)

INSERT INTO users (email, password_hash, name, phone, role_id, is_active, created_at, updated_at) 
VALUES (
    'admin@trangsuc.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Admin Trang Sức',
    '0999999999',
    1,
    1,
    NOW(),
    NOW()
);

-- Test credentials:
-- Email: admin@trangsuc.com
-- Password: password



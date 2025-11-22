-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 16, 2025 at 04:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_ecom`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AddProduct` (IN `p_name` VARCHAR(255), IN `p_description` TEXT, IN `p_base_price` DECIMAL(10,2), IN `p_sku` VARCHAR(50), IN `p_slug` VARCHAR(255), IN `p_collection_id` INT, IN `p_category_ids` TEXT)   BEGIN
    DECLARE v_product_id INT;
    DECLARE v_category_id VARCHAR(20);
    DECLARE v_pos INT DEFAULT 1;
    DECLARE v_len INT;
    DECLARE v_remaining TEXT;
    DECLARE v_final_collection_id INT DEFAULT NULL;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Xử lý collection_id: Để NULL nếu input là NULL hoặc 0
    IF p_collection_id IS NULL OR p_collection_id = 0 THEN
        SET v_final_collection_id = NULL;
    ELSE
        -- Kiểm tra collection_id có tồn tại không
        IF EXISTS (SELECT 1 FROM collection WHERE collection_id = p_collection_id) THEN
            SET v_final_collection_id = p_collection_id;
        ELSE
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Collection ID does not exist';
        END IF;
    END IF;
    
    -- Thêm sản phẩm với collection_id đã được xử lý
    INSERT INTO products (name, description, base_price, sku, slug, collection_id)
    VALUES (p_name, p_description, p_base_price, p_sku, p_slug, v_final_collection_id);
    
    SET v_product_id = LAST_INSERT_ID();
    
    -- Thêm categories nếu có
    IF p_category_ids IS NOT NULL AND LENGTH(TRIM(p_category_ids)) > 0 THEN
        SET v_remaining = TRIM(p_category_ids);
        
        WHILE LENGTH(v_remaining) > 0 DO
            SET v_len = LOCATE(',', v_remaining);
            
            IF v_len = 0 THEN
                SET v_category_id = TRIM(v_remaining);
                SET v_remaining = '';
            ELSE
                SET v_category_id = TRIM(SUBSTRING(v_remaining, 1, v_len - 1));
                SET v_remaining = TRIM(SUBSTRING(v_remaining, v_len + 1));
            END IF;
            
            -- Chỉ insert nếu category_id là số hợp lệ
            IF v_category_id REGEXP '^[0-9]+$' THEN
                INSERT IGNORE INTO product_categories (product_id, category_id) 
                VALUES (v_product_id, CAST(v_category_id AS UNSIGNED));
            END IF;
        END WHILE;
    END IF;
    
    COMMIT;
    
    SELECT v_product_id as product_id, v_final_collection_id as collection_id, 'Product added successfully' as message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AddProductReview` (IN `p_product_id` INT, IN `p_user_id` INT, IN `p_rating` TINYINT, IN `p_title` VARCHAR(200), IN `p_comment` TEXT)   BEGIN
    DECLARE v_purchased INT DEFAULT 0;
    
    -- Kiểm tra user đã mua sản phẩm chưa
    SELECT COUNT(*) INTO v_purchased
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    WHERE oi.product_id = p_product_id 
    AND o.user_id = p_user_id 
    AND o.order_status = 'delivered';
    
    IF v_purchased = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'You can only review products you have purchased';
    END IF;
    
    -- Kiểm tra rating hợp lệ
    IF p_rating < 1 OR p_rating > 5 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Rating must be between 1 and 5';
    END IF;
    
    INSERT INTO product_reviews (product_id, user_id, rating, title, comment)
    VALUES (p_product_id, p_user_id, p_rating, p_title, p_comment);
    
    SELECT LAST_INSERT_ID() as review_id, 'Review added successfully' as message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AddProductVariant` (IN `p_product_id` INT, IN `p_size` VARCHAR(20), IN `p_material` VARCHAR(100), IN `p_color` VARCHAR(100), IN `p_weight` DECIMAL(10,2), IN `p_price` DECIMAL(10,2), IN `p_sku` VARCHAR(100), IN `p_stock` INT)   BEGIN
    INSERT INTO product_variants (
        product_id, size, material, color, weight, price, sku, stock
    ) VALUES (
        p_product_id, p_size, p_material, p_color, p_weight, p_price, p_sku, GREATEST(p_stock, 0)
    );
    
    SELECT LAST_INSERT_ID() as variant_id, 'Variant added successfully' as message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AddToCart` (IN `p_user_id` INT, IN `p_session_id` VARCHAR(100), IN `p_product_id` INT, IN `p_variant_id` INT, IN `p_quantity` INT)   BEGIN
    DECLARE v_cart_id INT;
    DECLARE v_price DECIMAL(10,2);
    DECLARE v_existing_quantity INT DEFAULT 0;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Tìm hoặc tạo cart
    IF p_user_id IS NOT NULL THEN
        SELECT cart_id INTO v_cart_id FROM carts WHERE user_id = p_user_id LIMIT 1;
        IF v_cart_id IS NULL THEN
            INSERT INTO carts (user_id) VALUES (p_user_id);
            SET v_cart_id = LAST_INSERT_ID();
        END IF;
    ELSE
        SELECT cart_id INTO v_cart_id FROM carts WHERE session_id = p_session_id LIMIT 1;
        IF v_cart_id IS NULL THEN
            INSERT INTO carts (session_id) VALUES (p_session_id);
            SET v_cart_id = LAST_INSERT_ID();
        END IF;
    END IF;
    
    -- Lấy giá
    IF p_variant_id IS NOT NULL THEN
        SELECT price INTO v_price FROM product_variants WHERE variant_id = p_variant_id LIMIT 1;
    ELSE
        SELECT base_price INTO v_price FROM products WHERE product_id = p_product_id LIMIT 1;
    END IF;
    
    IF v_price IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Product or variant not found';
    END IF;
    
    -- Kiểm tra item đã tồn tại
    SELECT quantity INTO v_existing_quantity 
    FROM cart_items 
    WHERE cart_id = v_cart_id 
    AND product_id = p_product_id 
    AND (variant_id = p_variant_id OR (variant_id IS NULL AND p_variant_id IS NULL))
    LIMIT 1;
    
    IF v_existing_quantity > 0 THEN
        UPDATE cart_items 
        SET quantity = quantity + p_quantity 
        WHERE cart_id = v_cart_id 
        AND product_id = p_product_id 
        AND (variant_id = p_variant_id OR (variant_id IS NULL AND p_variant_id IS NULL));
    ELSE
        INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, price)
        VALUES (v_cart_id, p_product_id, p_variant_id, p_quantity, v_price);
    END IF;
    
    COMMIT;
    
    SELECT 'Item added to cart successfully' as message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AddToWishlist` (IN `p_user_id` INT, IN `p_product_id` INT)   BEGIN
    DECLARE v_wishlist_id INT;
    
    SELECT wishlist_id INTO v_wishlist_id FROM wishlists WHERE user_id = p_user_id LIMIT 1;
    
    IF v_wishlist_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User wishlist not found';
    END IF;
    
    INSERT IGNORE INTO wishlist_items (wishlist_id, product_id)
    VALUES (v_wishlist_id, p_product_id);
    
    SELECT 'Product added to wishlist' as message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AddUserAddress` (IN `p_user_id` INT, IN `p_full_name` VARCHAR(100), IN `p_phone` VARCHAR(20), IN `p_street` TEXT, IN `p_ward` VARCHAR(100), IN `p_province` VARCHAR(100), IN `p_postal_code` VARCHAR(20), IN `p_is_default` TINYINT(1))   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    SET p_is_default = COALESCE(p_is_default, 0);
    
    START TRANSACTION;
    
    -- Nếu đây là địa chỉ mặc định, bỏ default của các địa chỉ khác
    IF p_is_default = 1 THEN
        UPDATE user_addresses 
        SET is_default = 0 
        WHERE user_id = p_user_id;
    END IF;
    
    INSERT INTO user_addresses (
        user_id, full_name, phone, street, ward, province, postal_code, is_default
    ) VALUES (
        p_user_id, p_full_name, p_phone, p_street, p_ward, p_province, p_postal_code, p_is_default
    );
    
    COMMIT;
    
    SELECT LAST_INSERT_ID() as address_id, 'Address added successfully' as message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_ApplyDiscount` (IN `p_code` VARCHAR(50), IN `p_order_amount` DECIMAL(10,2), IN `p_user_id` INT, OUT `p_discount_id` INT, OUT `p_discount_amount` DECIMAL(10,2))   BEGIN
    DECLARE v_discount_type ENUM('percentage','fixed_amount');
    DECLARE v_discount_value DECIMAL(10,2);
    DECLARE v_min_order_amount DECIMAL(10,2);
    DECLARE v_max_discount_amount DECIMAL(10,2);
    DECLARE v_usage_limit INT;
    DECLARE v_usage_limit_per_user INT;
    DECLARE v_used_count INT;
    DECLARE v_user_usage_count INT DEFAULT 0;
    
    SET p_discount_amount = 0;
    SET p_discount_id = NULL;
    
    -- Lấy thông tin discount
    SELECT discount_id, discount_type, discount_value, 
           COALESCE(min_order_amount, 0), max_discount_amount, 
           usage_limit, COALESCE(usage_limit_per_user, 999999), COALESCE(used_count, 0)
    INTO p_discount_id, v_discount_type, v_discount_value, v_min_order_amount,
         v_max_discount_amount, v_usage_limit, v_usage_limit_per_user, v_used_count
    FROM discounts 
    WHERE code = p_code 
    AND is_active = 1 
    AND NOW() BETWEEN start_date AND end_date
    LIMIT 1;
    
    IF p_discount_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid or expired discount code';
    END IF;
    
    -- Kiểm tra điều kiện
    IF p_order_amount < v_min_order_amount THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Order amount does not meet minimum requirement';
    END IF;
    
    IF v_usage_limit IS NOT NULL AND v_used_count >= v_usage_limit THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Discount code usage limit reached';
    END IF;
    
    -- Kiểm tra usage per user
    IF p_user_id IS NOT NULL THEN
        SELECT COUNT(*) INTO v_user_usage_count
        FROM discount_usages 
        WHERE discount_id = p_discount_id AND user_id = p_user_id;
        
        IF v_user_usage_count >= v_usage_limit_per_user THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'You have reached usage limit for this code';
        END IF;
    END IF;
    
    -- Tính discount amount
    IF v_discount_type = 'percentage' THEN
        SET p_discount_amount = p_order_amount * (v_discount_value / 100);
    ELSE
        SET p_discount_amount = v_discount_value;
    END IF;
    
    -- Áp dụng max discount amount
    IF v_max_discount_amount IS NOT NULL AND p_discount_amount > v_max_discount_amount THEN
        SET p_discount_amount = v_max_discount_amount;
    END IF;
    
    -- Cập nhật used_count
    UPDATE discounts SET used_count = COALESCE(used_count, 0) + 1 WHERE discount_id = p_discount_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AuthenticateUser` (IN `p_email` VARCHAR(255), IN `p_password_hash` VARCHAR(255))   BEGIN
    SELECT 
        u.user_id,
        u.email,
        u.name,
        u.phone,
        u.is_active,
        COALESCE(r.role_name, 'customer') as role_name
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.role_id
    WHERE u.email = p_email 
    AND u.password_hash = p_password_hash
    AND u.is_active = 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CleanupOldCarts` (IN `p_days_old` INT)   BEGIN
    SET p_days_old = COALESCE(p_days_old, 30);
    
    DELETE c FROM carts c
    WHERE c.session_id IS NOT NULL 
    AND c.updated_at < DATE_SUB(NOW(), INTERVAL p_days_old DAY)
    AND NOT EXISTS (
        SELECT 1 FROM cart_items ci WHERE ci.cart_id = c.cart_id
    );
    
    SELECT ROW_COUNT() as deleted_carts, 'Old empty carts cleaned up' as message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CreateCategory` (IN `p_name` VARCHAR(100), IN `p_slug` VARCHAR(255), IN `p_parent_id` INT)   BEGIN
    DECLARE v_category_id INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Kiểm tra slug đã tồn tại chưa
    IF EXISTS(SELECT 1 FROM categories WHERE slug = p_slug) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Slug already exists';
    END IF;
    
    -- Chuẩn hóa parent_id: 0 hoặc NULL đều được coi là NULL
    IF p_parent_id = 0 THEN
        SET p_parent_id = NULL;
    END IF;
    
    -- Kiểm tra parent_id có tồn tại không (nếu có)
    IF p_parent_id IS NOT NULL AND NOT EXISTS(SELECT 1 FROM categories WHERE category_id = p_parent_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Parent category not found';
    END IF;
    
    -- Tạo category mới
    INSERT INTO categories (name, slug, parent_id, is_active)
    VALUES (p_name, p_slug, p_parent_id, 1);
    
    SET v_category_id = LAST_INSERT_ID();
    
    COMMIT;
    
    SELECT v_category_id as category_id, 'Category created successfully' as message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CreateDiscount` (IN `p_code` VARCHAR(50), IN `p_name` VARCHAR(255), IN `p_description` TEXT, IN `p_discount_type` ENUM('percentage','fixed_amount'), IN `p_discount_value` DECIMAL(10,2), IN `p_min_order_amount` DECIMAL(10,2), IN `p_max_discount_amount` DECIMAL(10,2), IN `p_usage_limit` INT, IN `p_usage_limit_per_user` INT, IN `p_start_date` DATETIME, IN `p_end_date` DATETIME)   BEGIN
    -- Validation
    IF p_discount_type = 'percentage' AND (p_discount_value < 0 OR p_discount_value > 100) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Percentage discount must be between 0 and 100';
    END IF;
    
    IF p_discount_type = 'fixed_amount' AND p_discount_value <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fixed amount discount must be greater than 0';
    END IF;
    
    IF p_start_date >= p_end_date THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Start date must be before end date';
    END IF;
    
    INSERT INTO discounts (
        code, name, description, discount_type, discount_value,
        min_order_amount, max_discount_amount, usage_limit, usage_limit_per_user,
        start_date, end_date, used_count
    ) VALUES (
        p_code, p_name, p_description, p_discount_type, p_discount_value,
        p_min_order_amount, p_max_discount_amount, p_usage_limit, p_usage_limit_per_user,
        p_start_date, p_end_date, 0
    );
    
    SELECT LAST_INSERT_ID() as discount_id, 'Discount created successfully' as message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CreateOrder` (IN `p_user_id` INT, IN `p_session_id` VARCHAR(100), IN `p_full_name` VARCHAR(255), IN `p_email` VARCHAR(255), IN `p_phone` VARCHAR(50), IN `p_street` VARCHAR(255), IN `p_ward` VARCHAR(100), IN `p_province` VARCHAR(100), IN `p_discount_code` VARCHAR(50), IN `p_shipping_fee` DECIMAL(10,2))   BEGIN
    DECLARE v_cart_id INT DEFAULT NULL;
    DECLARE v_order_id INT;
    DECLARE v_total_amount DECIMAL(10,2) DEFAULT 0;
    DECLARE v_discount_amount DECIMAL(10,2) DEFAULT 0;
    DECLARE v_discount_id INT DEFAULT NULL;
    DECLARE v_product_id INT;
    DECLARE v_variant_id INT;
    DECLARE v_quantity INT;
    DECLARE v_price DECIMAL(10,2);
    DECLARE v_finished INT DEFAULT FALSE;
    
    DECLARE cart_cursor CURSOR FOR
        SELECT ci.product_id, ci.variant_id, ci.quantity, ci.price
        FROM cart_items ci
        WHERE ci.cart_id = v_cart_id;
        
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_finished = TRUE;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Tìm cart
    IF p_user_id IS NOT NULL THEN
        SELECT cart_id INTO v_cart_id FROM carts WHERE user_id = p_user_id LIMIT 1;
    ELSE
        SELECT cart_id INTO v_cart_id FROM carts WHERE session_id = p_session_id LIMIT 1;
    END IF;
    
    IF v_cart_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cart not found';
    END IF;
    
    -- Tính tổng tiền
    SELECT COALESCE(SUM(quantity * price), 0) INTO v_total_amount 
    FROM cart_items WHERE cart_id = v_cart_id;
    
    IF v_total_amount = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cart is empty';
    END IF;
    
    -- Áp dụng discount nếu có
    IF p_discount_code IS NOT NULL AND LENGTH(TRIM(p_discount_code)) > 0 THEN
        CALL sp_ApplyDiscount(p_discount_code, v_total_amount, p_user_id, v_discount_id, v_discount_amount);
    END IF;
    
    -- Tạo order
    INSERT INTO orders (
        user_id, full_name, email, phone, street, ward, province,
        shipping_fee, total_amount, discount_code, discount_amount
    ) VALUES (
        p_user_id, p_full_name, p_email, p_phone, p_street, p_ward, p_province,
        COALESCE(p_shipping_fee, 0), 
        v_total_amount + COALESCE(p_shipping_fee, 0) - COALESCE(v_discount_amount, 0), 
        p_discount_code, COALESCE(v_discount_amount, 0)
    );
    
    SET v_order_id = LAST_INSERT_ID();
    
    -- Chuyển cart items sang order items
    OPEN cart_cursor;
    
    read_loop: LOOP
        FETCH cart_cursor INTO v_product_id, v_variant_id, v_quantity, v_price;
        
        IF v_finished THEN
            LEAVE read_loop;
        END IF;
        
        INSERT INTO order_items (order_id, product_id, variant_id, unit_price_snapshot, quantity, total_price)
        VALUES (v_order_id, v_product_id, v_variant_id, v_price, v_quantity, v_quantity * v_price);
        
        -- Giảm stock nếu có variant
        IF v_variant_id IS NOT NULL THEN
            CALL sp_UpdateStock(v_variant_id, -v_quantity);
        END IF;
    END LOOP;
    
    CLOSE cart_cursor;
    
    -- Ghi nhận discount usage
    IF v_discount_id IS NOT NULL THEN
        INSERT INTO discount_usages (discount_id, order_id, user_id, discount_amount)
        VALUES (v_discount_id, v_order_id, p_user_id, v_discount_amount);
    END IF;
    
    -- Xóa cart items
    DELETE FROM cart_items WHERE cart_id = v_cart_id;
    
    COMMIT;
    
    SELECT v_order_id as order_id, 'Order created successfully' as message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CreateUser` (IN `p_email` VARCHAR(255), IN `p_password_hash` VARCHAR(255), IN `p_name` VARCHAR(100), IN `p_phone` VARCHAR(20), IN `p_role_name` VARCHAR(50))   BEGIN
    DECLARE v_role_id INT DEFAULT NULL;
    DECLARE v_user_id INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Lấy role_id, nếu không có thì dùng role mặc định
    SELECT role_id INTO v_role_id FROM roles WHERE role_name = COALESCE(p_role_name, 'customer') LIMIT 1;
    
    -- Nếu vẫn không tìm thấy role, tạo role customer mặc định
    IF v_role_id IS NULL THEN
        INSERT IGNORE INTO roles (role_name, description) VALUES ('customer', 'Regular customer');
        SELECT role_id INTO v_role_id FROM roles WHERE role_name = 'customer' LIMIT 1;
    END IF;
    
    -- Tạo user mới
    INSERT INTO users (email, password_hash, name, phone, role_id)
    VALUES (p_email, p_password_hash, p_name, p_phone, v_role_id);
    
    SET v_user_id = LAST_INSERT_ID();
    
    -- Tạo wishlist cho user
    INSERT INTO wishlists (user_id, name) VALUES (v_user_id, 'My Wishlist');
    
    COMMIT;
    
    SELECT v_user_id as user_id, 'User created successfully' as message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetCartItems` (IN `p_user_id` INT, IN `p_session_id` VARCHAR(100))   BEGIN
    DECLARE v_cart_id INT;
    
    IF p_user_id IS NOT NULL THEN
        SELECT cart_id INTO v_cart_id FROM carts WHERE user_id = p_user_id LIMIT 1;
    ELSE
        SELECT cart_id INTO v_cart_id FROM carts WHERE session_id = p_session_id LIMIT 1;
    END IF;
    
    SELECT 
        ci.cart_item_id,
        ci.product_id,
        p.name as product_name,
        ci.variant_id,
        pv.size,
        pv.material,
        pv.color,
        ci.quantity,
        ci.price,
        (ci.quantity * ci.price) as total_price,
        p.slug
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.product_id
    LEFT JOIN product_variants pv ON ci.variant_id = pv.variant_id
    WHERE ci.cart_id = COALESCE(v_cart_id, 0)
    ORDER BY ci.added_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetCustomerStats` (IN `p_user_id` INT)   BEGIN
    IF p_user_id IS NOT NULL THEN
        -- Thống kê của 1 khách hàng cụ thể
        SELECT 
            u.user_id,
            u.name,
            u.email,
            COUNT(o.order_id) as total_orders,
            COALESCE(SUM(CASE WHEN o.order_status = 'delivered' THEN o.total_amount ELSE 0 END), 0) as total_spent,
            AVG(CASE WHEN o.order_status = 'delivered' THEN o.total_amount ELSE NULL END) as avg_order_value,
            MIN(o.created_at) as first_order_date,
            MAX(o.created_at) as last_order_date
        FROM users u
        LEFT JOIN orders o ON u.user_id = o.user_id
        WHERE u.user_id = p_user_id
        GROUP BY u.user_id;
    ELSE
        -- Thống kê tổng quan khách hàng
        SELECT 
            COUNT(DISTINCT u.user_id) as total_customers,
            COUNT(DISTINCT CASE WHEN o.order_id IS NOT NULL THEN u.user_id END) as customers_with_orders,
            COALESCE(AVG(customer_stats.total_orders), 0) as avg_orders_per_customer,
            COALESCE(AVG(customer_stats.total_spent), 0) as avg_spent_per_customer
        FROM users u
        LEFT JOIN (
            SELECT 
                user_id,
                COUNT(*) as total_orders,
                SUM(CASE WHEN order_status = 'delivered' THEN total_amount ELSE 0 END) as total_spent
            FROM orders
            GROUP BY user_id
        ) customer_stats ON u.user_id = customer_stats.user_id
        LEFT JOIN orders o ON u.user_id = o.user_id;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetLowStockProducts` (IN `p_threshold` INT)   BEGIN
    SET p_threshold = COALESCE(p_threshold, 10);
    
    SELECT 
        p.product_id,
        p.name as product_name,
        pv.variant_id,
        pv.size,
        pv.material,
        pv.color,
        pv.stock,
        pv.sku
    FROM product_variants pv
    JOIN products p ON pv.product_id = p.product_id
    WHERE pv.stock <= p_threshold
    AND p.is_active = 1
    ORDER BY pv.stock ASC, p.name ASC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetOrderDetails` (IN `p_order_id` INT)   BEGIN
    -- Thông tin đơn hàng
    SELECT 
        o.*,
        u.name as user_name,
        u.email as user_email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    WHERE o.order_id = p_order_id;
    
    -- Chi tiết sản phẩm trong đơn hàng
    SELECT 
        oi.*,
        p.name as product_name,
        p.slug,
        pv.size,
        pv.material,
        pv.color
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
    WHERE oi.order_id = p_order_id
    ORDER BY oi.order_item_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetProductAnalytics` (IN `p_product_id` INT, IN `p_start_date` DATE, IN `p_end_date` DATE)   BEGIN
    -- Bảng 1: Thông tin cơ bản sản phẩm
    SELECT 
        p.product_id,
        p.name,
        p.base_price,
        p.created_at,
        p.is_active,
        c.collection_name,
        GROUP_CONCAT(DISTINCT cat.name ORDER BY cat.name SEPARATOR ', ') as categories,
        COUNT(DISTINCT pv.variant_id) as variant_count,
        COALESCE(SUM(pv.stock), 0) as total_stock,
        COUNT(DISTINCT pr.review_id) as review_count,
        COALESCE(AVG(pr.rating), 0) as avg_rating
    FROM products p
    LEFT JOIN collection c ON p.collection_id = c.collection_id
    LEFT JOIN product_categories pc ON p.product_id = pc.product_id
    LEFT JOIN categories cat ON pc.category_id = cat.category_id
    LEFT JOIN product_variants pv ON p.product_id = pv.product_id
    LEFT JOIN product_reviews pr ON p.product_id = pr.product_id AND pr.status = 'approved'
    WHERE (p_product_id IS NULL OR p.product_id = p_product_id)
    GROUP BY p.product_id
    ORDER BY p.name;
    
    -- Bảng 2: Thống kê bán hàng
    SELECT 
        p.product_id,
        p.name,
        COALESCE(SUM(oi.quantity), 0) as total_sold,
        COALESCE(SUM(oi.total_price), 0) as total_revenue,
        COALESCE(COUNT(DISTINCT o.order_id), 0) as order_count,
        COALESCE(AVG(oi.quantity), 0) as avg_quantity_per_order
    FROM products p
    LEFT JOIN order_items oi ON p.product_id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.order_id 
        AND o.order_status NOT IN ('cancelled')
        AND (p_start_date IS NULL OR DATE(o.created_at) >= p_start_date)
        AND (p_end_date IS NULL OR DATE(o.created_at) <= p_end_date)
    WHERE (p_product_id IS NULL OR p.product_id = p_product_id)
    GROUP BY p.product_id
    ORDER BY total_sold DESC;
    
    -- Bảng 3: Thống kê variants (nếu có product_id cụ thể)
    IF p_product_id IS NOT NULL THEN
        SELECT 
            pv.variant_id,
            pv.size,
            pv.material,
            pv.color,
            pv.price,
            pv.stock,
            pv.sku,
            COALESCE(SUM(oi.quantity), 0) as sold_quantity,
            COALESCE(SUM(oi.total_price), 0) as variant_revenue
        FROM product_variants pv
        LEFT JOIN order_items oi ON pv.variant_id = oi.variant_id
        LEFT JOIN orders o ON oi.order_id = o.order_id 
            AND o.order_status NOT IN ('cancelled')
            AND (p_start_date IS NULL OR DATE(o.created_at) >= p_start_date)
            AND (p_end_date IS NULL OR DATE(o.created_at) <= p_end_date)
        WHERE pv.product_id = p_product_id
        GROUP BY pv.variant_id
        ORDER BY sold_quantity DESC;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetRevenueReport` (IN `p_start_date` DATE, IN `p_end_date` DATE, IN `p_group_by` ENUM('day','month','year'))   BEGIN
    SET p_group_by = COALESCE(p_group_by, 'day');
    
    IF p_group_by = 'day' THEN
        SELECT 
            DATE(created_at) as period,
            COUNT(*) as order_count,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value
        FROM orders 
        WHERE order_status NOT IN ('cancelled') 
        AND (p_start_date IS NULL OR DATE(created_at) >= p_start_date)
        AND (p_end_date IS NULL OR DATE(created_at) <= p_end_date)
        GROUP BY DATE(created_at)
        ORDER BY period;
        
    ELSEIF p_group_by = 'month' THEN
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as period,
            COUNT(*) as order_count,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value
        FROM orders 
        WHERE order_status NOT IN ('cancelled')
        AND (p_start_date IS NULL OR DATE(created_at) >= p_start_date)
        AND (p_end_date IS NULL OR DATE(created_at) <= p_end_date)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY period;
        
    ELSE -- year
        SELECT 
            YEAR(created_at) as period,
            COUNT(*) as order_count,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value
        FROM orders 
        WHERE order_status NOT IN ('cancelled')
        AND (p_start_date IS NULL OR DATE(created_at) >= p_start_date)
        AND (p_end_date IS NULL OR DATE(created_at) <= p_end_date)
        GROUP BY YEAR(created_at)
        ORDER BY period;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetTopSellingProducts` (IN `p_start_date` DATE, IN `p_end_date` DATE, IN `p_limit` INT)   BEGIN
    SET p_limit = COALESCE(p_limit, 10);
    
    SELECT 
        p.product_id,
        p.name,
        p.slug,
        SUM(oi.quantity) as total_sold,
        SUM(oi.total_price) as total_revenue,
        COUNT(DISTINCT o.order_id) as order_count
    FROM products p
    JOIN order_items oi ON p.product_id = oi.product_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.order_status NOT IN ('cancelled')
    AND (p_start_date IS NULL OR DATE(o.created_at) >= p_start_date)
    AND (p_end_date IS NULL OR DATE(o.created_at) <= p_end_date)
    AND p.is_active = 1
    GROUP BY p.product_id
    ORDER BY total_sold DESC
    LIMIT p_limit;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetUserOrderHistory` (IN `p_user_id` INT, IN `p_status` VARCHAR(50), IN `p_limit` INT, IN `p_offset` INT)   BEGIN
    SET p_limit = COALESCE(p_limit, 10);
    SET p_offset = COALESCE(p_offset, 0);
    
    SELECT 
        o.order_id,
        o.order_status,
        o.total_amount,
        o.created_at,
        o.updated_at,
        COUNT(oi.order_item_id) as item_count,
        GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ') as product_names
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE o.user_id = p_user_id
    AND (p_status IS NULL OR o.order_status = p_status)
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
    LIMIT p_limit OFFSET p_offset;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetWishlist` (IN `p_user_id` INT)   BEGIN
    SELECT 
        wi.wishlist_item_id,
        p.product_id,
        p.name,
        p.base_price,
        p.slug,
        COALESCE(MIN(pv.price), p.base_price) as min_price,
        COALESCE(MAX(pv.price), p.base_price) as max_price,
        wi.added_at
    FROM wishlist_items wi
    JOIN wishlists w ON wi.wishlist_id = w.wishlist_id
    JOIN products p ON wi.product_id = p.product_id
    LEFT JOIN product_variants pv ON p.product_id = pv.product_id
    WHERE w.user_id = p_user_id AND p.is_active = 1
    GROUP BY wi.wishlist_item_id, p.product_id
    ORDER BY wi.added_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_MergeGuestCartToUser` (IN `p_user_id` INT, IN `p_session_id` VARCHAR(100))   BEGIN
    DECLARE v_user_cart_id INT DEFAULT NULL;
    DECLARE v_guest_cart_id INT DEFAULT NULL;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Tìm cart của user và guest
    SELECT cart_id INTO v_user_cart_id FROM carts WHERE user_id = p_user_id LIMIT 1;
    SELECT cart_id INTO v_guest_cart_id FROM carts WHERE session_id = p_session_id LIMIT 1;
    
    IF v_guest_cart_id IS NOT NULL THEN
        -- Tạo user cart nếu chưa có
        IF v_user_cart_id IS NULL THEN
            INSERT INTO carts (user_id) VALUES (p_user_id);
            SET v_user_cart_id = LAST_INSERT_ID();
        END IF;
        
        -- Merge items từ guest cart sang user cart
        INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, price)
        SELECT v_user_cart_id, product_id, variant_id, quantity, price
        FROM cart_items
        WHERE cart_id = v_guest_cart_id
        ON DUPLICATE KEY UPDATE
        quantity = cart_items.quantity + VALUES(quantity);
        
        -- Xóa guest cart
        DELETE FROM cart_items WHERE cart_id = v_guest_cart_id;
        DELETE FROM carts WHERE cart_id = v_guest_cart_id;
    END IF;
    
    COMMIT;
    
    SELECT 'Cart merged successfully' as message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_product_checkStock` (IN `p_product_id` INT, IN `p_size` VARCHAR(50), IN `p_color` VARCHAR(50))   BEGIN
    SELECT stock FROM product_variants
    WHERE product_id = p_product_id
      AND BINARY size = BINARY p_size
      AND BINARY color = BINARY p_color;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_product_count` (IN `p_category_id` INT)   BEGIN
    IF p_category_id IS NOT NULL AND p_category_id > 0 THEN
        SELECT COUNT(*) as total 
        FROM products p
        WHERE p.is_active = 1
        AND EXISTS (
            SELECT 1 FROM product_categories pc 
            WHERE pc.product_id = p.product_id AND pc.category_id = p_category_id
        );
    ELSE
        SELECT COUNT(*) as total FROM products WHERE is_active = 1;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_product_create` (IN `p_name` VARCHAR(255), IN `p_description` TEXT, IN `p_base_price` DECIMAL(10,2), IN `p_sku` VARCHAR(100), IN `p_slug` VARCHAR(255), IN `p_collection_id` INT, IN `p_is_active` TINYINT)   BEGIN
    INSERT INTO products
        (name, description, base_price, sku, slug, collection_id, is_active, created_at, updated_at)
    VALUES 
        (p_name, p_description, p_base_price, p_sku, p_slug, p_collection_id, p_is_active, NOW(), NOW());
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_product_delete` (IN `p_id` INT)   BEGIN
    UPDATE products SET is_active = 0, updated_at = NOW() WHERE product_id = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_product_findById` (IN `p_id` INT)   BEGIN
    SELECT p.*, 
           COALESCE(SUM(pv.stock), 0) as stock_quantity,
           COUNT(pv.variant_id) as variant_count
    FROM products p 
    LEFT JOIN product_variants pv ON p.product_id = pv.product_id
    WHERE p.product_id = p_id AND p.is_active = 1
    GROUP BY p.product_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_product_findBySlug` (IN `p_slug` VARCHAR(255))   BEGIN
    SELECT * FROM products WHERE slug = p_slug AND is_active = 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_product_getAll` (IN `p_limit` INT, IN `p_offset` INT)   BEGIN
    SELECT * FROM products WHERE is_active = 1 
    ORDER BY created_at DESC 
    LIMIT p_limit OFFSET p_offset;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_product_getAvailableVariants` (IN `p_product_id` INT)   BEGIN
    SELECT * FROM product_variants
    WHERE product_id = p_product_id AND stock > 0
    ORDER BY color ASC, size ASC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_product_getTotalStock` (IN `p_product_id` INT)   BEGIN
    SELECT SUM(stock) as total_stock 
    FROM product_variants 
    WHERE product_id = p_product_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_product_getVariants` (IN `p_product_id` INT)   BEGIN
    SELECT * FROM product_variants 
    WHERE product_id = p_product_id AND stock > 0 
    ORDER BY size, color;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_product_update` (IN `p_id` INT, IN `p_name` VARCHAR(255), IN `p_description` TEXT, IN `p_base_price` DECIMAL(10,2), IN `p_sku` VARCHAR(100), IN `p_slug` VARCHAR(255), IN `p_collection_id` INT, IN `p_is_active` TINYINT)   BEGIN
    UPDATE products
    SET 
        name = p_name, 
        description = p_description, 
        base_price = p_base_price, 
        sku = p_sku, 
        slug = p_slug, 
        collection_id = p_collection_id,
        is_active = p_is_active, 
        updated_at = NOW()
    WHERE product_id = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_product_updateStock` (IN `p_product_id` INT, IN `p_size` VARCHAR(50), IN `p_color` VARCHAR(50), IN `p_quantity` INT)   BEGIN
    UPDATE product_variants
    SET stock = stock - p_quantity
    WHERE product_id = p_product_id 
      AND size = p_size 
      AND color = p_color 
      AND stock >= p_quantity;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_RemoveFromCart` (IN `p_cart_item_id` INT)   BEGIN
    DELETE FROM cart_items WHERE cart_item_id = p_cart_item_id;
    
    SELECT 'Item removed from cart' as message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_RemoveFromWishlist` (IN `p_wishlist_item_id` INT)   BEGIN
    DELETE FROM wishlist_items WHERE wishlist_item_id = p_wishlist_item_id;
    
    SELECT 'Product removed from wishlist' as message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_SearchProducts` (IN `p_keyword` VARCHAR(255), IN `p_category_id` INT, IN `p_collection_id` INT, IN `p_min_price` DECIMAL(10,2), IN `p_max_price` DECIMAL(10,2), IN `p_material` VARCHAR(100), IN `p_sort_by` VARCHAR(20), IN `p_limit` INT, IN `p_offset` INT)   BEGIN
    -- mỗi DECLARE phải trên 1 dòng và kết thúc bằng ;
    DECLARE v_sql TEXT DEFAULT '';
    DECLARE v_sort TEXT DEFAULT 'p.created_at DESC';

    IF p_sort_by = 'price_asc' THEN
        SET v_sort = 'pv_stats.min_price ASC';
    ELSEIF p_sort_by = 'price_desc' THEN
        SET v_sort = 'pv_stats.max_price DESC';
    ELSEIF p_sort_by = 'name' THEN
        SET v_sort = 'p.name ASC';
    ELSEIF p_sort_by = 'newest' THEN
        SET v_sort = 'p.created_at DESC';
    END IF;

    SET v_sql = '
    SELECT p.product_id, p.name, p.description, p.base_price, p.slug, p.created_at,
           c.collection_name, pv_stats.min_price, pv_stats.max_price, pr_stats.review_count, pr_stats.avg_rating
    FROM products p
    LEFT JOIN `collection` c ON p.collection_id = c.collection_id
    LEFT JOIN (
        SELECT pv.product_id, MIN(pv.price) AS min_price, MAX(pv.price) AS max_price
        FROM product_variants pv
        GROUP BY pv.product_id
    ) pv_stats ON p.product_id = pv_stats.product_id
    LEFT JOIN (
        SELECT pr.product_id, COUNT(pr.review_id) AS review_count, AVG(pr.rating) AS avg_rating
        FROM product_reviews pr
        WHERE pr.status = \'approved\'
        GROUP BY pr.product_id
    ) pr_stats ON p.product_id = pr_stats.product_id
    LEFT JOIN product_categories pc ON p.product_id = pc.product_id
    WHERE p.is_active = 1
    ';

    IF p_keyword IS NOT NULL AND p_keyword <> '' THEN
        SET v_sql = CONCAT(v_sql, ' AND (p.name LIKE ', QUOTE(CONCAT('%', p_keyword, '%')), 
                                  ' OR p.description LIKE ', QUOTE(CONCAT('%', p_keyword, '%')), ') ');
    END IF;

    IF p_category_id IS NOT NULL THEN
        SET v_sql = CONCAT(v_sql, ' AND pc.category_id = ', CAST(p_category_id AS CHAR), ' ');
    END IF;

    IF p_collection_id IS NOT NULL THEN
        SET v_sql = CONCAT(v_sql, ' AND p.collection_id = ', CAST(p_collection_id AS CHAR), ' ');
    END IF;

    IF p_material IS NOT NULL AND p_material <> '' THEN
        SET v_sql = CONCAT(v_sql, ' AND EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.product_id AND v.material = ', QUOTE(p_material), ') ');
    END IF;

    IF p_min_price IS NOT NULL THEN
        SET v_sql = CONCAT(v_sql, ' AND COALESCE(pv_stats.min_price, p.base_price) >= ', CAST(p_min_price AS CHAR), ' ');
    END IF;

    IF p_max_price IS NOT NULL THEN
        SET v_sql = CONCAT(v_sql, ' AND COALESCE(pv_stats.max_price, p.base_price) <= ', CAST(p_max_price AS CHAR), ' ');
    END IF;

    SET v_sql = CONCAT(v_sql, ' ORDER BY ', v_sort, ' LIMIT ', IFNULL(p_limit, 20), ' OFFSET ', IFNULL(p_offset, 0), ';');

    SET @final_sql = v_sql;
    PREPARE stmt FROM @final_sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_UpdateCartItemQuantity` (IN `p_cart_item_id` INT, IN `p_new_quantity` INT)   BEGIN
    IF p_new_quantity <= 0 THEN
        DELETE FROM cart_items WHERE cart_item_id = p_cart_item_id;
        SELECT 'Item removed from cart' as message;
    ELSE
        UPDATE cart_items 
        SET quantity = p_new_quantity 
        WHERE cart_item_id = p_cart_item_id;
        SELECT 'Cart item quantity updated' as message;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_UpdateOrderStatus` (IN `p_order_id` INT, IN `p_status` ENUM('pending','paid','shipped','delivered','cancelled'))   BEGIN
    UPDATE orders 
    SET order_status = p_status, updated_at = CURRENT_TIMESTAMP 
    WHERE order_id = p_order_id;
    
    SELECT 'Order status updated successfully' as message, ROW_COUNT() as affected_rows;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_UpdateStock` (IN `p_variant_id` INT, IN `p_quantity_change` INT)   BEGIN
    DECLARE v_current_stock INT;
    DECLARE v_new_stock INT;
    
    SELECT stock INTO v_current_stock FROM product_variants WHERE variant_id = p_variant_id;
    
    IF v_current_stock IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Variant not found';
    END IF;
    
    SET v_new_stock = v_current_stock + p_quantity_change;
    
    IF v_new_stock >= 0 THEN
        UPDATE product_variants 
        SET stock = v_new_stock 
        WHERE variant_id = p_variant_id;
        
        SELECT 'Stock updated successfully' as message, v_new_stock as new_stock;
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock';
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_UpdateUserProfile` (IN `p_user_id` INT, IN `p_name` VARCHAR(100), IN `p_phone` VARCHAR(20))   BEGIN
    UPDATE users 
    SET name = p_name, 
        phone = p_phone,
        updated_at = CURRENT_TIMESTAMP
    WHERE user_id = p_user_id;
    
    SELECT 'Profile updated successfully' as message, ROW_COUNT() as affected_rows;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_ValidateDiscountCode` (IN `p_code` VARCHAR(50), IN `p_order_amount` DECIMAL(10,2), IN `p_user_id` INT)   BEGIN
    DECLARE v_discount_id INT DEFAULT NULL;
    DECLARE v_discount_amount DECIMAL(10,2) DEFAULT 0;
    DECLARE v_error_message VARCHAR(500) DEFAULT '';
    DECLARE CONTINUE HANDLER FOR SQLSTATE '45000' 
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_error_message = MESSAGE_TEXT;
    END;
    
    CALL sp_ApplyDiscount(p_code, p_order_amount, p_user_id, v_discount_id, v_discount_amount);
    
    IF v_discount_id IS NOT NULL THEN
        SELECT 
            'valid' as status,
            v_discount_amount as discount_amount,
            'Discount code applied successfully' as message;
    ELSE
        SELECT 
            'invalid' as status,
            0 as discount_amount,
            COALESCE(v_error_message, 'Invalid discount code') as message;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`cart_id`, `user_id`, `session_id`, `created_at`, `updated_at`) VALUES
(1, 11, NULL, '2025-11-02 11:47:01', '2025-11-08 04:54:18');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_item_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1 CHECK (`quantity` > 0),
  `price` decimal(10,2) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`cart_item_id`, `cart_id`, `product_id`, `variant_id`, `quantity`, `price`, `added_at`, `created_at`, `updated_at`) VALUES
(22, 1, 3, 5, 1, 25000000.00, '2025-11-08 04:54:18', '2025-11-08 04:54:18', '2025-11-08 04:54:18');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `parent_id`, `slug`, `is_active`, `created_at`) VALUES
(1, 'Nhẫn', NULL, 'Rings', 1, '2025-09-20 16:47:13'),
(2, 'Dây chuyền', NULL, 'Necklaces', 1, '2025-09-20 16:47:13'),
(3, 'Bông tai', NULL, 'Earrings', 1, '2025-09-20 16:47:13'),
(4, 'Vòng tay', NULL, 'Bracelets', 1, '2025-09-20 16:47:13'),
(5, 'Đồng hồ', NULL, 'Watches', 1, '2025-09-20 16:47:13');

-- --------------------------------------------------------

--
-- Table structure for table `collection`
--

CREATE TABLE `collection` (
  `collection_id` int(11) NOT NULL,
  `collection_name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `collection`
--

INSERT INTO `collection` (`collection_id`, `collection_name`, `slug`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Luxury Gold Collection', 'luxury-gold-collection', 'Bộ sưu tập vàng cao cấp với thiết kế tinh tế', 1, '2025-09-20 16:47:13', '2025-09-20 16:47:13'),
(2, 'Diamond Star Series', 'diamond-star-series', 'Những viên kim cương lấp lánh nhất', 1, '2025-09-20 16:47:13', '2025-09-20 16:47:13'),
(3, 'Pearl Beauty Line', 'pearl-beauty-line', 'Vẻ đẹp tinh tế của ngọc trai', 1, '2025-09-20 16:47:13', '2025-09-20 16:47:13');

-- --------------------------------------------------------

--
-- Table structure for table `discounts`
--

CREATE TABLE `discounts` (
  `discount_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed_amount') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL CHECK (`discount_value` > 0),
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `applicable_to` enum('all','product','category','collection','user') DEFAULT 'all',
  `target_id` int(11) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_limit_per_user` int(11) DEFAULT 1,
  `used_count` int(11) DEFAULT 0,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discount_usages`
--

CREATE TABLE `discount_usages` (
  `usage_id` int(11) NOT NULL,
  `discount_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL CHECK (`discount_amount` >= 0),
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `images`
--

CREATE TABLE `images` (
  `image_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `images`
--

INSERT INTO `images` (`image_id`, `file_name`, `file_path`, `file_type`, `alt_text`, `created_at`) VALUES
(13, 'photo-1603561591411-07134e71a2a9?w=500', 'https://images.unsplash.com/photo-1603561591411-07134e71a2a9?w=500', '', 'Nhẫn Kim Cương Vàng Trắng 18K - Main Image', '2025-09-20 16:47:13'),
(14, 'photo-1605100804763-247f67b3557e?w=500', 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=500', '', 'Nhẫn Kim Cương Vàng Trắng 18K - Gallery Image', '2025-09-20 16:47:13'),
(15, 'photo-1603561591411-07134e71a2a9?w=500', 'https://images.unsplash.com/photo-1603561591411-07134e71a2a9?w=500', '', 'Nhẫn Kim Cương Vàng Trắng 18K - Gallery Image', '2025-09-20 16:47:13'),
(16, 'photo-1515562141207-7a88fb7ce338?w=500', 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=500', '', 'Dây Chuyền Ngọc Trai Akoya - Main Image', '2025-09-20 16:47:13'),
(17, 'photo-1605100804763-247f67b3557e?w=500', 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=500', '', 'Dây Chuyền Ngọc Trai Akoya - Gallery Image', '2025-09-20 16:47:13'),
(18, 'photo-1599643478518-a784e5dc4c8f?w=500', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=500', '', 'Dây Chuyền Ngọc Trai Akoya - Gallery Image', '2025-09-20 16:47:13'),
(19, 'photo-1603561591411-07134e71a2a9?w=500', 'https://images.unsplash.com/photo-1603561591411-07134e71a2a9?w=500', '', 'Dây Chuyền Ngọc Trai Akoya - Gallery Image', '2025-09-20 16:47:13'),
(20, 'photo-1617038260897-41a1f14a8ca0?w=500', 'https://images.unsplash.com/photo-1617038260897-41a1f14a8ca0?w=500', '', 'Bông Tai Kim Cương Thiên Nhiên - Main Image', '2025-09-20 16:47:13'),
(21, 'photo-1515562141207-7a88fb7ce338?w=500', 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=500', '', 'Bông Tai Kim Cương Thiên Nhiên - Gallery Image', '2025-09-20 16:47:13'),
(22, 'photo-1617038260897-41a1f14a8ca0?w=500', 'https://images.unsplash.com/photo-1617038260897-41a1f14a8ca0?w=500', '', 'Bông Tai Kim Cương Thiên Nhiên - Gallery Image', '2025-09-20 16:47:13'),
(23, 'photo-1599643478518-a784e5dc4c8f?w=500', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=500', '', 'Bông Tai Kim Cương Thiên Nhiên - Gallery Image', '2025-09-20 16:47:13'),
(24, 'photo-1611591437281-460bfbe1220a?w=500', 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=500', '', 'Vòng Tay Bạc 925 Charm - Main Image', '2025-09-20 16:47:13'),
(25, 'photo-1605100804763-247f67b3557e?w=500', 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=500', '', 'Vòng Tay Bạc 925 Charm - Gallery Image', '2025-09-20 16:47:13'),
(26, 'photo-1599643478518-a784e5dc4c8f?w=500', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=500', '', 'Vòng Tay Bạc 925 Charm - Gallery Image', '2025-09-20 16:47:13'),
(27, 'photo-1603561591411-07134e71a2a9?w=500', 'https://images.unsplash.com/photo-1603561591411-07134e71a2a9?w=500', '', 'Vòng Tay Bạc 925 Charm - Gallery Image', '2025-09-20 16:47:13'),
(28, 'photo-1523275335684-37898b6baf30?w=500', 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500', '', 'Đồng Hồ Diamond Luxury Swiss - Main Image', '2025-09-20 16:47:13'),
(29, 'photo-1605100804763-247f67b3557e?w=500', 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=500', '', 'Đồng Hồ Diamond Luxury Swiss - Gallery Image', '2025-09-20 16:47:13'),
(30, 'photo-1515562141207-7a88fb7ce338?w=500', 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=500', '', 'Đồng Hồ Diamond Luxury Swiss - Gallery Image', '2025-09-20 16:47:13'),
(31, 'photo-1617038260897-41a1f14a8ca0?w=500', 'https://images.unsplash.com/photo-1617038260897-41a1f14a8ca0?w=500', '', 'Đồng Hồ Diamond Luxury Swiss - Gallery Image', '2025-09-20 16:47:13'),
(32, 'photo-1606800052052-a08af7148866?w=500', 'https://images.unsplash.com/photo-1606800052052-a08af7148866?w=500', '', 'Nhẫn Cưới Vàng Trắng 18K - Main Image', '2025-09-20 16:47:13'),
(33, 'photo-1605100804763-247f67b3557e?w=500', 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=500', '', 'Nhẫn Cưới Vàng Trắng 18K - Gallery Image', '2025-09-20 16:47:13'),
(34, 'photo-1617038260897-41a1f14a8ca0?w=500', 'https://images.unsplash.com/photo-1617038260897-41a1f14a8ca0?w=500', '', 'Nhẫn Cưới Vàng Trắng 18K - Gallery Image', '2025-09-20 16:47:13'),
(35, 'photo-1599643478518-a784e5dc4c8f?w=500', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=500', '', 'Nhẫn Cưới Vàng Trắng 18K - Gallery Image', '2025-09-20 16:47:13'),
(36, 'photo-1602173574767-37ac01994b2a?w=500', 'https://images.unsplash.com/photo-1602173574767-37ac01994b2a?w=500', '', 'Dây Chuyền Vàng 18K Trái Tim - Main Image', '2025-09-20 16:47:13'),
(37, 'photo-1605100804763-247f67b3557e?w=500', 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=500', '', 'Dây Chuyền Vàng 18K Trái Tim - Gallery Image', '2025-09-20 16:47:13'),
(38, 'photo-1603561591411-07134e71a2a9?w=500', 'https://images.unsplash.com/photo-1603561591411-07134e71a2a9?w=500', '', 'Dây Chuyền Vàng 18K Trái Tim - Gallery Image', '2025-09-20 16:47:13'),
(39, 'photo-1535632066927-ab7c9ab60908?w=500', 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=500', '', 'Bông Tai Vàng 24K Hoa Hồng - Main Image', '2025-09-20 16:47:13'),
(40, 'photo-1605100804763-247f67b3557e?w=500', 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=500', '', 'Bông Tai Vàng 24K Hoa Hồng - Gallery Image', '2025-09-20 16:47:13'),
(41, 'photo-1599643478518-a784e5dc4c8f?w=500', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=500', '', 'Bông Tai Vàng 24K Hoa Hồng - Gallery Image', '2025-09-20 16:47:13'),
(42, 'photo-1605100804763-247f67b3557e?w=300', 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=300', '', 'Category Banner', '2025-09-20 16:47:13'),
(43, 'photo-1599643478518-a784e5dc4c8f?w=300', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=300', '', 'Category Banner', '2025-09-20 16:47:13'),
(44, 'photo-1617038260897-41a1f14a8ca0?w=300', 'https://images.unsplash.com/photo-1617038260897-41a1f14a8ca0?w=300', '', 'Category Banner', '2025-09-20 16:47:13'),
(45, 'photo-1515562141207-7a88fb7ce338?w=300', 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=300', '', 'Category Banner', '2025-09-20 16:47:13'),
(46, 'photo-1523275335684-37898b6baf30?w=300', 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=300', '', 'Category Banner', '2025-09-20 16:47:13'),
(47, '', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTB8fGpld2Vscnl8ZW58MHx8MHx8fDA%3D', '', NULL, '2025-09-24 16:03:05'),
(48, 'text', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTB8fGpld2Vscnl8ZW58MHx8MHx8fDA%3D', '', 'Dây chuyền bạc', '2025-09-24 16:05:04'),
(49, 'photo-1603561591411-07134e7100a9?w=500', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTB8fGpld2Vscnl8ZW58MHx8MHx8fDA%3D', '', 'day chuyền bạc', '2025-09-24 16:09:07'),
(50, 'photo-1603561591411-07134e7100a9?w=500', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTB8fGpld2Vscnl8ZW58MHx8MHx8fDA%3D', '', 'day chuyền bạc', '2025-09-24 16:09:42'),
(51, 'hero-slider-1.jpg', 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=1920&h=1080&fit=crop', 'image/jpeg', 'Bo Suu Tap Mua Dong 2025', '2025-10-04 07:37:46'),
(52, 'hero-slider-2.jpg', 'https://images.unsplash.com/photo-1611652022419-a9419f74343d?w=1920&h=1080&fit=crop', 'image/jpeg', 'Trang Suc Kim Cuong Moi', '2025-10-04 07:37:46'),
(53, 'hero-slider-3.jpg', 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=1920&h=1080&fit=crop', 'image/jpeg', 'Uu dai dac biet', '2025-10-04 07:37:46'),
(54, 'product_10_69086c40d8667.png', 'public/uploads/products/10/product_10_69086c40d8667.png', '', 'product_10_69086c40d8667.png', '2025-11-03 08:48:00'),
(55, 'product_10_69086c40d8e02.png', 'public/uploads/products/10/product_10_69086c40d8e02.png', '', 'product_10_69086c40d8e02.png', '2025-11-03 08:48:00'),
(56, 'product_10_69086c40d905b.png', 'public/uploads/products/10/product_10_69086c40d905b.png', '', 'product_10_69086c40d905b.png', '2025-11-03 08:48:00'),
(57, 'product_10_69086c40d92df.png', 'public/uploads/products/10/product_10_69086c40d92df.png', '', 'product_10_69086c40d92df.png', '2025-11-03 08:48:00'),
(58, 'product_10_69086c40d953a.png', 'public/uploads/products/10/product_10_69086c40d953a.png', '', 'product_10_69086c40d953a.png', '2025-11-03 08:48:00'),
(59, 'product_10_69086c40d96ef.png', 'public/uploads/products/10/product_10_69086c40d96ef.png', '', 'product_10_69086c40d96ef.png', '2025-11-03 08:48:00'),
(60, 'product_10_69086c40d9877.png', 'public/uploads/products/10/product_10_69086c40d9877.png', '', 'product_10_69086c40d9877.png', '2025-11-03 08:48:00'),
(61, 'product_10_69086c40d9a2b.png', 'public/uploads/products/10/product_10_69086c40d9a2b.png', '', 'product_10_69086c40d9a2b.png', '2025-11-03 08:48:00'),
(62, 'product_10_69086c40d9c1f.png', 'public/uploads/products/10/product_10_69086c40d9c1f.png', '', 'product_10_69086c40d9c1f.png', '2025-11-03 08:48:00'),
(63, 'product_11_69086ee2df3d7.png', 'public/uploads/products/11/product_11_69086ee2df3d7.png', '', 'product_11_69086ee2df3d7.png', '2025-11-03 08:59:14'),
(64, 'product_11_69086ee2df9c8.png', 'public/uploads/products/11/product_11_69086ee2df9c8.png', '', 'product_11_69086ee2df9c8.png', '2025-11-03 08:59:14'),
(65, 'product_11_69086ee2dfc9e.png', 'public/uploads/products/11/product_11_69086ee2dfc9e.png', '', 'product_11_69086ee2dfc9e.png', '2025-11-03 08:59:14'),
(66, 'product_12_690872b746c36.jpg', 'public/uploads/products/12/product_12_690872b746c36.jpg', '', 'product_12_690872b746c36.jpg', '2025-11-03 09:15:35'),
(67, 'product_12_690872b746fdb.jpg', 'public/uploads/products/12/product_12_690872b746fdb.jpg', '', 'product_12_690872b746fdb.jpg', '2025-11-03 09:15:35'),
(68, 'product_13_690875e12395f.jpg', 'public/uploads/products/13/product_13_690875e12395f.jpg', '', 'product_13_690875e12395f.jpg', '2025-11-03 09:29:05'),
(69, 'product_14_6908779230d66.png', 'public/uploads/products/14/product_14_6908779230d66.png', '', 'product_14_6908779230d66.png', '2025-11-03 09:36:18'),
(70, 'product_23_690899d6ef30b.png', 'public/uploads/products/23/product_23_690899d6ef30b.png', '', 'product_23_690899d6ef30b.png', '2025-11-03 12:02:30'),
(71, 'product_30_6908a052adb31.png', 'public/uploads/products/30/product_30_6908a052adb31.png', '', 'product_30_6908a052adb31.png', '2025-11-03 12:30:10'),
(72, 'test_image_1.jpg', 'public/uploads/products/44/test_image_1.jpg', 'image/jpeg', 'test_image_1.jpg', '2025-11-03 12:57:29'),
(73, 'test_image_2.png', 'public/uploads/products/44/test_image_2.png', 'image/png', 'test_image_2.png', '2025-11-03 12:57:29'),
(74, 'product_50_6908ab0877d8a.png', 'public/uploads/products/50/product_50_6908ab0877d8a.png', 'image/png', 'product_50_6908ab0877d8a.png', '2025-11-03 13:15:52'),
(75, 'product_65_6908cb40190fd.png', 'public/uploads/products/65/product_65_6908cb40190fd.png', '', 'product_65_6908cb40190fd.png', '2025-11-03 15:33:20'),
(76, 'product_66_6908cc1e8840f.jpg', 'public/uploads/products/66/product_66_6908cc1e8840f.jpg', '', 'product_66_6908cc1e8840f.jpg', '2025-11-03 15:37:02'),
(77, 'product_66_6908cc1e8857b.jpg', 'public/uploads/products/66/product_66_6908cc1e8857b.jpg', '', 'product_66_6908cc1e8857b.jpg', '2025-11-03 15:37:02'),
(78, 'product_66_6908cc1e88691.jpg', 'public/uploads/products/66/product_66_6908cc1e88691.jpg', '', 'product_66_6908cc1e88691.jpg', '2025-11-03 15:37:02'),
(79, 'product_66_6908cc1e887e5.jpg', 'public/uploads/products/66/product_66_6908cc1e887e5.jpg', '', 'product_66_6908cc1e887e5.jpg', '2025-11-03 15:37:02'),
(80, 'product_68_6908d17e20264.png', 'public/uploads/products/68/product_68_6908d17e20264.png', '', 'product_68_6908d17e20264.png', '2025-11-03 15:59:58'),
(81, 'product_68_6908d17e2067e.jpg', 'public/uploads/products/68/product_68_6908d17e2067e.jpg', '', 'product_68_6908d17e2067e.jpg', '2025-11-03 15:59:58'),
(82, 'product_68_6908d17e20955.jpg', 'public/uploads/products/68/product_68_6908d17e20955.jpg', '', 'product_68_6908d17e20955.jpg', '2025-11-03 15:59:58'),
(83, 'product_68_6908d17e21192.jpg', 'public/uploads/products/68/product_68_6908d17e21192.jpg', '', 'product_68_6908d17e21192.jpg', '2025-11-03 15:59:58'),
(84, 'product_69_6909470f936d6.png', 'public/uploads/products/69/product_69_6909470f936d6.png', 'image/png', 'product_69_6909470f936d6.png', '2025-11-04 00:21:35'),
(85, 'product_70_6909498013c8d.jpg', 'public/uploads/products/70/product_70_6909498013c8d.jpg', '', 'product_70_6909498013c8d.jpg', '2025-11-04 00:32:00'),
(86, 'product_70_6909498013f93.jpg', 'public/uploads/products/70/product_70_6909498013f93.jpg', '', 'product_70_6909498013f93.jpg', '2025-11-04 00:32:00'),
(87, 'product_71_6909e739431f6.jpg', 'public/uploads/products/71/product_71_6909e739431f6.jpg', '', 'product_71_6909e739431f6.jpg', '2025-11-04 11:44:57'),
(88, 'product_71_6909e73943876.png', 'public/uploads/products/71/product_71_6909e73943876.png', '', 'product_71_6909e73943876.png', '2025-11-04 11:44:57'),
(89, 'product_71_6909e739439f8.png', 'public/uploads/products/71/product_71_6909e739439f8.png', '', 'product_71_6909e739439f8.png', '2025-11-04 11:44:57'),
(90, 'product_72_690dc0661165b.jpg', 'public/uploads/products/72/product_72_690dc0661165b.jpg', '', 'product_72_690dc0661165b.jpg', '2025-11-07 09:48:22'),
(91, 'product_72_690dc06611f2e.jpg', 'public/uploads/products/72/product_72_690dc06611f2e.jpg', '', 'product_72_690dc06611f2e.jpg', '2025-11-07 09:48:22'),
(92, 'product_73_690dc66a656e3.jpg', 'public/uploads/products/73/product_73_690dc66a656e3.jpg', '', 'product_73_690dc66a656e3.jpg', '2025-11-07 10:14:02'),
(93, 'product_73_690dc66a6584b.jpg', 'public/uploads/products/73/product_73_690dc66a6584b.jpg', '', 'product_73_690dc66a6584b.jpg', '2025-11-07 10:14:02'),
(94, 'product_74_690dd3d1e7f2c.jpg', 'public/uploads/products/74/product_74_690dd3d1e7f2c.jpg', '', 'product_74_690dd3d1e7f2c.jpg', '2025-11-07 11:11:13'),
(95, 'product_74_690dd3d1e84d4.jpg', 'public/uploads/products/74/product_74_690dd3d1e84d4.jpg', '', 'product_74_690dd3d1e84d4.jpg', '2025-11-07 11:11:13'),
(96, 'product_76_690ddd9277e15.jpg', 'public/uploads/products/76/product_76_690ddd9277e15.jpg', '', 'product_76_690ddd9277e15.jpg', '2025-11-07 11:52:50'),
(97, 'product_76_690ddd92781aa.jpg', 'public/uploads/products/76/product_76_690ddd92781aa.jpg', '', 'product_76_690ddd92781aa.jpg', '2025-11-07 11:52:50'),
(98, 'product_79_690ec61b57873.png', 'public/uploads/products/79/product_79_690ec61b57873.png', '', 'product_79_690ec61b57873.png', '2025-11-08 04:24:59'),
(99, 'product_80_69101fd61398f.jpg', 'public/uploads/products/80/product_80_69101fd61398f.jpg', '', 'product_80_69101fd61398f.jpg', '2025-11-09 05:00:06'),
(100, 'product_80_69101fd613f9a.jpg', 'public/uploads/products/80/product_80_69101fd613f9a.jpg', '', 'product_80_69101fd613f9a.jpg', '2025-11-09 05:00:06');

-- --------------------------------------------------------

--
-- Table structure for table `image_usages`
--

CREATE TABLE `image_usages` (
  `usage_id` int(11) NOT NULL,
  `image_id` int(11) NOT NULL,
  `ref_type` enum('product','product_thumbnail','user_avatar','feedback','banner','header','collection') NOT NULL,
  `ref_id` int(11) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `image_usages`
--

INSERT INTO `image_usages` (`usage_id`, `image_id`, `ref_type`, `ref_id`, `is_primary`, `created_at`) VALUES
(13, 13, 'product', 1, 1, '2025-09-20 16:47:13'),
(14, 14, 'product', 1, 0, '2025-09-20 16:47:13'),
(15, 15, 'product', 1, 0, '2025-09-20 16:47:13'),
(16, 16, 'product', 2, 1, '2025-09-20 16:47:13'),
(17, 17, 'product', 2, 0, '2025-09-20 16:47:13'),
(18, 18, 'product', 2, 0, '2025-09-20 16:47:13'),
(19, 19, 'product', 2, 0, '2025-09-20 16:47:13'),
(20, 20, 'product', 3, 1, '2025-09-20 16:47:13'),
(21, 21, 'product', 3, 0, '2025-09-20 16:47:13'),
(22, 22, 'product', 3, 0, '2025-09-20 16:47:13'),
(23, 23, 'product', 3, 0, '2025-09-20 16:47:13'),
(24, 24, 'product', 4, 1, '2025-09-20 16:47:13'),
(25, 25, 'product', 4, 0, '2025-09-20 16:47:13'),
(26, 26, 'product', 4, 0, '2025-09-20 16:47:13'),
(27, 27, 'product', 4, 0, '2025-09-20 16:47:13'),
(28, 28, 'product', 5, 1, '2025-09-20 16:47:13'),
(29, 29, 'product', 5, 0, '2025-09-20 16:47:13'),
(30, 30, 'product', 5, 0, '2025-09-20 16:47:13'),
(31, 31, 'product', 5, 0, '2025-09-20 16:47:13'),
(32, 32, 'product', 6, 1, '2025-09-20 16:47:13'),
(33, 33, 'product', 6, 0, '2025-09-20 16:47:13'),
(34, 34, 'product', 6, 0, '2025-09-20 16:47:13'),
(35, 35, 'product', 6, 0, '2025-09-20 16:47:13'),
(36, 36, 'product', 7, 1, '2025-09-20 16:47:13'),
(37, 37, 'product', 7, 0, '2025-09-20 16:47:13'),
(38, 38, 'product', 7, 0, '2025-09-20 16:47:13'),
(39, 39, 'product', 8, 1, '2025-09-20 16:47:13'),
(40, 40, 'product', 8, 0, '2025-09-20 16:47:13'),
(41, 41, 'product', 8, 0, '2025-09-20 16:47:13'),
(42, 42, 'banner', 1, 1, '2025-09-20 16:47:13'),
(43, 43, 'banner', 2, 1, '2025-09-20 16:47:13'),
(44, 44, 'banner', 3, 1, '2025-09-20 16:47:13'),
(45, 45, 'banner', 4, 1, '2025-09-20 16:47:13'),
(46, 46, 'banner', 5, 1, '2025-09-20 16:47:13'),
(47, 48, 'product', 9, 1, '2025-09-24 16:11:31'),
(48, 51, 'banner', 1, 1, '2025-10-04 07:38:09'),
(49, 52, 'banner', 2, 1, '2025-10-04 07:38:09'),
(50, 53, 'banner', 3, 1, '2025-10-04 07:38:09'),
(65, 68, 'product', 13, 1, '2025-11-03 09:29:05'),
(66, 69, 'product', 14, 1, '2025-11-03 09:36:18'),
(67, 71, 'product', 30, 1, '2025-11-03 12:30:10'),
(68, 72, 'product', 44, 1, '2025-11-03 12:57:29'),
(69, 73, 'product', 44, 0, '2025-11-03 12:57:29'),
(70, 74, 'product', 50, 1, '2025-11-03 13:15:52'),
(71, 75, 'product', 65, 1, '2025-11-03 15:33:20'),
(83, 87, 'product', 71, 1, '2025-11-04 11:44:57'),
(84, 88, 'product', 71, 0, '2025-11-04 11:44:57'),
(85, 89, 'product', 71, 0, '2025-11-04 11:44:57'),
(94, 98, 'product', 79, 1, '2025-11-08 04:24:59'),
(95, 99, 'product', 80, 1, '2025-11-09 05:00:06'),
(96, 100, 'product', 80, 0, '2025-11-09 05:00:06');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('order_status','collection','promotion','system') NOT NULL DEFAULT 'system',
  `ref_type` varchar(50) DEFAULT NULL COMMENT 'order, collection, product, etc',
  `ref_id` int(11) DEFAULT NULL COMMENT 'id của entity được reference',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `type`, `ref_type`, `ref_id`, `is_read`, `read_at`, `created_at`, `updated_at`) VALUES
(1, 31, 'Đơn hàng #48 đã được giao', 'Đơn hàng của bạn đã được giao thành công. Vui lòng kiểm tra hàng.', 'order_status', 'order', 48, 1, '2025-11-15 20:41:44', '2025-11-15 20:21:53', '2025-11-15 20:41:44'),
(4, 31, 'Đơn hàng #50 đang giao hàng', 'Đơn hàng của bạn đang được giao bởi đơn vị vận chuyển.', 'order_status', 'order', 50, 1, '2025-11-15 20:40:24', '2025-11-15 20:30:15', '2025-11-15 20:40:24'),
(6, 31, 'Khuyến mãi: Giảm 20%', 'Giảm 20% cho tất cả sản phẩm trong tuần này', 'promotion', NULL, NULL, 1, '2025-11-15 20:41:43', '2025-11-15 20:30:15', '2025-11-15 20:41:43'),
(7, 31, 'Bộ sưu tập mới: Diamond Star Series', 'Khám phá bộ sưu tập Diamond Star Series với các thiết kế độc đáo', 'collection', 'collection', 2, 1, '2025-11-15 20:57:10', '2025-11-15 20:56:53', '2025-11-15 20:57:10');

-- --------------------------------------------------------

--
-- Table structure for table `notification_actions`
--

CREATE TABLE `notification_actions` (
  `action_id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` enum('read','delete','archive') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `street` varchar(255) DEFAULT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Vietnam',
  `order_status` enum('pending','paid','shipped','delivered','cancelled') DEFAULT 'pending',
  `shipping_fee` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL CHECK (`total_amount` >= 0),
  `discount_code` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00 CHECK (`discount_amount` >= 0),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `full_name`, `email`, `phone`, `street`, `ward`, `province`, `country`, `order_status`, `shipping_fee`, `total_amount`, `discount_code`, `discount_amount`, `created_at`, `updated_at`, `notes`) VALUES
(1, NULL, 'Nhi Thiều', 'nhithieu03@gmail.com', '0767288382', 'C13/74, Ton Dan Street, Ward 13, District 4.', '', 'hcm', 'Vietnam', 'pending', 0.00, 61050000.00, NULL, 0.00, '2025-10-19 12:59:21', '2025-10-19 12:59:21', NULL),
(2, NULL, 'THIEU KHAI NHI', 'khainhi03@gmail.com', '0767288382', 'C13/74, Ton Dan Street, Ward 13, District 4.', '', 'hcm', 'Vietnam', 'pending', 0.00, 20350000.00, NULL, 0.00, '2025-10-19 13:06:57', '2025-10-19 13:06:57', NULL),
(3, NULL, 'THIEU KHAI NHI', 'khainhi03@gmail.com', '0767288382', 'C13/74, Ton Dan Street, Ward 13, District 4.', '', 'hcm', 'Vietnam', 'pending', 0.00, 20350000.00, NULL, 0.00, '2025-10-19 13:07:00', '2025-10-19 13:07:00', NULL),
(4, NULL, 'Khải Nhi Thiều', 'pixal53161@mardiek.com', '0767288382', 'C13/74, Ton Dan Street, Ward 13, District 4.', '', 'hcm', 'Vietnam', 'pending', 0.00, 20350000.00, NULL, 0.00, '2025-10-19 13:09:01', '2025-10-19 13:09:01', NULL),
(5, NULL, 'THIEU KHAI NHI', 'khainhi03@gmail.com', '0767288382', 'C13/74, Ton Dan Street, Ward 13, District 4.', '', 'hcm', 'Vietnam', 'pending', 0.00, 20350000.00, NULL, 0.00, '2025-10-19 13:15:37', '2025-10-19 13:15:37', NULL),
(6, NULL, 'miii', 'khainhi03@gmail.com', '0767288382', 'C13/74, Ton Dan Street, Ward 13, District 4.', '', 'hcm', 'Vietnam', 'pending', 0.00, 49500000.00, NULL, 0.00, '2025-10-19 13:20:25', '2025-10-19 13:20:25', NULL),
(7, NULL, 'dommm', 'nhithieu03@gmail.com', '0767288382', 'C13/74, Ton Dan Street, Ward 13, District 4.', '', 'hcm', 'Vietnam', 'pending', 0.00, 69850000.00, NULL, 0.00, '2025-10-19 13:23:15', '2025-10-19 13:23:15', NULL),
(8, NULL, 'THIEU KHAI NHI', 'pixal53161@mardiek.com', '0767288382', 'C13/74, Ton Dan Street, Ward 13, District 4.', '', 'hcm', 'Vietnam', 'pending', 0.00, 49500000.00, NULL, 0.00, '2025-10-19 14:03:43', '2025-10-19 14:03:43', NULL),
(9, NULL, 'Nhi Thiều', 'pixal53161@mardiek.com', '0767288382', 'C13/74, Ton Dan Street, Ward 13, District 4.', '', 'hcm', 'Vietnam', 'pending', 0.00, 99999999.99, NULL, 0.00, '2025-10-19 14:40:53', '2025-10-19 14:40:53', NULL),
(10, 11, 'Nhi Thiều', 'khainhi03@gmail.com', '0767288380', NULL, NULL, NULL, 'Vietnam', 'pending', 0.00, 49500000.00, NULL, 0.00, '2025-11-01 17:41:59', '2025-11-01 17:41:59', 'kjk'),
(11, NULL, 'THIEU KHAI NHI', 'nhithieu03@gmail.com', '0767288382', NULL, NULL, NULL, 'Vietnam', 'delivered', 0.00, 49500000.00, NULL, 0.00, '2025-11-01 17:45:53', '2025-11-05 04:52:44', ''),
(12, NULL, 'THIEU KHAI NHI', 'pixal53161@mardiek.com', '0767288382', NULL, NULL, NULL, 'Vietnam', 'delivered', 0.00, 49500000.00, NULL, 0.00, '2025-11-03 05:32:37', '2025-11-05 04:52:41', ''),
(13, NULL, 'THIEU KHAI NHI', 'nhithieu03@gmail.com', '0767288382', NULL, NULL, NULL, 'Vietnam', 'delivered', 0.00, 49500000.00, NULL, 0.00, '2025-11-03 05:36:18', '2025-11-05 04:52:38', ''),
(14, NULL, 'THIEU KHAI NHI', 'khainhi03@gmail.com', '0767288382', 'C13/74, Ton Dan', '25966', '79', 'Vietnam', 'delivered', 0.00, 49500000.00, NULL, 0.00, '2025-11-03 05:49:12', '2025-11-05 04:51:49', ''),
(15, NULL, 'khai nhi', 'nhithieu03@gmail.com', '0767288388', NULL, NULL, NULL, 'Vietnam', 'delivered', 0.00, 49500000.00, NULL, 0.00, '2025-11-04 02:58:55', '2025-11-05 04:51:02', ''),
(16, NULL, 'khai nhi', 'nhithieu03@gmail.com', '0767288388', NULL, NULL, NULL, 'Vietnam', 'delivered', 0.00, 49500000.00, NULL, 0.00, '2025-11-04 03:00:02', '2025-11-05 04:50:58', ''),
(17, NULL, 'THIEU KHAI NHI', 'Meta.D289574@rws.com', '0767288382', NULL, NULL, NULL, 'Vietnam', 'delivered', 0.00, 49500000.00, NULL, 0.00, '2025-11-04 03:40:02', '2025-11-05 04:50:52', ''),
(18, 11, 'Nhi Thiều', 'khainhi03@gmail.com', '0767288389', 'C13/74, Ton Dan Street', '26590', '79', 'Vietnam', 'delivered', 0.00, 49500000.00, NULL, 0.00, '2025-11-04 16:29:54', '2025-11-05 04:50:47', ''),
(19, NULL, 'khai nhi', 'nhithieu03@gmail.com', '0767288388', NULL, NULL, NULL, 'Vietnam', 'pending', 30000.00, 162000.00, NULL, 0.00, '2025-11-05 04:54:10', '2025-11-05 04:54:10', ''),
(20, NULL, 'khai nhi', 'nhithieu03@gmail.com', '0767288388', NULL, NULL, NULL, 'Vietnam', 'delivered', 0.00, 20350000.00, NULL, 0.00, '2025-11-05 04:54:52', '2025-11-05 04:55:04', ''),
(21, NULL, 'THIEU KHAI NHI', 'nhithieu03@gmail.com', '0767288382', NULL, NULL, NULL, 'Vietnam', 'delivered', 0.00, 20350000.00, NULL, 0.00, '2025-11-06 06:17:37', '2025-11-06 06:21:13', ''),
(22, NULL, 'THIEU KHAI NHI', 'Meta.D289574@rws.com', '0767288382', NULL, NULL, NULL, 'Vietnam', 'pending', 0.00, 99000000.00, NULL, 0.00, '2025-11-06 06:23:07', '2025-11-06 06:23:07', ''),
(23, NULL, 'THIEU KHAI NHI', 'khainhi03@gmail.com', '0767288382', 'C13/74, Ton Dan Street, Ward 13, District 4.', 'Phường Hòa Lợi', 'Thành phố Hồ Chí Minh', 'Vietnam', 'pending', 0.00, 49500000.00, NULL, 0.00, '2025-11-06 06:25:24', '2025-11-06 06:25:24', ''),
(24, NULL, 'THIEU KHAI NHI', 'nhithieu03@gmail.com', '0767288382', NULL, NULL, NULL, 'Vietnam', 'pending', 0.00, 20350000.00, NULL, 0.00, '2025-11-06 06:25:51', '2025-11-06 06:25:51', ''),
(25, 11, 'Nhi Thiều', 'khainhi03@gmail.com', '0767288389', NULL, NULL, NULL, 'Vietnam', 'pending', 0.00, 49500000.00, NULL, 0.00, '2025-11-06 13:43:36', '2025-11-06 13:43:36', ''),
(26, 11, 'Nhi Thiều', 'khainhi03@gmail.com', '0767288389', NULL, NULL, NULL, 'Vietnam', 'pending', 0.00, 49500000.00, NULL, 0.00, '2025-11-07 09:03:56', '2025-11-07 09:03:56', ''),
(27, NULL, 'THIEU KHAI NHI', 'pixal53161@mardiek.com', '0767288382', NULL, NULL, NULL, 'Vietnam', 'pending', 0.00, 49500000.00, NULL, 0.00, '2025-11-07 09:04:54', '2025-11-07 09:04:54', ''),
(28, NULL, 'THIEU KHAI NHI', 'nhithieu03@gmail.com', '0767288382', NULL, NULL, NULL, 'Vietnam', 'pending', 0.00, 49500000.00, NULL, 0.00, '2025-11-07 09:22:55', '2025-11-07 09:22:55', ''),
(29, NULL, 'THIEU KHAI NHI', 'khainhi03@gmail.com', '0767288382', 'C13/74, Ton Dan Street, Ward 13, District 4.', 'Phường Tân Bình', 'Thành phố Hồ Chí Minh', 'Vietnam', 'paid', 0.00, 49500000.00, NULL, 0.00, '2025-11-07 09:42:03', '2025-11-07 09:49:02', ''),
(30, NULL, 'THIEU KHAI NHI', 'khainhi03@gmail.com', '0767288382', 'C13/74, Ton Dan', 'Phường Thuận An', 'Thành phố Hồ Chí Minh', 'Vietnam', 'pending', 0.00, 69850000.00, NULL, 0.00, '2025-11-07 10:01:47', '2025-11-07 10:01:47', ''),
(31, NULL, 'Khải Nhi', 'khainhi03@gmail.com', '0767288382', 'C13/74', 'Phường Phú An', 'Thành phố Hồ Chí Minh', 'Vietnam', 'delivered', 0.00, 99000000.00, NULL, 0.00, '2025-11-07 10:10:25', '2025-11-07 10:15:08', ''),
(32, NULL, 'abc', 'khainhi03@gmail.com', '0767288382', '123', 'Phường Bình Dương', 'Thành phố Hồ Chí Minh', 'Vietnam', 'pending', 0.00, 49500000.00, NULL, 0.00, '2025-11-07 11:06:29', '2025-11-07 11:06:29', ''),
(33, NULL, 'Nhi Thiều', 'nhithieu03@gmail.com', '0767288382', NULL, NULL, NULL, 'Vietnam', 'delivered', 0.00, 20350000.00, NULL, 0.00, '2025-11-07 11:09:43', '2025-11-07 11:12:25', ''),
(34, NULL, 'abc', 'pixal53161@mardiek.com', '0767288382', NULL, NULL, NULL, 'Vietnam', 'pending', 0.00, 49500000.00, NULL, 0.00, '2025-11-07 11:26:26', '2025-11-07 11:26:26', ''),
(35, NULL, 'THIEU KHAI NHI', 'nhithieu03@gmail.com', '0767288382', '123/7 tôn đản', 'Xã Xuyên Mộc', 'Thành phố Hồ Chí Minh', 'Vietnam', 'pending', 0.00, 20350000.00, NULL, 0.00, '2025-11-07 11:29:47', '2025-11-07 11:29:47', ''),
(36, NULL, 'abc', 'khainhi03@gmail.com', '0767288382', NULL, NULL, NULL, 'Vietnam', 'pending', 0.00, 49500000.00, NULL, 0.00, '2025-11-07 11:38:03', '2025-11-07 11:38:03', ''),
(37, NULL, 'THIEU KHAI NHI', 'nhithieu03@gmail.com', '0767288382', 'C13/74, Ton Dan Street, Ward 13, District 4.', 'Phường Bình Dương', 'Thành phố Hồ Chí Minh', 'Vietnam', 'pending', 0.00, 49500000.00, NULL, 0.00, '2025-11-07 11:40:48', '2025-11-07 11:40:48', ''),
(38, NULL, 'abc', 'khainhi03@gmail.com', '0767288382', NULL, NULL, NULL, 'Vietnam', 'pending', 0.00, 49500000.00, NULL, 0.00, '2025-11-07 11:44:16', '2025-11-07 11:44:16', ''),
(39, NULL, 'abc', 'khainhi03@gmail.com', '0767288382', NULL, NULL, NULL, 'Vietnam', 'pending', 0.00, 49500000.00, NULL, 0.00, '2025-11-07 11:48:21', '2025-11-07 11:48:21', ''),
(40, NULL, 'THIEU KHAI NHI', 'nhithieu03@gmail.com', '0767288382', 'C13/74, Ton Dan Street, Ward 13, District 4.', 'Phường Bình Dương', 'Thành phố Hồ Chí Minh', 'Vietnam', 'delivered', 0.00, 49500000.00, NULL, 0.00, '2025-11-07 11:50:54', '2025-11-07 11:54:09', ''),
(41, 11, 'Nhi Thiều', 'khainhi03@gmail.com', '0767288389', NULL, NULL, NULL, 'Vietnam', 'delivered', 30000.00, 43200.00, NULL, 0.00, '2025-11-08 04:26:48', '2025-11-08 05:06:48', ''),
(42, 11, 'Nhi Thiều', 'khainhi03@gmail.com', '0767288389', '123 tôn đản', 'Phường Xóm Chiếu', 'Thành phố Hồ Chí Minh', 'Vietnam', 'pending', 30000.00, 43200.00, NULL, 0.00, '2025-11-08 06:32:49', '2025-11-08 06:32:49', ''),
(43, NULL, 'THIEU KHAI NHI', 'nhithieu03@gmail.com', '0767288382', '123 tôn đản', 'Phường Long Nguyên', 'Thành phố Hồ Chí Minh', 'Vietnam', 'pending', 0.00, 20350000.00, NULL, 0.00, '2025-11-09 03:30:36', '2025-11-09 03:30:36', ''),
(44, NULL, 'THIEU KHAI NHI', 'nhithieu03@gmail.com', '0767288382', '123 tôn đản', 'Xã Bàu Bàng', 'Thành phố Hồ Chí Minh', 'Vietnam', 'pending', 0.00, 20350000.00, NULL, 0.00, '2025-11-09 03:32:27', '2025-11-09 03:32:27', ''),
(45, NULL, 'THIEU KHAI NHI', 'nhithieu03@gmail.com', '0767288382', '123 tôn đản', 'Xã Thanh An', 'Thành phố Hồ Chí Minh', 'Vietnam', 'pending', 0.00, 20350000.00, NULL, 0.00, '2025-11-09 04:02:43', '2025-11-09 04:02:43', ''),
(46, NULL, 'abc', 'pixal53161@mardiek.com', '0767288382', NULL, NULL, NULL, 'Vietnam', 'pending', 0.00, 20350000.00, NULL, 0.00, '2025-11-09 05:50:06', '2025-11-09 05:50:06', ''),
(47, 31, 'THIEU KHAI NHI', 'nhithieu03@gmail.com', '0767288382', '123 tôn đản', 'Xã Trừ Văn Thố', 'Thành phố Hồ Chí Minh', 'Vietnam', 'delivered', 0.00, 20350000.00, NULL, 0.00, '2025-11-09 07:35:03', '2025-11-13 14:28:37', ''),
(48, 31, 'THIEU KHAI NHI', 'nhithieu03@gmail.com', '0767288382', NULL, NULL, NULL, 'Vietnam', 'delivered', 0.00, 2200000.00, NULL, 0.00, '2025-11-13 14:27:52', '2025-11-13 14:28:46', '');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `unit_price_snapshot` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` > 0),
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `variant_id`, `unit_price_snapshot`, `quantity`, `total_price`) VALUES
(1, 5, 2, NULL, 18500000.00, 1, 0.00),
(2, 6, 1, NULL, 45000000.00, 1, 45000000.00),
(3, 7, 2, NULL, 18500000.00, 1, 18500000.00),
(4, 7, 1, NULL, 45000000.00, 1, 45000000.00),
(5, 8, 1, NULL, 45000000.00, 1, 45000000.00),
(6, 9, 1, NULL, 45000000.00, 4, 99999999.99),
(7, 10, 1, NULL, 45000000.00, 1, 45000000.00),
(8, 11, 1, NULL, 45000000.00, 1, 45000000.00),
(9, 12, 1, NULL, 45000000.00, 1, 45000000.00),
(10, 13, 1, NULL, 45000000.00, 1, 45000000.00),
(11, 14, 1, NULL, 45000000.00, 1, 45000000.00),
(12, 15, 1, NULL, 45000000.00, 1, 45000000.00),
(13, 16, 1, NULL, 45000000.00, 1, 45000000.00),
(14, 17, 1, 3, 45000000.00, 1, 45000000.00),
(15, 18, 1, 1, 45000000.00, 1, 45000000.00),
(16, 19, 71, 36, 120000.00, 1, 120000.00),
(17, 20, 2, 4, 18500000.00, 1, 18500000.00),
(18, 21, 2, 4, 18500000.00, 1, 18500000.00),
(19, 22, 1, 1, 45000000.00, 2, 90000000.00),
(20, 23, 1, 1, 45000000.00, 1, 45000000.00),
(21, 24, 2, 4, 18500000.00, 1, 18500000.00),
(22, 25, 1, 1, 45000000.00, 1, 45000000.00),
(23, 26, 1, 1, 45000000.00, 1, 45000000.00),
(24, 27, 1, 1, 45000000.00, 1, 45000000.00),
(25, 28, 1, 1, 45000000.00, 1, 45000000.00),
(26, 29, 1, 3, 45000000.00, 1, 45000000.00),
(27, 30, 1, 3, 45000000.00, 1, 45000000.00),
(28, 30, 2, 4, 18500000.00, 1, 18500000.00),
(29, 31, 1, 3, 45000000.00, 2, 90000000.00),
(30, 32, 1, 2, 45000000.00, 1, 45000000.00),
(31, 33, 2, 4, 18500000.00, 1, 18500000.00),
(32, 34, 1, 2, 45000000.00, 1, 45000000.00),
(33, 35, 2, 4, 18500000.00, 1, 18500000.00),
(34, 36, 1, 2, 45000000.00, 1, 45000000.00),
(35, 37, 1, 2, 45000000.00, 1, 45000000.00),
(36, 38, 1, 2, 45000000.00, 1, 45000000.00),
(37, 39, 1, 2, 45000000.00, 1, 45000000.00),
(38, 40, 1, 2, 45000000.00, 1, 45000000.00),
(39, 41, 79, 47, 12000.00, 1, 12000.00),
(40, 42, 79, 47, 12000.00, 1, 12000.00),
(41, 43, 2, 4, 18500000.00, 1, 18500000.00),
(42, 44, 2, 4, 18500000.00, 1, 18500000.00),
(43, 45, 2, 4, 18500000.00, 1, 18500000.00),
(44, 46, 2, 4, 18500000.00, 1, 18500000.00),
(45, 47, 2, 4, 18500000.00, 1, 18500000.00),
(46, 48, 80, 49, 2000000.00, 1, 2000000.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` enum('BANK_TRANSFER_HOME','CASH_STORE') NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `amount` decimal(10,2) NOT NULL CHECK (`amount` > 0),
  `transaction_code` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `payment_method`, `payment_status`, `amount`, `transaction_code`, `transaction_id`, `paid_at`, `created_at`) VALUES
(1, 10, 'CASH_STORE', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-01 17:41:59'),
(2, 11, 'CASH_STORE', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-01 17:45:53'),
(3, 12, 'CASH_STORE', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-03 05:32:37'),
(4, 13, 'CASH_STORE', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-03 05:36:18'),
(5, 14, 'BANK_TRANSFER_HOME', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-03 05:49:12'),
(6, 15, 'CASH_STORE', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-04 02:58:55'),
(7, 16, 'CASH_STORE', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-04 03:00:02'),
(8, 17, 'CASH_STORE', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-04 03:40:03'),
(9, 18, 'BANK_TRANSFER_HOME', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-04 16:29:54'),
(10, 19, 'CASH_STORE', 'pending', 162000.00, NULL, NULL, NULL, '2025-11-05 04:54:10'),
(11, 20, 'CASH_STORE', 'pending', 20350000.00, NULL, NULL, NULL, '2025-11-05 04:54:52'),
(12, 21, 'CASH_STORE', 'pending', 20350000.00, NULL, NULL, NULL, '2025-11-06 06:17:37'),
(13, 22, 'CASH_STORE', 'pending', 99000000.00, NULL, NULL, NULL, '2025-11-06 06:23:07'),
(14, 23, 'BANK_TRANSFER_HOME', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-06 06:25:25'),
(15, 24, 'CASH_STORE', 'pending', 20350000.00, NULL, NULL, NULL, '2025-11-06 06:25:51'),
(16, 25, 'CASH_STORE', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-06 13:43:36'),
(17, 26, 'CASH_STORE', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-07 09:03:56'),
(18, 27, 'CASH_STORE', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-07 09:04:54'),
(19, 28, 'CASH_STORE', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-07 09:22:55'),
(20, 29, 'BANK_TRANSFER_HOME', 'completed', 49500000.00, NULL, NULL, '2025-11-07 09:49:09', '2025-11-07 09:42:03'),
(21, 30, 'BANK_TRANSFER_HOME', 'pending', 69850000.00, NULL, NULL, NULL, '2025-11-07 10:01:47'),
(22, 31, 'BANK_TRANSFER_HOME', 'completed', 99000000.00, NULL, NULL, '2025-11-07 10:14:57', '2025-11-07 10:10:25'),
(23, 32, 'BANK_TRANSFER_HOME', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-07 11:06:29'),
(24, 33, 'CASH_STORE', 'completed', 20350000.00, NULL, NULL, '2025-11-07 11:15:46', '2025-11-07 11:09:43'),
(25, 34, 'CASH_STORE', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-07 11:26:26'),
(26, 35, 'BANK_TRANSFER_HOME', 'pending', 20350000.00, NULL, NULL, NULL, '2025-11-07 11:29:47'),
(27, 36, 'CASH_STORE', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-07 11:38:03'),
(28, 37, 'BANK_TRANSFER_HOME', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-07 11:40:48'),
(29, 38, 'CASH_STORE', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-07 11:44:16'),
(30, 39, 'CASH_STORE', 'pending', 49500000.00, NULL, NULL, NULL, '2025-11-07 11:48:21'),
(31, 40, 'BANK_TRANSFER_HOME', 'completed', 49500000.00, NULL, NULL, '2025-11-07 11:54:18', '2025-11-07 11:50:54'),
(32, 41, 'CASH_STORE', 'completed', 43200.00, NULL, NULL, '2025-11-08 05:06:53', '2025-11-08 04:26:48'),
(33, 42, 'BANK_TRANSFER_HOME', 'completed', 43200.00, NULL, NULL, '2025-11-08 06:33:14', '2025-11-08 06:32:49'),
(34, 43, 'BANK_TRANSFER_HOME', 'pending', 20350000.00, NULL, NULL, NULL, '2025-11-09 03:30:36'),
(35, 44, 'BANK_TRANSFER_HOME', 'completed', 20350000.00, NULL, NULL, '2025-11-09 03:32:59', '2025-11-09 03:32:27'),
(36, 45, 'BANK_TRANSFER_HOME', 'completed', 20350000.00, NULL, NULL, '2025-11-09 04:03:09', '2025-11-09 04:02:43'),
(37, 46, 'CASH_STORE', 'pending', 20350000.00, NULL, NULL, NULL, '2025-11-09 05:50:06'),
(38, 47, 'BANK_TRANSFER_HOME', 'completed', 20350000.00, NULL, NULL, '2025-11-09 07:35:43', '2025-11-09 07:35:03'),
(39, 48, 'CASH_STORE', 'completed', 2200000.00, NULL, NULL, '2025-11-13 14:40:26', '2025-11-13 14:27:53');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `material` enum('gold','silver','diamond','pearl') DEFAULT 'gold',
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `collection_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `name`, `material`, `description`, `base_price`, `sku`, `slug`, `collection_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Nhẫn Kim Cương Vàng Trắng 18K', 'diamond', 'Nhẫn kim cương vàng trắng 18K cao cấp với thiết kế tinh tế và sang trọng. Kim cương chất lượng cao được chế tác thủ công tỉ mỉ, tạo nên món trang sức hoàn hảo cho những dịp đặc biệt.', 45000000.00, 'RING-DIAMOND-001', 'ring-diamond-001', 1, 1, '2025-09-20 16:47:13', '2025-10-03 06:57:33'),
(2, 'Dây Chuyền Ngọc Trai Akoya', 'pearl', 'Dây chuyền ngọc trai Akoya tự nhiên với chất lượng cao nhất. Mỗi viên ngọc trai được lựa chọn kỹ lưỡng để tạo nên vẻ đẹp tinh tế và sang trọng.', 18500000.00, 'NECKLACE-PEARL-001', 'necklace-pearl-001', 3, 1, '2025-09-20 16:47:13', '2025-10-03 06:57:33'),
(3, 'Bông Tai Kim Cương Thiên Nhiên', 'diamond', 'Bông tai kim cương thiên nhiên với thiết kế cổ điển nhưng hiện đại. Kim cương được cắt tỉa hoàn hảo để tối đa hóa độ lấp lánh.', 25000000.00, 'EARRING-DIAMOND-001', 'earring-diamond-001', 2, 1, '2025-09-20 16:47:13', '2025-10-03 06:57:33'),
(4, 'Vòng Tay Bạc 925 Charm', 'silver', 'Vòng tay bạc 925 với các charm thiết kế độc đáo. Chất liệu bạc cao cấp, bền đẹp theo thời gian.', 2800000.00, 'BRACELET-SILVER-001', 'bracelet-silver-001', NULL, 1, '2025-09-20 16:47:13', '2025-10-03 06:57:33'),
(5, 'Đồng Hồ Diamond Luxury Swiss', 'diamond', 'Đồng hồ Thụy Sĩ cao cấp với kim cương đính kèm. Thiết kế sang trọng, phù hợp cho những dịp đặc biệt.', 45000000.00, 'WATCH-DIAMOND-001', 'watch-diamond-001', 2, 1, '2025-09-20 16:47:13', '2025-10-03 06:57:33'),
(6, 'Nhẫn Cưới Vàng Trắng 18K', 'gold', 'Nhẫn cưới vàng trắng 18K với thiết kế đơn giản nhưng tinh tế. Hoàn hảo cho cặp đôi trong ngày trọng đại.', 12000000.00, 'RING-WEDDING-001', 'ring-wedding-001', 1, 1, '2025-09-20 16:47:13', '2025-09-20 16:47:13'),
(7, 'Dây Chuyền Vàng 18K Trái Tim', 'gold', 'Dây chuyền vàng 18K với charm hình trái tim. Thiết kế lãng mạn, phù hợp làm quà tặng ý nghĩa.', 7800000.00, 'NECKLACE-HEART-001', 'necklace-heart-001', 1, 1, '2025-09-20 16:47:13', '2025-09-20 16:47:13'),
(9, 'Dây chuyền', 'silver', 'Dây chuyền bạc', 2000000.00, 'SILVER', 'Day-chuyen', 3, 1, '2025-09-24 15:51:23', '2025-10-27 13:28:40'),
(71, 'TESTSPN', 'gold', '', 120000.00, 'T', 'testspn', 2, 0, '2025-11-04 11:44:57', '2025-11-07 09:53:41'),
(79, 'test', 'gold', '', 12000.00, 'THIEU-KHAI-NHI', 'test', 1, 0, '2025-11-08 04:24:59', '2025-11-09 04:33:32'),
(80, 'Nhẫn vàng', 'gold', '18k', 2000000.00, 'N', 'nhan-vang', 1, 1, '2025-11-09 05:00:06', '2025-11-09 05:00:06');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`product_id`, `category_id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5),
(6, 1),
(7, 2),
(79, 1),
(80, 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `review_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `title` varchar(200) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`review_id`, `product_id`, `user_id`, `rating`, `title`, `comment`, `status`, `created_at`) VALUES
(49, 1, 1, 5, 'Nhan rat dep, kim cuong lap lanh', 'Nhan rat dep, kim cuong lap lanh, chat luong tuyet voi. Giao hang nhanh, dong goi can than.', 'approved', '2025-10-01 08:15:54'),
(50, 1, 2, 4, 'Thiet ke sang trong', 'Thiet ke sang trong, phu hop voi nhieu trang phuc. Rat hai long voi su lua chon nay.', 'approved', '2025-09-28 08:15:54'),
(51, 1, 3, 5, 'Tuyet voi!', 'San pham vuot mong doi, chat luong tuyet voi!', 'approved', '2025-09-26 08:15:54'),
(52, 1, 4, 3, 'Tot nhung hoi dat', 'San pham tot nhung hoi dat so voi chat luong. Minh se can nhac mua lan sau.', 'approved', '2025-09-23 08:15:54'),
(53, 2, 4, 4, 'Day chuyen rat dep', 'Day chuyen rat dep va tinh te, phu hop cho nhieu dip. Chat luong tot.', 'approved', '2025-10-02 08:15:54'),
(54, 2, 1, 5, 'Qua dep luon!', 'Minh rat hai long voi day chuyen nay, deo len rat dep va noi bat.', 'approved', '2025-09-30 08:15:54'),
(56, 2, 3, 2, 'Khong hai long', 'San pham khong nhu hinh anh mo ta tren web.', 'approved', '2025-09-21 08:15:54'),
(57, 3, 2, 5, 'Bong tai tuyet dep', 'Bong tai thiet ke rat dep, phu hop voi nhieu kieu toc khac nhau.', 'approved', '2025-09-29 08:15:54'),
(58, 3, 3, 4, 'Chat luong tuyet voi', 'Chat luong tuyet voi, san pham rat dep va tinh te. Rat hai long.', 'approved', '2025-09-25 08:15:54'),
(59, 4, 4, 5, 'Vong tay rat dep va ung y!', 'Vong tay rat dep va ung y, chat luong tuyet voi. Kim cuong dep.', 'approved', '2025-10-02 08:15:54'),
(61, 5, 1, 5, 'Cuc ky hai long voi san pham', 'Cuc ky hai long voi san pham nay, chat luong sang trong. Minh rat khuyen khich mua.', 'approved', '2025-10-01 08:15:54'),
(62, 5, 2, 4, 'Hang chat luong tot', 'Chat luong tuyet voi, thiet ke dep va thoai mai khi deo. Rat hai long.', 'approved', '2025-09-28 08:15:54'),
(63, 3, 1, 4, 'Dep nhung hoi nho', 'Bong tai thiet ke dep nhung kich thuoc nho hon toi tuong.', 'pending', '2025-10-03 08:15:54'),
(64, 4, 2, 5, 'Perfect!', 'Hoan hao! Qua hai long! Se gioi thieu cho ban be luon.', 'pending', '2025-10-03 08:15:54'),
(65, 2, 11, 4, 'đẹp', '123468', 'approved', '2025-10-27 06:53:17'),
(66, 9, 11, 5, '123', 'sdfghjkl', 'approved', '2025-11-01 17:17:37'),
(67, 1, 11, 5, 'đẹp', '12345', 'pending', '2025-11-06 13:44:08'),
(74, 80, 31, 4, '99', 'đẹp', 'pending', '2025-11-13 14:38:23');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `variant_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size` varchar(20) DEFAULT NULL,
  `color` varchar(100) DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`variant_id`, `product_id`, `size`, `color`, `weight`, `price`, `sku`, `stock`, `created_at`, `updated_at`) VALUES
(1, 1, '7', 'Trắng', NULL, 45000000.00, NULL, 0, '2025-10-13 08:18:09', '2025-11-07 09:22:55'),
(2, 1, '8', 'Vàng', NULL, 45000000.00, NULL, 0, '2025-10-13 08:18:09', '2025-11-07 11:50:54'),
(3, 1, '7', 'Vàng', NULL, 45000000.00, NULL, 0, '2025-10-13 08:18:09', '2025-11-07 10:10:25'),
(4, 2, 'One Size', 'Trắng', NULL, 18500000.00, NULL, 4, '2025-10-13 08:18:09', '2025-11-09 07:35:04'),
(5, 3, 'Small', 'Bạc', NULL, 25000000.00, NULL, 12, '2025-10-13 08:18:09', '2025-10-13 08:43:45'),
(6, 4, 'Medium', 'Bạc', NULL, 2800000.00, NULL, 20, '2025-10-13 08:18:09', '2025-10-13 11:25:04'),
(7, 5, 'One Size', 'Đen', NULL, 45000000.00, NULL, 3, '2025-10-13 08:18:09', '2025-10-13 11:25:12'),
(8, 4, 'Medium', 'Bạc', NULL, 2800000.00, NULL, 20, '2025-10-13 08:18:09', '2025-10-13 08:18:09'),
(36, 71, '41', 'Tím', NULL, 120000.00, NULL, 9, '2025-11-04 11:44:57', '2025-11-05 04:54:10'),
(37, 71, '37', 'Trắng', NULL, 120000.00, NULL, 10, '2025-11-04 11:44:57', '2025-11-04 11:44:57'),
(47, 79, '42', 'Đỏ', NULL, 12000.00, NULL, 7, '2025-11-08 04:24:59', '2025-11-08 06:32:49'),
(48, 80, 'S', 'Vàng', NULL, 2000000.00, NULL, 10, '2025-11-09 05:00:06', '2025-11-09 05:00:06'),
(49, 80, 'M', 'Vàng', NULL, 2500000.00, NULL, 9, '2025-11-09 05:00:06', '2025-11-13 14:27:53');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`, `created_at`) VALUES
(1, 'admin', 'Quản trị viên', '2025-09-14 17:28:44'),
(2, 'customer', 'Khách hàng', '2025-09-14 17:28:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `role_id` int(11) DEFAULT NULL,
  `verification_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL,
  `reset_expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `date_of_birth`, `gender`, `password_hash`, `name`, `phone`, `is_active`, `role_id`, `verification_token`, `reset_token`, `token_expires_at`, `reset_expires_at`, `created_at`, `updated_at`) VALUES
(1, 'minh.anh@gmail.com', NULL, NULL, '$2y$10$hash1', 'Minh Anh', '0901234567', 1, 1, NULL, NULL, NULL, NULL, '2025-10-03 08:15:54', '2025-10-03 08:15:54'),
(2, 'thu.huong@gmail.com', NULL, NULL, '$2y$10$hash2', 'Thu Hã░ãíng', '0901234568', 1, 1, NULL, NULL, NULL, NULL, '2025-10-03 08:15:54', '2025-10-03 08:15:54'),
(3, 'van.hung@gmail.com', NULL, NULL, '$2y$10$hash3', 'V─ân H├╣ng', '0901234569', 1, 1, NULL, NULL, NULL, NULL, '2025-10-03 08:15:54', '2025-10-03 08:15:54'),
(4, 'my.linh@gmail.com', NULL, NULL, '$2y$10$hash4', 'Mß╗╣ Linh', '0901234570', 1, 1, NULL, NULL, NULL, NULL, '2025-10-03 08:15:54', '2025-10-03 08:15:54'),
(11, 'khainhi03@gmail.com', '2003-04-16', 'female', '$2y$10$22Pc7XFcWAG7x/S4i0rLjeXqZo4mRkzOAgl6rPr5Q60nb/92CkZBC', 'Nhi Thiều', '0767288389', 1, 1, NULL, '19a459f5e35065ee10169b8e86924534e15ba8de86cbc7ec30d80a88f165da2b', NULL, '2025-11-04 03:30:26', '2025-10-11 10:02:38', '2025-11-07 09:22:08'),
(13, 'pixal53161@mardiek.com', NULL, NULL, '$2y$10$bMKIFJmmurOyAvp1/ZBdyeJsYk2EwWbLWFcpLr4J.yW3auQURXdqu', 'THIEU KHAI NHI', '0767288382', 0, NULL, '25e4f8d688ee442c43ced73ca798265ed379d3ab53b9bd00031dea16014b264f', NULL, '2025-11-06 14:41:39', NULL, '2025-11-05 07:41:39', '2025-11-05 07:41:39'),
(14, 'ngantran.11072005nd@gmail.com', NULL, NULL, '$2y$10$bGz58muLV5NV3IwjRN2vkepk9qd8fZRPFETuCGcv50NFCM0iUBtMq', 'ngan', '0123456789', 0, NULL, 'b26ee4e31402c78a69298c7701c5dc4085f420b1aac1246caeb6b91556189e0f', NULL, '2025-11-06 20:00:35', NULL, '2025-11-05 13:00:35', '2025-11-05 13:00:35'),
(16, 'test@example.com', NULL, NULL, '$2y$10$M320672HPCbtC8Dp/N4vouaj0iSEnI0C0p4F2K2GZlTGsOY.AW9Bu', 'Test User', '0123456789', 0, NULL, 'bcc08d3444e1fa9e226b85359769d1da90bc9789332a5cf2cc945fd8b0fd9a95', NULL, '2025-11-07 06:29:50', NULL, '2025-11-06 05:29:50', '2025-11-06 05:29:50'),
(17, 'riufckdiu@gmail.com', NULL, NULL, '$2y$10$IHi9yNbB0RXNoQpQVV8YRe.Vj4Ul3sitLy.D.Y.OaMi1fIVUXFbzm', 'THIEU KHAI NHI', '0767288382', 0, NULL, 'cca0b0e9fd1e498f55b07797de492068307b75905bdedb0b0a383f832abbae6c', NULL, '2025-11-07 12:34:22', NULL, '2025-11-06 05:34:22', '2025-11-06 05:34:22'),
(31, 'nhithieu03@gmail.com', NULL, NULL, '$2y$10$HDIOOKcG1YKscFLle1WsOuCAOYGYLOSEZmpHVculswxP39BOUav7u', 'THIEU KHAI NHI', '0767288382', 1, NULL, NULL, NULL, NULL, NULL, '2025-11-09 07:33:38', '2025-11-09 07:33:50');

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `user_address_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `street` text NOT NULL,
  `ward` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `country` varchar(100) DEFAULT 'Vietnam',
  `postal_code` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`user_address_id`, `user_id`, `full_name`, `phone`, `street`, `ward`, `province`, `country`, `postal_code`, `is_default`, `created_at`) VALUES
(1, 11, 'Nhi Thiều', '0767288382', 'C13/74, Ton Dan Street, Ward 13, District 4.', '', '', 'Vietnam', NULL, 0, '2025-11-01 05:52:40'),
(2, 11, 'Nhi Thiều', '', 'C13/74', '25990', '74', 'Vietnam', NULL, 0, '2025-11-01 06:03:11'),
(3, 11, 'Nhi Thiều', '', 'c13', '5449', '19', 'Vietnam', NULL, 0, '2025-11-01 06:30:53'),
(4, 11, 'Nhi Thiều', '0767288382', 'c13', '27259', '79', 'Vietnam', NULL, 0, '2025-11-01 06:42:42'),
(5, 11, 'Nhi Thiều', '0767288380', '123', '27259', '79', 'Vietnam', NULL, 0, '2025-11-01 07:45:41'),
(6, 11, NULL, NULL, 'c13/74', '27259', '79', 'Vietnam', NULL, 0, '2025-11-04 16:27:04'),
(7, 11, NULL, NULL, '1120', '27637', '79', 'Vietnam', NULL, 0, '2025-11-04 16:32:44'),
(8, 11, NULL, NULL, '100', 'Phường Chợ Quán', 'Thành phố Hồ Chí Minh', 'Vietnam', NULL, 0, '2025-11-04 16:42:31'),
(20, 11, NULL, NULL, '123', 'Phường Xóm Chiếu', 'Thành phố Hồ Chí Minh', 'Vietnam', NULL, 1, '2025-11-08 06:03:05');

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `wishlist_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT 'My Wishlist',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `wishlists`
--

INSERT INTO `wishlists` (`wishlist_id`, `user_id`, `name`, `created_at`) VALUES
(1, 11, 'Wishlist của tôi', '2025-10-27 08:19:56'),
(3, 1, 'Wishlist của tôi', '2025-11-04 10:49:49'),
(19, 31, 'Wishlist của tôi', '2025-11-09 07:34:04');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist_items`
--

CREATE TABLE `wishlist_items` (
  `wishlist_item_id` int(11) NOT NULL,
  `wishlist_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `wishlist_items`
--

INSERT INTO `wishlist_items` (`wishlist_item_id`, `wishlist_id`, `product_id`, `added_at`) VALUES
(36, 1, 1, '2025-11-08 03:29:48'),
(37, 1, 2, '2025-11-08 03:38:44'),
(39, 1, 9, '2025-11-08 03:39:35'),
(40, 1, 3, '2025-11-08 03:39:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `uk_carts_user` (`user_id`),
  ADD UNIQUE KEY `uk_carts_session` (`session_id`),
  ADD KEY `idx_carts_updated` (`updated_at`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD UNIQUE KEY `uk_cart_items` (`cart_id`,`product_id`,`variant_id`),
  ADD KEY `idx_cart_items_product` (`product_id`),
  ADD KEY `idx_cart_items_variant` (`variant_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `uk_categories_slug` (`slug`),
  ADD KEY `idx_categories_parent` (`parent_id`),
  ADD KEY `idx_categories_active` (`is_active`);

--
-- Indexes for table `collection`
--
ALTER TABLE `collection`
  ADD PRIMARY KEY (`collection_id`),
  ADD UNIQUE KEY `uk_collection_slug` (`slug`),
  ADD KEY `idx_collection_active` (`is_active`);

--
-- Indexes for table `discounts`
--
ALTER TABLE `discounts`
  ADD PRIMARY KEY (`discount_id`),
  ADD UNIQUE KEY `uk_discounts_code` (`code`),
  ADD KEY `idx_discounts_active` (`is_active`,`start_date`,`end_date`),
  ADD KEY `idx_discounts_applicable` (`applicable_to`,`target_id`);

--
-- Indexes for table `discount_usages`
--
ALTER TABLE `discount_usages`
  ADD PRIMARY KEY (`usage_id`),
  ADD UNIQUE KEY `uk_discount_usages_order` (`order_id`),
  ADD KEY `idx_discount_usages_discount` (`discount_id`),
  ADD KEY `idx_discount_usages_user` (`user_id`);

--
-- Indexes for table `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_images_type` (`file_type`);

--
-- Indexes for table `image_usages`
--
ALTER TABLE `image_usages`
  ADD PRIMARY KEY (`usage_id`),
  ADD KEY `idx_image_usages_image` (`image_id`),
  ADD KEY `idx_image_usages_ref` (`ref_type`,`ref_id`),
  ADD KEY `idx_image_usages_primary` (`ref_type`,`ref_id`,`is_primary`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `notification_actions`
--
ALTER TABLE `notification_actions`
  ADD PRIMARY KEY (`action_id`),
  ADD KEY `notification_id` (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_orders_user` (`user_id`),
  ADD KEY `idx_orders_status` (`order_status`),
  ADD KEY `idx_orders_date` (`created_at`),
  ADD KEY `idx_orders_discount` (`discount_code`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `idx_order_items_order` (`order_id`),
  ADD KEY `idx_order_items_product` (`product_id`),
  ADD KEY `idx_order_items_variant` (`variant_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `uk_payments_order` (`order_id`),
  ADD KEY `idx_payments_status` (`payment_status`),
  ADD KEY `idx_payments_method` (`payment_method`),
  ADD KEY `idx_payments_transaction` (`transaction_code`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `uk_products_slug` (`slug`),
  ADD UNIQUE KEY `uk_products_sku` (`sku`),
  ADD KEY `idx_products_collection` (`collection_id`),
  ADD KEY `idx_products_active` (`is_active`),
  ADD KEY `idx_products_material` (`material`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`product_id`,`category_id`),
  ADD KEY `idx_product_categories_category` (`category_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `idx_product_reviews_product` (`product_id`),
  ADD KEY `idx_product_reviews_user` (`user_id`),
  ADD KEY `idx_product_reviews_status` (`status`),
  ADD KEY `idx_product_reviews_rating` (`rating`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`variant_id`),
  ADD UNIQUE KEY `uk_product_variants_sku` (`sku`),
  ADD KEY `idx_product_variants_product` (`product_id`),
  ADD KEY `idx_product_variants_stock` (`stock`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `uk_roles_name` (`role_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uk_users_email` (`email`),
  ADD KEY `idx_users_role` (`role_id`),
  ADD KEY `idx_users_active` (`is_active`),
  ADD KEY `idx_reset_token` (`reset_token`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`user_address_id`),
  ADD KEY `idx_user_addresses_user` (`user_id`),
  ADD KEY `idx_user_addresses_default` (`user_id`,`is_default`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD UNIQUE KEY `uk_wishlists_user` (`user_id`);

--
-- Indexes for table `wishlist_items`
--
ALTER TABLE `wishlist_items`
  ADD PRIMARY KEY (`wishlist_item_id`),
  ADD UNIQUE KEY `uk_wishlist_items` (`wishlist_id`,`product_id`),
  ADD KEY `idx_wishlist_items_product` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `collection`
--
ALTER TABLE `collection`
  MODIFY `collection_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `discounts`
--
ALTER TABLE `discounts`
  MODIFY `discount_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discount_usages`
--
ALTER TABLE `discount_usages`
  MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `images`
--
ALTER TABLE `images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `image_usages`
--
ALTER TABLE `image_usages`
  MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notification_actions`
--
ALTER TABLE `notification_actions`
  MODIFY `action_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `variant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `user_address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `wishlist_items`
--
ALTER TABLE `wishlist_items`
  MODIFY `wishlist_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`cart_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_items_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`variant_id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `discount_usages`
--
ALTER TABLE `discount_usages`
  ADD CONSTRAINT `fk_discount_usages_discount` FOREIGN KEY (`discount_id`) REFERENCES `discounts` (`discount_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_discount_usages_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_discount_usages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `image_usages`
--
ALTER TABLE `image_usages`
  ADD CONSTRAINT `fk_image_usages_image` FOREIGN KEY (`image_id`) REFERENCES `images` (`image_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_actions`
--
ALTER TABLE `notification_actions`
  ADD CONSTRAINT `notification_actions_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notification_actions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `fk_order_items_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`variant_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_collection` FOREIGN KEY (`collection_id`) REFERENCES `collection` (`collection_id`) ON DELETE SET NULL;

--
-- Constraints for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `fk_product_categories_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_categories_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `fk_product_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_product_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE SET NULL;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `fk_user_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `fk_wishlists_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist_items`
--
ALTER TABLE `wishlist_items`
  ADD CONSTRAINT `fk_wishlist_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wishlist_items_wishlist` FOREIGN KEY (`wishlist_id`) REFERENCES `wishlists` (`wishlist_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

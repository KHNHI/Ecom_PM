<?php

const _HOST = 'localhost';
const _DB = 'db_ecom';
const _USER = 'root';
const _PASSWORD = '';
const _DRIVER = 'mysql';

// Base URL configuration
// For localhost/Ecom_website
define('BASE_URL', '/Ecom_website');
define('FULL_BASE_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL);

// Define root paths
define('ROOT', __DIR__ . '/../app/views/admin/');
define('ADMIN_PATH', __DIR__ . '/../app/views/admin/');
define('PUBLIC_PATH', __DIR__ . '/../public/');

// Email verification settings (moved to email.php)

// Include URL helper functions
require_once __DIR__ . '/../helpers/url_helper.php';
<?php
session_start();
echo "<h2>Session Debug</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Check Admin Login</h3>";
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    echo "✅ Admin is logged in<br>";
    echo "User ID: " . ($_SESSION['admin_user_id'] ?? 'N/A') . "<br>";
    echo "Email: " . ($_SESSION['admin_email'] ?? 'N/A') . "<br>";
    echo "Name: " . ($_SESSION['admin_name'] ?? 'N/A') . "<br>";
} else {
    echo "❌ Admin is NOT logged in<br>";
    echo "Please login at: <a href='/Ecom_PM/admin/login'>/Ecom_PM/admin/login</a>";
}
?>

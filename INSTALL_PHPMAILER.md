# 📧 Hướng Dẫn Cài Đặt PHPMailer

## Bước 1: Cài Đặt Composer (Nếu chưa có)

### Windows:
1. Tải Composer: https://getcomposer.org/download/
2. Chạy file `Composer-Setup.exe`
3. Cài đặt theo hướng dẫn

### Kiểm tra Composer đã cài:
```bash
composer --version
```

## Bước 2: Cài Đặt PHPMailer

Mở Terminal/CMD tại thư mục `C:\xampp\htdocs\Ecom_website\` và chạy:

```bash
composer install
```

Hoặc nếu đã có composer.json:

```bash
composer require phpmailer/phpmailer
```

## Bước 3: Kiểm Tra Cài Đặt

Sau khi chạy xong, bạn sẽ thấy:
- Thư mục `vendor/` được tạo
- File `vendor/autoload.php` tồn tại
- PHPMailer đã được cài đặt trong `vendor/phpmailer/`

## Bước 4: Test Email

Reload lại trang admin orders và thử cập nhật payment status.

---

## ⚡ NHANH: Chạy lệnh này ngay

```bash
cd C:\xampp\htdocs\Ecom_website
composer install
```

Xong! ✅



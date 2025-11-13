<?php
/**
 * ErrorHandler - Global Error and Exception Handler
 * Xử lý lỗi và exception toàn cục
 */
class ErrorHandler {
    
    /**
     * Register error handlers
     */
    public static function register() {
        // Set error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 0); // Don't display errors in production
        
        // Set custom error handler
        set_error_handler([self::class, 'handleError']);
        
        // Set custom exception handler
        set_exception_handler([self::class, 'handleException']);
        
        // Set shutdown handler for fatal errors
        register_shutdown_function([self::class, 'handleShutdown']);
    }
    
    /**
     * Handle PHP errors
     */
    public static function handleError($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $error = [
            'type' => 'Error',
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::logError($error);
        self::displayError($error);
        
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     */
    public static function handleException($exception) {
        $error = [
            'type' => 'Exception',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::logError($error);
        self::displayError($error);
    }
    
    /**
     * Handle fatal errors
     */
    public static function handleShutdown() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorData = [
                'type' => 'Fatal Error',
                'severity' => $error['type'],
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            self::logError($errorData);
            self::displayError($errorData);
        }
    }
    
    /**
     * Log error to file
     */
    private static function logError($error) {
        $logFile = __DIR__ . '/../logs/error.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logMessage = sprintf(
            "[%s] %s: %s in %s on line %d\n",
            $error['timestamp'],
            $error['type'],
            $error['message'],
            $error['file'],
            $error['line']
        );
        
        if (isset($error['trace'])) {
            $logMessage .= "Stack trace:\n" . $error['trace'] . "\n";
        }
        
        $logMessage .= str_repeat('-', 80) . "\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Display error to user
     */
    private static function displayError($error) {
        // Don't display errors if headers already sent
        if (headers_sent()) {
            return;
        }
        
        // Clear any previous output
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Set appropriate HTTP status code
        http_response_code(500);
        
        // Check if it's an admin request
        $isAdmin = strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== false;
        
        if ($isAdmin) {
            self::displayAdminError($error);
        } else {
            self::displayCustomerError($error);
        }
    }
    
    /**
     * Display error for admin
     */
    private static function displayAdminError($error) {
        ?>
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Lỗi - Trang Sức Admin</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">
                                <h4 class="mb-0">⚠️ Lỗi Hệ Thống</h4>
                            </div>
                            <div class="card-body">
                                <h5 class="text-danger"><?= htmlspecialchars($error['message']) ?></h5>
                                <p class="text-muted">
                                    <strong>File:</strong> <?= htmlspecialchars($error['file']) ?><br>
                                    <strong>Line:</strong> <?= $error['line'] ?><br>
                                    <strong>Time:</strong> <?= $error['timestamp'] ?>
                                </p>
                                
                                <?php if (isset($error['trace'])): ?>
                                <details class="mt-3">
                                    <summary class="btn btn-outline-secondary btn-sm">Xem Stack Trace</summary>
                                    <pre class="mt-2 p-3 bg-light border rounded"><code><?= htmlspecialchars($error['trace']) ?></code></pre>
                                </details>
                                <?php endif; ?>
                                
                                <div class="mt-4">
                                    <a href="javascript:history.back()" class="btn btn-secondary">Quay lại</a>
                                    <a href="/admin/" class="btn btn-primary">Về Dashboard</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    /**
     * Display error for customer
     */
    private static function displayCustomerError($error) {
        ?>
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Lỗi - Trang Sức</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h4 class="mb-0">⚠️ Có lỗi xảy ra</h4>
                            </div>
                            <div class="card-body text-center">
                                <h5 class="text-warning">Xin lỗi, có lỗi xảy ra với hệ thống</h5>
                                <p class="text-muted">Chúng tôi đang khắc phục sự cố này. Vui lòng thử lại sau.</p>
                                
                                <div class="mt-4">
                                    <a href="javascript:history.back()" class="btn btn-secondary">Quay lại</a>
                                    <a href="/" class="btn btn-primary">Về trang chủ</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
}

<?php
require_once __DIR__ . '/../../core/BaseController.php';

class LocationController extends BaseController {
    // API alternatives - fallback system
    private $apiUrls = [
        'primary' => 'https://provinces.open-api.vn/api/v2',
        'backup1' => 'https://vapi.vnappmob.com/api/province', // Vietnam API
        'backup2' => 'http://provinces.open-api.vn/api/v2'     // HTTP fallback
    ];
    public function getProvinces() {
        try {
            // Try multiple APIs with fallback
            $provinces = $this->callApiWithFallback('/p/');
            
            if ($provinces === null) {
                // Last resort: Return static data
                $provinces = $this->getStaticProvinces();
            }
            
            $this->jsonResponse(true, 'Success', $provinces);
        } catch (Exception $e) {
            error_log("Get Provinces Error: " . $e->getMessage());
            // Return static data as emergency fallback
            $this->jsonResponse(true, 'Success (Static)', $this->getStaticProvinces());
        }
    }
    public function getWards() {
        try {
            // 1. Lấy mã tỉnh từ JavaScript (thử cả 'province' và 'province_code')
            $provinceCode = $_GET['province'] ?? $_GET['province_code'] ?? ''; 
            
            if (empty($provinceCode) || !is_numeric($provinceCode)) {
                $this->jsonResponse(false, 'Thiếu hoặc sai mã tỉnh/thành phố');
                return;
            }

            // 2. Gọi API với fallback system
            $response = $this->callApiWithFallback("/p/{$provinceCode}?depth=2");

            if ($response === null) {
                // Fallback to static data
                error_log("Using static wards data for province: $provinceCode");
                $allWards = $this->getStaticWards($provinceCode);
                $this->jsonResponse(true, 'Success (Static)', $allWards);
                return;
            }

            // 3. "Làm phẳng" dữ liệu
            $allWards = [];

            // --- LOGIC XỬ LÝ MỚI ---
            
            // TRƯỜNG HỢP 1: API trả về Tỉnh (có key 'districts')
            if (isset($response['districts']) && is_array($response['districts'])) {
                foreach ($response['districts'] as $district) {
                    if (isset($district['wards']) && is_array($district['wards'])) {
                        
                        // Thêm 'district_name' vào cho JS
                        $wardsWithDistrict = array_map(function($ward) use ($district) {
                            $ward['district_name'] = $district['name']; 
                            return $ward;
                        }, $district['wards']);

                        $allWards = array_merge($allWards, $wardsWithDistrict);
                    }
                }
            } 
            // TRƯỜNG HỢP 2: API trả về Thành phố TW (có key 'wards' ở cấp 1)
            // (Đây chính là trường hợp trong ảnh của bạn)
            elseif (isset($response['wards']) && is_array($response['wards'])) {
                // Không có district_name, JS của bạn sẽ tự động chỉ hiển thị tên phường
                $allWards = $response['wards'];
            }
            // TRƯỜNG HỢP 3: API trả về cấu trúc không mong muốn
            else {
                throw new Exception("Cấu trúc API trả về không hợp lệ.");
            }
            // --- KẾT THÚC LOGIC MỚI ---

            // 7. Trả về danh sách PHẲNG (đã bỏ district) cho JavaScript
            $this->jsonResponse(true, 'Success', $allWards);

        } catch (Exception $e) {
            // Ghi lại lỗi chi tiết để bạn kiểm tra nếu cần
            error_log("Get Wards Error: " . $e->getMessage() . " - URL: " . ($urlToCall ?? ''));
            $this->jsonResponse(false, 'Có lỗi xảy ra khi lấy dữ liệu phường/xã');
        }
    }
    private function callApi($url) {
        try {
            // Phương pháp 1: Sử dụng cURL (ưu tiên)
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MyWebApp/1.0)');
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($response !== false && $httpCode === 200) {
                    return json_decode($response, true);
                }
                
                error_log("cURL failed with HTTP code: $httpCode");
            }
            
            // Phương pháp 2: Fallback với file_get_contents  
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0 (compatible; MyWebApp/1.0)'
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            
            if ($response !== false) {
                return json_decode($response, true);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("API Call Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Call API với fallback system - thử nhiều URL
     */
    private function callApiWithFallback($endpoint) {
        foreach ($this->apiUrls as $name => $baseUrl) {
            $url = $baseUrl . $endpoint;
            error_log("Trying API: $name - $url");
            
            $result = $this->callApi($url);
            if ($result !== null) {
                error_log("API Success: $name");
                return $result;
            }
        }
        
        error_log("All APIs failed");
        return null;
    }

    /**
     * Static provinces data - Emergency fallback
     */
    private function getStaticProvinces() {
        return [
            ["name" => "Thành phố Hà Nội", "code" => 1, "division_type" => "thành phố trung ương"],
            ["name" => "Thành phố Hồ Chí Minh", "code" => 79, "division_type" => "thành phố trung ương"],
            ["name" => "Thành phố Đà Nẵng", "code" => 48, "division_type" => "thành phố trung ương"],
            ["name" => "Thành phố Hải Phòng", "code" => 31, "division_type" => "thành phố trung ương"],
            ["name" => "Thành phố Cần Thơ", "code" => 92, "division_type" => "thành phố trung ương"],
            ["name" => "Bắc Ninh", "code" => 24, "division_type" => "tỉnh"],
            ["name" => "Quảng Ninh", "code" => 22, "division_type" => "tỉnh"],
            ["name" => "Thanh Hóa", "code" => 38, "division_type" => "tỉnh"],
            ["name" => "Nghệ An", "code" => 40, "division_type" => "tỉnh"],
            ["name" => "Khánh Hòa", "code" => 56, "division_type" => "tỉnh"]
        ];
    }

    /**
     * Static wards data - Emergency fallback  
     */
    private function getStaticWards($provinceCode) {
        // Simplified ward data for major cities
        $staticWards = [
            1 => [ // Hà Nội
                ["name" => "Phường Phúc Xá", "code" => 1, "district_name" => "Ba Đình"],
                ["name" => "Phường Trúc Bạch", "code" => 4, "district_name" => "Ba Đình"],
                ["name" => "Phường Cửa Nam", "code" => 7, "district_name" => "Hoàn Kiếm"],
                ["name" => "Phường Hàng Bạc", "code" => 10, "district_name" => "Hoàn Kiếm"]
            ],
            79 => [ // TP.HCM  
                ["name" => "Phường Bến Nghé", "code" => 26734, "district_name" => "Quận 1"],
                ["name" => "Phường Bến Thành", "code" => 26737, "district_name" => "Quận 1"],
                ["name" => "Phường Nguyễn Thái Bình", "code" => 26740, "district_name" => "Quận 1"]
            ]
        ];
        
        return $staticWards[$provinceCode] ?? [
            ["name" => "Phường/Xã mặc định", "code" => 999, "district_name" => "Quận/Huyện"]
        ];
    }
}
?>
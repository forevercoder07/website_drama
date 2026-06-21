<?php
// ==================== SECURITY CLASS ====================

class Security {
    
    /**
     * CSRF token yaratish
     */
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * CSRF token tekshirish
     */
    public static function validateCSRFToken($token) {
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }
        
        // Token lifetime tekshirish (1 soat)
        if (isset($_SESSION['csrf_token_time']) && (time() - $_SESSION['csrf_token_time']) > 3600) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Password hash qilish
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, [
            'cost' => 12
        ]);
    }
    
    /**
     * Password tekshirish
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Xavfsiz password generatsiya (admin uchun avtomatik)
     */
    public static function generateSecurePassword($length = 16) {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        
        return $password;
    }
    
    /**
     * XSS himoyasi - output escaping
     */
    public static function escape($input) {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * SQL Injection himoyasi - input validation
     */
    public static function validateInput($input, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL);
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL);
            case 'integer':
                return filter_var($input, FILTER_VALIDATE_INT);
            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT);
            case 'string':
                return preg_match('/^[a-zA-Z0-9_\-\.]+$/', $input);
            default:
                return true;
        }
    }
    
    /**
     * API key generatsiya
     */
    public static function generateAPIKey() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * JWT token yaratish
     */
    public static function generateJWT($data, $expiresIn = 3600) {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        
        $payload = array_merge($data, [
            'iat' => time(),
            'exp' => time() + $expiresIn
        ]);
        
        $header_encoded = self::base64UrlEncode(json_encode($header));
        $payload_encoded = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', $header_encoded . '.' . $payload_encoded, JWT_SECRET, true);
        $signature_encoded = self::base64UrlEncode($signature);
        
        return $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;
    }
    
    /**
     * JWT token tekshirish
     */
    public static function validateJWT($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header_encoded, $payload_encoded, $signature_encoded) = $parts;
        
        $signature = hash_hmac('sha256', $header_encoded . '.' . $payload_encoded, JWT_SECRET, true);
        $signature_expected = self::base64UrlEncode($signature);
        
        if (!hash_equals($signature_expected, $signature_encoded)) {
            return false;
        }
        
        $payload = json_decode(self::base64UrlDecode($payload_encoded), true);
        
        if ($payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 4 - (strlen($data) % 4)));
    }
    
    /**
     * Content Security Policy header
     */
    public static function setSecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
    
    /**
     * Rate limiting check
     */
    public static function checkRateLimit($identifier, $endpoint, $limit = 10, $window = 3600) {
        // Redis yoki simple file-based
        $file = sys_get_temp_dir() . '/rate_limit_' . md5($identifier . $endpoint);
        
        if (file_exists($file)) {
            $data = unserialize(file_get_contents($file));
            $timeDiff = time() - $data['first_time'];
            
            if ($timeDiff < $window) {
                if ($data['count'] >= $limit) {
                    return false;
                }
                $data['count']++;
                file_put_contents($file, serialize($data));
            } else {
                // Window tugagan
                unlink($file);
            }
        } else {
            file_put_contents($file, serialize([
                'first_time' => time(),
                'count' => 1
            ]));
        }
        
        return true;
    }
    
    /**
     * Sanitize input
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
}
?>

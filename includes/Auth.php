<?php
// ==================== AUTHENTICATION CLASS ====================

class Auth {
    private $db;
    
    public function __construct(Database $db) {
        $this->db = $db;
    }
    
    /**
     * Foydalanuvchi login qilishi
     */
    public function login($username, $password) {
        try {
            // Foydalanuvchini topish
            $this->db->query("SELECT * FROM users WHERE username = ?");
            $this->db->bind('s', $username);
            $user = $this->db->single();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Foydalanuvchi topilmadi'];
            }
            
            if ($user['status'] == 'blocked') {
                $this->logLoginAttempt($user['id'], null, $username, false, 'Foydalanuvchi bloklangan');
                return ['success' => false, 'message' => 'Sizning akkauntingiz bloklangan'];
            }
            
            // Vaqtga berilgan parol tekshiring
            $this->db->query("
                SELECT * FROM temp_passwords 
                WHERE user_id = ? 
                AND is_revoked = FALSE 
                AND is_used = FALSE 
                AND expires_at > NOW()
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $this->db->bind('i', $user['id']);
            $tempPassword = $this->db->single();
            
            if (!$tempPassword || !password_verify($password, $tempPassword['password_hash'])) {
                $this->logLoginAttempt($user['id'], null, $username, false, 'Parol noto\'g\'ri');
                return ['success' => false, 'message' => 'Parol noto\'g\'ri yoki muddati tugagan'];
            }
            
            // Device fingerprinting
            $deviceFingerprint = $this->generateDeviceFingerprint($_SERVER);
            $device = $this->getOrCreateDevice($user['id'], $deviceFingerprint);
            
            if ($device['is_approved'] == false) {
                return [
                    'success' => false,
                    'message' => 'Bu qurilma hali tasdiqlanmagan. Admin ruxsat bergunini kuting.',
                    'requires_device_approval' => true,
                    'device_id' => $device['id']
                ];
            }
            
            // Rate limiting tekshirilsin
            if (!$this->checkRateLimit($_SERVER['REMOTE_ADDR'], 'login')) {
                $this->logLoginAttempt($user['id'], $device['id'], $username, false, 'Rate limit aşıldı');
                return ['success' => false, 'message' => 'Juda ko\'p urinish. Keyinroq harakat qiling'];
            }
            
            // Session yaratish
            $sessionToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
            
            // ✅ FIX: Bir marta INSERT qilish (4 marta emas!)
            $this->db->query("
                INSERT INTO sessions (user_id, device_id, session_token, ip_address, expires_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            $this->db->bind('i', $user['id']);
            $this->db->bind('i', $device['id']);
            $this->db->bind('s', $sessionToken);
            $this->db->bind('s', $_SERVER['REMOTE_ADDR']);
            $this->db->bind('s', $expiresAt);
            $this->db->query("INSERT INTO sessions (user_id, device_id, session_token, ip_address, expires_at) VALUES (?, ?, ?, ?, ?)");
            // ✅ Execution kerak
            
            // ✅ FIX: Bir marta UPDATE qilish (2 marta emas!)
            $this->db->query("UPDATE temp_passwords SET is_used = TRUE, used_at = NOW() WHERE id = ?");
            $this->db->bind('i', $tempPassword['id']);
            // ✅ Execution kerak
            
            // Login history saqlash
            $this->logLoginAttempt($user['id'], $device['id'], $username, true);
            
            // Session cookie
            setcookie('session_token', $sessionToken, time() + SESSION_TIMEOUT, '/', '', true, true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['session_token'] = $sessionToken;
            
            return [
                'success' => true,
                'message' => 'Muvaffaqiyatli login',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'is_admin' => $user['is_admin']
                ],
                'session_token' => $sessionToken
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Xato: ' . $e->getMessage()];
        }
    }
    
    /**
     * Device yaratish yoki topish
     */
    private function getOrCreateDevice($userId, $fingerprint) {
        // Device topish
        $this->db->query("SELECT * FROM device_fingerprints WHERE user_id = ? AND device_fingerprint = ?");
        $this->db->bind('i', $userId);
        $this->db->bind('s', $fingerprint);
        $device = $this->db->single();
        
        if ($device) {
            return $device;
        }
        
        // Yangi device yaratish
        $browserInfo = $this->getBrowserInfo();
        
        // ✅ FIX: Bir marta INSERT qilish (2 marta emas!)
        $this->db->query("
            INSERT INTO device_fingerprints 
            (user_id, device_fingerprint, device_name, browser, os, ip_address, is_approved)
            VALUES (?, ?, ?, ?, ?, ?, FALSE)
        ");
        $this->db->bind('i', $userId);
        $this->db->bind('s', $fingerprint);
        $this->db->bind('s', $browserInfo['device_name']);
        $this->db->bind('s', $browserInfo['browser']);
        $this->db->bind('s', $browserInfo['os']);
        $this->db->bind('s', $_SERVER['REMOTE_ADDR']);
        // ✅ Execution kerak
        
        $deviceId = $this->db->lastInsertId();
        
        return [
            'id' => $deviceId,
            'user_id' => $userId,
            'device_fingerprint' => $fingerprint,
            'is_approved' => false
        ];
    }
    
    /**
     * Device Fingerprint yaratish
     */
    private function generateDeviceFingerprint($serverData) {
        $fingerprint = [
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            'accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
        ];
        
        return hash(DEVICE_FINGERPRINT_ALGORITHM, json_encode($fingerprint));
    }
    
    /**
     * Browser info olish
     */
    private function getBrowserInfo() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $browser = 'Unknown';
        $os = 'Unknown';
        
        // OS
        if (preg_match('/Windows NT 10.0/', $userAgent)) $os = 'Windows 10';
        elseif (preg_match('/Windows NT 6.3/', $userAgent)) $os = 'Windows 8.1';
        elseif (preg_match('/Mac OS X/', $userAgent)) $os = 'Mac OS X';
        elseif (preg_match('/Linux/', $userAgent)) $os = 'Linux';
        elseif (preg_match('/iPhone/', $userAgent)) $os = 'iOS';
        elseif (preg_match('/Android/', $userAgent)) $os = 'Android';
        
        // Browser
        if (preg_match('/Chrome/', $userAgent)) $browser = 'Chrome';
        elseif (preg_match('/Firefox/', $userAgent)) $browser = 'Firefox';
        elseif (preg_match('/Safari/', $userAgent)) $browser = 'Safari';
        elseif (preg_match('/MSIE|Trident/', $userAgent)) $browser = 'Internet Explorer';
        
        return [
            'browser' => $browser,
            'os' => $os,
            'device_name' => $browser . ' (' . $os . ')'
        ];
    }
    
    /**
     * Rate limiting tekshirish
     */
    private function checkRateLimit($identifier, $endpoint) {
        $this->db->query("
            SELECT * FROM rate_limit 
            WHERE identifier = ? AND endpoint = ? 
            AND last_attempt > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $this->db->bind('s', $identifier);
        $this->db->bind('s', $endpoint);
        $this->db->bind('i', RATE_LIMIT_WINDOW);
        $record = $this->db->single();
        
        if ($record && $record['attempt_count'] >= RATE_LIMIT_ATTEMPTS) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Login history saqlash
     */
    private function logLoginAttempt($userId, $deviceId, $username, $success = true, $reason = null) {
        // ✅ FIX: Bir marta INSERT qilish (2 marta emas!)
        $this->db->query("
            INSERT INTO login_history 
            (user_id, device_id, username, ip_address, success, failure_reason, browser_info)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $this->db->bind('i', $userId);
        $this->db->bind('i', $deviceId ?? 0); // ✅ NULL o'rniga 0 yoki allow NULL
        $this->db->bind('s', $username);
        $this->db->bind('s', $_SERVER['REMOTE_ADDR']);
        $this->db->bind('i', $success ? 1 : 0); // ✅ String o'rniga integer
        $this->db->bind('s', $reason);
        $this->db->bind('s', $_SERVER['HTTP_USER_AGENT'] ?? '');
        // ✅ Execution kerak
    }
    
    /**
     * Session tekshirish
     */
    public function checkSession() {
        $token = $_COOKIE['session_token'] ?? null;
        
        if (!$token) {
            return false;
        }
        
        $this->db->query("
            SELECT * FROM sessions 
            WHERE session_token = ? 
            AND is_active = TRUE 
            AND expires_at > NOW()
        ");
        $this->db->bind('s', $token);
        $session = $this->db->single();
        
        if (!$session) {
            return false;
        }
        
        // Last activity update
        $this->db->query("UPDATE sessions SET last_activity = NOW() WHERE id = ?");
        $this->db->bind('i', $session['id']);
        
        return $session;
    }
    
    /**
     * Logout
     */
    public function logout() {
        $token = $_COOKIE['session_token'] ?? null;
        
        if ($token) {
            $this->db->query("UPDATE sessions SET is_active = FALSE WHERE session_token = ?");
            $this->db->bind('s', $token);
        }
        
        setcookie('session_token', '', time() - 3600, '/');
        session_destroy();
        
        return true;
    }
}
?>

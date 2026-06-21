<?php
// ==================== ADMIN CLASS ====================

class Admin {
    private $db;
    private $adminId;
    
    public function __construct(Database $db, $adminId) {
        $this->db = $db;
        $this->adminId = $adminId;
    }
    
    /**
     * Yangi foydalanuvchi yaratish
     */
    public function createUser($username, $email) {
        try {
            // Tekshirish
            $this->db->query("SELECT * FROM users WHERE username = ? OR email = ?");
            $this->db->bind('s', $username);
            $this->db->bind('s', $email);
            if ($this->db->single()) {
                return ['success' => false, 'message' => 'Foydalanuvchi yoki email allaqachon mavjud'];
            }
            
            // ✅ FIX: Bir marta INSERT qilish (2 marta emas!)
            $this->db->query("INSERT INTO users (username, email) VALUES (?, ?)");
            $this->db->bind('s', $username);
            $this->db->bind('s', $email);
            
            $userId = $this->db->lastInsertId();
            
            // Admin log
            $this->logAdminAction('create_user', 'user', $userId, [
                'username' => $username,
                'email' => $email
            ]);
            
            return [
                'success' => true,
                'message' => 'Foydalanuvchi yaratildi',
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Xato: ' . $e->getMessage()];
        }
    }
    
    /**
     * Vaqtga berilgan parol belgilash (asosiy funksiya!)
     */
    public function setTemporaryPassword($userId, $expiresInDays = 30, $customPassword = null) {
        try {
            // Foydalanuvchi mavjudligini tekshirish
            $this->db->query("SELECT * FROM users WHERE id = ?");
            $this->db->bind('i', $userId);
            $user = $this->db->single();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Foydalanuvchi topilmadi'];
            }
            
            // Password yaratish (avtomatik yoki custom)
            $password = $customPassword ?? Security::generateSecurePassword();
            $passwordHash = Security::hashPassword($password);
            
            // Expiry time
            $expiresAt = date('Y-m-d H:i:s', time() + ($expiresInDays * 86400));
            
            // Previous temp passwords deactivate qilish
            $this->db->query("
                UPDATE temp_passwords 
                SET is_revoked = TRUE, revoked_by = ?, revoked_at = NOW()
                WHERE user_id = ? AND is_revoked = FALSE
            ");
            $this->db->bind('i', $this->adminId);
            $this->db->bind('i', $userId);
            
            // Yangi temporary password yaratish
            $this->db->query("
                INSERT INTO temp_passwords 
                (user_id, password_hash, created_by, expires_at)
                VALUES (?, ?, ?, ?)
            ");
            $this->db->bind('i', $userId);
            $this->db->bind('s', $passwordHash);
            $this->db->bind('i', $this->adminId);
            $this->db->bind('s', $expiresAt);
            
            $passwordId = $this->db->lastInsertId();
            
            // Admin log
            $this->logAdminAction('set_temp_password', 'password', $passwordId, [
                'user_id' => $userId,
                'expires_at' => $expiresAt,
                'expires_in_days' => $expiresInDays
            ]);
            
            return [
                'success' => true,
                'message' => 'Vaqtga berilgan parol belgilandi',
                'password' => $password, // Bir marta ko'rsatish
                'expires_at' => $expiresAt,
                'password_id' => $passwordId
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Xato: ' . $e->getMessage()];
        }
    }
    
    /**
     * Vaqtga berilgan parolni bekor qilish (revoke)
     */
    public function revokeTemporaryPassword($passwordId) {
        try {
            $this->db->query("SELECT user_id FROM temp_passwords WHERE id = ?");
            $this->db->bind('i', $passwordId);
            $password = $this->db->single();
            
            if (!$password) {
                return ['success' => false, 'message' => 'Parol topilmadi'];
            }
            
            $this->db->query("
                UPDATE temp_passwords 
                SET is_revoked = TRUE, revoked_by = ?, revoked_at = NOW()
                WHERE id = ?
            ");
            $this->db->bind('i', $this->adminId);
            $this->db->bind('i', $passwordId);
            
            // Admin log
            $this->logAdminAction('revoke_password', 'password', $passwordId, [
                'user_id' => $password['user_id']
            ]);
            
            return ['success' => true, 'message' => 'Parol bekor qilindi'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Xato: ' . $e->getMessage()];
        }
    }
    
    /**
     * Qurilma ruxsati berish
     */
    public function approveDevice($deviceId) {
        try {
            $this->db->query("SELECT user_id FROM device_fingerprints WHERE id = ?");
            $this->db->bind('i', $deviceId);
            $device = $this->db->single();
            
            if (!$device) {
                return ['success' => false, 'message' => 'Qurilma topilmadi'];
            }
            
            $this->db->query("
                UPDATE device_fingerprints 
                SET is_approved = TRUE, approved_by = ?, approved_at = NOW()
                WHERE id = ?
            ");
            $this->db->bind('i', $this->adminId);
            $this->db->bind('i', $deviceId);
            
            // Admin log
            $this->logAdminAction('approve_device', 'device', $deviceId, [
                'user_id' => $device['user_id']
            ]);
            
            return ['success' => true, 'message' => 'Qurilma tasdiqlandı'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Xato: ' . $e->getMessage()];
        }
    }
    
    /**
     * Qurilma rad qilish
     */
    public function rejectDevice($deviceId) {
        try {
            $this->db->query("DELETE FROM device_fingerprints WHERE id = ?");
            $this->db->bind('i', $deviceId);
            
            // Admin log
            $this->logAdminAction('reject_device', 'device', $deviceId, []);
            
            return ['success' => true, 'message' => 'Qurilma rad qilindi'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Xato: ' . $e->getMessage()];
        }
    }
    
    /**
     * Foydalanuvchini bloklash
     */
    public function blockUser($userId) {
        try {
            $this->db->query("UPDATE users SET status = 'blocked' WHERE id = ?");
            $this->db->bind('i', $userId);
            
            // Active sessions bekor qilish
            $this->db->query("UPDATE sessions SET is_active = FALSE WHERE user_id = ?");
            $this->db->bind('i', $userId);
            
            // Admin log
            $this->logAdminAction('block_user', 'user', $userId, []);
            
            return ['success' => true, 'message' => 'Foydalanuvchi bloklandi'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Xato: ' . $e->getMessage()];
        }
    }
    
    /**
     * Foydalanuvchini qo'lga olish
     */
    public function unblockUser($userId) {
        try {
            $this->db->query("UPDATE users SET status = 'active' WHERE id = ?");
            $this->db->bind('i', $userId);
            
            // Admin log
            $this->logAdminAction('unblock_user', 'user', $userId, []);
            
            return ['success' => true, 'message' => 'Foydalanuvchi qo\'lga olindi'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Xato: ' . $e->getMessage()];
        }
    }
    
    /**
     * Foydalanuvchi kuzatish - Login history
     */
    public function getUserLoginHistory($userId, $limit = 50) {
        try {
            $this->db->query("
                SELECT * FROM login_history 
                WHERE user_id = ?
                ORDER BY login_time DESC
                LIMIT ?
            ");
            $this->db->bind('i', $userId);
            $this->db->bind('i', $limit);
            
            return $this->db->resultSet();
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Foydalanuvchining qurilmalari
     */
    public function getUserDevices($userId) {
        try {
            $this->db->query("
                SELECT * FROM device_fingerprints 
                WHERE user_id = ?
                ORDER BY last_seen DESC
            ");
            $this->db->bind('i', $userId);
            
            return $this->db->resultSet();
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Foydalanuvchining vaqtga berilgan parollari
     */
    public function getUserTemporaryPasswords($userId) {
        try {
            $this->db->query("
                SELECT id, created_at, expires_at, is_used, is_revoked, used_at
                FROM temp_passwords 
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");
            $this->db->bind('i', $userId);
            
            return $this->db->resultSet();
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Foydalanuvchini kuzatish - Dashboard
     */
    public function getUserMonitoring($userId) {
        $user = $this->getUserInfo($userId);
        $loginHistory = $this->getUserLoginHistory($userId, 10);
        $devices = $this->getUserDevices($userId);
        $passwords = $this->getUserTemporaryPasswords($userId);
        
        return [
            'user' => $user,
            'login_history' => $loginHistory,
            'devices' => $devices,
            'passwords' => $passwords,
            'total_logins' => count($loginHistory),
            'active_devices' => count(array_filter($devices, fn($d) => $d['is_active'])),
            'last_login' => $loginHistory[0]['login_time'] ?? null
        ];
    }
    
    /**
     * Foydalanuvchi info
     */
    public function getUserInfo($userId) {
        try {
            $this->db->query("SELECT * FROM users WHERE id = ?");
            $this->db->bind('i', $userId);
            
            return $this->db->single();
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Barcha foydalanuvchilar
     */
    public function getAllUsers($page = 1, $perPage = 20) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $this->db->query("
                SELECT * FROM users 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $this->db->bind('i', $perPage);
            $this->db->bind('i', $offset);
            
            $users = $this->db->resultSet();
            
            // Total count
            $this->db->query("SELECT COUNT(*) as count FROM users");
            $countResult = $this->db->single();
            
            return [
                'users' => $users,
                'total' => $countResult['count'],
                'page' => $page,
                'pages' => ceil($countResult['count'] / $perPage)
            ];
            
        } catch (Exception $e) {
            return ['users' => [], 'total' => 0];
        }
    }
    
    /**
     * Admin action log
     */
    private function logAdminAction($action, $targetType, $targetId, $changes) {
        try {
            $this->db->query("
                INSERT INTO admin_logs 
                (admin_id, action, target_type, target_id, changes, ip_address)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $this->db->bind('i', $this->adminId);
            $this->db->bind('s', $action);
            $this->db->bind('s', $targetType);
            $this->db->bind('i', $targetId);
            $this->db->bind('s', json_encode($changes));
            $this->db->bind('s', $_SERVER['REMOTE_ADDR'] ?? '');
        } catch (Exception $e) {
            // Logging failed, continue
        }
    }
}
?>

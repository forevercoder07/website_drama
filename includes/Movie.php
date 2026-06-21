<?php
// ==================== MOVIE CLASS ====================

class Movie {
    private $db;
    
    public function __construct(Database $db) {
        $this->db = $db;
    }
    
    // ==================== PUBLIC MOVIES ====================
    
    /**
     * Yangi public kino yaratish
     */
    public function createPublicMovie($data) {
        try {
            $this->db->query("
                INSERT INTO movies 
                (title, slug, description, thumbnail, poster, category_id, duration, rating, release_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $this->db->bind('s', $data['title']);
            $this->db->bind('s', $this->generateSlug($data['title']));
            $this->db->bind('s', $data['description']);
            $this->db->bind('s', $data['thumbnail']);
            $this->db->bind('s', $data['poster']);
            $this->db->bind('i', $data['category_id']);
            $this->db->bind('i', $data['duration']);
            $this->db->bind('d', $data['rating']);
            $this->db->bind('s', $data['release_date']);
            
            return [
                'success' => true,
                'message' => 'Kino yaratildi',
                'movie_id' => $this->db->lastInsertId()
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Xato: ' . $e->getMessage()];
        }
    }
    
    /**
     * Public kino yangilash
     */
    public function updatePublicMovie($movieId, $data) {
        try {
            $this->db->query("
                UPDATE movies 
                SET title = ?, description = ?, thumbnail = ?, poster = ?, 
                    category_id = ?, duration = ?, rating = ?, release_date = ?
                WHERE id = ?
            ");
            $this->db->bind('s', $data['title']);
            $this->db->bind('s', $data['description']);
            $this->db->bind('s', $data['thumbnail']);
            $this->db->bind('s', $data['poster']);
            $this->db->bind('i', $data['category_id']);
            $this->db->bind('i', $data['duration']);
            $this->db->bind('d', $data['rating']);
            $this->db->bind('s', $data['release_date']);
            $this->db->bind('i', $movieId);
            
            return ['success' => true, 'message' => 'Kino yangilandi'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Xato: ' . $e->getMessage()];
        }
    }
    
    /**
     * Public kino o'chirish
     */
    public function deletePublicMovie($movieId) {
        try {
            $this->db->query("DELETE FROM movies WHERE id = ?");
            $this->db->bind('i', $movieId);
            
            return ['success' => true, 'message' => 'Kino o\'chirildi'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Xato: ' . $e->getMessage()];
        }
    }
    
    /**
     * Public kino olish
     */
    public function getPublicMovie($movieId) {
        try {
            $this->db->query("
                SELECT m.*, c.name as category_name 
                FROM movies m
                LEFT JOIN categories c ON m.category_id = c.id
                WHERE m.id = ? AND m.is_published = TRUE
            ");
            $this->db->bind('i', $movieId);
            
            return $this->db->single();
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Barcha public kinolar
     */
    public function getAllPublicMovies($page = 1, $perPage = 20, $categoryId = null) {
        try {
            $offset = ($page - 1) * $perPage;
            
            if ($categoryId) {
                $this->db->query("
                    SELECT m.*, c.name as category_name 
                    FROM movies m
                    LEFT JOIN categories c ON m.category_id = c.id
                    WHERE m.is_published = TRUE AND m.category_id = ?
                    ORDER BY m.created_at DESC
                    LIMIT ? OFFSET ?
                ");
                $this->db->bind('i', $categoryId);
                $this->db->bind('i', $perPage);
                $this->db->bind('i', $offset);
            } else {
                $this->db->query("
                    SELECT m.*, c.name as category_name 
                    FROM movies m
                    LEFT JOIN categories c ON m.category_id = c.id
                    WHERE m.is_published = TRUE
                    ORDER BY m.created_at DESC
                    LIMIT ? OFFSET ?
                ");
                $this->db->bind('i', $perPage);
                $this->db->bind('i', $offset);
            }
            
            return $this->db->resultSet();
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Qidirish - ✅ FIX: LIKE injection himoyasi
     */
    public function searchMovies($query, $isPrivate = false) {
        try {
            // ✅ FIX: Properly escaped LIKE pattern
            $searchTerm = '%' . $this->escapeLike($query) . '%';
            $table = $isPrivate ? 'private_movies' : 'movies';
            
            $this->db->query("
                SELECT * FROM $table 
                WHERE (title LIKE ? OR description LIKE ?) AND is_published = TRUE
                ORDER BY created_at DESC
                LIMIT 50
            ");
            $this->db->bind('s', $searchTerm);
            $this->db->bind('s', $searchTerm);
            
            return $this->db->resultSet();
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * ✅ FIX: LIKE va ESCAPE metodlari
     */
    private function escapeLike($str) {
        return strtr($str, ['%' => '\%', '_' => '\_', '\\' => '\\\\']);
    }
    
    // ==================== EPISODES ====================
    
    /**
     * Episode qo'shish
     */
    public function addEpisode($movieId, $episodeNumber, $data, $isPrivate = false) {
        try {
            $table = $isPrivate ? 'private_episodes' : 'episodes';
            $movie = $isPrivate ? 'private_movies' : 'movies';
            
            // Movie mavjudligini tekshirish
            $this->db->query("SELECT id FROM $movie WHERE id = ?");
            $this->db->bind('i', $movieId);
            if (!$this->db->single()) {
                return ['success' => false, 'message' => 'Kino topilmadi'];
            }
            
            $this->db->query("
                INSERT INTO $table 
                (movie_id, episode_number, title, description, video_url, duration, release_date)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $this->db->bind('i', $movieId);
            $this->db->bind('i', $episodeNumber);
            $this->db->bind('s', $data['title']);
            $this->db->bind('s', $data['description']);
            $this->db->bind('s', $data['video_url']);
            $this->db->bind('i', $data['duration']);
            $this->db->bind('s', $data['release_date']);
            
            return [
                'success' => true,
                'message' => 'Episode qo\'shildi',
                'episode_id' => $this->db->lastInsertId()
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Xato: ' . $e->getMessage()];
        }
    }
    
    /**
     * Kinoning barcha epizodlari
     */
    public function getMovieEpisodes($movieId, $isPrivate = false) {
        try {
            $table = $isPrivate ? 'private_episodes' : 'episodes';
            
            $this->db->query("
                SELECT * FROM $table 
                WHERE movie_id = ?
                ORDER BY episode_number ASC
            ");
            $this->db->bind('i', $movieId);
            
            return $this->db->resultSet();
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    // ==================== PRIVATE MOVIES ====================
    
    /**
     * Yangi private kino yaratish
     */
    public function createPrivateMovie($data) {
        try {
            $this->db->query("
                INSERT INTO private_movies 
                (title, slug, description, thumbnail, poster, category_id, duration, rating, release_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $this->db->bind('s', $data['title']);
            $this->db->bind('s', $this->generateSlug($data['title']));
            $this->db->bind('s', $data['description']);
            $this->db->bind('s', $data['thumbnail']);
            $this->db->bind('s', $data['poster']);
            $this->db->bind('i', $data['category_id']);
            $this->db->bind('i', $data['duration']);
            $this->db->bind('d', $data['rating']);
            $this->db->bind('s', $data['release_date']);
            
            return [
                'success' => true,
                'message' => 'Yopiq kino yaratildi',
                'movie_id' => $this->db->lastInsertId()
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Xato: ' . $e->getMessage()];
        }
    }
    
    /**
     * Top movies
     */
    public function getTopMovies($limit = 10, $isPrivate = false) {
        try {
            $table = $isPrivate ? 'private_movies' : 'movies';
            
            $this->db->query("
                SELECT * FROM $table 
                WHERE is_published = TRUE
                ORDER BY rating DESC, views DESC
                LIMIT ?
            ");
            $this->db->bind('i', $limit);
            
            return $this->db->resultSet();
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Slug yaratish
     */
    private function generateSlug($title) {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
}
?>

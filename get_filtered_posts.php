<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

function extractHashtags($text) {
    preg_match_all('/#(\w+)/', $text, $matches);
    return $matches[1];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $filter = $_GET['filter'] ?? 'foryou';
    
    try {
        switch ($filter) {
            case 'trending':
                // Get trending hashtags from last 7 days
                $sql = "SELECT p.*, 
                       COUNT(DISTINCT l.id) as like_count,
                       COUNT(DISTINCT c.id) as comment_count,
                       u.username, u.profile_pic
                FROM post p
                LEFT JOIN likes l ON p.id = l.post_id
                LEFT JOIN comments c ON p.id = c.post_id
                JOIN users u ON p.user_id = u.id
                WHERE p.date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY p.id
                ORDER BY 
                    (SELECT COUNT(*) 
                     FROM post p2 
                     WHERE p2.content REGEXP CONCAT('#', 
                        SUBSTRING_INDEX(
                            SUBSTRING_INDEX(p.content, '#', -1),
                            ' ', 1
                        )
                     )
                    ) DESC
                LIMIT 20";
                break;
                
            case 'popular':
                // Get posts with most likes and comments
                $sql = "SELECT p.*, 
                       COUNT(DISTINCT l.id) as like_count,
                       COUNT(DISTINCT c.id) as comment_count,
                       u.username, u.profile_pic
                FROM post p
                LEFT JOIN likes l ON p.id = l.post_id
                LEFT JOIN comments c ON p.id = c.post_id
                JOIN users u ON p.user_id = u.id
                GROUP BY p.id
                ORDER BY (COUNT(DISTINCT l.id) + COUNT(DISTINCT c.id)) DESC
                LIMIT 20";
                break;
                
            case 'recent':
                $sql = "SELECT p.*, 
                       COUNT(DISTINCT l.id) as like_count,
                       COUNT(DISTINCT c.id) as comment_count,
                       u.username, u.profile_pic
                FROM post p
                LEFT JOIN likes l ON p.id = l.post_id
                LEFT JOIN comments c ON p.id = c.post_id
                JOIN users u ON p.user_id = u.id
                GROUP BY p.id
                ORDER BY p.date DESC
                LIMIT 20";
                break;
                
            default: // 'foryou'
                // Personalized feed based on user's interactions
                $user_id = $_SESSION['user_id'] ?? 0;
                $sql = "SELECT p.*, 
                       COUNT(DISTINCT l.id) as like_count,
                       COUNT(DISTINCT c.id) as comment_count,
                       u.username, u.profile_pic
                FROM post p
                LEFT JOIN likes l ON p.id = l.post_id
                LEFT JOIN comments c ON p.id = c.post_id
                JOIN users u ON p.user_id = u.id
                LEFT JOIN likes ul ON ul.post_id = p.id AND ul.user_id = ?
                GROUP BY p.id
                ORDER BY 
                    CASE WHEN p.user_id = ? THEN 2
                         WHEN ul.id IS NOT NULL THEN 1
                         ELSE 0 END DESC,
                    p.date DESC
                LIMIT 20";
                break;
        }
        
        $stmt = $conn->prepare($sql);
        if ($filter === 'foryou' && isset($_SESSION['user_id'])) {
            $stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $posts = [];
        while ($row = $result->fetch_assoc()) {
            // Get hashtags for trending posts
            if ($filter === 'trending') {
                $row['hashtags'] = extractHashtags($row['content']);
            }
            
            // Check if current user has liked the post
            if (isset($_SESSION['user_id'])) {
                $like_check = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
                $like_check->bind_param("ii", $row['id'], $_SESSION['user_id']);
                $like_check->execute();
                $row['liked'] = $like_check->get_result()->num_rows > 0;
            }
            
            $posts[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'posts' => $posts
        ]);
        
    } catch (Exception $e) {
        error_log("Filter error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching posts'
        ]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
?> 
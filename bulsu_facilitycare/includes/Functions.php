<?php
/**
 * BulSU FacilityCare - Utility Functions
 */

class Functions {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function generateReportNumber() {
        $prefix = 'RPT';
        $year = date('Y');
        $lastReport = $this->db->fetch(
            "SELECT report_number FROM reports WHERE report_number LIKE :pattern ORDER BY id DESC LIMIT 1",
            ['pattern' => $prefix . '-' . $year . '-%']
        );

        if ($lastReport) {
            $lastNum = (int) substr(strrchr($lastReport['report_number'], '-'), 1);
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }

        return $prefix . '-' . $year . '-' . str_pad($newNum, 4, '0', STR_PAD_LEFT);
    }

    public function getAllCategories() {
        return $this->db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
    }

    public function getCategoryById($id) {
        return $this->db->fetch("SELECT * FROM categories WHERE id = :id", ['id' => $id]);
    }

    public function getAllFacilities() {
        return $this->db->fetchAll("SELECT * FROM facilities WHERE is_active = 1 ORDER BY building, floor, room_number");
    }

    public function getFacilityById($id) {
        return $this->db->fetch("SELECT * FROM facilities WHERE id = :id", ['id' => $id]);
    }

    public function getDepartments() {
        return $this->db->fetchAll("SELECT * FROM departments ORDER BY name");
    }

    public function getReportStatuses() {
        return $this->db->fetchAll("SELECT * FROM report_statuses ORDER BY display_order");
    }

    public function getStatusByCode($code) {
        return $this->db->fetch("SELECT * FROM report_statuses WHERE code = :code", ['code' => $code]);
    }

    public function getStatusLabel($code) {
        $status = $this->getStatusByCode($code);
        return $status ? $status['label'] : ucfirst($code);
    }

    public function getStatusBadgeClass($code) {
        $status = $this->getStatusByCode($code);
        $baseClass = $status ? $status['color_class'] : 'secondary';
        return 'badge-' . $baseClass;
    }

    public function getPriorityLabel($level) {
        $labels = [
            PRIORITY_LOW => 'Low',
            PRIORITY_MEDIUM => 'Medium',
            PRIORITY_HIGH => 'High',
        ];
        return $labels[$level] ?? 'Unknown';
    }

    public function getPriorityBadgeClass($level) {
        $classes = [
            PRIORITY_LOW => 'bg-success',
            PRIORITY_MEDIUM => 'bg-warning',
            PRIORITY_HIGH => 'bg-danger',
        ];
        return $classes[$level] ?? 'bg-secondary';
    }

    public function uploadPhoto($file) {
        if (!isset($file) || $file['error'] !== UPLOAD_OK) {
            return false;
        }

        $allowedTypes = ALLOWED_IMAGE_TYPES;
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedTypes)) {
            return false;
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            return false;
        }

        $fileName = uniqid('rpt_', true) . '.' . $extension;
        $uploadPath = UPLOAD_DIR . '/' . $fileName;

        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        $check = @getimagesize($file['tmp_name']);
        if ($check === false) {
            return false;
        }

        $imageType = $check[2];
        $image = null;

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = @imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'png':
                $image = @imagecreatefrompng($file['tmp_name']);
                break;
            case 'gif':
                $image = @imagecreatefromgif($file['tmp_name']);
                break;
        }

        if (!$image) {
            return false;
        }

        $maxWidth = 1200;
        $maxHeight = 1200;
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecolortransparent($resized, imagecolorallocatealpha($resized, 0, 0, 0, 127));
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($resized, $uploadPath, 85);
                    break;
                case 'png':
                    imagepng($resized, $uploadPath, 8);
                    break;
                case 'gif':
                    imagegif($resized, $uploadPath);
                    break;
            }

            imagedestroy($image);
            imagedestroy($resized);
        } else {
            move_uploaded_file($file['tmp_name'], $uploadPath);
        }

        return $fileName;
    }

    public function createReport($data) {
        $reportNumber = $this->generateReportNumber();

        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_NO_FILE) {
            $photoPath = $this->uploadPhoto($_FILES['photo']);
        }

        $affectedUsers = isset($data['affected_users']) ? $data['affected_users'] : null;
        if (is_array($affectedUsers)) {
            $affectedUsers = implode(', ', $affectedUsers);
        }

        $reportId = $this->db->query(
            "INSERT INTO reports (report_number, title, description, category_id, facility_id,
             urgency, safety_risk, severity, affected_users, photo_path, additional_info,
             status_code, reporter_id)
             VALUES (:report_number, :title, :description, :category_id, :facility_id,
             :urgency, :safety_risk, :severity, :affected_users, :photo_path, :additional_info,
             :status_code, :reporter_id)",
            [
                'report_number' => $reportNumber,
                'title' => $data['title'],
                'description' => $data['description'],
                'category_id' => $data['category_id'],
                'facility_id' => $data['facility_id'],
                'urgency' => $data['urgency'],
                'safety_risk' => $data['safety_risk'],
                'severity' => $data['severity'],
                'affected_users' => $affectedUsers,
                'photo_path' => $photoPath,
                'additional_info' => $data['additional_info'] ?? null,
                'status_code' => STATUS_SUBMITTED,
                'reporter_id' => $_SESSION['user_id'],
            ]
        );

        $this->db->query(
            "INSERT INTO report_history (report_id, status_code, changed_by, notes)
             VALUES (:report_id, :status_code, :changed_by, :notes)",
            [
                'report_id' => $reportId,
                'status_code' => STATUS_SUBMITTED,
                'changed_by' => $_SESSION['user_id'],
                'notes' => 'Report submitted by ' . $_SESSION['full_name'],
            ]
        );

        return $reportId;
    }

    public function getReportById($id) {
        $stmt = $this->db->fetch(
            "SELECT r.*, u.full_name as reporter_name, u.email as reporter_email,
                    m.full_name as assignee_name,
                    c.name as category_name, c.criticality_weight as category_weight,
                    f.building, f.floor, f.room_number, f.location_name, f.criticality_weight as facility_weight,
                    rs.label as status_label, rs.color_class as status_color
             FROM reports r
             JOIN categories c ON r.category_id = c.id
             JOIN facilities f ON r.facility_id = f.id
             JOIN report_statuses rs ON r.status_code = rs.code
             LEFT JOIN users u ON r.reporter_id = u.id
             LEFT JOIN users m ON r.assigned_to = m.id
             WHERE r.id = :id",
            ['id' => $id]
        );
        return $stmt;
    }

    public function getReports($filters = [], $limit = 20, $offset = 0) {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "r.status_code = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $where[] = "r.priority_level = :priority";
            $params['priority'] = $filters['priority'];
        }

        if (!empty($filters['category_id'])) {
            $where[] = "r.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }

        if (!empty($filters['facility_id'])) {
            $where[] = "r.facility_id = :facility_id";
            $params['facility_id'] = $filters['facility_id'];
        }

        if (!empty($filters['reporter_id'])) {
            $where[] = "r.reporter_id = :reporter_id";
            $params['reporter_id'] = $filters['reporter_id'];
        }

        if (!empty($filters['assigned_to'])) {
            $where[] = "r.assigned_to = :assigned_to";
            $params['assigned_to'] = $filters['assigned_to'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(r.title LIKE :search OR r.description LIKE :search OR r.report_number LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $where[] = "DATE(r.created_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "DATE(r.created_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['min_priority'])) {
            $priorityValues = [];
            if ($filters['min_priority'] === 'high') $priorityValues = ['high'];
            elseif ($filters['min_priority'] === 'medium') $priorityValues = ['high', 'medium'];
            elseif ($filters['min_priority'] === 'low') $priorityValues = ['high', 'medium', 'low'];

            if (!empty($priorityValues)) {
                $placeholders = implode(',', array_fill(0, count($priorityValues), '?'));
                $where[] = "r.priority_level IN ($placeholders)";
                foreach ($priorityValues as $i => $val) {
                    $params["priority_$i"] = $val;
                }
            }
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT DISTINCT r.*, u.full_name as reporter_name,
                c.name as category_name, f.location_name, f.building, rs.label as status_label, rs.color_class as status_color
                FROM reports r
                JOIN categories c ON r.category_id = c.id
                JOIN facilities f ON r.facility_id = f.id
                JOIN report_statuses rs ON r.status_code = rs.code
                LEFT JOIN users u ON r.reporter_id = u.id
                $whereClause
                ORDER BY r.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function getReportsCount($filters = []) {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "r.status_code = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $where[] = "r.priority_level = :priority";
            $params['priority'] = $filters['priority'];
        }

        if (!empty($filters['category_id'])) {
            $where[] = "r.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }

        if (!empty($filters['facility_id'])) {
            $where[] = "r.facility_id = :facility_id";
            $params['facility_id'] = $filters['facility_id'];
        }

        if (!empty($filters['reporter_id'])) {
            $where[] = "r.reporter_id = :reporter_id";
            $params['reporter_id'] = $filters['reporter_id'];
        }

        if (!empty($filters['assigned_to'])) {
            $where[] = "r.assigned_to = :assigned_to";
            $params['assigned_to'] = $filters['assigned_to'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(r.title LIKE :search OR r.description LIKE :search OR r.report_number LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT COUNT(*) as count FROM reports r
                JOIN categories c ON r.category_id = c.id
                JOIN facilities f ON r.facility_id = f.id
                $whereClause";

        $stmt = $this->db->fetch($sql, $params);
        return (int) $stmt['count'];
    }

    public function updateReportStatus($reportId, $statusCode, $userId, $notes = '') {
        $this->db->query(
            "UPDATE reports SET status_code = :status_code WHERE id = :id",
            [
                'status_code' => $statusCode,
                'id' => $reportId,
            ]
        );

        $this->db->query(
            "INSERT INTO report_history (report_id, status_code, changed_by, notes)
             VALUES (:report_id, :status_code, :changed_by, :notes)",
            [
                'report_id' => $reportId,
                'status_code' => $statusCode,
                'changed_by' => $userId,
                'notes' => $notes,
            ]
        );
    }

    public function addComment($reportId, $userId, $message, $isInternal = false) {
        return $this->db->query(
            "INSERT INTO comments (report_id, user_id, message, is_internal)
             VALUES (:report_id, :user_id, :message, :is_internal)",
            [
                'report_id' => $reportId,
                'user_id' => $userId,
                'message' => $message,
                'is_internal' => $isInternal ? 1 : 0,
            ]
        );
    }

    public function getComments($reportId, $includeInternal = false) {
        $where = $includeInternal ? '' : "WHERE c.is_internal = 0";
        return $this->db->fetchAll(
            "SELECT c.*, u.full_name, r.name as role_name, rs.display_name as role_display
             FROM comments c
             JOIN users u ON c.user_id = u.id
             JOIN roles r ON u.role_id = r.id
             JOIN roles rs ON r.id = rs.id
             $where AND c.report_id = :report_id
             ORDER BY c.created_at ASC",
            ['report_id' => $reportId]
        );
    }

    public function getMaintenanceUpdates($reportId) {
        return $this->db->fetchAll(
            "SELECT mu.*, u.full_name
             FROM maintenance_updates mu
             LEFT JOIN users u ON mu.updated_by = u.id
             WHERE mu.report_id = :report_id
             ORDER BY mu.created_at DESC",
            ['report_id' => $reportId]
        );
    }

    public function addNotification($userId, $title, $message, $type = null, $reportId = null, $url = null) {
        return $this->db->query(
            "INSERT INTO notifications (user_id, report_id, title, message, type, url)
             VALUES (:user_id, :report_id, :title, :message, :type, :url)",
            [
                'user_id' => $userId,
                'report_id' => $reportId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'url' => $url,
            ]
        );
    }

    public function getNotifications($userId, $limit = 10, $unreadOnly = false) {
        $where = "WHERE n.user_id = :user_id";
        $params = ['user_id' => $userId];

        if ($unreadOnly) {
            $where .= " AND n.is_read = 0";
        }

        return $this->db->fetchAll(
            "SELECT n.*, r.report_number, r.title as report_title
             FROM notifications n
             LEFT JOIN reports r ON n.report_id = r.id
             $where
             ORDER BY n.created_at DESC
             LIMIT :limit",
            $params
        );
    }

    public function getUnreadNotificationCount($userId) {
        $stmt = $this->db->fetch(
            "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0",
            ['user_id' => $userId]
        );
        return (int) $stmt['count'];
    }

    public function markNotificationRead($id, $userId) {
        $this->db->query(
            "UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id",
            ['id' => $id, 'user_id' => $userId]
        );
    }

    public function markAllNotificationsRead($userId) {
        $this->db->query(
            "UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0",
            ['user_id' => $userId]
        );
    }

    public function getPriorityScore($reportId) {
        return $this->db->fetch(
            "SELECT * FROM priority_scores WHERE report_id = :report_id",
            ['report_id' => $reportId]
        );
    }

    public function getReportComments($reportId, $includeInternal = false) {
        $where = $includeInternal ? '' : "WHERE c.is_internal = 0";

        $sql = "SELECT c.*, u.full_name, r.name as role_name, rs.display_name as role_display
                FROM comments c
                JOIN users u ON c.user_id = u.id
                JOIN roles r ON u.role_id = r.id
                JOIN roles rs ON r.id = rs.id
                $where AND c.report_id = :report_id
                ORDER BY c.created_at ASC";

        $stmt = $this->db->query($sql, ['report_id' => $reportId]);
        return $stmt->fetchAll();
    }

    public function getDashboardStats($userId = null, $role = null) {
        $where = [];
        $params = [];

        if ($role === ROLE_STUDENT_STAFF && $userId) {
            $where[] = "reporter_id = :user_id";
            $params['user_id'] = $userId;
        } elseif ($role === ROLE_MAINTENANCE && $userId) {
            $where[] = "(reporter_id = :user_id OR assigned_to = :user_id)";
            $params['user_id'] = $userId;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stats = $this->db->fetch(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status_code = 'submitted' OR status_code = 'under_review' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status_code = 'validated' OR status_code = 'assigned' OR status_code = 'ongoing' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status_code = 'resolved' OR status_code = 'closed' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status_code = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN priority_level = 'high' AND status_code NOT IN ('resolved','closed','rejected') THEN 1 ELSE 0 END) as high_priority,
                SUM(CASE WHEN priority_level = 'medium' AND status_code NOT IN ('resolved','closed','rejected') THEN 1 ELSE 0 END) as medium_priority,
                SUM(CASE WHEN priority_level = 'low' AND status_code NOT IN ('resolved','closed','rejected') THEN 1 ELSE 0 END) as low_priority,
                SUM(CASE WHEN safety_risk = 'severe' OR safety_risk = 'moderate' THEN 1 ELSE 0 END) as safety_risks
             FROM reports
             $whereClause",
            $params
        );

        return $stats;
    }

    public function getRecentReports($limit = 10, $userId = null, $role = null) {
        $where = [];
        $params = ['limit' => $limit];

        if ($role === ROLE_STUDENT_STAFF && $userId) {
            $where[] = "reporter_id = :user_id";
            $params['user_id'] = $userId;
        } elseif ($role === ROLE_MAINTENANCE && $userId) {
            $where[] = "(reporter_id = :user_id OR assigned_to = :user_id)";
            $params['user_id'] = $userId;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $whereClause .= $whereClause ? ' AND ' : 'WHERE ';
        $whereClause .= "status_code NOT IN ('resolved', 'closed', 'rejected')";

        return $this->db->fetchAll(
            "SELECT r.*, u.full_name as reporter_name,
                    c.name as category_name, f.location_name, rs.label as status_label
             FROM reports r
             JOIN categories c ON r.category_id = c.id
             JOIN facilities f ON r.facility_id = f.id
             JOIN report_statuses rs ON r.status_code = rs.code
             LEFT JOIN users u ON r.reporter_id = u.id
             $whereClause
             ORDER BY r.created_at DESC
             LIMIT :limit",
            $params
        );
    }

    public function getPriorityStats() {
        return $this->db->fetchAll(
            "SELECT priority_level, COUNT(*) as count
             FROM reports
             WHERE status_code NOT IN ('resolved', 'closed', 'rejected')
             GROUP BY priority_level
             ORDER BY FIELD(priority_level, 'high', 'medium', 'low')"
        );
    }

    public function getStatusStats() {
        return $this->db->fetchAll(
            "SELECT r.status_code, rs.label, rs.color_class, COUNT(*) as count
             FROM reports r
             JOIN report_statuses rs ON r.status_code = rs.code
             GROUP BY r.status_code
             ORDER BY rs.display_order"
        );
    }

    public function getCategoryStats() {
        return $this->db->fetchAll(
            "SELECT c.name, COUNT(*) as count
             FROM reports r
             JOIN categories c ON r.category_id = c.id
             WHERE r.status_code NOT IN ('resolved', 'closed', 'rejected')
             GROUP BY c.id, c.name
             ORDER BY count DESC"
        );
    }

    public function getRecurringIssues() {
        return $this->db->fetchAll(
            "SELECT c.name as category_name, f.location_name, f.building,
                    COUNT(*) as report_count,
                    MIN(r.created_at) as first_reported,
                    MAX(r.created_at) as last_reported
             FROM reports r
             JOIN categories c ON r.category_id = c.id
             JOIN facilities f ON r.facility_id = f.id
             WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY c.id, f.id
             HAVING report_count >= 2
             ORDER BY report_count DESC
             LIMIT 10"
        );
    }

    public function getUnresolvedHighPriority($limit = 10) {
        return $this->db->fetchAll(
            "SELECT r.*, u.full_name as reporter_name,
                    c.name as category_name, f.location_name,
                    ps.total_score
             FROM reports r
             JOIN users u ON r.reporter_id = u.id
             JOIN categories c ON r.category_id = c.id
             JOIN facilities f ON r.facility_id = f.id
             LEFT JOIN priority_scores ps ON r.id = ps.report_id
             WHERE r.priority_level = 'high'
             AND r.status_code NOT IN ('resolved', 'closed', 'rejected')
             ORDER BY r.created_at ASC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }

    public function getTimeStats() {
        $stats = $this->db->fetch(
            "SELECT
                AVG(DATEDIFF(updated_at, created_at)) as avg_resolution_days,
                MIN(created_at) as earliest_report,
                MAX(created_at) as latest_report
             FROM reports
             WHERE status_code IN ('resolved', 'closed')
             AND resolved_at IS NOT NULL"
        );
        return $stats ?: ['avg_resolution_days' => 0, 'earliest_report' => null, 'latest_report' => null];
    }

    public function searchSimilarReports($reportId, $title, $description, $categoryId, $facilityId) {
        $sql = "SELECT r.id, r.report_number, r.title, r.description, r.category_id, r.facility_id,
                r.created_at, r.status_code,
                (CASE
                    WHEN r.category_id = :cat_id AND r.facility_id = :fac_id THEN 0.30
                    WHEN r.category_id = :cat_id OR r.facility_id = :fac_id THEN 0.15
                    ELSE 0 END) as field_score
                FROM reports r
                WHERE r.id != :report_id
                AND r.status_code NOT IN ('rejected', 'closed')
                ORDER BY field_score DESC, r.created_at DESC
                LIMIT 10";

        $stmt = $this->db->query($sql, [
            'report_id' => $reportId,
            'cat_id' => $categoryId,
            'fac_id' => $facilityId,
        ]);

        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $similarity = $this->calculateSimilarity($title, $description, $row['title'], $row['description']);
            $totalScore = $similarity + (float)$row['field_score'];

            if ($totalScore >= (float)getSetting('duplicate_detection_threshold', 0.70)) {
                $results[] = [
                    'id' => $row['id'],
                    'report_number' => $row['report_number'],
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'similarity_score' => $totalScore,
                    'field_score' => $row['field_score'],
                ];
            }
        }

        usort($results, function($a, $b) {
            return $b['similarity_score'] <=> $a['similarity_score'];
        });

        return $results;
    }

    private function calculateSimilarity($str1, $str2) {
        similar_text(strtolower($str1 . ' ' . ($str2 ?? '')), strtolower(($str1 ?? '') . ' ' . $str2), $percent);
        return $percent / 100;
    }

    public function checkForDuplicates($reportId = null) {
        $sql = "SELECT r.id, r.report_number, r.title, r.description, r.category_id, r.facility_id,
                r.created_at, r.status_code,
                (CASE
                    WHEN r.category_id = :cat_id AND r.facility_id = :fac_id THEN 0.30
                    WHEN r.category_id = :cat_id OR r.facility_id = :fac_id THEN 0.15
                    ELSE 0 END) as field_score
                FROM reports r
                WHERE " . ($reportId ? "r.id != :report_id AND" : "") . "
                r.status_code NOT IN ('rejected', 'closed')
                ORDER BY field_score DESC, r.created_at DESC
                LIMIT 10";

        $params = [];
        if ($reportId) {
            $params['report_id'] = $reportId;
        }

        return $this->db->fetchAll($sql, $params);
    }

    public function mergeReports($originalReportId, $duplicateReportId, $userId) {
        $this->db->query(
            "UPDATE reports SET status_code = 'rejected' WHERE id = :id",
            ['id' => $duplicateReportId]
        );

        $this->db->query(
            "UPDATE reports SET status_code = 'validated' WHERE id = :id",
            ['id' => $originalReportId]
        );

        $this->db->query(
            "INSERT INTO report_history (report_id, status_code, changed_by, notes)
             VALUES (:report_id, :status_code, :changed_by, :notes)",
            [
                'report_id' => $duplicateReportId,
                'status_code' => 'rejected',
                'changed_by' => $userId,
                'notes' => 'Report merged with original report #' . $originalReportId . ' as a duplicate.',
            ]
        );

        $this->db->query(
            "INSERT INTO duplicate_reports (report_id, original_report_id, similarity_score, is_merged, reviewed_by, reviewed_at)
             VALUES (:report_id, :original_report_id, :similarity_score, :is_merged, :reviewed_by, NOW())",
            [
                'report_id' => $duplicateReportId,
                'original_report_id' => $originalReportId,
                'similarity_score' => 1.00,
                'is_merged' => 1,
                'reviewed_by' => $userId,
            ]
        );

        $duplicateReport = $this->getReportById($duplicateReportId);
        $reporterId = $duplicateReport['reporter_id'];
        if ($reporterId) {
            $this->addNotification(
                $reporterId,
                'Report Merged',
                'Your report #' . $duplicateReport['report_number'] . ' has been merged with report #' . $originalReportId . ' as a duplicate. Please refer to the original report for updates.',
                NOTIF_DUPLICATE_MERGED,
                $originalReportId,
                '/report/' . $originalReportId
            );
        }

        return true;
    }

    public function formatDate($date) {
        if (!$date) return '';
        $datetime = new DateTime($date);
        return $datetime->format('M j, Y g:i A');
    }

    public function timeAgo($datetime) {
        if (!$datetime) return '';
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;

        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . ' min ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
        if ($diff < 604800) return floor($diff / 86400) . ' day ago';
        return date('M j, Y', $time);
    }

    public function getReportStatusFlow() {
        return [
            STATUS_SUBMITTED => ['Under Review'],
            STATUS_UNDER_REVIEW => ['Validated', 'Rejected'],
            STATUS_VALIDATED => ['Assigned'],
            STATUS_ASSIGNED => ['Ongoing'],
            STATUS_ONGOING => ['Resolved'],
            STATUS_RESOLVED => ['Closed'],
            STATUS_REJECTED => [],
            STATUS_CLOSED => [],
        ];
    }

    public function getAvailableStatusTransitions($currentStatus) {
        $flow = $this->getReportStatusFlow();
        return $flow[$currentStatus] ?? [];
    }

    public function getAdminUsers() {
        return $this->db->fetchAll(
            "SELECT u.* FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name IN ('admin', 'maintenance') AND u.is_active = 1"
        );
    }

    public function getUsersByRole($roleName) {
        return $this->db->fetchAll(
            "SELECT u.*, r.display_name as role_display, d.name as department_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             LEFT JOIN departments d ON u.department_id = d.id
             WHERE r.name = :role_name",
            ['role_name' => $roleName]
        );
    }

    public function getAllUsers($filters = []) {
        $where = [];
        $params = [];

        if (!empty($filters['role'])) {
            $where[] = "r.name = :role";
            $params['role'] = $filters['role'];
        }
        if (!empty($filters['department'])) {
            $where[] = "u.department_id = :dept";
            $params['dept'] = $filters['department'];
        }
        if (!empty($filters['search'])) {
            $where[] = "(u.full_name LIKE :search OR u.username LIKE :search OR u.email LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (array_key_exists('is_active', $filters)) {
            $where[] = "u.is_active = :is_active";
            $params['is_active'] = $filters['is_active'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        return $this->db->fetchAll(
            "SELECT u.*, r.name as role_name, r.display_name as role_display, d.name as department_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             LEFT JOIN departments d ON u.department_id = d.id
             $whereClause
             ORDER BY u.created_at DESC"
        );
    }

    public function getUserById($id) {
        return $this->db->fetch(
            "SELECT u.*, r.name as role_name, r.display_name as role_display, d.name as department_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             LEFT JOIN departments d ON u.department_id = d.id
             WHERE u.id = :id",
            ['id' => $id]
        );
    }

    public function createUser($data) {
        $roleId = $this->db->fetch("SELECT id FROM roles WHERE name = :name", ['name' => $data['role_name']])['id'];

        return $this->db->query(
            "INSERT INTO users (role_id, department_id, employee_id, username, email, password, full_name, phone)
             VALUES (:role_id, :dept_id, :employee_id, :username, :email, :password, :full_name, :phone)",
            [
                'role_id' => $roleId,
                'dept_id' => $data['department_id'] ?: null,
                'employee_id' => $data['employee_id'] ?: null,
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'full_name' => $data['full_name'],
                'phone' => $data['phone'] ?: null,
            ]
        );
    }

    public function updateUser($id, $data, $changePassword = false) {
        $updates = [];
        $params = ['id' => $id];

        if (!empty($data['full_name'])) {
            $updates[] = "full_name = :full_name";
            $params['full_name'] = $data['full_name'];
        }
        if (!empty($data['email'])) {
            $updates[] = "email = :email";
            $params['email'] = $data['email'];
        }
        if (!empty($data['username'])) {
            $updates[] = "username = :username";
            $params['username'] = $data['username'];
        }
        if (!empty($data['department_id'])) {
            $updates[] = "department_id = :department_id";
            $params['department_id'] = $data['department_id'];
        }
        if (!empty($data['phone'])) {
            $updates[] = "phone = :phone";
            $params['phone'] = $data['phone'];
        }
        if (!empty($data['role_name'])) {
            $roleId = $this->db->fetch("SELECT id FROM roles WHERE name = :name", ['name' => $data['role_name']]);
            if ($roleId) {
                $updates[] = "role_id = :role_id";
                $params['role_id'] = $roleId['id'];
            }
        }
        if ($changePassword && !empty($data['password'])) {
            $updates[] = "password = :password";
            $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        if (array_key_exists('is_active', $data)) {
            $updates[] = "is_active = :is_active";
            $params['is_active'] = $data['is_active'];
        }

        if (!empty($updates)) {
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
            return $this->db->query($sql, $params);
        }
        return false;
    }

    public function getAllRoles() {
        return $this->db->fetchAll("SELECT * FROM roles ORDER BY display_order, name");
    }

    public function getReportStatusCountByMonth($months = 6) {
        return $this->db->fetchAll(
            "SELECT rs.label as status, COUNT(*) as count,
                    DATE_FORMAT(r.created_at, '%Y-%m') as month
             FROM reports r
             JOIN report_statuses rs ON r.status_code = rs.code
             WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
             GROUP BY rs.code, DATE_FORMAT(r.created_at, '%Y-%m')
             ORDER BY DATE_FORMAT(r.created_at, '%Y-%m') ASC, rs.display_order ASC"
        );
    }

    public function getPriorityTrend($months = 6) {
        return $this->db->fetchAll(
            "SELECT priority_level, COUNT(*) as count,
                    DATE_FORMAT(created_at, '%Y-%m') as month
             FROM reports
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
             AND status_code NOT IN ('resolved', 'closed', 'rejected')
             GROUP BY priority_level, DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month ASC"
        );
    }

    public function getReportResolutionTime() {
        return $this->db->fetchAll(
            "SELECT c.name as category_name,
                    AVG(DATEDIFF(r.updated_at, r.created_at)) as avg_days,
                    COUNT(*) as count
             FROM reports r
             JOIN categories c ON r.category_id = c.id
             WHERE r.status_code IN ('resolved', 'closed')
             AND r.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY c.id, c.name
             ORDER BY avg_days DESC"
        );
    }

    public function getMaintenancePerformance($userId = null, $limit = 10) {
        $where = $userId ? "WHERE mu.updated_by = :user_id" : "WHERE 1=1";
        $params = $userId ? ['user_id' => $userId, 'limit' => $limit] : ['limit' => $limit];

        return $this->db->fetchAll(
            "SELECT u.full_name, COUNT(*) as updates_count,
                    COUNT(DISTINCT mu.report_id) as reports_handled,
                    AVG(CASE WHEN r.priority_score > 0 THEN r.priority_score ELSE 0 END) as avg_priority
             FROM maintenance_updates mu
             JOIN users u ON mu.updated_by = u.id
             JOIN reports r ON mu.report_id = r.id
             $where
             GROUP BY mu.updated_by
             ORDER BY updates_count DESC
             LIMIT :limit",
            $params
        );
    }

    public function getAssignedReports($userId, $status = null) {
        $where = ["r.assigned_to = :user_id"];
        $params = ['user_id' => $userId];

        if ($status) {
            $where[] = "r.status_code = :status";
            $params['status'] = $status;
        }

        return $this->db->fetchAll(
            "SELECT r.*, c.name as category_name, f.location_name, f.building,
                    u.full_name as reporter_name, rs.label as status_label, rs.color_class as status_color
             FROM reports r
             JOIN categories c ON r.category_id = c.id
             JOIN facilities f ON r.facility_id = f.id
             JOIN report_statuses rs ON r.status_code = rs.code
             LEFT JOIN users u ON r.reporter_id = u.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY r.created_at DESC",
            $params
        );
    }

    public function assignReport($reportId, $userId, $assignedBy) {
        $this->db->query("UPDATE reports SET assigned_to = :user_id, status_code = 'assigned' WHERE id = :id", [
            'user_id' => $userId,
            'id' => $reportId,
        ]);

        $this->updateReportStatus($reportId, STATUS_ASSIGNED, $assignedBy, 'Report assigned to maintenance personnel.');

        $report = $this->getReportById($reportId);
        $this->addNotification(
            $userId,
            'Report Assigned',
            'Report #' . $report['report_number'] . ' has been assigned to you for resolution.',
            NOTIF_REPORT_ASSIGNED,
            $reportId,
            '/report/' . $reportId
        );
    }
}
}

<?php
/**
 * BulSU FacilityCare - Multi-Criteria Priority Assessment
 * Implements the Risk Prioritization Framework based on:
 *  - Safety Risk, Severity of Damage, Urgency
 *  - Location Criticality, Impact on Operations
 *  - Report Frequency, Issue Category
 */

class Priority {
    private $db;
    private $thresholds;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->thresholds = [
            'high' => (float)getSetting('priority_high_threshold', 7.5),
            'medium' => (float)getSetting('priority_medium_threshold', 4.0),
        ];
    }

    public function calculatePriority($reportId) {
        $report = $this->getReportWithDetails($reportId);

        if (!$report) {
            return null;
        }

        $safetyRiskScore = $this->getSafetyRiskScore($report['safety_risk']);
        $severityScore = $this->getSeverityScore($report['severity']);
        $urgencyScore = $this->getUrgencyScore($report['urgency']);
        $locationScore = $this->getLocationScore($report['facility_weight']);
        $operationsScore = $this->getOperationsScore($report['safety_risk'], $report['severity'], $report['affected_users']);
        $frequencyScore = $this->getFrequencyScore($report['id'], $report['category_id'], $report['facility_id']);
        $categoryScore = $this->getCategoryScore($report['category_weight']);

        $totalScore = $safetyRiskScore + $severityScore + $urgencyScore + $locationScore
                    + $operationsScore + $frequencyScore + $categoryScore;

        $priorityLevel = $this->determinePriorityLevel($totalScore);

        $this->savePriorityScore($reportId, [
            'safety_risk_score' => $safetyRiskScore,
            'severity_score' => $severityScore,
            'urgency_score' => $urgencyScore,
            'location_score' => $locationScore,
            'operations_score' => $operationsScore,
            'frequency_score' => $frequencyScore,
            'category_score' => $categoryScore,
            'total_score' => $totalScore,
            'priority_level' => $priorityLevel,
        ]);

        $this->updateReportPriority($reportId, $totalScore, $priorityLevel);

        return [
            'total_score' => round($totalScore, 2),
            'priority_level' => $priorityLevel,
            'breakdown' => [
                'safety_risk' => $safetyRiskScore,
                'severity' => $severityScore,
                'urgency' => $urgencyScore,
                'location' => $locationScore,
                'operations' => $operationsScore,
                'frequency' => $frequencyScore,
                'category' => $categoryScore,
            ],
            'criteria' => [
                'safety_risk' => $report['safety_risk'],
                'severity' => $report['severity'],
                'urgency' => $report['urgency'],
                'facility_weight' => $report['facility_weight'],
                'category_weight' => $report['category_weight'],
            ],
        ];
    }

    public function calculatePriorityForSubmission($data) {
        $safetyRiskScore = $this->getSafetyRiskScore($data['safety_risk']);
        $severityScore = $this->getSeverityScore($data['severity']);
        $urgencyScore = $this->getUrgencyScore($data['urgency']);

        $facility = $this->db->fetch("SELECT criticality_weight FROM facilities WHERE id = :id", ['id' => $data['facility_id']]);
        $locationScore = $this->getLocationScore($facility ? $facility['criticality_weight'] : 1.0);

        $operationsScore = $this->getOperationsScore($data['safety_risk'], $data['severity'], $data['affected_users']);

        $category = $this->db->fetch("SELECT criticality_weight FROM categories WHERE id = :id", ['id' => $data['category_id']]);
        $categoryScore = $this->getCategoryScore($category ? $category['criticality_weight'] : 1.0);

        $frequencyScore = 5.0;

        $totalScore = $safetyRiskScore + $severityScore + $urgencyScore + $locationScore
                    + $operationsScore + $frequencyScore + $categoryScore;

        $priorityLevel = $this->determinePriorityLevel($totalScore);

        return [
            'total_score' => round($totalScore, 2),
            'priority_level' => $priorityLevel,
            'breakdown' => [
                'safety_risk' => $safetyRiskScore,
                'severity' => $severityScore,
                'urgency' => $urgencyScore,
                'location' => $locationScore,
                'operations' => $operationsScore,
                'frequency' => $frequencyScore,
                'category' => $categoryScore,
            ],
        ];
    }

    private function getReportWithDetails($reportId) {
        return $this->db->fetch(
            "SELECT r.*, c.criticality_weight as category_weight,
                    f.criticality_weight as facility_weight
             FROM reports r
             JOIN categories c ON r.category_id = c.id
             JOIN facilities f ON r.facility_id = f.id
             WHERE r.id = :id",
            ['id' => $reportId]
        );
    }

    private function getSafetyRiskScore($level) {
        $scores = [
            'no' => 0,
            'minor' => 2,
            'moderate' => 5,
            'severe' => 8,
        ];
        return $scores[$level] ?? 0;
    }

    private function getSeverityScore($level) {
        $scores = [
            'minor' => 1,
            'moderate' => 3,
            'major' => 5,
            'critical' => 8,
        ];
        return $scores[$level] ?? 0;
    }

    private function getUrgencyScore($level) {
        $scores = [
            'low' => 1,
            'medium' => 3,
            'high' => 5,
        ];
        return $scores[$level] ?? 0;
    }

    private function getLocationScore($weight) {
        $maxScore = 5;
        $normalizedWeight = min(max($weight, 0.5), 2.0);
        return round(($normalizedWeight - 0.5) / 1.5 * $maxScore, 2);
    }

    private function getOperationsScore($safetyRisk, $severity, $affectedUsers) {
        $baseScore = 0;

        $safetyMap = ['no' => 0, 'minor' => 1, 'moderate' => 2, 'severe' => 3];
        $baseScore += $safetyMap[$safetyRisk] ?? 0;

        $severityMap = ['minor' => 0, 'moderate' => 1, 'major' => 2, 'critical' => 3];
        $baseScore += $severityMap[$severity] ?? 0;

        $affectedCount = 0;
        if ($affectedUsers) {
            $affectedList = is_array($affectedUsers) ? $affectedUsers : explode(',', $affectedUsers);
            $affectedCount = count(array_filter($affectedList));
        }

        if ($affectedCount > 50) $baseScore += 2;
        elseif ($affectedCount > 20) $baseScore += 1;
        elseif ($affectedCount > 5) $baseScore += 0.5;

        $maxScore = 8;
        return min(round($baseScore, 2), $maxScore);
    }

    private function getFrequencyScore($reportId, $categoryId, $facilityId) {
        $count = $this->db->fetch(
            "SELECT COUNT(*) as count FROM reports
             WHERE category_id = :category_id AND facility_id = :facility_id
             AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
             AND status_code IN ('resolved', 'closed')",
            [
                'category_id' => $categoryId,
                'facility_id' => $facilityId,
            ]
        );

        $reportCount = (int)($count['count'] ?? 0);

        if ($reportCount >= 5) return 3;
        if ($reportCount >= 3) return 2;
        if ($reportCount >= 1) return 1;
        return 0;
    }

    private function getCategoryScore($weight) {
        $maxScore = 3;
        $normalizedWeight = min(max($weight, 0.5), 2.5);
        return round(($normalizedWeight - 0.5) / 2.0 * $maxScore, 2);
    }

    private function determinePriorityLevel($totalScore) {
        if ($totalScore >= $this->thresholds['high']) {
            return PRIORITY_HIGH;
        }
        if ($totalScore >= $this->thresholds['medium']) {
            return PRIORITY_MEDIUM;
        }
        return PRIORITY_LOW;
    }

    private function savePriorityScore($reportId, $data) {
        $existing = $this->db->fetch("SELECT id FROM priority_scores WHERE report_id = :report_id", ['report_id' => $reportId]);

        if ($existing) {
            $this->db->query(
                "UPDATE priority_scores SET
                    safety_risk_score = :safety_risk_score,
                    severity_score = :severity_score,
                    urgency_score = :urgency_score,
                    location_score = :location_score,
                    operations_score = :operations_score,
                    frequency_score = :frequency_score,
                    category_score = :category_score,
                    total_score = :total_score,
                    priority_level = :priority_level,
                    assessed_by = :assessed_by,
                    assessed_at = NOW()
                 WHERE report_id = :report_id",
                [
                    'safety_risk_score' => $data['safety_risk_score'],
                    'severity_score' => $data['severity_score'],
                    'urgency_score' => $data['urgency_score'],
                    'location_score' => $data['location_score'],
                    'operations_score' => $data['operations_score'],
                    'frequency_score' => $data['frequency_score'],
                    'category_score' => $data['category_score'],
                    'total_score' => $data['total_score'],
                    'priority_level' => $data['priority_level'],
                    'assessed_by' => $_SESSION['user_id'] ?? null,
                    'report_id' => $reportId,
                ]
            );
        } else {
            $this->db->query(
                "INSERT INTO priority_scores
                    (report_id, safety_risk_score, severity_score, urgency_score, location_score,
                     operations_score, frequency_score, category_score, total_score, priority_level, assessed_by, assessed_at)
                 VALUES
                    (:report_id, :safety_risk_score, :severity_score, :urgency_score, :location_score,
                     :operations_score, :frequency_score, :category_score, :total_score, :priority_level, :assessed_by, NOW())",
                [
                    'report_id' => $reportId,
                    'safety_risk_score' => $data['safety_risk_score'],
                    'severity_score' => $data['severity_score'],
                    'urgency_score' => $data['urgency_score'],
                    'location_score' => $data['location_score'],
                    'operations_score' => $data['operations_score'],
                    'frequency_score' => $data['frequency_score'],
                    'category_score' => $data['category_score'],
                    'total_score' => $data['total_score'],
                    'priority_level' => $data['priority_level'],
                    'assessed_by' => $_SESSION['user_id'] ?? null,
                ]
            );
        }
    }

    private function updateReportPriority($reportId, $totalScore, $priorityLevel) {
        $this->db->query(
            "UPDATE reports SET priority_score = :priority_score, priority_level = :priority_level WHERE id = :id",
            [
                'priority_score' => $totalScore,
                'priority_level' => $priorityLevel,
                'id' => $reportId,
            ]
        );
    }

    public function getCriteriaDescriptions() {
        return [
            'safety_risk' => [
                'label' => 'Safety Risk',
                'description' => 'Potential danger to individuals or property',
                'levels' => [
                    'no' => ['label' => 'No Risk', 'score' => 0, 'color' => 'success'],
                    'minor' => ['label' => 'Minor Risk', 'score' => 2, 'color' => 'info'],
                    'moderate' => ['label' => 'Moderate Risk', 'score' => 5, 'color' => 'warning'],
                    'severe' => ['label' => 'Severe Risk', 'score' => 8, 'color' => 'danger'],
                ],
            ],
            'severity' => [
                'label' => 'Severity of Damage',
                'description' => 'Extent and seriousness of the reported issue',
                'levels' => [
                    'minor' => ['label' => 'Minor', 'score' => 1, 'color' => 'success'],
                    'moderate' => ['label' => 'Moderate', 'score' => 3, 'color' => 'info'],
                    'major' => ['label' => 'Major', 'score' => 5, 'color' => 'warning'],
                    'critical' => ['label' => 'Critical', 'score' => 8, 'color' => 'danger'],
                ],
            ],
            'urgency' => [
                'label' => 'Urgency',
                'description' => 'Time sensitivity of the required action',
                'levels' => [
                    'low' => ['label' => 'Low', 'score' => 1, 'color' => 'success'],
                    'medium' => ['label' => 'Medium', 'score' => 3, 'color' => 'info'],
                    'high' => ['label' => 'High', 'score' => 5, 'color' => 'warning'],
                ],
            ],
            'location' => [
                'label' => 'Location Criticality',
                'description' => 'Importance of the affected facility location',
                'max_score' => 5,
            ],
            'operations' => [
                'label' => 'Impact on Operations',
                'description' => 'Effect on normal university operations and number of affected users',
                'max_score' => 8,
            ],
            'frequency' => [
                'label' => 'Report Frequency',
                'description' => 'How often this issue recurs at this location',
                'levels' => [
                    0 => ['label' => 'No Recurrence', 'score' => 0, 'color' => 'success'],
                    1 => ['label' => 'Occasional', 'score' => 1, 'color' => 'info'],
                    2 => ['label' => 'Frequent', 'score' => 2, 'color' => 'warning'],
                    3 => ['label' => 'Very Frequent', 'score' => 3, 'color' => 'danger'],
                ],
            ],
            'category' => [
                'label' => 'Issue Category',
                'description' => 'Criticality weight of the issue category',
                'max_score' => 3,
            ],
        ];
    }
}

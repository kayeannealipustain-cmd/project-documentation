<?php
// reports.php
require 'db.php';

// Helper function to process database row into JS-friendly format
function format_report($report) {
    $report['id'] = $report['display_id']; // Use the padded ID as the main ID
    $report['timestamp'] = (int)$report['timestamp'];
    $report['resolvedAt'] = $report['resolved_at'] ? (int)$report['resolved_at'] : null;

    // Convert photo
    $report['photoBase64'] = $report['photo_base64'] ?? null;

    // Convert submitted-by fields
    $report['submittedBy'] = $report['submitted_by'] ?? null;
    $report['submittedByRole'] = $report['submitted_by_role'] ?? null;
    $report['submittedById'] = $report['submitted_by_id'] ?? null;

    // Cleanup snake_case fields
    unset($report['display_id']);
    unset($report['resolved_at']);
    unset($report['photo_base64']);
    unset($report['submitted_by']);
    unset($report['submitted_by_role']);
    unset($report['submitted_by_id']);

    return $report;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- CREATE NEW REPORT ---
    $data = json_decode(file_get_contents("php://input"), true);
    // Basic validation
    if (empty($data['content']) || empty($data['category']) || empty($data['location'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required report fields.']);
        exit();
    }
    
    $timestamp = round(microtime(true) * 1000);
    $photoBase64 = $data['photoBase64'] ?? null;
    $status = $data['status'] ?? 'New';
    
    try {
        // 1. Insert and get the auto-incremented ID
        $stmt = $pdo->prepare("INSERT INTO reports 
            (content, category, location, submitted_by, submitted_by_role, submitted_by_id, status, photo_base64, timestamp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['content'], $data['category'], $data['location'], 
            $data['submittedBy'], $data['submittedByRole'], $data['submittedById'], 
            $status, $photoBase64, $timestamp
        ]);
        
        $newId = $pdo->lastInsertId();
        // 2. Generate the padded display ID (e.g., '001') and update the row
        $displayId = str_pad($newId, 3, '0', STR_PAD_LEFT);
        $updateStmt = $pdo->prepare("UPDATE reports SET display_id = ? WHERE id = ?");
        $updateStmt->execute([$displayId, $newId]);
        
        http_response_code(201);
        echo json_encode(['message' => 'Report submitted successfully.', 'id' => $displayId]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error on submission.', 'details' => $e->getMessage()]);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // --- FETCH REPORTS (READ) ---
    $tab = $_GET['tab'] ?? 'global';
    $userId = $_GET['userId'] ?? null;
    $filterStatus = $_GET['filter'] ?? 'all';
    $fetchAll = filter_var($_GET['fetchAll'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

    $filteredReports = [];
    $allReports = [];
    
    // 1. Fetch ALL reports for IT Staff Analytics
    if ($fetchAll) {
         $stmt = $pdo->query("SELECT * FROM reports ORDER BY timestamp DESC");
         $allReports = array_map('format_report', $stmt->fetchAll());
    }

    // 2. Construct the query for the filtered list view
    $sql = "SELECT * FROM reports WHERE 1=1";
    $params = [];
    
    // Filter by 'my reports' tab
    if ($tab === 'my' && $userId) {
        $sql .= " AND submitted_by_id = ?";
        $params[] = $userId;
    } 
    
    // Filter by status (only applies to global/IT staff views)
    if ($filterStatus !== 'all' && $tab !== 'my') {
        $sql .= " AND status = ?";
        $params[] = $filterStatus;
    }

    $sql .= " ORDER BY timestamp DESC";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $filteredReports = array_map('format_report', $stmt->fetchAll());
        
        // If not fetching all, the filtered list serves as the "all reports" for student/teacher dashboards
        if (!$fetchAll) {
             $allReports = $filteredReports;
        }

        echo json_encode([
            'filtered' => $filteredReports,
            'all' => $allReports
        ]);

    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error on fetch.', 'details' => $e->getMessage()]);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // --- UPDATE REPORT STATUS ---
    $data = json_decode(file_get_contents("php://input"), true);
    $reportDisplayId = $data['id'] ?? null; // The '001', '002' ID
    $newStatus = $data['status'] ?? null;
    
    if (!$reportDisplayId || !$newStatus) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing report ID or status.']);
        exit();
    }
    
    $resolvedAt = ($newStatus === 'Resolved') ? round(microtime(true) * 1000) : null;
    
    try {
        $stmt = $pdo->prepare("UPDATE reports SET status = ?, resolved_at = ? WHERE display_id = ?");
        $stmt->execute([$newStatus, $resolvedAt, $reportDisplayId]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Report not found.']);
        } else {
            echo json_encode(['message' => "Report $reportDisplayId status updated to $newStatus."]);
        }
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error on update.', 'details' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
}
?>
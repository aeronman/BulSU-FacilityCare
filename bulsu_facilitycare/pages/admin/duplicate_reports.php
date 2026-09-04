<?php
/**
 * Admin - Duplicate Report Detection
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

if (!$currentUser || !$auth->isAdmin()) {
    redirect('/admin/dashboard');
}

$showMerged = isset($_GET['merged']) ? (bool)$_GET['merged'] : false;
$detectedDuplicates = [];

if ($showMerged) {
    $detectedDuplicates = $func->getDb()->fetchAll(
        "SELECT d.*, r1.report_number as dup_report_number, r1.title as dup_title,
                r2.report_number as orig_report_number, r2.title as orig_title,
                ru.full_name as reviewed_by_name
         FROM duplicate_reports d
         JOIN reports r1 ON d.report_id = r1.id
         JOIN reports r2 ON d.original_report_id = r2.id
         LEFT JOIN users ru ON d.reviewed_by = ru.id
         WHERE d.is_merged = 1
         ORDER BY d.reviewed_at DESC"
    );
} else {
    $allReports = $func->getReports([], 100, 0);

    foreach ($allReports as $report) {
        if ($report['status_code'] === 'rejected') continue;

        $duplicates = $func->searchSimilarReports(
            $report['id'], $report['title'], '',
            null, null
        );

        foreach ($duplicates as $dup) {
            $dupReport = $func->getReportById($dup['id']);
            if ($dupReport['status_code'] === 'rejected') continue;

            $existingRecord = $func->getDb()->fetch(
                "SELECT id FROM duplicate_reports WHERE report_id = :report_id AND original_report_id = :original_id",
                ['report_id' => $dup['id'], 'original_id' => $report['id']]
            );

            if (!$existingRecord) {
                $detectedDuplicates[] = [
                    'report' => $report,
                    'duplicate' => $dup,
                    'duplicate_report' => $dupReport,
                    'similarity' => $dup['similarity_score'],
                ];
            }
        }
    }

    $detectedDuplicates = array_slice($detectedDuplicates, 0, 15);
}

$pageTitle = 'Duplicate Report Detection';
ob_start();
?>

<?php echo renderNavbar($auth); ?>

<div class="container-fluid py-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/admin/dashboard">Admin Dashboard</a></li>
                        <li class="breadcrumb-item active">Duplicate Detection</li>
                    </ol>
                </nav>
                <h1 class="page-title">Duplicate Report Detection</h1>
                <p class="page-subtitle text-muted">
                    System detected <?php echo count($detectedDuplicates); ?> potential duplicate report(s).
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="/admin/duplicates?merged=0" class="btn btn-outline-gold btn-sm <?php echo !$showMerged ? 'active' : ''; ?>">
                    <i class="fas fa-bug me-1"></i>Pending
                </a>
                <a href="/admin/duplicates?merged=1" class="btn btn-outline-gold btn-sm <?php echo $showMerged ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle me-1"></i>Merged
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($detectedDuplicates)): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-5">
                <i class="fas fa-<?php echo $showMerged ? 'clipboard-check' : 'search'; ?> fa-3x text-muted mb-3"></i>
                <h4 class="h5 text-bulsumaroon">
                    <?php echo $showMerged ? 'No merged duplicates found' : 'No duplicate reports detected'; ?>
                </h4>
                <p class="text-muted">
                    <?php echo $showMerged ? 'No reports have been merged as duplicates yet.' : 'The system has not found any potential duplicate reports.'; ?>
                </p>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($detectedDuplicates as $item):
                $isPending = !$showMerged;
            ?>
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 h-100 duplicate-card">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-warning text-dark">
                                    Similarity: <?php echo round($item['similarity'] * 100, 1); ?>%
                                </span>
                            </div>
                            <?php if (!$isPending): ?>
                                <span class="badge bg-success">Merged</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6">
                                    <h5 class="h6 text-bulsumaroon fw-bold">Original Report</h5>
                                    <div class="bg-light p-2 rounded">
                                        <div class="fw-medium"><?php echo htmlspecialchars($item['report']['report_number']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($item['report']['title']); ?></div>
                                        <div class="small text-muted">
                                            <?php echo htmlspecialchars($item['report']['location_name']); ?>
                                        </div>
                                        <?php echo getReportStatusBadge($item['report']['status_code']); ?>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h5 class="h6 text-bulsumaroon fw-bold">Potential Duplicate</h5>
                                    <div class="bg-light p-2 rounded">
                                        <div class="fw-medium"><?php echo htmlspecialchars($item['duplicate']['report_number']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($item['duplicate']['title']); ?></div>
                                        <div class="small text-muted">
                                            <?php echo htmlspecialchars($item['duplicate']['location_name']); ?>
                                        </div>
                                        <?php echo getReportStatusBadge($item['duplicate']['status_code']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if ($isPending): ?>
                            <div class="card-footer bg-light border-0">
                                <form method="POST" action="/admin/duplicates/merge" class="d-flex gap-2">
                                    <?php echo CSRF::tokenField(); ?>
                                    <input type="hidden" name="original_id" value="<?php echo $item['report']['id']; ?>">
                                    <input type="hidden" name="duplicate_id" value="<?php echo $item['duplicate']['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm w-50">
                                        <i class="fas fa-compress me-1"></i>Merge
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm w-50"
                                            onclick="markNotDuplicate(<?php echo $item['duplicate']['id']; ?>)">
                                        <i class="fas fa-times me-1"></i>Not Duplicate
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php echo renderFooter(); ?>

<script>
function markNotDuplicate(reportId) {
    if (confirm('Mark this report as not a duplicate?')) {
        fetch('/admin/duplicates/not-duplicate', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'csrf_token=' + document.querySelector('input[name="csrf_token"]').value + '&report_id=' + reportId
        })
        .then(() => location.reload());
    }
}
</script>

<?php
$content = ob_get_clean();
renderPage($pageTitle, $content);

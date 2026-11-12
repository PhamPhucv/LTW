<?php
/**
 * index.php
 * Màn hình Dashboard (Màn hình 2)
 */

$currentPage = 'dashboard';
$pageTitle = 'Dashboard Tổng Quan';
$extraScripts = [
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js',
    'assets/js/dashboard.js'
];

require_once __DIR__ . '/includes/header.php';
?>

<main class="py-4">
    <div class="container-fluid px-lg-4">
        <!-- Tiêu đề trang & Lời chào -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
            <div>
                <h3 class="fw-bold mb-1">Dashboard Tổng Quan</h3>
                <p class="text-muted mb-0 small">
                    Xin chào, <strong class="text-dark"><?= e($currentUser['full_name']) ?></strong>! Chúc bạn một ngày làm việc hiệu quả.
                </p>
            </div>
            <div>
                <a href="customers.php" class="btn btn-primary d-inline-flex align-items-center shadow-sm">
                    <i class="bi bi-people-fill me-2"></i> Quản lý khách hàng ngay
                </a>
            </div>
        </div>

        <!-- 4 Card KPI Thống kê -->
        <div class="row g-3 mb-4">
            <!-- 1. Tổng khách hàng -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-kpi bg-white h-100 p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-medium">Tổng khách hàng</span>
                            <h3 class="fw-bold my-1 text-dark" id="kpiTotalActive">
                                <span class="placeholder-glow"><span class="placeholder col-6"></span></span>
                            </h3>
                            <span class="text-success small fw-medium"><i class="bi bi-check2-circle me-1"></i>Đang hoạt động</span>
                        </div>
                        <div class="kpi-icon-box bg-primary-subtle text-primary">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Khách mới trong tháng -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-kpi bg-white h-100 p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-medium">Khách mới tháng này</span>
                            <h3 class="fw-bold my-1 text-dark" id="kpiNewThisMonth">
                                <span class="placeholder-glow"><span class="placeholder col-6"></span></span>
                            </h3>
                            <span class="text-primary small fw-medium"><i class="bi bi-calendar-plus me-1"></i>Tháng <?= date('m/Y') ?></span>
                        </div>
                        <div class="kpi-icon-box bg-success-subtle text-success">
                            <i class="bi bi-person-plus"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Số nhóm khách hàng -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-kpi bg-white h-100 p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-medium">Nhóm phân loại</span>
                            <h3 class="fw-bold my-1 text-dark" id="kpiTotalGroups">
                                <span class="placeholder-glow"><span class="placeholder col-6"></span></span>
                            </h3>
                            <span class="text-info small fw-medium"><i class="bi bi-tag me-1"></i>Phân khúc khách</span>
                        </div>
                        <div class="kpi-icon-box bg-info-subtle text-info">
                            <i class="bi bi-tags"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Thùng rác (Xóa mềm) -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-kpi bg-white h-100 p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-medium">Trong thùng rác</span>
                            <h3 class="fw-bold my-1 text-dark" id="kpiTotalTrash">
                                <span class="placeholder-glow"><span class="placeholder col-6"></span></span>
                            </h3>
                            <span class="text-danger small fw-medium"><i class="bi bi-recycle me-1"></i>Có thể khôi phục</span>
                        </div>
                        <div class="kpi-icon-box bg-danger-subtle text-danger">
                            <i class="bi bi-trash"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Biểu đồ & Lịch sử hoạt động -->
        <div class="row g-4">
            <!-- Biểu đồ phân bổ nhóm khách hàng (Chart.js) -->
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-pie-chart-fill text-primary me-2"></i>Phân bổ khách theo nhóm
                        </h6>
                        <span class="badge bg-light text-muted border">Tỷ lệ %</span>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center align-items-center p-4">
                        <div style="position: relative; width: 100%; max-width: 320px; height: 300px;">
                            <canvas id="groupChart"></canvas>
                        </div>
                        <div id="chartLegend" class="mt-3 w-100 small d-flex flex-wrap justify-content-center gap-3"></div>
                    </div>
                </div>
            </div>

            <!-- 10 Hoạt động gần nhất (Activity Logs) -->
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-clock-history text-primary me-2"></i>10 Hoạt động mới nhất
                        </h6>
                        <span class="badge bg-secondary-subtle text-secondary border">activity_logs</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Thời gian</th>
                                        <th>Người thực hiện</th>
                                        <th>Hành động</th>
                                        <th class="pe-4">Đối tượng</th>
                                    </tr>
                                </thead>
                                <tbody id="activityLogsTableBody">
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                            Đang tải dữ liệu nhật ký...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

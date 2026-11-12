/**
 * assets/js/dashboard.js
 * Quản lý logic hiển thị số liệu Dashboard, biểu đồ Chart.js và nhật ký hoạt động
 */

$(document).ready(function () {
    loadDashboardData();
});

let myGroupChart = null;

function loadDashboardData() {
    $.ajax({
        url: 'api/dashboard.php',
        type: 'GET',
        dataType: 'json',
        success: function (res) {
            if (res.success) {
                renderKPIs(res.data.stats);
                renderChart(res.data.chart_data);
                renderActivityLogs(res.data.recent_logs);
            } else {
                showToast(res.message, 'danger');
            }
        },
        error: function () {
            showToast('Không thể kết nối đến máy chủ lấy dữ liệu Dashboard!', 'danger');
        }
    });
}

/**
 * Hiển thị số liệu 4 thẻ KPI
 */
function renderKPIs(stats) {
    $('#kpiTotalActive').text(stats.total_active.toLocaleString('vi-VN'));
    $('#kpiNewThisMonth').text(stats.new_this_month.toLocaleString('vi-VN'));
    $('#kpiTotalGroups').text(stats.total_groups.toLocaleString('vi-VN'));
    $('#kpiTotalTrash').text(stats.total_trash.toLocaleString('vi-VN'));
}

/**
 * Khởi tạo & Vẽ biểu đồ phân bổ nhóm khách hàng bằng Chart.js
 */
function renderChart(chartData) {
    const ctx = document.getElementById('groupChart');
    if (!ctx) return;

    if (myGroupChart) {
        myGroupChart.destroy();
    }

    myGroupChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: chartData.labels,
            datasets: [{
                data: chartData.datasets[0].data,
                backgroundColor: chartData.datasets[0].backgroundColor,
                hoverOffset: 6,
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 14,
                        padding: 15,
                        font: {
                            family: "'Inter', sans-serif",
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const val = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percent = total > 0 ? Math.round((val / total) * 100) : 0;
                            return ` ${context.label}: ${val} khách (${percent}%)`;
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });
}

/**
 * Hiển thị 10 hoạt động gần nhất vào bảng
 */
function renderActivityLogs(logs) {
    const tbody = $('#activityLogsTableBody');
    tbody.empty();

    if (!logs || logs.length === 0) {
        tbody.html('<tr><td colspan="4" class="text-center py-4 text-muted">Chưa có hoạt động nào được ghi lại.</td></tr>');
        return;
    }

    logs.forEach(log => {
        let badgeClass = 'bg-secondary';
        let actionLabel = log.action;

        switch (log.action) {
            case 'CREATE':
                badgeClass = 'bg-success';
                actionLabel = 'Thêm mới';
                break;
            case 'UPDATE':
                badgeClass = 'bg-primary';
                actionLabel = 'Cập nhật';
                break;
            case 'DELETE':
                badgeClass = 'bg-danger';
                actionLabel = 'Xóa mềm';
                break;
            case 'RESTORE':
                badgeClass = 'bg-warning text-dark';
                actionLabel = 'Khôi phục';
                break;
            case 'LOGIN':
                badgeClass = 'bg-info text-dark';
                actionLabel = 'Đăng nhập';
                break;
            case 'LOGOUT':
                badgeClass = 'bg-secondary';
                actionLabel = 'Đăng xuất';
                break;
        }

        let targetText = '-';
        if (log.target_table === 'customers') {
            targetText = `Khách hàng #${log.target_id}`;
        } else if (log.target_table === 'customer_groups') {
            targetText = `Nhóm #${log.target_id}`;
        } else if (log.target_table === 'care_notes') {
            targetText = `Chăm sóc KH #${log.target_id}`;
        } else if (log.target_table === 'auth') {
            targetText = 'Hệ thống';
        }

        const roleBadge = log.user_role === 'admin'
            ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Admin</span>'
            : '<span class="badge bg-info-subtle text-info border border-info-subtle">Staff</span>';

        const row = `
            <tr>
                <td class="ps-4 small text-muted text-nowrap">
                    <i class="bi bi-clock me-1"></i>${formatDateTimeVN(log.created_at)}
                </td>
                <td>
                    <div class="fw-semibold text-dark">${escapeHtml(log.user_name || 'Hệ thống')}</div>
                    <small>${roleBadge}</small>
                </td>
                <td>
                    <span class="badge ${badgeClass} text-uppercase" style="font-size: 0.75rem;">${actionLabel}</span>
                </td>
                <td class="pe-4 text-nowrap">
                    <code class="text-secondary fw-semibold">${escapeHtml(targetText)}</code>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

<?php

$currentPage = 'customers';
$pageTitle = 'Quản Lý Khách Hàng';
$extraScripts = [
    'assets/js/customers.js'
];

require_once __DIR__ . '/includes/header.php';
$isAdmin = ($currentUser['role'] === 'admin');
?>

<main class="py-4">
    <div class="container-fluid px-lg-4">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
            <div>
                <h3 class="fw-bold mb-1">Danh Sách Khách Hàng</h3>
                <p class="text-muted mb-0 small">Quản lý thông tin, phân loại và theo dõi lịch sử chăm sóc</p>
            </div>
            <div class="d-flex flex-wrap gap-2">

                <button type="button" id="btnExportCSV" class="btn btn-outline-success d-inline-flex align-items-center shadow-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-2"></i> Xuất CSV
                </button>

                <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center shadow-sm" data-bs-toggle="modal" data-bs-target="#groupModal">
                    <i class="bi bi-tags-fill me-2"></i> Quản lý nhóm
                </button>

                <?php if ($isAdmin): ?>
                <button type="button" class="btn btn-outline-danger d-inline-flex align-items-center shadow-sm position-relative" data-bs-toggle="modal" data-bs-target="#trashModal" id="btnOpenTrash">
                    <i class="bi bi-trash3 me-1"></i> Thùng rác
                    <span id="trashBadgeCount" class="badge bg-danger rounded-pill ms-1">0</span>
                </button>
                <?php endif; ?>

                <button type="button" class="btn btn-primary d-inline-flex align-items-center shadow-sm" id="btnOpenAddModal">
                    <i class="bi bi-plus-lg me-2"></i> Thêm khách hàng
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center">

                    <div class="col-12 col-md-4">
                        <div class="position-relative">
                            <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" id="searchInput" class="form-control ps-5 pe-5" placeholder="Tìm theo tên, email, SĐT...">
                            <div id="searchSpinner" class="spinner-border spinner-border-sm text-primary search-spinner" role="status"></div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <select id="filterGroup" class="form-select">
                            <option value="">-- Tất cả các nhóm --</option>

                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <select id="filterGender" class="form-select">
                            <option value="">-- Giới tính --</option>
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>

                    <div class="col-8 col-md-2">
                        <select id="sortBy" class="form-select">
                            <option value="id-desc">Mới nhất</option>
                            <option value="id-asc">Cũ nhất</option>
                            <option value="full_name-asc">Tên (A - Z)</option>
                            <option value="full_name-desc">Tên (Z - A)</option>
                            <option value="birthday-asc">Ngày sinh</option>
                        </select>
                    </div>

                    <div class="col-4 col-md-1 text-end">
                        <button type="button" id="btnResetFilter" class="btn btn-light border w-100" title="Đặt lại bộ lọc">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 60px;">Avatar</th>
                                <th>Khách hàng</th>
                                <th>Liên hệ</th>
                                <th>Nhóm</th>
                                <th>Địa chỉ</th>
                                <th>Ngày tạo</th>
                                <th class="text-end pe-4" style="min-width: 140px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="customersTableBody">
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    Đang tải dữ liệu khách hàng...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-white border-top py-3 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <div class="small text-muted" id="paginationInfo">
                    Đang tải thông tin bản ghi...
                </div>
                <nav aria-label="Pagination">
                    <ul class="pagination pagination-sm mb-0" id="paginationControls">

                    </ul>
                </nav>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="customerModalTitle">
                    <i class="bi bi-person-plus-fill text-primary me-2"></i>Thêm Khách Hàng Mới
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="customerForm" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" id="customerId" name="id" value="">
                    <input type="hidden" id="formAction" name="action" value="create">

                    <div id="formAlert" class="alert alert-danger d-none py-2 small mb-3"></div>

                    <div class="row g-3">

                        <div class="col-12 col-md-4 text-center border-end-md pb-3 pb-md-0">
                            <label class="form-label small fw-semibold d-block">Ảnh đại diện</label>
                            <div class="position-relative d-inline-block mb-3">
                                <img id="avatarPreview" src="assets/images/default-avatar.svg" onerror="this.src='https://ui-avatars.com/api/?name=User&background=e9ecef&color=495057&size=128'" class="avatar-preview-box shadow-sm" alt="Preview Avatar">
                            </div>
                            <div class="px-2">
                                <input type="file" class="form-control form-control-sm" id="avatarInput" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif">
                                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">JPG, PNG, WEBP (Tối đa 2MB)</small>
                            </div>
                        </div>

                        <div class="col-12 col-md-8">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="fullName" class="form-label small fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="fullName" name="full_name" placeholder="VD: Nguyễn Văn An" required>
                                </div>

                                <div class="col-12 col-sm-6">
                                    <label for="email" class="form-label small fw-semibold">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="VD: an.nguyen@gmail.com" required>
                                </div>

                                <div class="col-12 col-sm-6">
                                    <label for="phone" class="form-label small fw-semibold">Số điện thoại <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="VD: 0912345678" required>
                                </div>

                                <div class="col-12 col-sm-6">
                                    <label for="groupId" class="form-label small fw-semibold">Nhóm khách hàng</label>
                                    <select class="form-select" id="groupId" name="group_id">
                                        <option value="">-- Chưa phân nhóm --</option>
                                    </select>
                                </div>

                                <div class="col-12 col-sm-6">
                                    <label for="gender" class="form-label small fw-semibold">Giới tính</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="Khác">Khác</option>
                                        <option value="Nam">Nam</option>
                                        <option value="Nữ">Nữ</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="birthday" class="form-label small fw-semibold">Ngày sinh</label>
                                    <input type="date" class="form-control" id="birthday" name="birthday">
                                </div>

                                <div class="col-12">
                                    <label for="address" class="form-label small fw-semibold">Địa chỉ</label>
                                    <input type="text" class="form-control" id="address" name="address" placeholder="VD: 123 Cầu Giấy, Hà Nội">
                                </div>

                                <div class="col-12">
                                    <label for="note" class="form-label small fw-semibold">Ghi chú thêm</label>
                                    <textarea class="form-control" id="note" name="note" rows="2" placeholder="Thông tin đặc điểm, sở thích..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" id="btnSaveCustomer" class="btn btn-primary d-inline-flex align-items-center gap-2">
                        <span>Lưu thông tin</span>
                        <div id="saveSpinner" class="spinner-border spinner-border-sm d-none" role="status"></div>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="customerDetailModal" tabindex="-1" aria-labelledby="detailModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="detailModalTitle">
                    <i class="bi bi-person-vcard text-primary me-2"></i>Hồ Sơ & Lịch Sử Chăm Sóc
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-4">

                    <div class="col-12 col-lg-5 border-end-lg">
                        <div class="text-center pb-3 border-bottom">
                            <img id="detailAvatar" src="" class="avatar-preview-box mb-2" alt="Avatar">
                            <h5 class="fw-bold mb-1" id="detailFullName">-</h5>
                            <div id="detailGroupBadge" class="mb-2"></div>
                            <small class="text-muted d-block">Mã khách hàng: #<span id="detailId">-</span></small>
                        </div>

                        <div class="mt-3">
                            <h6 class="fw-bold text-dark small text-uppercase mb-3"><i class="bi bi-info-circle me-1"></i>Chi tiết liên hệ</h6>
                            <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
                                <li><strong>Email:</strong> <span id="detailEmail" class="text-muted">-</span></li>
                                <li><strong>SĐT:</strong> <span id="detailPhone" class="text-muted">-</span></li>
                                <li><strong>Giới tính:</strong> <span id="detailGender" class="text-muted">-</span></li>
                                <li><strong>Ngày sinh:</strong> <span id="detailBirthday" class="text-muted">-</span></li>
                                <li><strong>Địa chỉ:</strong> <span id="detailAddress" class="text-muted">-</span></li>
                                <li><strong>Người tạo:</strong> <span id="detailCreatedBy" class="text-muted">-</span></li>
                                <li><strong>Ngày tạo:</strong> <span id="detailCreatedAt" class="text-muted">-</span></li>
                            </ul>
                        </div>

                        <div class="mt-3 p-3 bg-light rounded-3">
                            <h6 class="fw-semibold text-dark small mb-1"><i class="bi bi-sticky me-1"></i>Ghi chú ban đầu:</h6>
                            <p id="detailNote" class="text-muted small mb-0">-</p>
                        </div>
                    </div>

                    <div class="col-12 col-lg-7">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="fw-bold text-dark mb-0">
                                <i class="bi bi-chat-left-dots-fill text-primary me-2"></i>Lịch sử tương tác & chăm sóc
                            </h6>
                            <span class="badge bg-secondary-subtle text-secondary" id="notesCountBadge">0 ghi chú</span>
                        </div>

                        <div class="card border bg-light-subtle mb-4">
                            <div class="card-body p-3">
                                <form id="addCareNoteForm">
                                    <input type="hidden" id="noteCustomerId" value="">
                                    <div class="mb-2">
                                        <textarea class="form-control form-control-sm" id="noteContent" rows="2" placeholder="Nhập nội dung cuộc gọi, tư vấn, gặp gỡ khách hàng..." required></textarea>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" id="btnSubmitNote" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-send-fill"></i>
                                            <span>Thêm ghi chú</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div id="careNotesTimeline" class="timeline" style="max-height: 400px; overflow-y: auto;">

                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="groupModal" tabindex="-1" aria-labelledby="groupModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="groupModalTitle">
                    <i class="bi bi-tags-fill text-primary me-2"></i>Quản Lý Nhóm Khách Hàng
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">

                <?php if ($isAdmin): ?>
                <form id="groupForm" class="mb-4 p-3 bg-light rounded-3 border">
                    <input type="hidden" id="groupIdField" value="">
                    <h6 class="fw-bold mb-2 small" id="groupFormTitle">Thêm nhóm mới</h6>
                    <div class="row g-2 align-items-center">
                        <div class="col-7">
                            <input type="text" id="groupNameField" class="form-control form-control-sm" placeholder="Tên nhóm (VD: VIP, Tiềm năng...)" required>
                        </div>
                        <div class="col-3">
                            <input type="color" id="groupColorField" class="form-control form-control-sm form-control-color w-100" value="#0d6efd" title="Chọn màu nhóm">
                        </div>
                        <div class="col-2">
                            <button type="submit" id="btnSaveGroup" class="btn btn-sm btn-primary w-100" title="Lưu">
                                <i class="bi bi-check-lg"></i>
                            </button>
                        </div>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-info py-2 small mb-3">
                    <i class="bi bi-info-circle me-1"></i> Nhân viên chỉ có quyền xem danh sách nhóm, không được chỉnh sửa.
                </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Màu</th>
                                <th>Tên nhóm</th>
                                <th class="text-center">Số khách</th>
                                <?php if ($isAdmin): ?>
                                <th class="text-end">Thao tác</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="groupsTableBody">

                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<div class="modal fade" id="trashModal" tabindex="-1" aria-labelledby="trashModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger" id="trashModalTitle">
                    <i class="bi bi-trash3-fill me-2"></i>Thùng Rác Khách Hàng (Xóa mềm)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <p class="text-muted small">
                    Danh sách khách hàng đã xóa mềm (`deleted_at`). Bạn có thể bấm nút <strong>Khôi phục</strong> để đưa khách hàng trở lại danh sách hoạt động bất cứ lúc nào.
                </p>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Họ tên</th>
                                <th>Email / SĐT</th>
                                <th>Thời gian xóa</th>
                                <th class="text-end">Khôi phục</th>
                            </tr>
                        </thead>
                        <tbody id="trashTableBody">

                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center p-3">
            <div class="modal-body">
                <div class="text-danger mb-3">
                    <i class="bi bi-exclamation-circle-fill display-4"></i>
                </div>
                <h5 class="fw-bold mb-2">Xác nhận chuyển vào thùng rác?</h5>
                <p class="text-muted small mb-0">
                    Khách hàng <strong id="deleteCustomerName"></strong> sẽ được chuyển vào thùng rác và có thể khôi phục lại bất kỳ lúc nào.
                </p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Hủy</button>
                <button type="button" id="btnConfirmDelete" class="btn btn-danger">Đồng ý xóa</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

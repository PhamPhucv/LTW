
let state = {
    page: 1,
    limit: 10,
    search: '',
    groupId: '',
    gender: '',
    sortBy: 'id',
    sortDir: 'desc'
};

const userRole = $('meta[name="user-role"]').attr('content') || 'staff';
const isAdmin = (userRole === 'admin');
let customerToDeleteId = null;

$(document).ready(function () {

    loadGroups();
    loadCustomers();
    if (isAdmin) {
        updateTrashCount();
    }

    const debouncedSearch = debounce(function () {
        state.search = $('#searchInput').val().trim();
        state.page = 1;
        loadCustomers(function () {
            $('#searchSpinner').hide();
        });
    }, 300);

    $('#searchInput').on('input', function () {
        $('#searchSpinner').show();
        debouncedSearch();
    });

    $('#filterGroup').on('change', function () {
        state.groupId = $(this).val();
        state.page = 1;
        loadCustomers();
    });

    $('#filterGender').on('change', function () {
        state.gender = $(this).val();
        state.page = 1;
        loadCustomers();
    });

    $('#sortBy').on('change', function () {
        const val = $(this).val().split('-');
        state.sortBy = val[0];
        state.sortDir = val[1];
        loadCustomers();
    });

    $('#btnResetFilter').on('click', function () {
        $('#searchInput').val('');
        $('#filterGroup').val('');
        $('#filterGender').val('');
        $('#sortBy').val('id-desc');
        state = {
            page: 1,
            limit: 10,
            search: '',
            groupId: '',
            gender: '',
            sortBy: 'id',
            sortDir: 'desc'
        };
        loadCustomers();
    });

    $('#btnOpenAddModal').on('click', function () {
        resetCustomerForm();
        $('#customerModalTitle').html('<i class="bi bi-person-plus-fill text-primary me-2"></i>Thêm Khách Hàng Mới');
        $('#formAction').val('create');
        $('#customerId').val('');
        $('#avatarPreview').attr('src', 'https://ui-avatars.com/api/?name=User&background=e9ecef&color=495057&size=128');
        $('#customerModal').modal('show');
    });

    $('#avatarInput').on('change', function () {
        const file = this.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                showToast('Dung lượng ảnh vượt quá 2MB!', 'warning');
                $(this).val('');
                return;
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#avatarPreview').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

    $('#customerForm').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const action = $('#formAction').val();
        const fullName = $('#fullName').val().trim();
        const email = $('#email').val().trim();
        const phone = $('#phone').val().trim();

        if (!fullName || !email || !phone) {
            showFormAlert('Vui lòng điền đầy đủ các trường bắt buộc (*)!');
            return;
        }

        const phoneRegex = /^(0)(3[2-9]|5[2689]|7[06-9]|8[1-9]|9[0-9])[0-9]{7}$/;
        if (!phoneRegex.test(phone)) {
            showFormAlert('Số điện thoại không hợp lệ! Vui lòng nhập số điện thoại Việt Nam 10 chữ số (VD: 0912345678).');
            return;
        }

        const formData = new FormData(this);
        const btnSave = $('#btnSaveCustomer');
        const spinner = $('#saveSpinner');

        btnSave.prop('disabled', true);
        spinner.removeClass('d-none');
        $('#formAlert').addClass('d-none');

        $.ajax({
            url: 'api/customers.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function (res) {
                btnSave.prop('disabled', false);
                spinner.addClass('d-none');

                if (res.success) {
                    $('#customerModal').modal('hide');
                    showToast(res.message, 'success');
                    loadCustomers();
                    loadGroups();
                } else {
                    showFormAlert(res.message);
                }
            },
            error: function (xhr) {
                btnSave.prop('disabled', false);
                spinner.addClass('d-none');
                let msg = 'Đã xảy ra lỗi khi lưu thông tin!';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showFormAlert(msg);
            }
        });
    });

    $(document).on('click', '.btn-delete-customer', function () {
        customerToDeleteId = $(this).data('id');
        const name = $(this).data('name');
        $('#deleteCustomerName').text(name);
        $('#deleteConfirmModal').modal('show');
    });

    $('#btnConfirmDelete').on('click', function () {
        if (!customerToDeleteId) return;

        const btn = $(this);
        btn.prop('disabled', true);

        $.ajax({
            url: 'api/customers.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'delete',
                id: customerToDeleteId
            },
            success: function (res) {
                btn.prop('disabled', false);
                $('#deleteConfirmModal').modal('hide');
                if (res.success) {
                    showToast(res.message, 'success');
                    loadCustomers();
                    updateTrashCount();
                    loadGroups();
                } else {
                    showToast(res.message, 'danger');
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false);
                $('#deleteConfirmModal').modal('hide');
                let msg = 'Không thể xóa khách hàng!';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showToast(msg, 'danger');
            }
        });
    });

    $('#btnOpenTrash').on('click', function () {
        loadTrashList();
    });

    $(document).on('click', '.btn-restore-customer', function () {
        const id = $(this).data('id');
        const row = $(this).closest('tr');

        $.ajax({
            url: 'api/customers.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'restore',
                id: id
            },
            success: function (res) {
                if (res.success) {
                    row.fadeOut(200, function () { $(this).remove(); });
                    showToast(res.message, 'success');
                    loadCustomers();
                    updateTrashCount();
                    loadGroups();
                } else {
                    showToast(res.message, 'danger');
                }
            },
            error: function () {
                showToast('Lỗi khi khôi phục khách hàng!', 'danger');
            }
        });
    });

    $(document).on('click', '.btn-view-customer', function () {
        const id = $(this).data('id');
        openCustomerDetailModal(id);
    });

    $('#addCareNoteForm').on('submit', function (e) {
        e.preventDefault();
        const customerId = $('#noteCustomerId').val();
        const content = $('#noteContent').val().trim();
        const btn = $('#btnSubmitNote');

        if (!content) return;

        btn.prop('disabled', true);

        $.ajax({
            url: 'api/notes.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'create',
                customer_id: customerId,
                content: content
            },
            success: function (res) {
                btn.prop('disabled', false);
                if (res.success) {
                    $('#noteContent').val('');
                    showToast(res.message, 'success');

                    prependNoteToTimeline(res.data);

                    const countBadge = $('#notesCountBadge');
                    const currentCount = parseInt(countBadge.text()) || 0;
                    countBadge.text(`${currentCount + 1} ghi chú`);
                } else {
                    showToast(res.message, 'danger');
                }
            },
            error: function () {
                btn.prop('disabled', false);
                showToast('Lỗi khi lưu ghi chú chăm sóc!', 'danger');
            }
        });
    });

    $(document).on('click', '.btn-edit-customer', function () {
        const id = $(this).data('id');
        openEditCustomerModal(id);
    });

    $('#groupForm').on('submit', function (e) {
        e.preventDefault();
        const id = $('#groupIdField').val();
        const name = $('#groupNameField').val().trim();
        const color = $('#groupColorField').val();
        const action = id ? 'update' : 'create';

        if (!name) return;

        $.ajax({
            url: 'api/groups.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: action,
                id: id,
                name: name,
                color: color
            },
            success: function (res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    $('#groupNameField').val('');
                    $('#groupIdField').val('');
                    $('#groupColorField').val('#0d6efd');
                    $('#groupFormTitle').text('Thêm nhóm mới');
                    loadGroups();
                    loadCustomers();
                } else {
                    showToast(res.message, 'danger');
                }
            },
            error: function () {
                showToast('Không thể lưu thông tin nhóm!', 'danger');
            }
        });
    });

    $(document).on('click', '.btn-edit-group', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const color = $(this).data('color');

        $('#groupIdField').val(id);
        $('#groupNameField').val(name);
        $('#groupColorField').val(color);
        $('#groupFormTitle').text('Chỉnh sửa nhóm #' + id);
        $('#groupNameField').focus();
    });

    $(document).on('click', '.btn-delete-group', function () {
        const id = $(this).data('id');
        if (!confirm('Bạn có chắc chắn muốn xóa nhóm này? Các khách hàng thuộc nhóm sẽ chuyển về trạng thái Chưa phân nhóm.')) {
            return;
        }

        $.ajax({
            url: 'api/groups.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'delete',
                id: id
            },
            success: function (res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    loadGroups();
                    loadCustomers();
                } else {
                    showToast(res.message, 'danger');
                }
            },
            error: function () {
                showToast('Lỗi khi xóa nhóm khách hàng!', 'danger');
            }
        });
    });

    $('#btnExportCSV').on('click', function () {
        const params = new URLSearchParams({
            search: state.search,
            group_id: state.groupId,
            gender: state.gender
        });
        window.location.href = `api/export.php?${params.toString()}`;
    });
});

function loadCustomers(callback) {
    const tbody = $('#customersTableBody');

    $.ajax({
        url: 'api/customers.php',
        type: 'GET',
        dataType: 'json',
        data: {
            action: 'list',
            page: state.page,
            limit: state.limit,
            search: state.search,
            group_id: state.groupId,
            gender: state.gender,
            sort_by: state.sortBy,
            sort_dir: state.sortDir,
            trash: 0
        },
        success: function (res) {
            if (res.success) {
                renderCustomersTable(res.data.customers);
                renderPagination(res.data.pagination);
            } else {
                tbody.html(`<tr><td colspan="7" class="text-center py-4 text-danger">${res.message}</td></tr>`);
            }
            if (typeof callback === 'function') callback();
        },
        error: function () {
            tbody.html('<tr><td colspan="7" class="text-center py-4 text-danger">Không thể tải danh sách khách hàng!</td></tr>');
            if (typeof callback === 'function') callback();
        }
    });
}

function renderCustomersTable(customers) {
    const tbody = $('#customersTableBody');
    tbody.empty();

    if (!customers || customers.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                    Không tìm thấy khách hàng nào phù hợp với điều kiện lọc!
                </td>
            </tr>
        `);
        return;
    }

    customers.forEach(c => {

        let avatarHtml = '';
        if (c.avatar) {
            avatarHtml = `<img src="${escapeHtml(c.avatar)}" class="avatar-img" alt="${escapeHtml(c.full_name)}" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(c.full_name)}&background=e9ecef&color=495057&size=64';">`;
        } else {
            const initials = c.full_name.charAt(0).toUpperCase();
            avatarHtml = `<div class="avatar-placeholder">${initials}</div>`;
        }

        let groupHtml = '<span class="badge bg-secondary-subtle text-secondary border">Chưa phân nhóm</span>';
        if (c.group_name) {
            groupHtml = `<span class="badge badge-group text-white" style="background-color: ${c.group_color || '#0d6efd'};">${escapeHtml(c.group_name)}</span>`;
        }

        const deleteBtnHtml = isAdmin ? `
            <button class="btn btn-sm btn-outline-danger btn-delete-customer" data-id="${c.id}" data-name="${escapeHtml(c.full_name)}" title="Xóa mềm">
                <i class="bi bi-trash"></i>
            </button>
        ` : '';

        const row = `
            <tr>
                <td class="ps-4">${avatarHtml}</td>
                <td>
                    <div class="fw-bold text-dark">${escapeHtml(c.full_name)}</div>
                    <small class="text-muted">
                        <i class="bi ${c.gender === 'Nam' ? 'bi-gender-male text-primary' : (c.gender === 'Nữ' ? 'bi-gender-female text-danger' : 'bi-gender-ambiguous text-secondary')}"></i>
                        ${escapeHtml(c.gender)} ${c.birthday ? '• ' + formatDateVN(c.birthday) : ''}
                    </small>
                </td>
                <td>
                    <div class="text-nowrap"><i class="bi bi-envelope me-1 text-muted"></i><a href="mailto:${escapeHtml(c.email)}" class="text-decoration-none">${escapeHtml(c.email)}</a></div>
                    <div class="small text-muted text-nowrap"><i class="bi bi-telephone me-1"></i>${escapeHtml(c.phone)}</div>
                </td>
                <td>${groupHtml}</td>
                <td class="small text-muted" style="max-width: 200px;">
                    <div class="text-truncate" title="${escapeHtml(c.address || '-')}">${escapeHtml(c.address || '-')}</div>
                </td>
                <td class="small text-muted text-nowrap">
                    ${formatDateVN(c.created_at)}
                </td>
                <td class="text-end pe-4 text-nowrap">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-info btn-view-customer" data-id="${c.id}" title="Xem chi tiết & Lịch sử chăm sóc">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-primary btn-edit-customer" data-id="${c.id}" title="Chỉnh sửa">
                            <i class="bi bi-pencil"></i>
                        </button>
                        ${deleteBtnHtml}
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function renderPagination(p) {
    const info = $('#paginationInfo');
    const controls = $('#paginationControls');
    controls.empty();

    if (p.total_items === 0) {
        info.text('Không có dữ liệu.');
        return;
    }

    const start = (p.current_page - 1) * p.limit + 1;
    const end = Math.min(p.current_page * p.limit, p.total_items);
    info.html(`Hiển thị <strong>${start} - ${end}</strong> trong tổng số <strong>${p.total_items}</strong> khách hàng`);

    if (p.total_pages <= 1) return;

    const prevDisabled = p.current_page === 1 ? 'disabled' : '';
    controls.append(`
        <li class="page-item ${prevDisabled}">
            <a class="page-link" href="#" data-page="${p.current_page - 1}"><i class="bi bi-chevron-left"></i></a>
        </li>
    `);

    let startPage = Math.max(1, p.current_page - 2);
    let endPage = Math.min(p.total_pages, p.current_page + 2);

    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === p.current_page ? 'active' : '';
        controls.append(`
            <li class="page-item ${activeClass}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `);
    }

    const nextDisabled = p.current_page === p.total_pages ? 'disabled' : '';
    controls.append(`
        <li class="page-item ${nextDisabled}">
            <a class="page-link" href="#" data-page="${p.current_page + 1}"><i class="bi bi-chevron-right"></i></a>
        </li>
    `);

    controls.find('a.page-link').on('click', function (e) {
        e.preventDefault();
        const targetPage = parseInt($(this).data('page'));
        if (targetPage && targetPage >= 1 && targetPage <= p.total_pages && targetPage !== p.current_page) {
            state.page = targetPage;
            loadCustomers();
        }
    });
}

function loadGroups() {
    $.ajax({
        url: 'api/groups.php',
        type: 'GET',
        dataType: 'json',
        data: { action: 'list' },
        success: function (res) {
            if (res.success) {
                const groups = res.data;
                const filterSelect = $('#filterGroup');
                const formSelect = $('#groupId');
                const groupTableBody = $('#groupsTableBody');

                const currentFilterVal = filterSelect.val();
                const currentFormVal = formSelect.val();

                filterSelect.find('option:not(:first)').remove();

                formSelect.find('option:not(:first)').remove();
                groupTableBody.empty();

                groups.forEach(g => {
                    filterSelect.append(`<option value="${g.id}">${escapeHtml(g.name)} (${g.total_customers})</option>`);
                    formSelect.append(`<option value="${g.id}">${escapeHtml(g.name)}</option>`);

                    const actionHtml = isAdmin ? `
                        <td class="text-end">
                            <button class="btn btn-sm btn-link p-0 text-primary me-2 btn-edit-group" data-id="${g.id}" data-name="${escapeHtml(g.name)}" data-color="${g.color}" title="Sửa">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn btn-sm btn-link p-0 text-danger btn-delete-group" data-id="${g.id}" title="Xóa">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    ` : '';

                    groupTableBody.append(`
                        <tr>
                            <td><span class="d-inline-block rounded-circle" style="width: 14px; height: 14px; background-color: ${g.color};"></span></td>
                            <td class="fw-semibold">${escapeHtml(g.name)}</td>
                            <td class="text-center"><span class="badge bg-light text-dark border">${g.total_customers}</span></td>
                            ${actionHtml}
                        </tr>
                    `);
                });

                filterSelect.val(currentFilterVal);
                formSelect.val(currentFormVal);
            }
        }
    });
}

function updateTrashCount() {
    $.ajax({
        url: 'api/customers.php',
        type: 'GET',
        dataType: 'json',
        data: { action: 'list', trash: 1, limit: 1 },
        success: function (res) {
            if (res.success) {
                const count = res.data.pagination.total_items || 0;
                $('#trashBadgeCount').text(count);
            }
        }
    });
}

function loadTrashList() {
    const tbody = $('#trashTableBody');
    tbody.html('<tr><td colspan="4" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-danger me-2"></div>Đang tải thùng rác...</td></tr>');

    $.ajax({
        url: 'api/customers.php',
        type: 'GET',
        dataType: 'json',
        data: { action: 'list', trash: 1, limit: 100 },
        success: function (res) {
            tbody.empty();
            if (res.success && res.data.customers.length > 0) {
                res.data.customers.forEach(c => {
                    tbody.append(`
                        <tr>
                            <td class="fw-bold">${escapeHtml(c.full_name)}</td>
                            <td class="small text-muted">
                                <div>${escapeHtml(c.email)}</div>
                                <div>${escapeHtml(c.phone)}</div>
                            </td>
                            <td class="small text-danger">
                                <i class="bi bi-clock me-1"></i>${formatDateTimeVN(c.deleted_at)}
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-success btn-restore-customer" data-id="${c.id}">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Khôi phục
                                </button>
                            </td>
                        </tr>
                    `);
                });
            } else {
                tbody.html('<tr><td colspan="4" class="text-center py-4 text-muted">Thùng rác trống. Không có khách hàng nào bị xóa.</td></tr>');
            }
        },
        error: function () {
            tbody.html('<tr><td colspan="4" class="text-center py-4 text-danger">Không thể tải dữ liệu thùng rác!</td></tr>');
        }
    });
}

function openCustomerDetailModal(id) {
    $('#detailModalTitle').html('<div class="spinner-border spinner-border-sm text-primary me-2"></div>Đang tải...');
    $('#customerDetailModal').modal('show');
    $('#noteCustomerId').val(id);

    $.ajax({
        url: 'api/customers.php',
        type: 'GET',
        dataType: 'json',
        data: { action: 'get', id: id },
        success: function (res) {
            if (res.success) {
                const c = res.data;
                $('#detailModalTitle').html(`<i class="bi bi-person-vcard text-primary me-2"></i>Hồ Sơ: ${escapeHtml(c.full_name)}`);
                $('#detailId').text(c.id);
                $('#detailFullName').text(c.full_name);
                $('#detailEmail').text(c.email);
                $('#detailPhone').text(c.phone);
                $('#detailGender').text(c.gender);
                $('#detailBirthday').text(c.birthday ? formatDateVN(c.birthday) : '-');
                $('#detailAddress').text(c.address || '-');
                $('#detailNote').text(c.note || 'Không có ghi chú');
                $('#detailCreatedBy').text(c.created_by_name || 'Hệ thống');
                $('#detailCreatedAt').text(formatDateTimeVN(c.created_at));

                const avatarImg = c.avatar
                    ? c.avatar
                    : `https://ui-avatars.com/api/?name=${encodeURIComponent(c.full_name)}&background=e9ecef&color=495057&size=128`;
                $('#detailAvatar').attr('src', avatarImg);

                if (c.group_name) {
                    $('#detailGroupBadge').html(`<span class="badge badge-group text-white" style="background-color: ${c.group_color || '#0d6efd'};">${escapeHtml(c.group_name)}</span>`);
                } else {
                    $('#detailGroupBadge').html('<span class="badge bg-secondary">Chưa phân nhóm</span>');
                }

                loadCareNotes(id);
            }
        }
    });
}

function loadCareNotes(customerId) {
    const timeline = $('#careNotesTimeline');
    timeline.html('<div class="text-center py-3 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Đang tải lịch sử...</div>');

    $.ajax({
        url: 'api/notes.php',
        type: 'GET',
        dataType: 'json',
        data: { action: 'list', customer_id: customerId },
        success: function (res) {
            timeline.empty();
            if (res.success) {
                const notes = res.data;
                $('#notesCountBadge').text(`${notes.length} ghi chú`);

                if (notes.length === 0) {
                    timeline.html('<p class="text-muted small text-center my-4">Chưa có lịch sử chăm sóc nào cho khách hàng này. Hãy thêm ghi chú đầu tiên ở trên!</p>');
                    return;
                }

                notes.forEach(note => {
                    prependNoteToTimeline(note);
                });
            }
        }
    });
}

function prependNoteToTimeline(note) {
    const timeline = $('#careNotesTimeline');

    if (timeline.find('p').length > 0) {
        timeline.empty();
    }

    const roleBadge = note.staff_role === 'admin'
        ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" style="font-size:0.65rem;">Admin</span>'
        : '<span class="badge bg-info-subtle text-info border border-info-subtle ms-1" style="font-size:0.65rem;">Staff</span>';

    const item = `
        <div class="timeline-item">
            <div class="timeline-content">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-semibold text-dark small">
                        ${escapeHtml(note.staff_name || 'Nhân viên')} ${roleBadge}
                    </span>
                    <span class="text-muted" style="font-size: 0.75rem;">
                        <i class="bi bi-clock me-1"></i>${formatDateTimeVN(note.created_at)}
                    </span>
                </div>
                <div class="small text-secondary" style="white-space: pre-line;">${escapeHtml(note.content)}</div>
            </div>
        </div>
    `;
    timeline.prepend(item);
}

function openEditCustomerModal(id) {
    resetCustomerForm();
    $('#customerModalTitle').html('<div class="spinner-border spinner-border-sm text-primary me-2"></div>Đang tải dữ liệu...');
    $('#customerModal').modal('show');

    $.ajax({
        url: 'api/customers.php',
        type: 'GET',
        dataType: 'json',
        data: { action: 'get', id: id },
        success: function (res) {
            if (res.success) {
                const c = res.data;
                $('#customerModalTitle').html(`<i class="bi bi-pencil-square text-primary me-2"></i>Chỉnh Sửa: ${escapeHtml(c.full_name)}`);
                $('#formAction').val('update');
                $('#customerId').val(c.id);
                $('#fullName').val(c.full_name);
                $('#email').val(c.email);
                $('#phone').val(c.phone);
                $('#groupId').val(c.group_id || '');
                $('#gender').val(c.gender);
                $('#birthday').val(c.birthday || '');
                $('#address').val(c.address || '');
                $('#note').val(c.note || '');

                const previewUrl = c.avatar
                    ? c.avatar
                    : `https://ui-avatars.com/api/?name=${encodeURIComponent(c.full_name)}&background=e9ecef&color=495057&size=128`;
                $('#avatarPreview').attr('src', previewUrl);
            } else {
                showToast(res.message, 'danger');
                $('#customerModal').modal('hide');
            }
        },
        error: function () {
            showToast('Không thể lấy thông tin khách hàng!', 'danger');
            $('#customerModal').modal('hide');
        }
    });
}

function resetCustomerForm() {
    $('#customerForm')[0].reset();
    $('#formAlert').addClass('d-none');
    $('#avatarPreview').attr('src', 'https://ui-avatars.com/api/?name=User&background=e9ecef&color=495057&size=128');
    $('#avatarInput').val('');
}

function showFormAlert(message) {
    $('#formAlert').removeClass('d-none').html(`<i class="bi bi-exclamation-triangle-fill me-1"></i> ${escapeHtml(message)}`);
}

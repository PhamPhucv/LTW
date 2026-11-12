
$(document).ready(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    if (csrfToken) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
    }
});

function showToast(message, type = 'success') {
    const toastEl = document.getElementById('liveToast');
    const toastMessage = document.getElementById('toastMessage');
    const toastIcon = document.getElementById('toastIcon');

    if (!toastEl) return;

    toastEl.className = 'toast align-items-center border-0 shadow-lg text-white';
    toastIcon.className = 'bi me-2 fs-5';

    switch (type) {
        case 'success':
            toastEl.classList.add('bg-success');
            toastIcon.classList.add('bi-check-circle-fill');
            break;
        case 'danger':
        case 'error':
            toastEl.classList.add('bg-danger');
            toastIcon.classList.add('bi-exclamation-triangle-fill');
            break;
        case 'warning':
            toastEl.classList.add('bg-warning', 'text-dark');
            toastIcon.classList.add('bi-exclamation-circle-fill');
            break;
        case 'info':
        default:
            toastEl.classList.add('bg-primary');
            toastIcon.classList.add('bi-info-circle-fill');
            break;
    }

    toastMessage.textContent = message;
    const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
    toast.show();
}

function debounce(func, wait = 300) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

function escapeHtml(text) {
    if (!text && text !== 0) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function formatDateVN(dateStr) {
    if (!dateStr) return '-';
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
}

function formatDateTimeVN(dateTimeStr) {
    if (!dateTimeStr) return '-';
    const d = new Date(dateTimeStr.replace(' ', 'T'));
    if (isNaN(d.getTime())) return dateTimeStr;
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

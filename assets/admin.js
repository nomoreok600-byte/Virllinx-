// Admin AJAX helpers (CSRF token comes from window.VL_CSR injected by footer)
function vlAdminCsrf() {
    var input = document.querySelector('input[name="csrf"]');
    return (input && input.value) || window.VL_CSR || '';
}

function adminPost(body, cb) {
    fetch(window.location.pathname, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body + '&csrf=' + encodeURIComponent(vlAdminCsrf())
    })
    .then(function (r) { return r.json(); })
    .then(cb);
}

function deleteRow(action, id, label) {
    if (!confirm('Delete this ' + label + '? This cannot be undone.')) return;
    adminPost('ajax_action=' + action + '&id=' + id, function (data) {
        if (data.success) location.reload();
        else alert(data.message || 'Action failed');
    });
}

function toggleField(action, id) {
    adminPost('ajax_action=' + action + '&id=' + id, function (data) {
        if (data.success) location.reload();
        else alert(data.message || 'Action failed');
    });
}

// Select-all checkbox for bulk actions
document.addEventListener('DOMContentLoaded', function () {
    var selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('input[name="ids[]"]').forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
        });
    }

    // Live table filter
    var filter = document.getElementById('tableFilter');
    if (filter) {
        filter.addEventListener('input', function () {
            var q = filter.value.toLowerCase();
            document.querySelectorAll('table tbody tr').forEach(function (tr) {
                tr.style.display = tr.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
            });
        });
    }
});

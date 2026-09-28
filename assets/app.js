// ---------- YouTube-style sidebar toggle (also defined in footer for reliability) ----------
if (typeof toggleSidebar !== 'function') {
    window.toggleSidebar = function () {
        var isMobile = window.innerWidth <= 900;
        if (isMobile) {
            document.body.classList.toggle('sidebar-open-mobile');
        } else {
            document.body.classList.toggle('sidebar-collapsed');
            try { localStorage.setItem('vl_sidebar', document.body.classList.contains('sidebar-collapsed') ? '0' : '1'); } catch (e) {}
        }
    };
}

// ---------- Copy link (no alert popup) ----------
function copyLink(url) {
    var done = function () {
        var btn = event && event.currentTarget;
        if (btn) {
            var old = btn.innerHTML;
            btn.innerHTML = old.replace(/Copy Link/, 'Copied!');
            setTimeout(function () { btn.innerHTML = old; }, 1600);
        }
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(done).catch(function () { prompt('Copy this link:', url); });
    } else {
        prompt('Copy this link:', url);
    }
}

// Close user dropdown on outside click
document.addEventListener('click', function (e) {
    var dd = document.querySelector('.user-dropdown');
    if (dd && dd.classList.contains('open') && !e.target.closest('.user-menu')) {
        dd.classList.remove('open');
    }
});

// Like video (watch page)
function likeVideo(videoId) {
    var btn = document.getElementById('likeBtn');
    fetch(window.location.pathname, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=like&id=' + encodeURIComponent(videoId)
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (data.success) {
            document.getElementById('likeCount').textContent = data.likes;
            if (btn) btn.classList.toggle('liked', data.liked);
        } else if (data.message) {
            alert(data.message);
        }
    });
}

// Like comment
function likeComment(commentId) {
    fetch(window.location.pathname, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=like_comment&comment_id=' + encodeURIComponent(commentId)
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (data.success) {
            var el = document.getElementById('commentLikes-' + commentId);
            if (el) el.textContent = data.likes;
        } else if (data.message) {
            alert(data.message);
        }
    });
}

// Enable comment submit button when text is present
document.addEventListener('DOMContentLoaded', function () {
    var ta = document.getElementById('commentText');
    var btn = document.getElementById('submitBtn');
    if (ta && btn) {
        ta.addEventListener('input', function () {
            btn.disabled = ta.value.trim().length === 0;
        });
    }
});

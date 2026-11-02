// 删除确认：所有带 data-confirm 的表单提交前弹窗确认
document.addEventListener('submit', function (ev) {
    var form = ev.target;
    var msg = form.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) {
        ev.preventDefault();
    }
});
// 防止评论/发帖表单被快速连点重复提交：提交后禁用按钮
document.addEventListener('submit', function (ev) {
    var btn = ev.target.querySelector('button[type="submit"], input[type="submit"]');
    if (btn) { btn.disabled = true; btn.textContent = '提交中…'; }
});

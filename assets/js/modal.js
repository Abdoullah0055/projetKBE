function showCustomConfirm(message, title) {
    title = title || 'Confirmation';
    return new Promise(function (resolve) {
        var modal = document.getElementById('custom-modal');
        var msgPara = document.getElementById('modal-message');
        var titleH3 = document.getElementById('modal-title');
        var btnConfirm = document.getElementById('modal-btn-confirm');
        var btnCancel = document.getElementById('modal-btn-cancel');

        msgPara.innerText = message;
        titleH3.innerText = title;
        modal.style.display = 'flex';

        var close = function (result) {
            modal.style.display = 'none';
            btnConfirm.onclick = null;
            btnCancel.onclick = null;
            modal.onclick = null;
            resolve(result);
        };

        btnConfirm.onclick = function () { close(true); };
        btnCancel.onclick = function () { close(false); };
        modal.onclick = function (e) {
            if (e.target === modal) close(false);
        };
    });
}

function showToast(message, type) {
    type = type || 'succes';
    var oldAlert = document.querySelector('.alert-box');
    if (oldAlert) oldAlert.remove();

    var box = document.createElement('div');
    box.className = 'alert-box ' + type;
    var iconClass = type === 'succes' ? 'fa-check-circle' : 'fa-exclamation-triangle';
    box.innerHTML = '<i class="fa-solid ' + iconClass + '"></i> ' + message;
    document.body.appendChild(box);

    setTimeout(function () {
        if (box.parentNode) box.remove();
    }, 3000);
}

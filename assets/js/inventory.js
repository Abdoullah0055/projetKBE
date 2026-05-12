function updateRecommendations(newHp, maxHp) {
var missingHP = Math.max(0, maxHp - newHp);
var allBtns = document.querySelectorAll('.btn-use-item');
console.log('[reco] updateRecommendations called, newHp=' + newHp + ', maxHp=' + maxHp + ', missingHP=' + missingHP + ', btns=' + allBtns.length);

allBtns.forEach(function (b) {
var oldBadge = b.querySelector('.recommended-badge');
if (oldBadge) {
console.log('[reco] removing old badge from item ' + b.dataset.itemId);
oldBadge.remove();
}
b.removeAttribute('data-is-recommended');
b.dataset.wouldWaste = '0';
});

var bestBtn = null;
var bestScore = -Infinity;
var bestReason = '';

if (missingHP > 0) {
allBtns.forEach(function (b) {
var hv = parseInt(b.dataset.healValue) || 3;
var effectiveHeal = Math.min(hv, missingHP);
var waste = hv > effectiveHeal;
b.dataset.wouldWaste = waste ? '1' : '0';

var score = waste ? -hv : hv;
console.log('[reco] item ' + b.dataset.itemId + ' heal=' + hv + ' waste=' + waste + ' score=' + score);
if (score > bestScore) {
bestScore = score;
bestBtn = b;
if (!waste) {
bestReason = 'Soin optimal : ' + hv + ' PV pour ' + missingHP + ' PV manquants';
} else {
bestReason = 'Moindre gaspillage : ' + hv + ' PV (surcap de ' + (hv - missingHP) + ' PV)';
}
}
});
}

if (bestBtn && missingHP > 0) {
bestBtn.dataset.isRecommended = '1';
var badge = document.createElement('span');
badge.className = 'recommended-badge';
badge.dataset.tooltip = bestReason;
badge.innerHTML = '<i class="fa-solid fa-star"></i> Recommande';
bestBtn.appendChild(document.createTextNode(' '));
bestBtn.appendChild(badge);
console.log('[reco] new badge on item ' + bestBtn.dataset.itemId + ': ' + bestReason);
} else {
console.log('[reco] no recommendation (missingHP=' + missingHP + ', bestBtn=' + !!bestBtn + ')');
}
}

document.addEventListener('DOMContentLoaded', function () {
document.querySelectorAll('.btn-use-item').forEach(function (btn) {
btn.addEventListener('click', async function () {
var itemId = this.dataset.itemId;
var itemName = this.dataset.itemName;
var wouldWaste = this.dataset.wouldWaste === '1';
var healValue = parseInt(this.dataset.healValue) || 3;

if (wouldWaste) {
var wasteConfirmed = await showCustomConfirm(
'Cet objet va partiellement gaspiller son effet (PV presque au max). Le soin de ' + healValue + ' PV sera partiellement perdu. Continuer ?',
'Gaspillage potentiel'
);
if (!wasteConfirmed) return;
} else {
var confirmed = await showCustomConfirm('Utiliser ' + itemName + ' pour regagner des PV ?', 'Utiliser un objet');
if (!confirmed) return;
}

btn.disabled = true;

var formData = new FormData();
formData.append('item_id', itemId);

fetch('backend/use_item.php', {
method: 'POST',
body: formData
})
.then(function (r) { return r.json(); })
.then(function (data) {
if (data.success) {
showToast(data.message, 'succes');

var hpValue = document.querySelector('.hp-value');
var hpFill = document.querySelector('.hp-bar-fill');
if (hpValue) hpValue.textContent = data.new_hp + '/' + data.max_hp;
if (hpFill) hpFill.style.width = Math.round(data.new_hp / data.max_hp * 100) + '%';

var drawerHpValue = document.querySelectorAll('.hp-value');
var drawerHpFill = document.querySelectorAll('.hp-bar-fill');
drawerHpValue.forEach(function(el) {
el.textContent = data.new_hp + '/' + data.max_hp;
});
drawerHpFill.forEach(function(el) {
el.style.width = Math.round(data.new_hp / data.max_hp * 100) + '%';
});

if (data.item_consumed) {
btn.closest('.inventory-slot').remove();
} else {
var qtyBadge = btn.closest('.inventory-slot').querySelector('.slot-qty-badge');
if (qtyBadge) {
var currentQty = parseInt(qtyBadge.textContent.replace('x', '')) - 1;
qtyBadge.textContent = 'x' + currentQty;
}
}

updateRecommendations(data.new_hp, data.max_hp);
} else {
showToast(data.message, 'erreur');
}
})
.catch(function () {
showToast('Erreur de connexion', 'erreur');
})
.finally(function () {
btn.disabled = false;
});
});
});

  document.querySelectorAll('.btn-sell-item').forEach(function (btn) {
  btn.addEventListener('click', async function () {
    var itemId = this.dataset.itemId;
    var itemName = this.dataset.itemName;
    var sellGold = this.dataset.sellGold || '0';
    var sellSilver = this.dataset.sellSilver || '0';
    var sellBronze = this.dataset.sellBronze || '0';
    var originalGold = this.dataset.originalGold || '0';
    var originalSilver = this.dataset.originalSilver || '0';
    var originalBronze = this.dataset.originalBronze || '0';
    var multiplier = parseFloat(this.dataset.multiplier || '0.6');
    var percentText = Math.round(multiplier * 100);

    var originalText = originalGold + ' GP | ' + originalSilver + ' SP | ' + originalBronze + ' BP';
    var sellText = sellGold + ' GP | ' + sellSilver + ' SP | ' + sellBronze + ' BP';
    var confirmMsg = 'Vendre ' + itemName + ' ?\n\n' +
      'Prix original : ' + originalText + '\n' +
      'Pourcentage de revente : ' + percentText + '%\n' +
      'Prix de revente : ' + sellText;
    var confirmed = await showCustomConfirm(confirmMsg, 'Vendre un objet');
    if (!confirmed) return;

btn.disabled = true;

var formData = new FormData();
formData.append('item_id', itemId);

fetch('backend/vendre_item.php', {
method: 'POST',
body: formData
})
.then(function (r) { return r.json(); })
.then(function (data) {
if (data.success) {
showToast(data.message, 'succes');

var walletSpans = document.querySelectorAll('.user-wallet span');
if (data.new_balance && walletSpans.length >= 3) {
walletSpans[0].textContent = data.new_balance.gold + ' G';
walletSpans[1].textContent = data.new_balance.silver + ' S';
walletSpans[2].textContent = data.new_balance.bronze + ' B';
}

if (data.item_consumed) {
btn.closest('.inventory-slot').remove();
} else {
var qtyBadge = btn.closest('.inventory-slot').querySelector('.slot-qty-badge');
if (qtyBadge) {
var currentQty = parseInt(qtyBadge.textContent.replace('x', '')) - 1;
qtyBadge.textContent = 'x' + currentQty;
}
}
} else {
showToast(data.message, 'erreur');
}
})
.catch(function () {
showToast('Erreur de connexion', 'erreur');
})
.finally(function () {
btn.disabled = false;
});
});
});
});

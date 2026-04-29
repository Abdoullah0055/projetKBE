document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-use-item').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var itemId = this.dataset.itemId;
            var itemName = this.dataset.itemName;

            if (!confirm('Utiliser ' + itemName + ' pour regagner des PV ?')) return;

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
                    alert(data.message);

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
                } else {
                    alert(data.message);
                }
            })
            .catch(function () {
                alert('Erreur de connexion');
            })
.finally(function () {
      btn.disabled = false;
    });
  });
});

document.querySelectorAll('.btn-sell-item').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var itemId = this.dataset.itemId;
      var itemName = this.dataset.itemName;

      if (!confirm('Vendre ' + itemName + ' ?')) return;

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
          alert(data.message);

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
          alert(data.message);
        }
      })
      .catch(function () {
        alert('Erreur de connexion');
      })
      .finally(function () {
        btn.disabled = false;
      });
    });
  });
});

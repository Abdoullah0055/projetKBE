document.addEventListener('DOMContentLoaded', function () {
  function renderStarsMarkup(value) {
    const rating = Math.max(0, Math.min(5, Number(value) || 0));
    const rounded = Math.round(rating * 2) / 2;
    const full = Math.floor(rounded);
    const hasHalf = rounded - full >= 0.5;
    const empty = 5 - full - (hasHalf ? 1 : 0);
    const parts = ['<span class="rating-stars" aria-hidden="true">'];

    for (let i = 0; i < full; i += 1) {
      parts.push('<i class="fa-solid fa-star"></i>');
    }
    if (hasHalf) {
      parts.push('<i class="fa-solid fa-star-half-stroke"></i>');
    }
    for (let i = 0; i < empty; i += 1) {
      parts.push('<i class="fa-regular fa-star"></i>');
    }

    parts.push('</span>');
    return parts.join('');
  }

  function parseHpFromUi() {
    const hpNode = document.querySelector('.hp-value');
    if (!hpNode) {
      return { current: 100, max: 100 };
    }

    const match = String(hpNode.textContent || '').match(/(\d+)\s*\/\s*(\d+)/);
    if (!match) {
      return { current: 100, max: 100 };
    }

    return {
      current: Number.parseInt(match[1], 10) || 100,
      max: Number.parseInt(match[2], 10) || 100,
    };
  }

  function updateHpUi(currentHp, maxHp) {
    const safeMax = Math.max(1, Number(maxHp) || 1);
    const safeCurrent = Math.max(0, Math.min(safeMax, Number(currentHp) || 0));
    const width = Math.round((safeCurrent / safeMax) * 100);

    document.querySelectorAll('.hp-value').forEach(function (node) {
      node.textContent = safeCurrent + '/' + safeMax;
    });

    document.querySelectorAll('.hp-bar-fill').forEach(function (node) {
      node.style.width = width + '%';
    });
  }

  function refreshHealRecommendations(currentHp, maxHp) {
    const buttons = Array.from(document.querySelectorAll('.btn-use-item'));
    const missingHp = Math.max(0, (Number(maxHp) || 0) - (Number(currentHp) || 0));

    let bestButton = null;
    let bestEffective = -1;
    let bestWasted = Number.MAX_SAFE_INTEGER;

    buttons.forEach(function (btn) {
      const healAmount = Math.max(0, Number.parseInt(btn.dataset.healAmount || '0', 10) || 0);
      const effective = Math.min(healAmount, missingHp);
      const wasted = Math.max(0, healAmount - effective);

      btn.dataset.effectiveHeal = String(effective);
      btn.dataset.wastedHeal = String(wasted);
      btn.dataset.isRecommended = '0';

      if (
        effective > bestEffective ||
        (effective === bestEffective && wasted < bestWasted)
      ) {
        bestEffective = effective;
        bestWasted = wasted;
        bestButton = btn;
      }
    });

    if (bestButton && bestEffective > 0 && missingHp > 0) {
      bestButton.dataset.isRecommended = '1';
    }

    const hpMaxed = missingHp <= 0;

    buttons.forEach(function (btn) {
      if (hpMaxed) {
        btn.classList.add('is-disabled-hp');
        btn.disabled = true;
      } else {
        btn.classList.remove('is-disabled-hp');
        btn.disabled = false;
      }

      const slot = btn.closest('.inventory-slot');
      if (!slot) {
        return;
      }

      const recommendBadge = slot.querySelector('.recommend-badge');
      const warningNode = slot.querySelector('.overheal-warning');
      const isRecommended = btn.dataset.isRecommended === '1';

      if (recommendBadge) {
        recommendBadge.classList.toggle('is-active', isRecommended);
      }

      if (warningNode) {
        const wasted = Number.parseInt(btn.dataset.wastedHeal || '0', 10) || 0;
        if (wasted > 0 && !hpMaxed) {
          warningNode.textContent = 'Attention: ' + wasted + ' PV seraient perdus.';
          warningNode.style.display = 'block';
        } else {
          warningNode.style.display = 'none';
        }
      }
    });
  }

  function updateSlotQuantity(slot) {
    if (!slot) {
      return;
    }

    const badge = slot.querySelector('.slot-qty-badge');
    const owned = slot.querySelector('.slot-owned');
    const currentQty = Number.parseInt(String(slot.dataset.itemQuantity || '1'), 10) || 1;
    const nextQty = Math.max(0, currentQty - 1);

    slot.dataset.itemQuantity = String(nextQty);

    if (badge) {
      badge.textContent = 'x' + nextQty;
    }

    if (owned) {
      owned.textContent = 'Quantite possedee: ' + nextQty;
    }
  }

  async function handleUseItemClick(btn) {
    const itemId = btn.dataset.itemId;
    const itemName = btn.dataset.itemName || 'cet objet';
    const healAmount = Number.parseInt(btn.dataset.healAmount || '0', 10) || 0;
    const effective = Number.parseInt(btn.dataset.effectiveHeal || '0', 10) || 0;
    const wasted = Number.parseInt(btn.dataset.wastedHeal || '0', 10) || 0;
    const recommended = btn.dataset.isRecommended === '1';

    let confirmMessage = 'Utiliser ' + itemName + ' ?\n\n'
      + 'Soin brut: +' + healAmount + ' PV\n'
      + 'Soin effectif estime: +' + effective + ' PV';

    if (wasted > 0) {
      confirmMessage += '\nPV perdus (sur-soin): ' + wasted;
    }

    if (recommended) {
      confirmMessage += '\n\nCet item est recommande pour votre etat actuel.';
    }

    const confirmed = await showCustomConfirm(confirmMessage, 'Utiliser un objet');
    if (!confirmed) {
      return;
    }

    btn.disabled = true;

    const formData = new FormData();
    formData.append('item_id', itemId || '0');

    fetch('backend/use_item.php', {
      method: 'POST',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) {
          showToast(data.message || 'Action impossible', 'erreur');
          return;
        }

        showToast(data.message || 'Item utilise', 'succes');

        const newHp = Number.parseInt(data.new_hp, 10) || 0;
        const maxHp = Number.parseInt(data.max_hp, 10) || 1;
        updateHpUi(newHp, maxHp);

        const slot = btn.closest('.inventory-slot');
        if (data.item_consumed) {
          if (slot) {
            slot.remove();
          }
        } else {
          updateSlotQuantity(slot);
        }

        refreshHealRecommendations(newHp, maxHp);
      })
      .catch(function () {
        showToast('Erreur de connexion', 'erreur');
      })
      .finally(function () {
        if (!btn.classList.contains('is-disabled-hp')) {
          btn.disabled = false;
        }
      });
  }

  document.addEventListener('click', function (event) {
    const useBtn = event.target.closest('.btn-use-item');
    if (useBtn) {
      handleUseItemClick(useBtn);
      return;
    }

    const deleteBtn = event.target.closest('.btn-delete-review');
    if (deleteBtn) {
      const reviewId = deleteBtn.dataset.reviewId;
      const itemId = deleteBtn.dataset.itemId;

      if (!reviewId) {
        showToast('Avis invalide.', 'erreur');
        return;
      }

      showCustomConfirm('Retirer votre avis pour cet item ?', 'Supprimer mon avis').then(function (ok) {
        if (!ok) {
          return;
        }

        const payload = new FormData();
        payload.append('review_id', reviewId);

        fetch('backend/supprimer_review.php', {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: payload,
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (!data.success) {
              showToast(data.message || 'Suppression impossible', 'erreur');
              return;
            }

            showToast(data.message || 'Avis supprime', 'succes');

            const slot = itemId ? document.querySelector('.inventory-slot[data-item-id="' + itemId + '"]') : null;
            if (slot) {
              slot.dataset.userReviewId = '0';

              const statusNode = slot.querySelector('.slot-status');
              if (statusNode) {
                statusNode.textContent = 'Non evalue';
                statusNode.classList.remove('is-rated');
                statusNode.classList.add('is-unrated');
              }

              const reviewCountNode = slot.querySelector('.slot-review-count');
              if (reviewCountNode && Number.isFinite(Number(data.reviewCount))) {
                reviewCountNode.textContent = String(Number(data.reviewCount));
              }

              const ratingLineNode = slot.querySelector('.slot-rating-line');
              if (ratingLineNode && Number.isFinite(Number(data.rating))) {
                const parsed = Number.parseFloat(String(data.rating).replace(',', '.'));
                ratingLineNode.innerHTML =
                  renderStarsMarkup(parsed) +
                  '<span class="rating-value-inline slot-rating-value">' + parsed.toFixed(1) + '/5</span>';
              }
            }

            deleteBtn.remove();
          })
          .catch(function () {
            showToast('Erreur de connexion', 'erreur');
          });
      });

      return;
    }
  });

  document.querySelectorAll('.btn-sell-item').forEach(function (btn) {
    btn.addEventListener('click', async function () {
      const itemId = this.dataset.itemId;
      const itemName = this.dataset.itemName;
      const sellGold = this.dataset.sellGold || '0';
      const sellSilver = this.dataset.sellSilver || '0';
      const sellBronze = this.dataset.sellBronze || '0';
      const originalGold = this.dataset.originalGold || '0';
      const originalSilver = this.dataset.originalSilver || '0';
      const originalBronze = this.dataset.originalBronze || '0';
      const multiplier = parseFloat(this.dataset.multiplier || '0.6');
      const percentText = Math.round(multiplier * 100);

      const originalText = originalGold + ' GP | ' + originalSilver + ' SP | ' + originalBronze + ' BP';
      const sellText = sellGold + ' GP | ' + sellSilver + ' SP | ' + sellBronze + ' BP';
      const confirmMsg = 'Vendre ' + itemName + ' ?\n\n'
        + 'Prix original : ' + originalText + '\n'
        + 'Pourcentage de revente : ' + percentText + '%\n'
        + 'Prix de revente : ' + sellText;

      const confirmed = await showCustomConfirm(confirmMsg, 'Vendre un objet');
      if (!confirmed) {
        return;
      }

      btn.disabled = true;

      const formData = new FormData();
      formData.append('item_id', itemId || '0');

      fetch('backend/vendre_item.php', {
        method: 'POST',
        body: formData,
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data.success) {
            showToast(data.message || 'Vente impossible', 'erreur');
            return;
          }

          showToast(data.message || 'Item vendu !', 'succes');

          const walletSpans = document.querySelectorAll('.user-wallet span');
          if (data.new_balance && walletSpans.length >= 3) {
            walletSpans[0].textContent = data.new_balance.gold + ' G';
            walletSpans[1].textContent = data.new_balance.silver + ' S';
            walletSpans[2].textContent = data.new_balance.bronze + ' B';
          }

          const slot = btn.closest('.inventory-slot');
          if (data.item_consumed) {
            if (slot) {
              slot.remove();
            }
          } else {
            updateSlotQuantity(slot);
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

  const hp = parseHpFromUi();
  refreshHealRecommendations(hp.current, hp.max);
});

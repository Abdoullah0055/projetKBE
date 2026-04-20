document.addEventListener("DOMContentLoaded", function () {
  const confirmForms = document.querySelectorAll(".confirm-form");

  confirmForms.forEach((form) => {
    const confirmText = (form.dataset.confirmText || "").trim();
    const finalConfirm = form.dataset.finalConfirm || "Confirmer ?";
    const input = form.querySelector('input[name="confirmation_text"]');
    const submitButton = form.querySelector('button[type="submit"]');
    const checkbox = form.querySelector('input[type="checkbox"]');

    if (!input || !submitButton) {
      return;
    }

    const refreshButtonState = () => {
      const phraseOk = input.value.trim() === confirmText;
      const checkOk = checkbox ? checkbox.checked : true;
      submitButton.disabled = !(phraseOk && checkOk);
    };

    input.addEventListener("input", refreshButtonState);
    if (checkbox) {
      checkbox.addEventListener("change", refreshButtonState);
    }
    refreshButtonState();

    form.addEventListener("submit", function (event) {
      if (submitButton.disabled) {
        event.preventDefault();
        return;
      }

      if (input.value.trim() !== confirmText) {
        event.preventDefault();
        alert("La phrase de confirmation ne correspond pas.");
        return;
      }

      if (!window.confirm(finalConfirm)) {
        event.preventDefault();
      }
    });
  });
});

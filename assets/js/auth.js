document.addEventListener("DOMContentLoaded", function () {
  const switchLink = document.getElementById("switch-link");
  const authForm = document.getElementById("auth-form");
  const authMode = document.getElementById("auth-mode");

  const title = document.getElementById("form-title");
  const subtitle = document.getElementById("form-subtitle");
  const labelAlias = document.getElementById("label-alias");
  const submitBtn = document.getElementById("submit-btn");
  const confirmGroup = document.getElementById("confirm-group");
  const emailGroup = document.getElementById("email-group");
  const switchText = document.getElementById("switch-text");
  const confirmInput = document.getElementById("confirm-password");
  const emailInput = document.getElementById("email");
  const confirmError = document.getElementById("confirm-error");

  let isLoginMode = true;

  if (
    !authForm ||
    !authMode ||
    !title ||
    !subtitle ||
    !labelAlias ||
    !submitBtn ||
    !confirmGroup ||
    !emailGroup ||
    !switchText ||
    !confirmInput ||
    !emailInput
  ) {
    return;
  }

  function setMode(loginMode) {
    isLoginMode = loginMode;

    if (isLoginMode) {
      title.innerText = "Connexion";
      subtitle.innerText = "Entrez dans l'Arsenal de Sombre-Donjon";
      labelAlias.innerText = "Alias de l'Aventurier";
      submitBtn.innerText = "Se connecter";
      confirmGroup.style.display = "none";
      emailGroup.style.display = "none";
      switchText.innerText = "Nouveau ici ?";
      if (switchLink) {
        switchLink.innerText = "Creer un compte";
      }
      authMode.value = "login";
      confirmInput.required = false;
      emailInput.required = false;
      confirmInput.style.borderColor = "";
      if (confirmError) {
        confirmError.style.display = "none";
      }
    } else {
      title.innerText = "Inscription";
      subtitle.innerText = "Rejoignez les rangs de l'Arsenal";
      labelAlias.innerText = "Choisir un Alias Unique";
      submitBtn.innerText = "Forger mon compte";
      confirmGroup.style.display = "block";
      emailGroup.style.display = "block";
      switchText.innerText = "Deja membre ?";
      if (switchLink) {
        switchLink.innerText = "Se connecter";
      }
      authMode.value = "register";
      confirmInput.required = true;
      emailInput.required = true;
    }
  }

  if (switchLink) {
    switchLink.addEventListener("click", function (e) {
      e.preventDefault();
      setMode(!isLoginMode);
    });
  }

  authForm.addEventListener("submit", function (e) {
    if (!isLoginMode) {
      const pass = (document.getElementById("password") || { value: "" }).value;
      const conf = confirmInput.value;

      if (pass !== conf) {
        e.preventDefault();
        if (confirmError) {
          confirmError.style.display = "block";
        }
        confirmInput.style.borderColor = "var(--error)";
      }
    }
  });

  setMode(true);
});

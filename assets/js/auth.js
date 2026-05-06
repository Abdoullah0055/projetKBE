document.addEventListener("DOMContentLoaded", function () {
  const switchLink = document.getElementById("switch-link");
  const authForm = document.getElementById("auth-form");
  const authMode = document.getElementById("auth-mode");
  const emailGroup = document.getElementById("email-group");
  const emailInput = document.getElementById("email");

  const title = document.getElementById("form-title");
  const subtitle = document.getElementById("form-subtitle");
  const labelAlias = document.getElementById("label-alias");
  const submitBtn = document.getElementById("submit-btn");
  const confirmGroup = document.getElementById("confirm-group");
  const switchText = document.getElementById("switch-text");
  const confirmInput = document.getElementById("confirm-password");

  if (!authForm || !authMode) {
    return;
  }

  let isLoginMode = authMode.value !== "register";

  if (switchLink) {
    switchLink.addEventListener("click", function (e) {
      e.preventDefault();
      isLoginMode = !isLoginMode;

      if (isLoginMode) {
        title.innerText = "Connexion";
        subtitle.innerText = "Entrez dans l'Arsenal de Sombre-Donjon";
        labelAlias.innerText = "Alias de l'Aventurier";
        submitBtn.innerText = "Se connecter";
        confirmGroup.style.display = "none";
        emailGroup.style.display = "none";
        switchText.innerText = "Nouveau ici ?";
        switchLink.innerText = "Créer un compte";
        authMode.value = "login";
        confirmInput.required = false;
        emailInput.required = false;
      } else {
        title.innerText = "Inscription";
        subtitle.innerText = "Rejoignez les rangs de l'Arsenal";
        labelAlias.innerText = "Choisir un Alias Unique";
        submitBtn.innerText = "Forger mon compte";
        confirmGroup.style.display = "block";
        emailGroup.style.display = "block";
        switchText.innerText = "Déjà membre ?";
        switchLink.innerText = "Se connecter";
        authMode.value = "register";
        confirmInput.required = true;
        emailInput.required = true;
      }
    });
  }

  authForm.addEventListener("submit", function (e) {
    if (!isLoginMode) {
      const pass = document.getElementById("password").value;
      const conf = confirmInput.value;
      if (pass !== conf) {
        e.preventDefault();
        document.getElementById("confirm-error").style.display = "block";
        confirmInput.style.borderColor = "var(--error)";
      }
    }
  });
});

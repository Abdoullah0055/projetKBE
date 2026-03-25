// 1. Initialisation des variables
// On récupère le numéro ET le thème directement des cookies pour être raccord avec le PHP
let currentImgNum = parseInt(getCookie("bgNumber")) || 1;

function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(";").shift();
}

function toggleMenu() {
  const sidebar = document.getElementById("sidebar");
  const arrow = document.getElementById("arrow-icon");
  if (sidebar && arrow) {
    sidebar.classList.toggle("collapsed");
    arrow.innerHTML = sidebar.classList.contains("collapsed") ? "»" : "«";
  }
}

document.addEventListener("DOMContentLoaded", () => {
  const themeToggle = document.getElementById("theme-toggle");
  const themeIcon = document.getElementById("theme-icon");
  const easterEgg = document.getElementById("easter-egg");

  function applyChanges(theme, num) {
    const path = `img/${theme}theme/${theme}${num}.png`;
    const urlValue = `url('${path}')`;

    // Mise à jour CSS
    document.documentElement.style.setProperty("--main-bg", urlValue);
    document.body.style.setProperty("background-image", urlValue, "important");

    // Mise à jour Cookies (30 jours)
    const expires = "; max-age=" + 30 * 24 * 60 * 60;
    document.cookie = `theme=${theme}${expires}; path=/`;
    document.cookie = `bgNumber=${num}${expires}; path=/`;

    // On met à jour la variable globale pour que le prochain clic soit correct
    currentImgNum = num;
  }

  // --- CLIC SUR LE THÈME ---
  if (themeToggle && themeIcon) {
    themeToggle.addEventListener("click", (e) => {
      e.preventDefault();

      // On détecte le thème via le cookie plutôt que l'icône pour éviter les décalages
      const savedTheme = getCookie("theme") || "light";
      const newTheme = savedTheme === "dark" ? "light" : "dark";
      const newIcon = newTheme === "dark" ? "fa-sun" : "fa-moon";

      applyChanges(newTheme, currentImgNum);

      themeIcon.classList.remove("fa-moon", "fa-sun");
      themeIcon.classList.add(newIcon);
    });
  }

  // --- CLIC SUR L'EASTER EGG ---
  if (easterEgg) {
    easterEgg.addEventListener("click", () => {
      const currentTheme = getCookie("theme") || "light";

      // On boucle sur 3 images uniquement (1 -> 2 -> 3 -> 1)
      // Si tu as 4 images, change le 3 par 4.
      let nextNum = (currentImgNum % 4) + 1;

      applyChanges(currentTheme, nextNum);
    });
  }
});

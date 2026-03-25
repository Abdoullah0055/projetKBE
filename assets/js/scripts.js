// 1. Initialisation des variables (on récupère les cookies ou les valeurs par défaut)
let currentImgNum = parseInt(getCookie("bgNumber")) || 1;

// Fonction utilitaire pour lire les cookies proprement
function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(";").shift();
}

// 2. Fonction globale pour le Sidebar (doit rester en dehors pour le onclick HTML)
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
  const root = document.documentElement;

  // 3. Fonction unique pour TOUT mettre à jour (Image + Cookies)
  function applyChanges(theme, num) {
    const path = `img/${theme}theme/${theme}${num}.png`;

    // Appliquer l'image
    root.style.setProperty("--main-bg", `url('${path}')`);

    // Sauvegarder dans les cookies (pour que le PHP le voit au prochain refresh)
    const date = new Date();
    date.setTime(date.getTime() + 30 * 24 * 60 * 60 * 1000);
    const expires = "; expires=" + date.toUTCString();

    document.cookie = `theme=${theme}${expires}; path=/`;
    document.cookie = `bgNumber=${num}${expires}; path=/`;

    console.log(`Appliqué : ${theme}${num}`);
  }

  // --- CLIC SUR LE THÈME ---
  if (themeToggle) {
    themeToggle.addEventListener("click", (e) => {
      e.preventDefault();

      // On regarde l'icône actuelle pour savoir on est en quoi
      const isNowDark = themeIcon.classList.contains("fa-sun");

      // On inverse
      const newTheme = isNowDark ? "light" : "dark";
      const newIcon = isNowDark ? "fa-moon" : "fa-sun";

      // Appliquer l'image avec le MÊME numéro
      applyChanges(newTheme, currentImgNum);

      // Changer l'icône
      themeIcon.classList.remove("fa-moon", "fa-sun");
      themeIcon.classList.add(newIcon);
    });
  }

  // --- CLIC SUR L'EASTER EGG ---
  if (easterEgg) {
    easterEgg.addEventListener("click", () => {
      const isNowDark = themeIcon.classList.contains("fa-sun");
      const currentTheme = isNowDark ? "dark" : "light";

      // On change le numéro (1 -> 2 -> 3 -> 1)
      currentImgNum = (currentImgNum % 4) + 1;

      applyChanges(currentTheme, currentImgNum);
    });
  }
});

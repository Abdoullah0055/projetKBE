function toggleMenu() {
  const sidebar = document.getElementById("sidebar");
  const arrow = document.getElementById("arrow-icon");

  sidebar.classList.toggle("collapsed");
  arrow.innerHTML = sidebar.classList.contains("collapsed") ? "»" : "«";
}
document.addEventListener("DOMContentLoaded", () => {
  const themeToggle = document.getElementById("theme-toggle");
  const themeIcon = document.getElementById("theme-icon");
  const root = document.documentElement; // Pour modifier la variable CSS

  themeToggle.addEventListener("click", (e) => {
    e.preventDefault();

    // On vérifie l'état actuel (basé sur l'icône)
    const isDark = themeIcon.classList.contains("fa-sun");

    // On définit les nouvelles valeurs
    const newTheme = isDark ? "light" : "dark";
    const newIcon = isDark ? "fa-moon" : "fa-sun";
    const newImg = isDark
      ? "img/lighttheme/light1.png"
      : "img/darktheme/dark1.png";

    // 1. Mise à jour visuelle immédiate via la variable CSS
    root.style.setProperty("--main-bg", `url('${newImg}')`);

    // 2. Bascule de l'icône
    themeIcon.classList.remove("fa-moon", "fa-sun");
    themeIcon.classList.add(newIcon);

    // 3. Mise à jour du Cookie (30 jours)
    const date = new Date();
    date.setTime(date.getTime() + 30 * 24 * 60 * 60 * 1000);
    document.cookie = `theme=${newTheme}; expires=${date.toUTCString()}; path=/`;

    console.log("Thème changé pour : " + newTheme); // Pour débugger dans ta console (F12)
  });
});

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

// === MOBILE DRAWER MANAGEMENT ===
class MobileDrawer {
  constructor() {
    this.drawer = document.getElementById("mobile-drawer");
    this.overlay = document.getElementById("mobile-drawer-overlay");
    this.toggle = document.getElementById("mobile-menu-toggle");
    this.isOpen = false;

    if (this.drawer && this.overlay && this.toggle) {
      this.init();
    }
  }

  init() {
    // Toggle button click
    this.toggle.addEventListener("click", (e) => {
      e.preventDefault();
      this.toggleDrawer();
    });

    // Overlay click to close
    this.overlay.addEventListener("click", () => {
      this.closeDrawer();
    });

    // Escape key to close
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && this.isOpen) {
        this.closeDrawer();
      }
    });

    // Drawer action buttons - close drawer on click
    const drawerButtons = this.drawer.querySelectorAll(".drawer-action");
    drawerButtons.forEach((btn) => {
      btn.addEventListener("click", () => {
        this.closeDrawer();
      });
    });

    // Search input in drawer
    const drawerSearch = this.drawer.querySelector(
      'input[name="drawer-search"]',
    );
    if (drawerSearch) {
      // Copy search functionality from main search bar
      drawerSearch.addEventListener("change", () => {
        const searchValue = drawerSearch.value;
        if (searchValue.trim()) {
          // Navigate to inventory with search param if needed
          // For now, just trigger search if callback exists
          window.location.href = `inventory.php?search=${encodeURIComponent(searchValue)}`;
        }
      });
    }
  }

  toggleDrawer() {
    if (this.isOpen) {
      this.closeDrawer();
    } else {
      this.openDrawer();
    }
  }

  openDrawer() {
    this.drawer.classList.add("open");
    this.overlay.classList.add("open");
    this.toggle.setAttribute("aria-expanded", "true");
    this.isOpen = true;

    // Prevent body scroll
    document.body.classList.add("drawer-open");

    // Focus management
    this.drawer.focus();
  }

  closeDrawer() {
    this.drawer.classList.remove("open");
    this.overlay.classList.remove("open");
    this.toggle.setAttribute("aria-expanded", "false");
    this.isOpen = false;

    // Allow body scroll
    document.body.classList.remove("drawer-open");

    // Return focus to toggle button
    this.toggle.focus();
  }

  // Auto-close on resize to desktop
  handleResize() {
    if (window.innerWidth >= 1200 && this.isOpen) {
      this.closeDrawer();
    }
  }
}

let mobileDrawer;

document.addEventListener("DOMContentLoaded", () => {
  const themeToggle = document.getElementById("theme-toggle");
  const themeIcon = document.getElementById("theme-icon");
  const easterEgg = document.getElementById("easter-egg");

  // Initialize mobile drawer
  mobileDrawer = new MobileDrawer();

  // Handle window resize
  window.addEventListener("resize", () => {
    if (mobileDrawer) {
      mobileDrawer.handleResize();
    }
  });

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

      let nextNum = (currentImgNum % 5) + 1;

      applyChanges(currentTheme, nextNum);
    });
  }
});

// Initialisation globale
let currentImgNum = parseInt(getCookie("bgNumber"), 10) || 1;
const SIDEBAR_STATE_KEY = "arsenal.sidebar.collapsed";

function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) {
    return parts.pop().split(";").shift();
  }
  return undefined;
}

function getSidebarElements() {
  return {
    sidebar: document.getElementById("sidebar"),
    arrow: document.getElementById("arrow-icon"),
    toggleButton: document.getElementById("toggle-btn"),
  };
}

function setSidebarState(sidebar, arrow, toggleButton, collapsed) {
  sidebar.classList.toggle("collapsed", collapsed);

  if (arrow) {
    arrow.textContent = collapsed ? "»" : "«";
  }

  if (toggleButton) {
    toggleButton.setAttribute("aria-expanded", collapsed ? "false" : "true");
    toggleButton.setAttribute(
      "aria-label",
      collapsed ? "Ouvrir la sidebar" : "Réduire la sidebar",
    );
    toggleButton.title = collapsed
      ? "Déplier la sidebar"
      : "Replier la sidebar";
  }
}

function persistSidebarState(collapsed) {
  try {
    localStorage.setItem(SIDEBAR_STATE_KEY, collapsed ? "1" : "0");
  } catch (_error) {
    // Ignore storage errors.
  }
}

function readSidebarState() {
  try {
    const saved = localStorage.getItem(SIDEBAR_STATE_KEY);
    if (saved === "1") {
      return true;
    }
    if (saved === "0") {
      return false;
    }
  } catch (_error) {
    // Ignore storage errors.
  }
  return null;
}

function initSidebarToggle() {
  const { sidebar, arrow, toggleButton } = getSidebarElements();
  if (!sidebar || !arrow || !toggleButton) {
    return;
  }

  const savedState = readSidebarState();
  if (savedState !== null) {
    setSidebarState(sidebar, arrow, toggleButton, savedState);
  } else {
    setSidebarState(
      sidebar,
      arrow,
      toggleButton,
      sidebar.classList.contains("collapsed"),
    );
  }

  toggleButton.addEventListener("click", (event) => {
    event.preventDefault();
    toggleMenu();
  });
}

function toggleMenu(forceState) {
  const { sidebar, arrow, toggleButton } = getSidebarElements();
  if (!sidebar || !arrow) {
    return;
  }

  const nextCollapsed =
    typeof forceState === "boolean"
      ? forceState
      : !sidebar.classList.contains("collapsed");

  setSidebarState(sidebar, arrow, toggleButton, nextCollapsed);
  persistSidebarState(nextCollapsed);
}

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
    this.toggle.addEventListener("click", (event) => {
      event.preventDefault();
      this.toggleDrawer();
    });

    this.overlay.addEventListener("click", () => {
      this.closeDrawer();
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && this.isOpen) {
        this.closeDrawer();
      }
    });

    const drawerButtons = this.drawer.querySelectorAll(".drawer-action");
    drawerButtons.forEach((button) => {
      button.addEventListener("click", () => {
        this.closeDrawer();
      });
    });
  }

  toggleDrawer() {
    if (this.isOpen) {
      this.closeDrawer();
      return;
    }

    this.openDrawer();
  }

  openDrawer() {
    this.drawer.classList.add("open");
    this.overlay.classList.add("open");
    this.toggle.setAttribute("aria-expanded", "true");
    this.isOpen = true;

    document.body.classList.add("drawer-open");
    this.drawer.focus();
  }

  closeDrawer() {
    this.drawer.classList.remove("open");
    this.overlay.classList.remove("open");
    this.toggle.setAttribute("aria-expanded", "false");
    this.isOpen = false;

    document.body.classList.remove("drawer-open");
    this.toggle.focus();
  }

  handleResize() {
    if (window.innerWidth >= 1200 && this.isOpen) {
      this.closeDrawer();
    }
  }
}

class LiveSearchSuggestions {
  constructor() {
    this.endpoint = "backend/search_items.php";
    this.instances = [];
    this.requestIdCounter = 0;
    this.minQueryLength = 1;

    this.init();
  }

  init() {
    this.registerInstance({
      formId: "header-search-form",
      inputId: "header-search-input",
      panelId: "header-search-suggestions",
      listId: "header-search-suggestions-list",
    });

    this.registerInstance({
      formId: "drawer-search-form",
      inputId: "drawer-search-input",
      panelId: "drawer-search-suggestions",
      listId: "drawer-search-suggestions-list",
    });

    document.addEventListener("click", (event) => {
      this.instances.forEach((instance) => {
        if (!instance.root.contains(event.target)) {
          this.hide(instance);
        }
      });
    });
  }

  registerInstance({ formId, inputId, panelId, listId }) {
    const form = document.getElementById(formId);
    const input = document.getElementById(inputId);
    const panel = document.getElementById(panelId);
    const list = document.getElementById(listId);

    if (!form || !input || !panel || !list) {
      return;
    }

    const root =
      input.closest(".search-container, .mobile-drawer-search") || form;

    const instance = {
      form,
      input,
      panel,
      list,
      root,
      items: [],
      highlightedIndex: -1,
      debounceTimer: null,
      latestRequestId: 0,
    };

    this.instances.push(instance);

    form.addEventListener("submit", (event) => {
      event.preventDefault();

      const value = input.value.trim();
      if (value === "") {
        this.hide(instance);
        return;
      }

      window.location.href = `inventory.php?search=${encodeURIComponent(value)}`;
    });

    input.addEventListener("input", () => {
      this.onInput(instance);
    });

    input.addEventListener("focus", () => {
      if (instance.input.value.trim().length >= this.minQueryLength) {
        this.onInput(instance);
      }
    });

    input.addEventListener("blur", () => {
      setTimeout(() => {
        this.hide(instance);
      }, 120);
    });

    input.addEventListener("keydown", (event) => {
      this.onKeyDown(event, instance);
    });

    list.addEventListener("mousedown", (event) => {
      const link = event.target.closest("a.search-suggestion-link");
      if (!link) {
        return;
      }

      event.preventDefault();
      window.location.href = link.getAttribute("href") || "index.php";
    });
  }

  onInput(instance) {
    const query = instance.input.value.trim();
    instance.highlightedIndex = -1;

    if (instance.debounceTimer) {
      clearTimeout(instance.debounceTimer);
    }

    if (query.length < this.minQueryLength) {
      instance.latestRequestId = ++this.requestIdCounter;
      instance.items = [];
      instance.list.innerHTML = "";
      this.hide(instance);
      return;
    }

    instance.debounceTimer = setTimeout(() => {
      this.fetchSuggestions(instance, query);
    }, 170);
  }

  async fetchSuggestions(instance, query) {
    const requestId = ++this.requestIdCounter;
    instance.latestRequestId = requestId;

    try {
      const response = await fetch(
        `${this.endpoint}?q=${encodeURIComponent(query)}`,
        {
          headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
          },
        },
      );

      let payload = null;
      try {
        payload = await response.json();
      } catch (_error) {
        payload = null;
      }

      if (instance.latestRequestId !== requestId) {
        return;
      }

      if (
        !response.ok ||
        !payload ||
        payload.success !== true ||
        !Array.isArray(payload.items)
      ) {
        instance.items = [];
      } else {
        instance.items = payload.items;
      }

      this.render(instance);
      this.show(instance);
    } catch (_error) {
      if (instance.latestRequestId !== requestId) {
        return;
      }

      instance.items = [];
      this.render(instance);
      this.show(instance);
    }
  }

  render(instance) {
    instance.list.innerHTML = "";

    if (!instance.items.length) {
      const emptyItem = document.createElement("li");
      emptyItem.className = "search-suggestion-empty";
      emptyItem.textContent = "Aucun item correspondant.";
      instance.list.appendChild(emptyItem);
      return;
    }

    instance.items.forEach((item, index) => {
      const li = document.createElement("li");
      li.className = "search-suggestion-item";

      const link = document.createElement("a");
      link.className = "search-suggestion-link";
      link.href = item.detailsUrl || `details.php?id=${Number(item.id) || 0}`;

      const thumb = document.createElement("span");
      thumb.className = "search-suggestion-thumb";
      thumb.textContent = item.image || "❓";

      const textWrap = document.createElement("span");
      textWrap.className = "search-suggestion-text";

      const title = document.createElement("span");
      title.className = "search-suggestion-title";
      title.textContent = item.name || "Item sans nom";

      const meta = document.createElement("span");
      meta.className = "search-suggestion-meta";

      const ratingValue = Number.isFinite(Number(item.rating))
        ? Number(item.rating)
        : 0;
      const ratingText =
        typeof item.ratingText === "string"
          ? item.ratingText
          : this.formatRatingValue(ratingValue);
      const reviewCount = Number.isFinite(Number(item.reviewCount))
        ? Number(item.reviewCount)
        : 0;

      meta.innerHTML = `${this.renderStars(ratingValue)}<span class="rating-value-inline">${ratingText}/5 (${reviewCount} avis)</span>`;

      textWrap.appendChild(title);
      textWrap.appendChild(meta);

      link.appendChild(thumb);
      link.appendChild(textWrap);
      li.appendChild(link);
      li.dataset.index = String(index);

      instance.list.appendChild(li);
    });
  }

  onKeyDown(event, instance) {
    if (!instance.items.length) {
      return;
    }

    if (event.key === "Escape") {
      this.hide(instance);
      return;
    }

    if (event.key === "ArrowDown") {
      event.preventDefault();
      const nextIndex =
        instance.highlightedIndex >= instance.items.length - 1
          ? 0
          : instance.highlightedIndex + 1;
      this.selectIndex(instance, nextIndex);
      return;
    }

    if (event.key === "ArrowUp") {
      event.preventDefault();
      const nextIndex =
        instance.highlightedIndex <= 0
          ? instance.items.length - 1
          : instance.highlightedIndex - 1;
      this.selectIndex(instance, nextIndex);
      return;
    }

    if (event.key === "Enter" && instance.highlightedIndex >= 0) {
      event.preventDefault();
      const selected = instance.items[instance.highlightedIndex];
      if (selected && selected.detailsUrl) {
        window.location.href = selected.detailsUrl;
      }
    }
  }

  selectIndex(instance, index) {
    instance.highlightedIndex = index;

    const rows = instance.list.querySelectorAll(".search-suggestion-item");
    rows.forEach((row, rowIndex) => {
      row.classList.toggle("is-selected", rowIndex === index);
      if (rowIndex === index) {
        row.scrollIntoView({ block: "nearest" });
      }
    });
  }

  show(instance) {
    instance.panel.hidden = false;
    instance.input.setAttribute("aria-expanded", "true");
  }

  hide(instance) {
    instance.panel.hidden = true;
    instance.input.setAttribute("aria-expanded", "false");
    instance.highlightedIndex = -1;
    instance.list
      .querySelectorAll(".search-suggestion-item")
      .forEach((row) => row.classList.remove("is-selected"));
  }

  formatRatingValue(value) {
    const clamped = Math.max(0, Math.min(5, Number(value) || 0));
    return clamped.toFixed(1);
  }

  renderStars(value) {
    const clamped = Math.max(0, Math.min(5, Number(value) || 0));
    const rounded = Math.round(clamped * 2) / 2;
    const fullStars = Math.floor(rounded);
    const hasHalf = rounded - fullStars >= 0.5;
    const emptyStars = 5 - fullStars - (hasHalf ? 1 : 0);

    const stars = [];
    stars.push('<span class="rating-stars" aria-hidden="true">');

    for (let i = 0; i < fullStars; i += 1) {
      stars.push('<i class="fa-solid fa-star"></i>');
    }

    if (hasHalf) {
      stars.push('<i class="fa-solid fa-star-half-stroke"></i>');
    }

    for (let i = 0; i < emptyStars; i += 1) {
      stars.push('<i class="fa-regular fa-star"></i>');
    }

    stars.push("</span>");
    return stars.join("");
  }
}

let mobileDrawer;

document.addEventListener("DOMContentLoaded", () => {
  const themeToggle = document.getElementById("theme-toggle");
  const themeIcon = document.getElementById("theme-icon");
  const easterEgg = document.getElementById("easter-egg");

  initSidebarToggle();

  mobileDrawer = new MobileDrawer();
  new LiveSearchSuggestions();

  window.addEventListener("resize", () => {
    if (mobileDrawer) {
      mobileDrawer.handleResize();
    }
  });

  function applyChanges(theme, num) {
    const path = `img/${theme}theme/${theme}${num}.png`;
    const urlValue = `url('${path}')`;

    document.documentElement.style.setProperty("--main-bg", urlValue);
    document.body.style.setProperty("background-image", urlValue, "important");

    const expires = "; max-age=" + 30 * 24 * 60 * 60;
    document.cookie = `theme=${theme}${expires}; path=/`;
    document.cookie = `bgNumber=${num}${expires}; path=/`;

    currentImgNum = num;
  }

  if (themeToggle && themeIcon) {
    themeToggle.addEventListener("click", (event) => {
      event.preventDefault();

      const savedTheme = getCookie("theme") || "light";
      const newTheme = savedTheme === "dark" ? "light" : "dark";
      const newIcon = newTheme === "dark" ? "fa-sun" : "fa-moon";

      applyChanges(newTheme, currentImgNum);

      themeIcon.classList.remove("fa-moon", "fa-sun");
      themeIcon.classList.add(newIcon);
    });
  }

  if (easterEgg) {
    easterEgg.addEventListener("click", () => {
      const currentTheme = getCookie("theme") || "light";
      const nextNum = (currentImgNum % 5) + 1;

      applyChanges(currentTheme, nextNum);
    });
  }
});

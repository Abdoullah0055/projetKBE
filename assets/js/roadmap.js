(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", () => {
    const stage = document.querySelector(".roadmap-stage");
    const noticeNodes = document.querySelectorAll("[data-roadmap-dialogues]");
    const randomButton = document.querySelector(".roadmap-random-btn");
    const overlay = document.getElementById("roadmapMageOverlay");
    const backdrop = document.getElementById("roadmapMageBackdrop");
    const mageButton = document.getElementById("roadmapMageButton");
    const mageImage = document.getElementById("roadmapMageImage");
    const mageText = document.getElementById("roadmapMageText");
    const mageHint = document.getElementById("roadmapMageHint");

    if (
      !stage ||
      !overlay ||
      !backdrop ||
      !mageButton ||
      !mageImage ||
      !mageText ||
      !mageHint
    ) {
      return;
    }

    const mageFrames = {
      default: [
        "assets/img/Magicien/furieux.png",
        "assets/img/Magicien/mage2.png",
        "assets/img/Magicien/mage3.png"
      ],
      welcome: [
        "assets/img/Magicien/mage5.png",
        "assets/img/Magicien/mage5.png",
        "assets/img/Magicien/mage_pointe.png"
      ]
    };

    let activeDialogues = [];
    let activeIndex = 0;
    let activeMode = "default";
    let closeMode = "close";
    let switchTimerId = null;
    let typeTimerId = null;
    let typeRunId = 0;
    const typeDelay = 35;
    const postDialogues = parseDialogues(stage.dataset.postDialogues);

    const welcomeDialogues = [
      {
        text: "Bienvenue, jeune vagabond. Sur cette roadmap, chaque quête accomplie te rapporte 50 gold.",
        frame: mageFrames.welcome[0]
      },
      {
        text: "Progresse parmi les huit guerriers legendaires : triomphe de chaque epreuve dans l’ordre, et grave ta voie dans les chroniques du royaume.",
        frame: "assets/img/Magicien/mage6.png"
      },
      {
        text: "Si tu invoques les defis aleatoires via le bouton des des, la bourse suit la difficulte : 10 gold en difficile, 10 argent en moyen, 10 bronze en facile.",
        frame: mageFrames.welcome[2],
        focusRandomButton: true
      }
    ];

    if (postDialogues.length > 0) {
      openOverlay(postDialogues, {
        mode: "default",
        closeMode: "close"
      });
    } else {
      openOverlay(welcomeDialogues, {
        mode: "welcome",
        closeMode: "close"
      });
    }

    noticeNodes.forEach((node) => {
      node.addEventListener("click", () => {
        const dialogues = parseDialogues(node.dataset.roadmapDialogues);

        if (dialogues.length === 0) {
          return;
        }

        openOverlay(dialogues, {
          mode: "default",
          closeMode: "close"
        });
      });
    });

    mageButton.addEventListener("click", () => {
      if (activeDialogues.length === 0) {
        closeOverlay();
        return;
      }

      if (activeIndex < activeDialogues.length - 1) {
        activeIndex += 1;
        renderDialogue();
        mageHint.textContent = activeIndex < activeDialogues.length - 1
          ? "Cliquez sur le mage pour continuer."
          : "Cliquez sur le mage pour fermer.";
        return;
      }

      closeOverlay();
    });

    backdrop.addEventListener("click", closeOverlay);

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && !overlay.hidden) {
        closeOverlay();
      }
    });

    function parseDialogues(rawValue) {
      if (!rawValue) {
        return [];
      }

      try {
        const parsed = JSON.parse(rawValue);
        return Array.isArray(parsed) ? parsed : [];
      } catch (error) {
        return [];
      }
    }

    function openOverlay(dialogues, options) {
      activeDialogues = dialogues;
      activeIndex = 0;
      activeMode = options.mode || "default";
      closeMode = options.closeMode || "close";
      overlay.hidden = false;
      overlay.setAttribute("aria-hidden", "false");
      renderDialogue();
    }

    function renderDialogue() {
      const dialogue = normalizeDialogue(activeDialogues[activeIndex]);
      swapMageFrame(activeIndex, dialogue.frame);
      typeDialogue(dialogue.text, updateHintText);
      toggleRandomFocus(dialogue.focusRandomButton);
    }

    function clearTypewriter() {
      typeRunId += 1;

      if (typeTimerId !== null) {
        window.clearTimeout(typeTimerId);
        typeTimerId = null;
      }

      mageText.classList.remove("is-typing");
    }

    function typeDialogue(text, onComplete) {
      clearTypewriter();

      const runId = typeRunId;
      let charIndex = 0;

      mageText.textContent = "";
      mageText.classList.add("is-typing");

      const step = () => {
        if (runId !== typeRunId) {
          return;
        }

        if (charIndex >= text.length) {
          mageText.classList.remove("is-typing");
          typeTimerId = null;

          if (typeof onComplete === "function") {
            onComplete();
          }

          return;
        }

        mageText.textContent += text.charAt(charIndex);
        charIndex += 1;
        typeTimerId = window.setTimeout(step, typeDelay);
      };

      step();
    }

    function normalizeDialogue(dialogue) {
      if (typeof dialogue === "string") {
        return {
          text: dialogue,
          frame: mageFrames.default[Math.min(activeIndex, mageFrames.default.length - 1)],
          focusRandomButton: false
        };
      }

      return {
        text: dialogue.text || "",
        frame: dialogue.frame || mageFrames.default[Math.min(activeIndex, mageFrames.default.length - 1)],
        focusRandomButton: Boolean(dialogue.focusRandomButton)
      };
    }

    function setMageFrame(frameSource, frameIndex) {
      if (typeof frameSource === "string" && frameSource !== "") {
        mageImage.src = frameSource;
        mageImage.alt = `Mage phase ${frameIndex + 1}`;
        return;
      }

      const fallbackFrames = mageFrames[activeMode] || mageFrames.default;
      const safeIndex = Math.min(frameIndex, fallbackFrames.length - 1);
      mageImage.src = fallbackFrames[safeIndex];
      mageImage.alt = `Mage phase ${safeIndex + 1}`;
    }

    function swapMageFrame(nextIndex, frameSource) {
      if (switchTimerId !== null) {
        window.clearTimeout(switchTimerId);
      }

      mageButton.classList.add("is-switching");

      switchTimerId = window.setTimeout(() => {
        setMageFrame(frameSource, nextIndex);
        mageButton.classList.remove("is-switching");
        switchTimerId = null;
      }, 120);
    }

    function updateHintText() {
      mageHint.textContent = activeIndex < activeDialogues.length - 1
        ? "Cliquez sur le mage pour continuer."
        : "Cliquez sur le mage pour fermer.";
    }

    function toggleRandomFocus(isFocused) {
      stage.classList.toggle("roadmap-stage--random-focus", isFocused);
      overlay.classList.toggle("is-random-focus", isFocused);

      if (randomButton) {
        randomButton.classList.toggle("is-highlighted", isFocused);
      }
    }

    function closeOverlay() {
      clearTypewriter();

      overlay.hidden = true;
      overlay.setAttribute("aria-hidden", "true");
      activeDialogues = [];
      activeIndex = 0;
      activeMode = "default";
      closeMode = "close";
      toggleRandomFocus(false);
      setMageFrame(mageFrames.default[0], 0);
      mageText.textContent = "";
      mageHint.textContent = "Cliquez sur le mage pour continuer.";
    }
  });
})();



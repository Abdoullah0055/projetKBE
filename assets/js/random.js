(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", () => {
    const stage = document.querySelector(".random-stage");
    const difficultySelect = document.getElementById("random-difficulty");
    const categoryButtons = document.querySelectorAll("[data-category-id]");
    const overlay = document.getElementById("randomMageOverlay");
    const mageButton = document.getElementById("randomMageButton");
    const mageImage = document.getElementById("randomMageImage");
    const mageText = document.getElementById("randomMageText");
    const mageHint = document.getElementById("randomMageHint");

    if (!stage || !difficultySelect || categoryButtons.length === 0) {
      return;
    }

    const introDialogues = [
      {
        text: "Jeune vagabond, lorsque j’ouvrirai mon grimoire, les pages apparaitront et tu pourras y choisir la categorie de ton alchimie.",
        frame: "assets/img/Magicien/fade.png",
        focus: ""
      },
      {
        text: "Ajuste la difficulte selon ton courage, jeune vagabond : en difficile tu remporteras 10 gold, en moyen 10 argent, et en facile 10 bronze.",
        frame: "assets/img/Magicien/pointe.png",
        focus: "choice"
      },
      {
        text: "Une fois ton choix grave sur ces pages, le destin ouvrira un portail et te lancera vers une epreuve aleatoire.",
        frame: "assets/img/Magicien/portail.png",
        focus: ""
      }
    ];

    let activeIndex = 0;
    let isTransitioning = false;
    let switchTimerId = null;
    let typeTimerId = null;
    let typeRunId = 0;
    const fogDuration = 1050;
    const revealDuration = 500;
    const typeDelay = 35;

    if (overlay && mageButton && mageImage && mageText && mageHint) {
      renderDialogue();

      mageButton.addEventListener("click", () => {
        if (isTransitioning) {
          return;
        }

        if (activeIndex < introDialogues.length - 1) {
          activeIndex += 1;
          renderDialogue();
          return;
        }

        startRevealTransition();
      });
    } else {
      stage.classList.remove("is-intro-active", "random-stage--pages-focus", "random-stage--choice-focus");
      stage.classList.add("is-page-ready");
    }

    categoryButtons.forEach((button) => {
      button.addEventListener("click", () => {
        if (stage.classList.contains("is-intro-active")) {
          return;
        }

        const categoryId = button.dataset.categoryId;
        const difficulty = difficultySelect.value;

        if (!categoryId || !difficulty) {
          return;
        }

        const params = new URLSearchParams({
          source: "random",
          category_id: categoryId,
          difficulty
        });

        window.location.href = `enigme.php?${params.toString()}`;
      });
    });

    function renderDialogue() {
      const dialogue = normalizeDialogue(introDialogues[activeIndex]);
      if (activeIndex === 0) {
        setMageFrame(dialogue.frame, activeIndex);
      } else {
        swapMageFrame(activeIndex, dialogue.frame);
      }
      typeDialogue(dialogue.text, updateHintText);
      setFocus(dialogue.focus);
    }

    function normalizeDialogue(dialogue) {
      if (typeof dialogue === "string") {
        return {
          text: dialogue,
          frame: "",
          focus: ""
        };
      }

      return {
        text: dialogue && typeof dialogue.text === "string" ? dialogue.text : "",
        frame: dialogue && typeof dialogue.frame === "string" ? dialogue.frame : "",
        focus: dialogue && typeof dialogue.focus === "string" ? dialogue.focus : ""
      };
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

    function setMageFrame(frameSource, frameIndex) {
      if (typeof frameSource === "string" && frameSource !== "") {
        mageImage.src = frameSource;
        mageImage.alt = `Mage aleatoire phase ${frameIndex + 1}`;
        return;
      }

      mageImage.alt = `Mage aleatoire phase ${frameIndex + 1}`;
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
      mageHint.textContent = activeIndex < introDialogues.length - 1
        ? "Cliquez sur le mage pour continuer."
        : "Cliquez sur le mage pour reveler l epreuve.";
    }

    function setFocus(focusMode) {
      stage.classList.remove("random-stage--pages-focus", "random-stage--choice-focus");

      if (focusMode === "pages") {
        stage.classList.add("random-stage--pages-focus");
        return;
      }

      if (focusMode === "choice") {
        stage.classList.add("random-stage--choice-focus");
      }
    }

    function startRevealTransition() {
      isTransitioning = true;
      clearTypewriter();
      setFocus("");
      stage.classList.add("is-fogging");

      window.setTimeout(() => {
        stage.classList.remove("is-intro-active");
        stage.classList.add("is-page-ready");

        if (overlay) {
          overlay.setAttribute("aria-hidden", "true");
          overlay.hidden = true;
        }

        window.setTimeout(() => {
          stage.classList.remove("is-fogging");
          isTransitioning = false;
        }, revealDuration);
      }, fogDuration);
    }
  });
})();



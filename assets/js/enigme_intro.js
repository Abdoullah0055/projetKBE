(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", () => {
    const stage = document.querySelector(".enigmes-stage");
    const mageButton = document.getElementById("enigmesMage");
    const mageImage = document.getElementById("enigmesMageImage");
    const dialogText = document.getElementById("enigmesDialogText");
    const dialogHint = document.getElementById("enigmesDialogHint");
    const backLink = document.querySelector(".enigmes-back");
    const backImage = document.querySelector(".enigmes-back-image");

    if (!stage || !mageButton || !mageImage || !dialogText || !dialogHint) {
      return;
    }

    const nextUrl = stage.dataset.nextUrl || "";
    const dialogues = parseDialogues(stage.dataset.mainDialogues);
    const mageFrames = [
      "assets/img/Magicien/mage1.png",
      "assets/img/Magicien/mage2.png",
      "assets/img/Magicien/mage3.png",
      "assets/img/Magicien/mage4.png",
      "assets/img/Magicien/mage5.png",
      "assets/img/Magicien/mage6.png",
      "assets/img/Magicien/mage7.png",
      "assets/img/Magicien/mage8.png"
    ];

    if (dialogues.length === 0) {
      if (nextUrl) {
        window.location.href = nextUrl;
      }
      return;
    }

    disableBackDuringIntro();

    let currentIndex = 0;
    let switchTimerId = null;
    let typeTimerId = null;
    let typeRunId = 0;
    let redirectTimerId = null;
    let isTransitioning = false;
    const typeDelay = 35;
    const settleDelay = 500;
    const fogDuration = 1050;

    setMageFrame(0);
    renderDialogue();

    mageButton.addEventListener("click", () => {
      if (isTransitioning) {
        return;
      }

      clearRedirectTimer();

      if (currentIndex < dialogues.length - 1) {
        currentIndex += 1;
        renderDialogue();
        return;
      }

      startExitTransition();
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

    function clearTypewriter() {
      typeRunId += 1;

      if (typeTimerId !== null) {
        window.clearTimeout(typeTimerId);
        typeTimerId = null;
      }

      dialogText.classList.remove("is-typing");
    }

    function clearRedirectTimer() {
      if (redirectTimerId !== null) {
        window.clearTimeout(redirectTimerId);
        redirectTimerId = null;
      }
    }

    function typeDialogue(text, onComplete) {
      clearTypewriter();
      clearRedirectTimer();

      const runId = typeRunId;
      let charIndex = 0;

      dialogText.textContent = "";
      dialogText.classList.add("is-typing");

      const step = () => {
        if (runId !== typeRunId) {
          return;
        }

        if (charIndex >= text.length) {
          dialogText.classList.remove("is-typing");
          typeTimerId = null;

          if (typeof onComplete === "function") {
            onComplete();
          }

          return;
        }

        dialogText.textContent += text.charAt(charIndex);
        charIndex += 1;
        typeTimerId = window.setTimeout(step, typeDelay);
      };

      step();
    }

    function setMageFrame(frameSource, frameIndex) {
      if (typeof frameSource === "string" && frameSource !== "") {
        mageImage.src = frameSource;
        mageImage.alt = `Mage phase ${frameIndex + 1}`;
        return;
      }

      const safeIndex = Math.min(frameIndex, mageFrames.length - 1);
      mageImage.src = mageFrames[safeIndex];
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

    function renderDialogue() {
      const dialogue = normalizeDialogue(dialogues[currentIndex]);
      swapMageFrame(currentIndex, dialogue.frame);
      typeDialogue(dialogue.text, updateHint);
    }

    function normalizeDialogue(dialogue) {
      if (typeof dialogue === "string") {
        return {
          text: dialogue,
          frame: ""
        };
      }

      return {
        text: dialogue && typeof dialogue.text === "string" ? dialogue.text : "",
        frame: dialogue && typeof dialogue.frame === "string" ? dialogue.frame : ""
      };
    }

    function updateHint() {
      if (currentIndex < dialogues.length - 1) {
        dialogHint.textContent = "Cliquez sur le mage pour continuer.";
        return;
      }

      dialogHint.textContent = "Cliquez sur le mage pour passer a la reponse.";
      redirectTimerId = window.setTimeout(startExitTransition, settleDelay);
    }

    function startExitTransition() {
      clearRedirectTimer();

      if (isTransitioning) {
        return;
      }

      isTransitioning = true;
      mageButton.disabled = true;
      stage.classList.add("is-fogging");

      window.setTimeout(() => {
        redirectToResponse();
      }, fogDuration);
    }

    function redirectToResponse() {
      clearRedirectTimer();

      if (nextUrl) {
        window.location.href = nextUrl;
      }
    }

    function disableBackDuringIntro() {
      if (backImage) {
        backImage.src = "assets/img/Magicien/retour_off.png";
      }

      if (!backLink) {
        return;
      }

      backLink.removeAttribute("href");
      backLink.setAttribute("aria-disabled", "true");
      backLink.style.pointerEvents = "none";
    }
  });
})();



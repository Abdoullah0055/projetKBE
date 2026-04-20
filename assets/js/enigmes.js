(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", () => {
    const stage = document.querySelector(".enigmes-stage");
    const intro = document.getElementById("enigmesIntro");
    const mageButton = document.getElementById("enigmesMage");
    const mageImage = document.getElementById("enigmesMageImage");
    const dialogText = document.getElementById("enigmesDialogText");
    const hintButton = document.getElementById("enigmesHintBtn");
    const responseInput = document.getElementById("enigmesResponseInput");
    const backLink = stage.querySelector(".enigmes-back");
    const backImage = stage.querySelector(".enigmes-back-image");

    if (!stage || !intro || !mageButton || !mageImage || !dialogText) {
      return;
    }

    const startMode = stage.dataset.startMode === "riddle" ? "riddle" : "main";
    const hintUnlockStep = Number.parseInt(stage.dataset.hintUnlockStep || "3", 10);
    const responseUrl = stage.dataset.responseUrl || "reponse.php";
    const abandonUrl = stage.dataset.abandonUrl || (backLink ? backLink.getAttribute("href") || "" : "");
    const abandonBackImage = "assets/img/Magicien/abandon.png";

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

    const hintFrames = [
      "assets/img/Magicien/mage_rigole.png",
      "assets/img/Magicien/mage_grimoire.png",
      "assets/img/Magicien/mage8.png"
    ];

    const backgroundFrames = [
      "assets/img/Magicien/gone.png",
      "assets/img/Magicien/respond.png"
    ];

    const uiAssets = [
      "assets/img/Magicien/scroll.png",
      "assets/img/Magicien/info.png"
    ];

    const fallbackMainDialogues = [
      "Bienvenue dans Enigme. Le but du jeu est de repondre correctement a une question qui te sera posee.",
      "Les reponses peuvent se trouver directement sur le site, dans les descriptions d’objets, ou concerner des evenements historiques de notre monde.",
      "Tu peux cliquer en tout temps sur l’icone I en haut a droite afin d’obtenir un indice. Dans le message suivant se trouvera l’enigme a resoudre.",
      "Une enigme devrait se presenter ici.",
      "Il est maintenant a toi de jouer, jeune vagabond. Je te souhaite la meilleure des chances."
    ];

    const fallbackHintDialogues = [
      "Ah... je savais bien que tu aurais besoin d’un indice. Cette enigme semblait deja trop grande pour toi.",
      "Les grimoires demeurent muets pour l’instant.",
      "J’espere que cela suffira a eclairer ton esprit, jeune vagabond."
    ];

    const mainDialogues = parseDialogues(stage.dataset.mainDialogues, fallbackMainDialogues);
    const hintDialogues = parseDialogues(stage.dataset.hintDialogues, fallbackHintDialogues);

    preloadAssets([...mageFrames, ...backgroundFrames, ...uiAssets]);

    let mode = startMode;
    let currentIndex = 0;
    let isTransitioning = false;
    let switchTimerId = null;
    let typeTimerId = null;
    let settleTimerId = null;
    let typeRunId = 0;
    let activeTypeText = "";
    let isTyping = false;

    const typeDelay = 35;
    const fogDuration = 1050;
    const introFadeDuration = 500;
    const settleDelay = 650;

    stage.classList.remove("is-riddle-active", "is-replay-active", "is-intro-active", "is-hint-available");

    if (startMode === "riddle") {
      stage.classList.add("is-riddle-active", "is-hint-available");
      intro.setAttribute("aria-hidden", "true");

      if (responseInput) {
        responseInput.disabled = false;
        responseInput.focus({ preventScroll: true });
      }

      setHintButtonEnabled(true);
      syncBackLink();
    } else {
      stage.classList.add("is-intro-active");
      setHintButtonEnabled(false);

      if (responseInput) {
        responseInput.disabled = true;
      }

      setMageFrame(currentIndex);
      typeDialogue(mainDialogues[currentIndex], handleSequenceCompletion);
      syncBackLink();
    }

    mageButton.addEventListener("click", () => {
      if (isTransitioning) {
        return;
      }

      if (mode === "main") {
        if (isTyping && currentIndex >= mainDialogues.length - 1) {
          activateRiddleMode();
          return;
        }

        handleSequenceClick(mainDialogues);
        return;
      }

      if (mode === "hint") {
        if (isTyping && currentIndex >= hintDialogues.length - 1) {
          activateRiddleMode();
          return;
        }

        handleSequenceClick(hintDialogues);
      }
    });

    if (hintButton) {
      hintButton.addEventListener("click", () => {
        if (isTransitioning || !isHintAvailable()) {
          return;
        }

        startHintSequence();
      });
    }

    window.triggerEnigmeHint = () => {
      if (isTransitioning || !isHintAvailable()) {
        return false;
      }

      startHintSequence();
      return true;
    };

    function preloadAssets(paths) {
      paths.forEach((path) => {
        const img = new Image();
        img.src = path;
      });
    }

    function parseDialogues(rawValue, fallback) {
      if (!rawValue) {
        return fallback;
      }

      try {
        const parsed = JSON.parse(rawValue);
        return Array.isArray(parsed) && parsed.length > 0 ? parsed : fallback;
      } catch (error) {
        return fallback;
      }
    }

    function cancelTypewriter() {
      typeRunId += 1;
      isTyping = false;

      if (typeTimerId !== null) {
        window.clearTimeout(typeTimerId);
        typeTimerId = null;
      }

      dialogText.classList.remove("is-typing");
    }

    function clearSettleTimer() {
      if (settleTimerId !== null) {
        window.clearTimeout(settleTimerId);
        settleTimerId = null;
      }
    }

    function typeDialogue(text, onComplete) {
      cancelTypewriter();
      clearSettleTimer();
      activeTypeText = text;
      isTyping = true;

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
          isTyping = false;

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

    function setMageFrame(frameIndex) {
      const safeIndex = Math.min(frameIndex, mageFrames.length - 1);

      if (mode === "hint" && safeIndex < hintFrames.length) {
        mageImage.src = hintFrames[safeIndex];
        mageImage.alt = `Mage indice phase ${safeIndex + 1}`;
        return;
      }

      mageImage.src = mageFrames[safeIndex];
      mageImage.alt = `Mage phase ${safeIndex + 1}`;
    }

    function swapMageFrame(nextIndex) {
      if (switchTimerId !== null) {
        window.clearTimeout(switchTimerId);
        switchTimerId = null;
      }

      mageButton.classList.add("is-switching");

      switchTimerId = window.setTimeout(() => {
        setMageFrame(nextIndex);
        mageButton.classList.remove("is-switching");
        switchTimerId = null;
      }, 120);
    }

    function handleSequenceClick(dialogues) {
      clearSettleTimer();

      if (currentIndex < dialogues.length - 1) {
        currentIndex += 1;
        swapMageFrame(currentIndex);
        syncHintAvailability();
        typeDialogue(dialogues[currentIndex], handleSequenceCompletion);
      }
    }

    function handleSequenceCompletion() {
      syncHintAvailability();

      if (mode !== "main" && mode !== "hint") {
        return;
      }

      const activeDialogues = mode === "main" ? mainDialogues : hintDialogues;

      if (currentIndex !== activeDialogues.length - 1) {
        return;
      }

      settleTimerId = window.setTimeout(() => {
        settleTimerId = null;

        if (mode === "main" || mode === "hint") {
          activateRiddleMode();
        }
      }, settleDelay);
    }

    function activateRiddleMode() {
      isTransitioning = true;
      mode = "transition";
      syncBackLink();
      cancelTypewriter();
      clearSettleTimer();
      dialogText.textContent = activeTypeText;

      if (switchTimerId !== null) {
        window.clearTimeout(switchTimerId);
        switchTimerId = null;
      }

      mageButton.disabled = true;
      stage.classList.remove("is-replay-active");
      stage.classList.add("is-fogging");

      window.setTimeout(() => {
        stage.classList.remove("is-intro-active", "is-replay-active");
        stage.classList.add("is-riddle-active", "is-hint-available");

        intro.setAttribute("aria-hidden", "true");

        if (responseInput) {
          responseInput.disabled = false;
          responseInput.focus({ preventScroll: true });
        }

        setHintButtonEnabled(true);

        window.setTimeout(() => {
          stage.classList.remove("is-fogging");
          isTransitioning = false;
          mode = "riddle";
          syncBackLink();
        }, introFadeDuration);
      }, fogDuration);
    }

    function startHintSequence() {
      cancelTypewriter();
      clearSettleTimer();
      setHintButtonEnabled(false);

      if (switchTimerId !== null) {
        window.clearTimeout(switchTimerId);
        switchTimerId = null;
      }

      if (mode === "main") {
        mode = "hint";
        syncBackLink();
        currentIndex = 0;
        stage.classList.remove("is-hint-available");
        setMageFrame(0);
        typeDialogue(hintDialogues[currentIndex], handleSequenceCompletion);
        return;
      }

      if (mode !== "riddle") {
        return;
      }

      isTransitioning = true;
      mode = "transition";
      stage.classList.remove("is-hint-available");
      stage.classList.add("is-fogging");
      mageButton.disabled = true;

      if (responseInput) {
        responseInput.disabled = true;
      }

      window.setTimeout(() => {
        currentIndex = 0;
        stage.classList.remove("is-riddle-active");
        stage.classList.add("is-intro-active", "is-replay-active");
        intro.setAttribute("aria-hidden", "false");

        mageButton.disabled = false;
        mode = "hint";
        syncBackLink();
        setMageFrame(0);
        typeDialogue(hintDialogues[currentIndex], handleSequenceCompletion);

        window.setTimeout(() => {
          stage.classList.remove("is-fogging");
          isTransitioning = false;
        }, introFadeDuration);
      }, fogDuration);
    }

    function syncBackLink() {
      if (!backLink) {
        return;
      }

      // During dialogue/hint phases, keep back disabled and visibly grayed out.
      if (mode === "main" || mode === "hint" || mode === "transition") {
        backLink.removeAttribute("href");
        backLink.setAttribute("aria-disabled", "true");
        backLink.setAttribute("aria-label", "Retour indisponible pendant la sequence");
        backLink.style.pointerEvents = "none";

        if (backImage) {
          backImage.src = "assets/img/Magicien/retour_off.png";
        }

        return;
      }

      backLink.setAttribute("href", abandonUrl || responseUrl);
      backLink.removeAttribute("aria-disabled");
      backLink.setAttribute("aria-label", "Abandonner l enigme");
      backLink.style.pointerEvents = "";

      if (backImage) {
        backImage.src = abandonBackImage;
      }
    }

    function syncHintAvailability() {
      if (mode === "riddle") {
        setHintButtonEnabled(true);
        return;
      }

      if (mode === "main" && currentIndex >= hintUnlockStep) {
        setHintButtonEnabled(true);
        return;
      }

      setHintButtonEnabled(false);
    }

    function setHintButtonEnabled(isEnabled) {
      if (!hintButton) {
        return;
      }

      hintButton.disabled = !isEnabled;
      hintButton.setAttribute("aria-hidden", isEnabled ? "false" : "true");
      stage.classList.toggle("is-hint-available", isEnabled);
    }

    function isHintAvailable() {
      return mode === "riddle" || (mode === "main" && currentIndex >= hintUnlockStep);
    }
  });
})();



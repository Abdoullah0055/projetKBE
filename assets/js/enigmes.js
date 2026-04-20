(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", () => {
    const stage = document.querySelector(".enigmes-stage");
    const intro = document.getElementById("enigmesIntro");
    const mageButton = document.getElementById("enigmesMage");
    const mageImage = document.getElementById("enigmesMageImage");
    const dialogText = document.getElementById("enigmesDialogText");
    const replayButton = document.getElementById("enigmesReplayBtn");
    const responseInput = document.getElementById("enigmesResponseInput");

    if (!stage || !intro || !mageButton || !mageImage || !dialogText) {
      return;
    }

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

    const dialogues = [
      "Bienvenue, voyageur. Avant de repondre, je vais vous expliquer comment cette enigme se resout.",
      "Premier principe: lis chaque indice lentement, parce qu'un seul mot peut changer tout le sens.",
      "Deuxieme principe: elimine les reponses faciles qui ne respectent pas tous les indices en meme temps.",
      "Troisieme principe: quand tu hesites entre deux pistes, garde celle qui reste logique du debut a la fin.",
      "Si vous avez besoin de revoir l'enigme, cliquez sur l'icone i en haut a gauche de la page de reponse.",
      "Enigme, partie 1: Je parle sans bouche et j'entends sans oreilles.",
      "Enigme, partie 2: Je n'ai pas de corps, mais je prends vie avec le vent. Qui suis-je?",
      "L'enigme est maintenant entre vos mains... il est a vous de jouer."
    ];

    const backgroundFrames = [
      "assets/img/Magicien/gone.png",
      "assets/img/Magicien/respond.png"
    ];

    const uiAssets = [
      "assets/img/Magicien/scroll.png",
      "assets/img/Magicien/info.png"
    ];

    const riddleStartIndex = 5;
    const riddleEndIndex = 7;

    preloadAssets([...mageFrames, ...backgroundFrames, ...uiAssets]);

    let mode = "intro";
    let currentIndex = 0;
    let replayIndex = riddleStartIndex;
    let isTransitioning = false;
    let switchTimerId = null;
    let typeTimerId = null;
    let typeRunId = 0;

    const typeDelay = 35;
    const fogDuration = 1050;
    const introFadeDuration = 500;

    stage.classList.add("is-intro-active");
    stage.classList.remove("is-riddle-active", "is-replay-active");

    if (responseInput) {
      responseInput.disabled = true;
    }

    if (replayButton) {
      replayButton.setAttribute("aria-hidden", "true");
    }

    setMageFrame(currentIndex);
    typeDialogue(dialogues[currentIndex]);

    mageButton.addEventListener("click", () => {
      if (isTransitioning) {
        return;
      }

      if (mode === "intro") {
        handleIntroClick();
        return;
      }

      if (mode === "replay") {
        handleReplayClick();
      }
    });

    if (replayButton) {
      replayButton.addEventListener("click", () => {
        if (isTransitioning || mode !== "riddle") {
          return;
        }

        startRiddleReplay();
      });
    }

    function preloadAssets(paths) {
      paths.forEach((path) => {
        const img = new Image();
        img.src = path;
      });
    }

    function cancelTypewriter() {
      typeRunId += 1;

      if (typeTimerId !== null) {
        window.clearTimeout(typeTimerId);
        typeTimerId = null;
      }

      dialogText.classList.remove("is-typing");
    }

    function typeDialogue(text) {
      cancelTypewriter();

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
          return;
        }

        dialogText.textContent += text.charAt(charIndex);
        charIndex += 1;
        typeTimerId = window.setTimeout(step, typeDelay);
      };

      step();
    }

    function setMageFrame(frameIndex) {
      mageImage.src = mageFrames[frameIndex];
      mageImage.alt = `Mage phase ${frameIndex + 1}`;
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

    function handleIntroClick() {
      if (currentIndex < dialogues.length - 1) {
        currentIndex += 1;
        swapMageFrame(currentIndex);
        typeDialogue(dialogues[currentIndex]);
        return;
      }

      activateRiddleMode();
    }

    function handleReplayClick() {
      if (replayIndex < riddleEndIndex) {
        replayIndex += 1;
        swapMageFrame(replayIndex);
        typeDialogue(dialogues[replayIndex]);
        return;
      }

      finishRiddleReplay();
    }

    function activateRiddleMode() {
      isTransitioning = true;
      mode = "transition";
      cancelTypewriter();

      if (switchTimerId !== null) {
        window.clearTimeout(switchTimerId);
        switchTimerId = null;
      }

      mageButton.disabled = true;
      stage.classList.remove("is-replay-active");
      stage.classList.add("is-fogging");

      window.setTimeout(() => {
        stage.classList.remove("is-intro-active", "is-replay-active");
        stage.classList.add("is-riddle-active");

        intro.setAttribute("aria-hidden", "true");

        if (responseInput) {
          responseInput.disabled = false;
          responseInput.focus({ preventScroll: true });
        }

        if (replayButton) {
          replayButton.setAttribute("aria-hidden", "false");
        }

        window.setTimeout(() => {
          stage.classList.remove("is-fogging");
          isTransitioning = false;
          mode = "riddle";
        }, introFadeDuration);
      }, fogDuration);
    }

    function startRiddleReplay() {
      isTransitioning = true;
      mode = "transition";
      replayIndex = riddleStartIndex;

      cancelTypewriter();

      if (switchTimerId !== null) {
        window.clearTimeout(switchTimerId);
        switchTimerId = null;
      }

      stage.classList.add("is-fogging");
      mageButton.disabled = true;

      if (responseInput) {
        responseInput.disabled = true;
      }

      if (replayButton) {
        replayButton.setAttribute("aria-hidden", "true");
      }

      window.setTimeout(() => {
        stage.classList.remove("is-riddle-active");
        stage.classList.add("is-intro-active", "is-replay-active");
        intro.setAttribute("aria-hidden", "false");

        mageButton.disabled = false;
        setMageFrame(replayIndex);
        typeDialogue(dialogues[replayIndex]);

        window.setTimeout(() => {
          stage.classList.remove("is-fogging");
          isTransitioning = false;
          mode = "replay";
        }, introFadeDuration);
      }, fogDuration);
    }

    function finishRiddleReplay() {
      isTransitioning = true;
      mode = "transition";
      cancelTypewriter();

      mageButton.disabled = true;
      stage.classList.add("is-fogging");

      if (switchTimerId !== null) {
        window.clearTimeout(switchTimerId);
        switchTimerId = null;
      }

      window.setTimeout(() => {
        stage.classList.remove("is-intro-active", "is-replay-active");
        stage.classList.add("is-riddle-active");

        intro.setAttribute("aria-hidden", "true");

        if (responseInput) {
          responseInput.disabled = false;
          responseInput.focus({ preventScroll: true });
        }

        if (replayButton) {
          replayButton.setAttribute("aria-hidden", "false");
        }

        window.setTimeout(() => {
          stage.classList.remove("is-fogging");
          isTransitioning = false;
          mode = "riddle";
        }, introFadeDuration);
      }, fogDuration);
    }
  });
})();

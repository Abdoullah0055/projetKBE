(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", () => {
        const stage = document.querySelector(".enigmes-stage");
        const mageButton = document.getElementById("enigmesMage");
        const mageImage = document.getElementById("enigmesMageImage");
        const dialogText = document.getElementById("enigmesDialogText");
        const dialogHint = document.getElementById("enigmesDialogHint");
        const summary = document.getElementById("resultatSummary");

        if (!stage || !mageButton || !mageImage || !dialogText || !dialogHint || !summary) {
            return;
        }

        const dialogues = parseDialogues(stage.dataset.mainDialogues);
        const continueHref = stage.dataset.continueHref || "roadmap.php";
        const retryHref = stage.dataset.retryHref || "";
        const isCorrect = stage.dataset.isCorrect === "1";
        const isAbandoned = stage.dataset.isAbandoned === "1";

        const successFrames = [
            "assets/img/Magicien/houra.png",
            "assets/img/Magicien/houra.png",
            "assets/img/Magicien/mage_rigole.png",
            "assets/img/Magicien/houra.png",
            "assets/img/Magicien/mage8.png"
        ];

        const failureFrames = [
            "assets/img/Magicien/furieux.png",
            "assets/img/Magicien/mage8.png"
        ];

        const abandonedFrames = [
            "assets/img/Magicien/womp.png"
        ];

        let currentIndex = 0;
        let switchTimerId = null;
        let typeTimerId = null;
        let typeRunId = 0;
        let isTransitioning = false;
        const typeDelay = 35;
        const fogDuration = 1050;
        const revealDelay = 500;

        if (dialogues.length === 0) {
            showSummary();
            return;
        }

        setMageFrame(0);
        typeDialogue(dialogues[0].text, updateHint);

        mageButton.addEventListener("click", () => {
            if (isTransitioning) {
                return;
            }

            if (currentIndex < dialogues.length - 1) {
                currentIndex += 1;
                swapMageFrame(currentIndex);
                typeDialogue(dialogues[currentIndex].text, updateHint);
                return;
            }

            startSummaryTransition();
        });

        function parseDialogues(rawValue) {
            if (!rawValue) {
                return [];
            }

            try {
                const parsed = JSON.parse(rawValue);
                return Array.isArray(parsed) && parsed.length > 0 ? parsed : [];
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

        function typeDialogue(text, onComplete) {
            clearTypewriter();

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

        function setMageFrame(frameIndex) {
            const dialogue = dialogues[frameIndex];

            if (dialogue && dialogue.frame) {
                mageImage.src = dialogue.frame;
                mageImage.alt = "Mage phase " + (frameIndex + 1);
                return;
            }

            let frames = failureFrames;

            if (isAbandoned) {
                frames = abandonedFrames;
            } else if (isCorrect) {
                frames = successFrames;
            }

            const safeIndex = Math.min(frameIndex, frames.length - 1);
            mageImage.src = frames[safeIndex];
            mageImage.alt = "Mage phase " + (safeIndex + 1);
        }

        function swapMageFrame(nextIndex) {
            if (switchTimerId !== null) {
                window.clearTimeout(switchTimerId);
            }

            mageButton.classList.add("is-switching");

            switchTimerId = window.setTimeout(() => {
                setMageFrame(nextIndex);
                mageButton.classList.remove("is-switching");
                switchTimerId = null;
            }, 120);
        }

        function updateHint() {
            if (currentIndex < dialogues.length - 1) {
                dialogHint.textContent = "Cliquez sur le mage pour continuer.";
            } else {
                dialogHint.textContent = "Cliquez sur le mage pour voir le resultat.";
            }
        }

        function startSummaryTransition() {
            isTransitioning = true;
            clearTypewriter();
            mageButton.disabled = true;
            stage.classList.add("is-fogging");

            window.setTimeout(() => {
                showSummary();

                window.setTimeout(() => {
                    stage.classList.remove("is-fogging");
                    isTransitioning = false;
                }, revealDelay);
            }, fogDuration);
        }

        function showSummary() {
            stage.classList.remove("is-intro-active");
            summary.removeAttribute("hidden");
            summary.setAttribute("aria-hidden", "false");
        }
    });
})();

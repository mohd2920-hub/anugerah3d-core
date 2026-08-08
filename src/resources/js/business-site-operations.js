const formatElapsedTime = (startedAt, endedAt = Date.now()) => {
    const elapsedSeconds = Math.max(0, Math.floor((endedAt - startedAt) / 1000));
    const hours = String(Math.floor(elapsedSeconds / 3600)).padStart(2, "0");
    const minutes = String(Math.floor((elapsedSeconds % 3600) / 60)).padStart(2, "0");
    const seconds = String(elapsedSeconds % 60).padStart(2, "0");
    return `${hours}:${minutes}:${seconds}`;
};

document.querySelectorAll("[data-business-timer]").forEach((timer) => {
    const openedAt = new Date(timer.dataset.openedAt).getTime();
    const tick = () => { timer.textContent = formatElapsedTime(openedAt); };
    tick();
    window.setInterval(tick, 1000);
});

document.querySelectorAll("[data-attendance-timer]").forEach((timer) => {
    const signedInAt = new Date(timer.dataset.signedInAt).getTime();
    const signedOutAt = timer.dataset.signedOutAt
        ? new Date(timer.dataset.signedOutAt).getTime()
        : null;
    const tick = () => { timer.textContent = formatElapsedTime(signedInAt, signedOutAt ?? Date.now()); };
    tick();
    if (signedOutAt === null) window.setInterval(tick, 1000);
});

const businessSitesRoot = document.querySelector("[data-business-sites-root]");
const stopModal = businessSitesRoot?.querySelector("[data-stop-business-modal]");
const stopForm = stopModal?.querySelector("[data-stop-business-form]");
const closeStopModal = () => {
    stopModal?.classList.add("hidden");
    stopModal?.classList.remove("flex");
    document.body.classList.remove("overflow-hidden");
};

businessSitesRoot?.querySelectorAll("[data-stop-business]").forEach((button) => {
    button.addEventListener("click", () => {
        stopForm.action = button.dataset.action;
        stopModal.querySelector("[data-stop-business-site]").textContent = button.dataset.siteName;
        stopModal.classList.remove("hidden");
        stopModal.classList.add("flex");
        document.body.classList.add("overflow-hidden");
    });
});
stopModal?.querySelectorAll("[data-close-stop-business]").forEach((button) => button.addEventListener("click", closeStopModal));

const checkinForm = document.querySelector("[data-pos-checkin-form]");
const siteSelect = checkinForm?.querySelector("[data-pos-site-select]");
const closedModal = document.querySelector("[data-site-closed-modal]");
const openClosedModal = () => {
    closedModal?.classList.remove("hidden");
    closedModal?.classList.add("flex");
    document.body.classList.add("overflow-hidden");
};
const closeClosedModal = () => {
    closedModal?.classList.add("hidden");
    closedModal?.classList.remove("flex");
    document.body.classList.remove("overflow-hidden");
};

checkinForm?.addEventListener("submit", (event) => {
    if (siteSelect?.selectedOptions[0]?.dataset.siteOpen === "false") {
        event.preventDefault();
        openClosedModal();
    }
});
closedModal?.querySelectorAll("[data-close-site-closed]").forEach((button) => button.addEventListener("click", closeClosedModal));
if (closedModal?.dataset.openOnLoad === "true") openClosedModal();


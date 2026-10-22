
const API_URL = "/WEB%20DEVELOPMENT/HTML/events_api.php";

const eventForm = document.getElementById("eventForm");
const eventsTable = document.getElementById("eventsTable");
const message = document.getElementById("message");
const loadingMessage = document.getElementById("loadingMessage");

const saveBtn = document.getElementById("saveBtn");
const cancelBtn = document.getElementById("cancelBtn");
const formHeading = document.getElementById("formHeading");

let csrfToken = "";
let editingId = null;
let busy = false;

// Show message
function showMessage(text, success = true) {
    message.textContent = text;
    message.className = success ? "success" : "error";
}

// Send request to PHP API
async function requestAPI(method, data = null) {
    const options = {
        method: method,
        credentials: "same-origin",
        headers: {
            "Accept": "application/json"
        }
    };

    if (method === "POST") {
        options.headers["Content-Type"] = "application/json";

        options.body = JSON.stringify({
            ...data,
            csrf_token: csrfToken
        });
    }

    const response = await fetch(API_URL, options);

    let result;

    try {
        result = await response.json();
    } catch {
        throw new Error(
            "The API returned HTML instead of JSON. Check the API URL and PHP errors."
        );
    }

    if (result.data && result.data.csrf_token) {
        csrfToken = result.data.csrf_token;
    }

    if (!response.ok || !result.success) {
        if (response.status === 401) {
            throw new Error("Please login again as admin.");
        }

        throw new Error(result.message || "Something went wrong.");
    }

    return result;
}

// Load and display events
async function loadEvents() {
    loadingMessage.textContent = "Loading events...";
    eventsTable.replaceChildren();

    try {
        const result = await requestAPI("GET");
        const events = result.data || [];

        if (events.length === 0) {
            const row = eventsTable.insertRow();
            const cell = row.insertCell();
            cell.colSpan = 7;
            cell.textContent = "No events available.";
            return;
        }

        events.forEach(event => {
            const row = eventsTable.insertRow();

            const values = [
                event.event_id,
                event.title,
                event.description || "",
                event.event_date,
                event.event_time,
                event.venue
            ];

            values.forEach(value => {
                const cell = row.insertCell();
                cell.textContent = value ?? "";
            });

            const actionCell = row.insertCell();

            const editBtn = document.createElement("button");
            editBtn.textContent = "Edit";
            editBtn.className = "edit-btn";
            editBtn.type = "button";

            editBtn.addEventListener("click", () => {
                editEvent(event);
            });

            const deleteBtn = document.createElement("button");
            deleteBtn.textContent = "Delete";
            deleteBtn.className = "delete-btn";
            deleteBtn.type = "button";

            deleteBtn.addEventListener("click", () => {
                deleteEvent(event.event_id);
            });

            actionCell.append(editBtn, deleteBtn);
        });

    } catch (error) {
        const row = eventsTable.insertRow();
        const cell = row.insertCell();

        cell.colSpan = 7;
        cell.textContent = error.message;

        showMessage(error.message, false);

    } finally {
        loadingMessage.textContent = "";
    }
}

// ADD OR UPDATE EVENT
eventForm.addEventListener("submit", async function(event) {
    event.preventDefault();

    if (busy) return;

    const eventData = {
        title: document.getElementById("title").value.trim(),
        description: document.getElementById("description").value.trim(),
        event_date: document.getElementById("event_date").value,
        event_time: document.getElementById("event_time").value,
        venue: document.getElementById("venue").value.trim()
    };

    const action = editingId !== null ? "update" : "add";

    if (editingId !== null) {
        eventData.event_id = Number(editingId);
    }

    busy = true;
    saveBtn.disabled = true;
    showMessage("Saving event...");

    try {
        const result = await requestAPI("POST", {
            action: action,
            ...eventData
        });

        showMessage(result.message);

        resetForm();
        await loadEvents();

    } catch (error) {
        showMessage(error.message, false);

    } finally {
        busy = false;
        saveBtn.disabled = false;
    }
});

// Fill form for editing
function editEvent(event) {
    editingId = event.event_id;

    document.getElementById("title").value = event.title;
    document.getElementById("description").value =
        event.description || "";

    document.getElementById("event_date").value = event.event_date;
    document.getElementById("event_time").value =
        String(event.event_time || "").slice(0, 5);

    document.getElementById("venue").value = event.venue;

    formHeading.textContent = "Edit Event";
    saveBtn.textContent = "Update Event";
    cancelBtn.hidden = false;

    showMessage("Edit the details and click Update Event.");

    window.scrollTo({
        top: 0,
        behavior: "smooth"
    });
}

// DELETE EVENT
async function deleteEvent(id) {
    if (busy) return;

    const confirmed = confirm(
        "Are you sure you want to delete this event?"
    );

    if (!confirmed) return;

    busy = true;

    try {
        const result = await requestAPI("POST", {
            action: "delete",
            event_id: Number(id)
        });

        if (String(editingId) === String(id)) {
            resetForm();
        }

        showMessage(result.message);
        await loadEvents();

    } catch (error) {
        showMessage(error.message, false);

    } finally {
        busy = false;
    }
}

// Reset form
function resetForm() {
    eventForm.reset();
    editingId = null;

    formHeading.textContent = "Add New Event";
    saveBtn.textContent = "Add Event";
    cancelBtn.hidden = true;
}

cancelBtn.addEventListener("click", resetForm);

document.getElementById("refreshBtn").addEventListener(
    "click",
    loadEvents
);

// Load events when page opens
loadEvents();

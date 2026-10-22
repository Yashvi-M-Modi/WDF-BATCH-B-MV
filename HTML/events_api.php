<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Events API</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #F0F4F8;
            color: #222;
        }

        .container {
            width: 100%;
            max-width: 1000px;
            margin: auto;
        }

        h1 {
            color: #001f3f;
            text-align: center;
        }

        .form-box, .list-box {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 2px 8px #00000012;
        }

        label {
            display: block;
            margin-top: 12px;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            padding: 11px;
            margin-top: 5px;
            border: 1px solid #bbb;
            border-radius: 5px;
            font: inherit;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        button {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 8px 5px 0 0;
            background: #004b87;
            color: white;
        }

        button:hover {
            opacity: 0.9;
        }

        .delete-btn {
            background: #b42318;
        }

        .cancel-btn {
            background: #555;
        }

        .event-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 12px 0;
            border-radius: 7px;
            overflow-wrap: anywhere;
        }

        .event-card h3 {
            color: #004b87;
            margin-top: 0;
        }

        .message {
            padding: 12px;
            border-radius: 5px;
            background: #e5edf5;
            margin: 12px 0;
        }

        .empty {
            text-align: center;
            color: #666;
        }

        .hidden {
            display: none;
        }

        @media (max-width: 600px) {
            body {
                padding: 10px;
            }

            .form-box, .list-box {
                padding: 14px;
            }

            button {
                width: 100%;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <h1>StudentHub Events</h1>
    <p>Manage events using Fetch API without page reload.</p>

    <div id="message" class="message" role="status" aria-live="polite">
        Loading events...
    </div>

    <div class="form-box">
        <h2 id="formTitle">Add Event</h2>

        <form id="eventForm">

            <input type="hidden" id="event_id">

            <label for="title">Event Title</label>
            <input type="text" id="title" maxlength="200" required>

            <label for="description">Description</label>
            <textarea id="description" maxlength="10000"></textarea>

            <label for="event_date">Event Date</label>
            <input type="date" id="event_date" required>

            <label for="event_time">Event Time</label>
            <input type="time" id="event_time" required>

            <label for="venue">Venue</label>
            <input type="text" id="venue" maxlength="200" required>

            <button type="submit" id="saveBtn">Add Event</button>

            <button
                type="button"
                id="cancelBtn"
                class="cancel-btn hidden"
            >
                Cancel Edit
            </button>

        </form>
    </div>

    <div class="list-box">
        <h2>All Events</h2>

        <button type="button" id="refreshBtn">
            Refresh Events
        </button>

        <div id="eventList">
            <p class="empty">Loading...</p>
        </div>
    </div>

</div>

<script>
    const API_URL = "events_api.php";

    let csrfToken = "";
    let events = [];

    const form = document.getElementById("eventForm");
    const message = document.getElementById("message");
    const eventList = document.getElementById("eventList");

    const eventId = document.getElementById("event_id");
    const titleInput = document.getElementById("title");
    const descriptionInput = document.getElementById("description");
    const dateInput = document.getElementById("event_date");
    const timeInput = document.getElementById("event_time");
    const venueInput = document.getElementById("venue");

    const formTitle = document.getElementById("formTitle");
    const saveBtn = document.getElementById("saveBtn");
    const cancelBtn = document.getElementById("cancelBtn");

    function showMessage(text) {
        message.textContent = text;
    }

    // Fetch API helper
    async function apiRequest(url, options = {}) {
        const response = await fetch(url, {
            credentials: "same-origin",
            ...options
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Request failed.");
        }

        return result;
    }

    // Load CSRF token
    async function loadToken() {
        const result = await apiRequest(API_URL + "?token=1");
        csrfToken = result.data.csrf_token;
    }

    // LIST events
    async function loadEvents() {
        try {
            eventList.textContent = "Loading events...";

            const result = await apiRequest(API_URL);

            events = result.data || [];

            displayEvents();

            showMessage("Events loaded successfully.");

        } catch (error) {
            eventList.textContent = "";
            showMessage(error.message);
        }
    }

    // Display event cards safely
    function displayEvents() {
        eventList.textContent = "";

        if (events.length === 0) {
            const p = document.createElement("p");
            p.className = "empty";
            p.textContent = "No events found. Add your first event.";
            eventList.appendChild(p);
            return;
        }

        events.forEach(event => {
            const card = document.createElement("div");
            card.className = "event-card";

            const heading = document.createElement("h3");
            heading.textContent = event.title;

            const desc = document.createElement("p");
            desc.textContent = event.description || "No description";

            const date = document.createElement("p");
            date.textContent = "Date: " + event.event_date;

            const time = document.createElement("p");
            time.textContent = "Time: " + (event.event_time || "Not set");

            const venue = document.createElement("p");
            venue.textContent = "Venue: " + event.venue;

            const editBtn = document.createElement("button");
            editBtn.textContent = "Edit";
            editBtn.type = "button";
            editBtn.addEventListener("click", () => editEvent(event));

            const deleteBtn = document.createElement("button");
            deleteBtn.textContent = "Delete";
            deleteBtn.type = "button";
            deleteBtn.className = "delete-btn";
            deleteBtn.addEventListener("click", () => deleteEvent(event.event_id));

            card.append(
                heading,
                desc,
                date,
                time,
                venue,
                editBtn,
                deleteBtn
            );

            eventList.appendChild(card);
        });
    }

    // ADD or UPDATE event
    form.addEventListener("submit", async function(e) {
        e.preventDefault();

        if (!form.reportValidity()) {
            return;
        }

        const isEditing = eventId.value !== "";

        const data = {
            action: isEditing ? "update" : "add",
            csrf_token: csrfToken,
            title: titleInput.value.trim(),
            description: descriptionInput.value.trim(),
            event_date: dateInput.value,
            event_time: timeInput.value,
            venue: venueInput.value.trim()
        };

        if (isEditing) {
            data.event_id = Number(eventId.value);
        }

        saveBtn.disabled = true;

        try {
            const result = await apiRequest(API_URL, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            });

            showMessage(result.message);

            resetForm();

            await loadEvents();

        } catch (error) {
            showMessage(error.message);
        } finally {
            saveBtn.disabled = false;
        }
    });

    // Load event details into form
    function editEvent(event) {
        eventId.value = event.event_id;
        titleInput.value = event.title;
        descriptionInput.value = event.description || "";
        dateInput.value = event.event_date;
        timeInput.value = (event.event_time || "").substring(0, 5);
        venueInput.value = event.venue;

        formTitle.textContent = "Update Event";
        saveBtn.textContent = "Update Event";
        cancelBtn.classList.remove("hidden");

        form.scrollIntoView({
            behavior: "smooth",
            block: "start"
        });
    }

    // DELETE event
    async function deleteEvent(id) {
        if (!confirm("Are you sure you want to delete this event?")) {
            return;
        }

        try {
            const result = await apiRequest(API_URL, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    action: "delete",
                    csrf_token: csrfToken,
                    event_id: Number(id)
                })
            });

            showMessage(result.message);

            if (eventId.value === String(id)) {
                resetForm();
            }

            await loadEvents();

        } catch (error) {
            showMessage(error.message);
        }
    }

    // Reset form
    function resetForm() {
        form.reset();
        eventId.value = "";

        formTitle.textContent = "Add Event";
        saveBtn.textContent = "Add Event";
        cancelBtn.classList.add("hidden");
    }

    cancelBtn.addEventListener("click", resetForm);

    document.getElementById("refreshBtn").addEventListener(
        "click",
        loadEvents
    );

    // Initialize
    async function initialize() {
        try {
            await loadToken();
            await loadEvents();
        } catch (error) {
            showMessage(error.message);
            eventList.textContent = "";
        }
    }

    initialize();
</script>

</body>
</html>

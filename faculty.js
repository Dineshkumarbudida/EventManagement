document.addEventListener("DOMContentLoaded", function () {
    // ✅ Fetch and display Faculty details
    fetch("faculty_details.php")
        .then(response => response.json())
        .then(data => {
            document.getElementById("faculty-name").innerText = data.name;
            document.getElementById("faculty-branch").innerText = data.branch;
        })
        .catch(error => console.error("Error fetching faculty details:", error));

    // ✅ Select elements
    const uploadEventBtn = document.querySelector(".upload-button button");
    const eventFormModal = document.getElementById("event-form-modal");
    const closeButton = document.querySelector(".close-button");
    const eventForm = document.getElementById("event-form");
    const mediaInput = document.getElementById("upload-media");
    const eventTable = document.getElementById("events-table").querySelector("tbody");
    const filterSelect = document.getElementById("filter-select");
    let editingEventId = null;

    // ✅ Persist filter selection after refresh
    filterSelect.value = localStorage.getItem("selectedFilter") || "my-uploads";

    // ✅ Open and close form functions
    function openForm() {
        eventFormModal.style.display = "flex";
    }

    function closeForm() {
        eventFormModal.style.display = "none";
        eventForm.reset();
        document.getElementById("existing-media").style.display = "none";
        editingEventId = null; // ✅ Reset editing state
    }

    // ✅ Event: Click "Upload Event" button
    uploadEventBtn.addEventListener("click", function () {
        closeForm();
        openForm();
    });

    // ✅ Event: Click close button
    closeButton.addEventListener("click", closeForm);

    // ✅ Fetch and display events
    function loadEvents(filter = "my-uploads") {
        eventTable.innerHTML = `<tr><td colspan="6">Loading events...</td></tr>`;
        fetch(`get_events.php?filter=${filter}`)
            .then(response => response.json())
            .then(data => {
                eventTable.innerHTML = "";
                if (data.success) {
                    data.data.forEach(event => {
                        let mediaLinks = event.media?.length 
                            ? event.media.map(file => `<a href="${file}" target="_blank">View</a>`).join(", ")
                            : "No Media";

                        const newRow = document.createElement("tr");
                        newRow.innerHTML = `
                            <td>${event.name}</td>
                            <td>${event.description}</td>
                            <td>${event.start_date}</td>
                            <td>${event.end_date}</td>
                            <td>${mediaLinks}</td>
                            <td>
                                <button onclick="editEvent(${event.id})">Edit</button>
                            </td>
                        `;
                        eventTable.appendChild(newRow);
                    });
                } else {
                    console.error("Error fetching events:", data.message);
                }
            })
            .catch(error => console.error("Error fetching events:", error));
    }

    // ✅ Load events on page load with persisted filter
    loadEvents(filterSelect.value);

    // ✅ Event: Change filter and persist selection
    filterSelect.addEventListener("change", () => {
        localStorage.setItem("selectedFilter", filterSelect.value);
        loadEvents(filterSelect.value);
    });

    // ✅ Handle Form Submission
    eventForm.addEventListener("submit", function (event) {
        event.preventDefault();
        const submitButton = eventForm.querySelector("button[type='submit']");
        submitButton.disabled = true;
        submitButton.innerText = "Uploading...";

        // Get form values
        const eventName = document.getElementById("event-name").value.trim();
        const eventDescription = document.getElementById("event-description").value.trim();
        const fromDate = document.getElementById("from-date").value;
        const toDate = document.getElementById("to-date").value;
        const uploadedFiles = mediaInput.files;

        if (!eventName || !eventDescription || !fromDate || !toDate) {
            alert("Please fill all fields.");
            submitButton.disabled = false;
            submitButton.innerText = "Submit";
            return;
        }

        let formData = new FormData();
        formData.append("event_name", eventName);
        formData.append("description", eventDescription);
        formData.append("start_date", fromDate);
        formData.append("end_date", toDate);

        for (let i = 0; i < uploadedFiles.length; i++) {
            formData.append("media[]", uploadedFiles[i]);
        }

        if (editingEventId) {
            formData.append("event_id", editingEventId);
        }

        fetch("upload_event.php", { method: "POST", body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadEvents(filterSelect.value);
                    closeForm();
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(error => console.error("Error uploading event:", error))
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerText = "Submit";
            });
    });

    // ✅ Edit Event Function
    window.editEvent = function (eventId) {
        fetch(`get_events.php?id=${eventId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    let eventDetails = data.data[0];

                    // ✅ Fill form
                    document.getElementById("event-name").value = eventDetails.name;
                    document.getElementById("event-description").value = eventDetails.description;
                    document.getElementById("from-date").value = eventDetails.start_date;
                    document.getElementById("to-date").value = eventDetails.end_date;

                    // ✅ Store event ID
                    editingEventId = eventId;

                    // ✅ Media preview
                    const mediaContainer = document.getElementById("media-container");
                    mediaContainer.innerHTML = "";

                    if (eventDetails.media?.length) {
                        document.getElementById("existing-media").style.display = "block";
                        eventDetails.media.forEach(file => {
                            let mediaLink = document.createElement("a");
                            mediaLink.href = file;
                            mediaLink.target = "_blank";
                            mediaLink.innerText = "View Media";
                            mediaContainer.appendChild(mediaLink);
                            mediaContainer.appendChild(document.createElement("br"));
                        });
                    }
                    openForm();
                }
            })
            .catch(error => console.error("Error fetching event details:", error));
    };
});

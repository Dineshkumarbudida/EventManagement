document.addEventListener("DOMContentLoaded", function () {
    // Fetch and display HOD details
    fetch("hod_details.php")
        .then(response => response.json())
        .then(data => {
            document.getElementById("hod-name").innerText = data.name;
            document.getElementById("hod-branch").innerText = data.branch;
        })
        .catch(error => console.error("❌ Error fetching HOD details:", error));

    // Initialize Select2 for Faculty Dropdown
    $("#facultyDropdown").select2({
        placeholder: "Select Faculty",
        allowClear: true
    }).prop('disabled', true); // Initially disabled

    // Fetch Faculty List
    fetch("get_faculty.php")
        .then(response => response.json())
        .then(facultyList => {
            facultyList.forEach(faculty => {
                let newOption = new Option(faculty.username, faculty.id, false, false);
                $("#facultyDropdown").append(newOption);
            });
        })
        .catch(error => console.error("❌ Error fetching faculty list:", error));

    // ✅ Load previously selected filter from localStorage OR default to 'my-uploads'
    let savedFilter = localStorage.getItem("selectedFilter") || "my-uploads";

    // ✅ Enable/Disable Faculty Dropdown Based on Filter Selection
    document.getElementById("filter-select").addEventListener("change", function () {
        let selectedFilter = this.value;
        localStorage.setItem("selectedFilter", selectedFilter); // Save selection

        if (selectedFilter === "faculty-collaboration") {
            $("#facultyDropdown").prop('disabled', false);
        } else {
            $("#facultyDropdown").prop('disabled', true).val(null).trigger("change");
        }

        loadEvents(selectedFilter);
    });


    // ✅ Fetch and display events dynamically
    function loadEvents(filter) {
        fetch(`get_events.php?filter=${filter}`)
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    const tableHead = document.querySelector("#events-table thead tr");
                    const tableBody = document.querySelector("#events-table tbody");
    
                    tableBody.innerHTML = ""; // Clear previous rows
    
                    // ✅ Handle Dynamic Columns
                    let assignedHeader = document.getElementById("assigned-to-header");
    
                    if (filter === "faculty-collaboration") {
                        if (!assignedHeader) {
                            assignedHeader = document.createElement("th");
                            assignedHeader.id = "assigned-to-header";
                            assignedHeader.innerText = "Assigned To";
                            tableHead.appendChild(assignedHeader);  
                        }
                    } else {
                        if (assignedHeader) assignedHeader.remove();
                    }
    
                    // ✅ Handle "Created By" column for "faculty-uploads" filter
                    let createdByHeader = document.getElementById("created-by-header");
    
                    if (filter === "faculty-uploads") {
                        if (!createdByHeader) {
                            createdByHeader = document.createElement("th");
                            createdByHeader.id = "created-by-header";
                            createdByHeader.innerText = "Created By";
                            tableHead.insertBefore(createdByHeader, tableHead.children[1]);
                        }
                    } else {
                        if (createdByHeader) createdByHeader.remove();
                    }
    
                    // ✅ Populate Table Rows
                    response.data.forEach(event => {
                        let mediaLinks = "No Media";
                        if (Array.isArray(event.media) && event.media.length > 0) {
                            mediaLinks = event.media.map(file => 
                                `<a href="${file}" target="_blank">View</a>`).join(", ");
                        }
    
// In the loadEvents function, update the assignedToCell creation:
                        let assignedToCell = filter === "faculty-collaboration" ? 
                            `<td>${event.assigned_faculty_names || event.assigned_faculty || 'Not assigned'}</td>` : "";                        let createdByCell = filter === "faculty-uploads" ? `<td>${event.created_by}</td>` : "";
    
                            let row = `<tr id="event-${event.id}">
                            <td>${event.event_name}</td>  <!-- Changed from event.name -->
                            ${assignedToCell}
                            ${createdByCell}
                            <td>${event.description}</td>
                            <td>${event.from_date}</td>  <!-- Changed from event.start_date -->
                            <td>${event.to_date}</td>    <!-- Changed from event.end_date -->
                            <td>${mediaLinks}</td>
                            <td>
                                <button onclick="editEvent('${event.id}')">Edit</button>
                                <button onclick="deleteEvent('${event.id}')">Delete</button>
                            </td>
                        </tr>`;
                        tableBody.innerHTML += row;
                    });
                } else {
                    console.error("❌ Error: Failed to fetch events.");
                }
            })
            .catch(error => console.error("❌ Error fetching events:", error));
    }
    
    // ✅ Load saved filter on page load
    loadEvents(savedFilter);
    document.getElementById("event-form").addEventListener("submit", function (e) {
        e.preventDefault();
        let formData = new FormData(this);
        let eventId = this.getAttribute("data-event-id");

        let selectedFilter = localStorage.getItem("selectedFilter") || "my-uploads";
        formData.append("filter", selectedFilter);

        if (selectedFilter === "faculty-collaboration") {
            let selectedFaculties = $("#facultyDropdown").val();
            formData.append("selected_faculties", JSON.stringify(selectedFaculties));
        }

        if (eventId) {
            formData.append("id", eventId);
            formData.append("action", "update");
        }

        fetch("upload_event.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadEvents(selectedFilter); // Refresh events dynamically
                closeForm();
            } else {
                alert("❌ Error updating event: " + (data.message || "Unknown error"));
            }
        })
        .catch(error => console.error("❌ Error updating event:", error));
    });

    window.editEvent = function (id) {
        let row = document.querySelector(`#event-${id}`);
        let cells = row.children;
        let columnOffset = localStorage.getItem("selectedFilter") === "faculty-uploads" ? 1 : 0;
    
        let name = cells[0].innerText;
        let description = cells[2 + columnOffset].innerText;
        let startDate = cells[3 + columnOffset].innerText;
        let endDate = cells[4 + columnOffset].innerText;
    
        document.getElementById("form-title").innerText = "Edit Event";
        document.getElementById("event-name").value = name;
        document.getElementById("event-description").value = description;
        document.getElementById("from-date").value = startDate;
        document.getElementById("to-date").value = endDate;
    
        document.getElementById("event-form").setAttribute("data-event-id", id);
        document.getElementById("event-form-modal").style.display = "flex";
    };

    function openForm() {
        document.getElementById("event-form-modal").style.display = "flex";
    }

    function closeForm() {
        document.getElementById("event-form-modal").style.display = "none";
        document.getElementById("event-form").reset();
        document.getElementById("event-form").removeAttribute("data-event-id");
    }

    window.openForm = openForm;
    window.closeForm = closeForm;

    window.deleteEvent = function (id) {
        if (confirm("Are you sure you want to delete this event?")) {
            fetch("delete_event.php", {
                method: "POST",
                body: JSON.stringify({ id: id }),
                headers: {
                    "Content-Type": "application/json"
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadEvents(localStorage.getItem("selectedFilter") || "my-uploads");
                } else {
                    alert("❌ Error deleting event: " + (data.message || "Unknown error"));
                }
            })
            .catch(error => console.error("❌ Error deleting event:", error));
        }
    };
});  

// Function to create folders via API
async function createFolder(action, branchName, role, username) {
    const response = await fetch('manage_folders.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: action,
            branch_name: branchName,
            role: role,
            username: username,
        }),
    });

    const result = await response.json();
    console.log("Folder creation response:", result); // Debugging: Log the result
    return result;
}

// Add User Form Submission
document.getElementById("addUserForm").addEventListener("submit", async function (event) {
    event.preventDefault(); // Prevent the default form submission
    console.log("Form submitted"); // Debugging

    // Collect form data
    const formData = new FormData();
    formData.append("username", document.getElementById("userName").value); // Use "username" instead of "name"
    formData.append("password", document.getElementById("password").value);
    formData.append("email", document.getElementById("userEmail").value);
    formData.append("role", document.getElementById("userRole").value);
    formData.append("branch", document.getElementById("userBranch").value);

    console.log("Form data:", {
        username: document.getElementById("userName").value,
        password: document.getElementById("password").value,
        email: document.getElementById("userEmail").value,
        role: document.getElementById("userRole").value,
        branch: document.getElementById("userBranch").value
    }); // Debugging

    // Send the form data to add_user.php using fetch
    const addUserResponse = await fetch("add_user.php", {
        method: "POST",
        body: formData
    });
    const addUserData = await addUserResponse.json();
    console.log("Response from add_user.php:", addUserData); // Debugging

    if (addUserData.status === "success") {
        alert("User added successfully!");

        // Create folders based on the user's role
        const role = document.getElementById("userRole").value;
        const branchName = document.getElementById("userBranch").value;
        const username = document.getElementById("userName").value;

        if (role === "HOD") {
            const folderResult = await createFolder("add_user", branchName, "HOD", username);
            if (folderResult.status === "success") {
                alert("HOD folder created successfully!");
            } else {
                alert("Failed to create HOD folder: " + folderResult.message);
            }
        } else if (role === "Faculty") {
            const folderResult = await createFolder("add_user", branchName, "Faculty", username);
            if (folderResult.status === "success") {
                alert("Faculty folder created successfully!");
            } else {
                alert("Failed to create faculty folder: " + folderResult.message);
            }
        } else if (role === "Principal") {
            const folderResult = await createFolder("add_user", null, "Principal", username);
            if (folderResult.status === "success") {
                alert("Principal folder created successfully!");
            } else {
                alert("Failed to create Principal folder: " + folderResult.message);
            }
        }

        // Refresh the user list
        fetchUsers();
    } else {
        alert("Error: " + addUserData.message);
    }
});

// Add Branch Button
document.getElementById("addBranchButton").addEventListener("click", async function () {
    const newBranch = prompt("Enter the name of the new branch:").trim();
    if (!newBranch) {
        alert("Branch name cannot be empty.");
        return;
    }

    console.log("Adding branch:", newBranch); // Debugging

    // Send the new branch to add_branch.php
    const addBranchResponse = await fetch('add_branch.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ branch: newBranch.trim() })
    });
    const addBranchData = await addBranchResponse.json();
    console.log("Server Response:", addBranchData);  // Debugging

    if (addBranchData.status === "success") {
        alert("Branch added successfully!");

        // Create the branch folder
        const folderResult = await createFolder("add_branch", newBranch, null, null);
        if (folderResult.status === "success") {
            alert("Branch folder created successfully!");
        } else {
            alert("Failed to create branch folder: " + folderResult.message);
        }

        // Refresh the branches dropdown
        fetchBranches();
    } else {
        alert("Error: " + addBranchData.message);
    }
});

// Remove Branch Button
document.getElementById("removeBranchButton").addEventListener("click", async function () {
    const branchSelect = document.getElementById("userBranch");
    const selectedBranch = branchSelect.value;

    if (!selectedBranch) {
        alert("Please select a branch to remove.");
        return;
    }

    console.log("Removing branch:", selectedBranch); // Debugging

    // Send the selected branch to remove_branch.php
    const removeBranchResponse = await fetch("remove_branch.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ branch: selectedBranch })
    });
    const removeBranchData = await removeBranchResponse.json();
    console.log("Response from remove_branch.php:", removeBranchData); // Debugging

    if (removeBranchData.status === "success") {
        alert("Branch removed successfully!");
        // Remove the branch from the dropdown
        fetchBranches();
    } else {
        alert("Error: " + removeBranchData.message);
    }
});

// Remove User Button
document.getElementById("removeUserButton").addEventListener("click", async function () {
    const username = document.getElementById("removeUserInput").value.trim(); // Use "username" instead of "email"
    if (!username) {
        alert("Please enter a username.");
        return;
    }

    console.log("Removing user:", username); // Debugging

    // Send the username to remove_user.php
    const removeUserResponse = await fetch("remove_user.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username: username })  // Ensure username is being sent
    });
    const removeUserData = await removeUserResponse.json();
    console.log("Response from remove_user.php:", removeUserData); // Debugging

    if (removeUserData.status === "success") {
        alert("User removed successfully!");
        fetchUsers(); // Refresh the user list
    } else {
        alert("Error: " + removeUserData.message);
    }
});

// Logout Button
document.getElementById("logoutButton").addEventListener("click", async function () {
    const logoutResponse = await fetch("logout.php", {
        method: "POST"
    });
    const logoutData = await logoutResponse.json();
    console.log("Response from logout.php:", logoutData); // Debugging

    if (logoutData.status === "success") {
        alert("Logged out successfully!");
        // Redirect to the login page
        window.location.href = "login.html";
    } else {
        alert("Error: " + logoutData.message);
    }
});

// Fetch Users Function (to refresh the user list)
async function fetchUsers() {
    const fetchUsersResponse = await fetch("get_users.php");
    const usersData = await fetchUsersResponse.json();
    console.log("Fetched users:", usersData); // Debugging

    const userTable = document.getElementById("userTable");
    userTable.innerHTML = ""; // Clear existing rows

    usersData.forEach((user, index) => {
        const row = `<tr>
                        <td>${index + 1}</td>
                        <td>${user.username}</td> <!-- Display username instead of email -->
                        <td>${user.role}</td>
                        <td>${user.branch}</td>
                        <td>${user.date_created}</td>
                    </tr>`;
        userTable.innerHTML += row;
    });
}

// Fetch Branches Function (to refresh the branches dropdown)
async function fetchBranches() {
    const fetchBranchesResponse = await fetch("get_branches.php");
    const branchesData = await fetchBranchesResponse.json();
    console.log("Fetched branches:", branchesData); // Debugging

    const branchSelect = document.getElementById("userBranch");
    branchSelect.innerHTML = ""; // Clear the dropdown before updating
 
    branchesData.forEach(branch => {
        const option = document.createElement("option");
        option.value = branch.name;
        option.textContent = branch.name;
        branchSelect.appendChild(option);
    });
}

// Initial fetch of users and branches
fetchUsers();
fetchBranches();
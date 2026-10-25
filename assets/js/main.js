/**
 * Department Communication Management System
 * Rajarata University of Sri Lanka - ICT 1407 Database Systems
 * Simple Client-side JavaScript Helpers
 */

// Function to select all attendance radio buttons with a single click
function markAllStatus(status) {
    var radios = document.querySelectorAll('input[type="radio"][value="' + status + '"]');
    for (var i = 0; i < radios.length; i++) {
        radios[i].checked = true;
    }
}

// Confirmation prompt for important actions (cancellations, status updates)
function confirmAction(message) {
    return confirm(message || "Are you sure you want to proceed?");
}

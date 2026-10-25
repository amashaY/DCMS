<?php
/**
 * Department Communication Management System
 * Faculty of Applied Sciences, Rajarata University of Sri Lanka
 * Schedule New Meeting Page: schedule_meeting.php
 */

$page_title = 'Schedule Meeting';
require_once __DIR__ . '/includes/header.php';

// Access Control: Only HOD or Management Assistant can schedule meetings
if (!is_admin()) {
    echo "<div class='alert alert-danger'>Access Denied: Only HOD or Management Assistant can schedule meetings.</div>";
    require_once __DIR__ . '/includes/footer.php';
    exit();
}
?>

<div class="page-header">
    <div class="page-title">
        <h1>Schedule New Department Meeting</h1>
        <p>Plan a new meeting session across the 13 standing department meeting categories</p>
    </div>
    <div>
        <a href="meetings.php" class="btn btn-secondary">&larr; Back to Meetings</a>
    </div>
</div>

<div class="card" style="max-width: 750px; margin: 0 auto;">
    <div class="card-header">
        <h2>Meeting Details Form</h2>
    </div>
    <div class="card-body">
        <form action="meeting_actions.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="schedule_meeting">

            <div class="form-group">
                <label for="type_id" class="form-label">Department Meeting Category <span style="color: #e53e3e;">*</span></label>
                <select name="type_id" id="type_id" class="form-control" required>
                    <option value="">-- Select from 13 Department Meeting Types --</option>
                    <?php
                    $types = $conn->query("SELECT type_id, type_name FROM Meeting_Type ORDER BY type_id ASC");
                    if ($types) {
                        while ($t = $types->fetch_assoc()) {
                            echo '<option value="' . $t['type_id'] . '">' . $t['type_id'] . '. ' . htmlspecialchars($t['type_name']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="date" class="form-label">Meeting Date <span style="color: #e53e3e;">*</span></label>
                    <input type="date" name="date" id="date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="time" class="form-label">Meeting Time <span style="color: #e53e3e;">*</span></label>
                    <input type="time" name="time" id="time" class="form-control" value="09:00" required>
                </div>
            </div>

            <div class="form-group">
                <label for="location" class="form-label">Venue / Location</label>
                <input type="text" name="location" id="location" class="form-control" value="Department Boardroom" placeholder="e.g. Department Boardroom, Lab 01, or Zoom">
            </div>

            <div class="form-group">
                <label for="agenda_details" class="form-label">Initial Meeting Agenda Points (Optional)</label>
                <textarea name="agenda_details" id="agenda_details" class="form-control" rows="4" placeholder="1. Confirmation of previous minutes&#10;2. Current semester academic review&#10;3. Any other matters"></textarea>
            </div>

            <div class="form-group">
                <label for="material_file" class="form-label">Attach Pre-Meeting Material (Optional)</label>
                <input type="file" name="material_file" id="material_file" class="form-control">
                <small style="color: #718096; font-size: 11px;">Supported file formats: PDF, DOCX, PPTX, XLSX, TXT (Max 20MB)</small>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Schedule Meeting & Notify Staff</button>
                <a href="meetings.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

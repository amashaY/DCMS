<?php
/**
 * Department Communication Management System
 * Faculty of Applied Sciences, Rajarata University of Sri Lanka
 * Meeting Session Details: meeting_details.php
 */

$page_title = 'Meeting Details';
require_once __DIR__ . '/includes/header.php';

$meeting_id = (int)($_GET['id'] ?? 0);
$msg = $_GET['msg'] ?? '';
$err = $_GET['error'] ?? '';

if ($meeting_id <= 0) {
    header("Location: meetings.php");
    exit();
}

// 1. Fetch Meeting Information & Type
$stmt = $conn->prepare("
    SELECT sm.meeting_id, sm.type_id, sm.date, sm.time, sm.location, sm.status,
           mt.type_name, mt.description AS type_desc
    FROM Scheduled_Meeting sm
    LEFT JOIN Meeting_Type mt ON sm.type_id = mt.type_id
    WHERE sm.meeting_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $meeting_id);
$stmt->execute();
$meeting = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$meeting) {
    echo "<div class='alert alert-danger'>Meeting record not found. <a href='meetings.php'>Back to Meetings</a></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

// 2. Fetch Agenda
$ag_stmt = $conn->prepare("SELECT agenda_id, agenda_details FROM Agenda WHERE meeting_id = ? LIMIT 1");
$ag_stmt->bind_param("i", $meeting_id);
$ag_stmt->execute();
$agenda = $ag_stmt->get_result()->fetch_assoc();
$ag_stmt->close();

// 3. Fetch Decisions
$dec_stmt = $conn->prepare("SELECT report_id, decisions FROM Decision_Report WHERE meeting_id = ? LIMIT 1");
$dec_stmt->bind_param("i", $meeting_id);
$dec_stmt->execute();
$decision = $dec_stmt->get_result()->fetch_assoc();
$dec_stmt->close();

// 4. Fetch Attached Materials
$mat_stmt = $conn->prepare("SELECT material_id, file_name, file_path, uploaded_at FROM Meeting_Materials WHERE meeting_id = ? ORDER BY uploaded_at DESC");
$mat_stmt->bind_param("i", $meeting_id);
$mat_stmt->execute();
$materials_res = $mat_stmt->get_result();

// 5. Fetch Attendance summary
$att_stmt = $conn->prepare("
    SELECT 
        COUNT(*) AS total_staff,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS absent_count,
        SUM(CASE WHEN status = 'Excused' THEN 1 ELSE 0 END) AS excused_count
    FROM Attendance
    WHERE meeting_id = ?
");
$att_stmt->bind_param("i", $meeting_id);
$att_stmt->execute();
$att_summary = $att_stmt->get_result()->fetch_assoc();
$att_stmt->close();
?>

<!-- Feedback Messages -->
<?php if (!empty($msg)): ?>
    <div class="alert alert-success">
        <span>✓ <?= htmlspecialchars($msg); ?></span>
    </div>
<?php endif; ?>
<?php if (!empty($err)): ?>
    <div class="alert alert-danger">
        <span>✕ <?= htmlspecialchars($err); ?></span>
    </div>
<?php endif; ?>

<div class="page-header">
    <div class="page-title">
        <h1>Meeting Details: <?= htmlspecialchars($meeting['type_name']); ?></h1>
        <p>Session #<?= $meeting['meeting_id']; ?> &bull; Department of Computing</p>
    </div>
    <div>
        <a href="meetings.php" class="btn btn-secondary">&larr; Back to Meetings</a>
        <?php if (is_admin()): ?>
            <a href="attendance.php?meeting_id=<?= $meeting['meeting_id']; ?>" class="btn btn-primary">
                👥 Record Attendance
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Meeting Meta Info Card -->
<div class="card">
    <div class="card-header">
        <h2>📌 Meeting Overview</h2>
        <span class="badge badge-<?= strtolower($meeting['status']); ?>">
            <?= htmlspecialchars($meeting['status']); ?>
        </span>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div>
                <strong>Meeting Type:</strong><br>
                <?= htmlspecialchars($meeting['type_name']); ?>
                <?php if (!empty($meeting['type_desc'])): ?>
                    <br><small style="color: #718096;"><?= htmlspecialchars($meeting['type_desc']); ?></small>
                <?php endif; ?>
            </div>
            <div>
                <strong>Date & Time:</strong><br>
                <?= date('l, F j, Y', strtotime($meeting['date'])); ?><br>
                <?= date('g:i A', strtotime($meeting['time'])); ?>
            </div>
            <div>
                <strong>Location / Venue:</strong><br>
                <?= htmlspecialchars($meeting['location'] ?: 'Department Boardroom'); ?>
            </div>
            <div>
                <strong>Attendance Summary:</strong><br>
                <?php if ($att_summary && $att_summary['total_staff'] > 0): ?>
                    <span style="color: #276749; font-weight: bold;"><?= $att_summary['present_count']; ?> Present</span>, 
                    <span style="color: #c53030;"><?= $att_summary['absent_count']; ?> Absent</span>
                <?php else: ?>
                    <span style="color: #718096;">Attendance not recorded</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (is_admin()): ?>
            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 15px 0;">
            <!-- Status Update Form for Admin -->
            <form action="meeting_actions.php" method="POST" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="meeting_id" value="<?= $meeting['meeting_id']; ?>">
                <label for="status_select" style="font-weight: 600; font-size: 13px;">Change Meeting Status:</label>
                <select name="status" id="status_select" class="form-control" style="width: auto; display: inline-block;">
                    <option value="Scheduled" <?= ($meeting['status'] === 'Scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="Completed" <?= ($meeting['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="Rescheduled" <?= ($meeting['status'] === 'Rescheduled') ? 'selected' : ''; ?>>Rescheduled</option>
                    <option value="Cancelled" <?= ($meeting['status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm">Update Status</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Agenda Section -->
<div class="card">
    <div class="card-header">
        <h2>📋 Meeting Agenda</h2>
    </div>
    <div class="card-body">
        <?php if ($agenda && !empty($agenda['agenda_details'])): ?>
            <div style="white-space: pre-wrap; line-height: 1.6; color: #2d3748;">
                <?= htmlspecialchars($agenda['agenda_details']); ?>
            </div>
        <?php else: ?>
            <p style="color: #718096;"><em>No agenda details have been posted for this meeting yet.</em></p>
        <?php endif; ?>

        <?php if (is_admin()): ?>
            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 15px 0;">
            <details>
                <summary style="cursor: pointer; color: #2b6cb0; font-weight: 600; font-size: 13px;">
                    <?= ($agenda) ? '✏️ Edit Agenda' : '+ Add Agenda'; ?>
                </summary>
                <form action="meeting_actions.php" method="POST" style="margin-top: 10px;">
                    <input type="hidden" name="action" value="save_agenda">
                    <input type="hidden" name="meeting_id" value="<?= $meeting['meeting_id']; ?>">
                    <div class="form-group">
                        <textarea name="agenda_details" class="form-control" rows="4" placeholder="Enter agenda points..." required><?= htmlspecialchars($agenda['agenda_details'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Save Agenda</button>
                </form>
            </details>
        <?php endif; ?>
    </div>
</div>

<!-- Decision Report & Formal Minutes Section -->
<div class="card">
    <div class="card-header">
        <h2>📝 Decisions & Action Points (Minutes)</h2>
    </div>
    <div class="card-body">
        <?php if ($decision && !empty($decision['decisions'])): ?>
            <div style="white-space: pre-wrap; line-height: 1.6; color: #2d3748;">
                <?= htmlspecialchars($decision['decisions']); ?>
            </div>
        <?php else: ?>
            <p style="color: #718096;"><em>No formal decision report or minutes logged yet.</em></p>
        <?php endif; ?>

        <?php if (is_admin()): ?>
            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 15px 0;">
            <details>
                <summary style="cursor: pointer; color: #2b6cb0; font-weight: 600; font-size: 13px;">
                    <?= ($decision) ? '✏️ Update Decisions' : '+ Log Meeting Decisions'; ?>
                </summary>
                <form action="meeting_actions.php" method="POST" style="margin-top: 10px;">
                    <input type="hidden" name="action" value="save_decisions">
                    <input type="hidden" name="meeting_id" value="<?= $meeting['meeting_id']; ?>">
                    <div class="form-group">
                        <textarea name="decisions" class="form-control" rows="4" placeholder="Enter ratified decisions and action plans..." required><?= htmlspecialchars($decision['decisions'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Save Decisions & Mark Completed</button>
                </form>
            </details>
        <?php endif; ?>
    </div>
</div>

<!-- Attached Materials Section -->
<div class="card">
    <div class="card-header">
        <h2>📁 Meeting Documents & Attachments</h2>
    </div>
    <div class="card-body">
        <?php if ($materials_res && $materials_res->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Document Name</th>
                        <th>Uploaded Date</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($m = $materials_res->fetch_assoc()): ?>
                        <tr>
                            <td>📄 <strong><?= htmlspecialchars($m['file_name']); ?></strong></td>
                            <td><?= date('M d, Y h:i A', strtotime($m['uploaded_at'])); ?></td>
                            <td style="text-align: right;">
                                <a href="<?= htmlspecialchars($m['file_path']); ?>" download class="btn btn-secondary btn-sm">
                                    Download
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: #718096;"><em>No supplementary files or presentations uploaded for this meeting.</em></p>
        <?php endif; ?>

        <?php if (is_admin()): ?>
            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 15px 0;">
            <details>
                <summary style="cursor: pointer; color: #2b6cb0; font-weight: 600; font-size: 13px;">
                    + Upload New Document / Attachment
                </summary>
                <form action="meeting_actions.php" method="POST" enctype="multipart/form-data" style="margin-top: 10px;">
                    <input type="hidden" name="action" value="upload_material">
                    <input type="hidden" name="meeting_id" value="<?= $meeting['meeting_id']; ?>">
                    <div class="form-group">
                        <input type="file" name="document_file" class="form-control" required>
                        <small style="color: #718096; font-size: 11px;">Supported files: PDF, DOCX, PPTX, XLSX, TXT (Max 20MB)</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Upload File</button>
                </form>
            </details>
        <?php endif; ?>
    </div>
</div>

<?php
$mat_stmt->close();
require_once __DIR__ . '/includes/footer.php';
?>

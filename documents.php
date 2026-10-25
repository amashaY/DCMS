<?php
/**
 * Department Communication Management System
 * Faculty of Applied Sciences, Rajarata University of Sri Lanka
 * Centralized Document Repository: documents.php
 */

$page_title = 'Document Repository';
require_once __DIR__ . '/includes/header.php';

$msg = $_GET['msg'] ?? '';
$err = $_GET['error'] ?? '';

// Active tab ('materials', 'agendas', 'decisions')
$tab = sanitize_input($_GET['tab'] ?? 'materials');

// Fetch Materials
$materials_sql = "
    SELECT mm.material_id, mm.file_name, mm.file_path, mm.uploaded_at,
           sm.meeting_id, sm.date, sm.time, mt.type_name
    FROM Meeting_Materials mm
    JOIN Scheduled_Meeting sm ON mm.meeting_id = sm.meeting_id
    LEFT JOIN Meeting_Type mt ON sm.type_id = mt.type_id
    ORDER BY mm.uploaded_at DESC
";
$materials_res = $conn->query($materials_sql);

// Fetch Agendas
$agendas_sql = "
    SELECT ag.agenda_id, ag.agenda_details, sm.meeting_id, sm.date, sm.time, mt.type_name
    FROM Agenda ag
    JOIN Scheduled_Meeting sm ON ag.meeting_id = sm.meeting_id
    LEFT JOIN Meeting_Type mt ON sm.type_id = mt.type_id
    ORDER BY sm.date DESC
";
$agendas_res = $conn->query($agendas_sql);

// Fetch Decisions
$decisions_sql = "
    SELECT dr.report_id, dr.decisions, sm.meeting_id, sm.date, sm.time, mt.type_name
    FROM Decision_Report dr
    JOIN Scheduled_Meeting sm ON dr.meeting_id = sm.meeting_id
    LEFT JOIN Meeting_Type mt ON sm.type_id = mt.type_id
    ORDER BY sm.date DESC
";
$decisions_res = $conn->query($decisions_sql);

// Fetch meetings for upload dropdowns
$all_meetings = [];
$m_res = $conn->query("
    SELECT sm.meeting_id, sm.date, mt.type_name
    FROM Scheduled_Meeting sm
    LEFT JOIN Meeting_Type mt ON sm.type_id = mt.type_id
    ORDER BY sm.date DESC
");
if ($m_res) {
    while ($r = $m_res->fetch_assoc()) {
        $all_meetings[] = $r;
    }
}
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
        <h1>Centralized Document Repository</h1>
        <p>Agendas, supplementary slide decks, policy documents, and formal minutes</p>
    </div>
</div>

<!-- Tab Navigation Bar -->
<div style="display: flex; gap: 8px; margin-bottom: 20px;">
    <a href="documents.php?tab=materials" class="btn <?= ($tab === 'materials') ? 'btn-primary' : 'btn-secondary'; ?>">
        📁 Attached Files (<?= ($materials_res) ? $materials_res->num_rows : 0; ?>)
    </a>
    <a href="documents.php?tab=agendas" class="btn <?= ($tab === 'agendas') ? 'btn-primary' : 'btn-secondary'; ?>">
        📋 Meeting Agendas (<?= ($agendas_res) ? $agendas_res->num_rows : 0; ?>)
    </a>
    <a href="documents.php?tab=decisions" class="btn <?= ($tab === 'decisions') ? 'btn-primary' : 'btn-secondary'; ?>">
        📝 Decision Logs & Minutes (<?= ($decisions_res) ? $decisions_res->num_rows : 0; ?>)
    </a>
</div>

<!-- TAB 1: MATERIALS -->
<?php if ($tab === 'materials'): ?>
    <?php if (is_admin()): ?>
        <!-- Upload Form for Admin -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h3>📤 Upload New Document to Meeting</h3>
            </div>
            <div class="card-body">
                <form action="meeting_actions.php" method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end;">
                    <input type="hidden" name="action" value="upload_material">
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="doc_meeting_id" class="form-label">Select Meeting Session <span style="color:#e53e3e;">*</span></label>
                        <select name="meeting_id" id="doc_meeting_id" class="form-control" required>
                            <option value="">-- Choose Meeting --</option>
                            <?php foreach ($all_meetings as $m): ?>
                                <option value="<?= $m['meeting_id']; ?>">
                                    #<?= $m['meeting_id']; ?> - <?= htmlspecialchars($m['type_name']); ?> (<?= date('M d, Y', strtotime($m['date'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="document_file" class="form-label">Choose File <span style="color:#e53e3e;">*</span></label>
                        <input type="file" name="document_file" id="document_file" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="height: 38px;">Upload File</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2>📁 Attached Meeting Documents & Files</h2>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if ($materials_res && $materials_res->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Meeting Session</th>
                                <th>Meeting Date</th>
                                <th>Uploaded Date</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($f = $materials_res->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong>📄 <?= htmlspecialchars($f['file_name']); ?></strong>
                                    </td>
                                    <td>
                                        <a href="meeting_details.php?id=<?= $f['meeting_id']; ?>" style="color: #2b6cb0; text-decoration: none;">
                                            <?= htmlspecialchars($f['type_name']); ?> (#<?= $f['meeting_id']; ?>)
                                        </a>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($f['date'])); ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($f['uploaded_at'])); ?></td>
                                    <td style="text-align: right;">
                                        <a href="<?= htmlspecialchars($f['file_path']); ?>" download class="btn btn-primary btn-sm">
                                            Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="padding: 30px; text-align: center; color: #718096;">
                    No documents uploaded in the repository yet.
                </p>
            <?php endif; ?>
        </div>
    </div>

<!-- TAB 2: AGENDAS -->
<?php elseif ($tab === 'agendas'): ?>
    <?php if (is_admin()): ?>
        <!-- Agenda Form for Admin -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h3>📋 Add or Update Meeting Agenda</h3>
            </div>
            <div class="card-body">
                <form action="meeting_actions.php" method="POST">
                    <input type="hidden" name="action" value="save_agenda">
                    <div class="form-group">
                        <label for="ag_meeting_id" class="form-label">Select Meeting Session <span style="color:#e53e3e;">*</span></label>
                        <select name="meeting_id" id="ag_meeting_id" class="form-control" required>
                            <option value="">-- Choose Meeting --</option>
                            <?php foreach ($all_meetings as $m): ?>
                                <option value="<?= $m['meeting_id']; ?>">
                                    #<?= $m['meeting_id']; ?> - <?= htmlspecialchars($m['type_name']); ?> (<?= date('M d, Y', strtotime($m['date'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="agenda_details" class="form-label">Agenda Details & Topics <span style="color:#e53e3e;">*</span></label>
                        <textarea name="agenda_details" id="agenda_details" class="form-control" rows="3" placeholder="1. Opening remarks&#10;2. Semester curriculum status&#10;3. Any other matters" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Agenda</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2>📋 Distributed Meeting Agendas</h2>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if ($agendas_res && $agendas_res->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Meeting Type</th>
                                <th>Date & Time</th>
                                <th>Agenda Points</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($ag = $agendas_res->fetch_assoc()): ?>
                                <tr>
                                    <td style="width: 25%;">
                                        <strong><?= htmlspecialchars($ag['type_name']); ?></strong>
                                    </td>
                                    <td style="width: 20%;">
                                        <?= date('M d, Y', strtotime($ag['date'])); ?><br>
                                        <small style="color: #718096;"><?= date('h:i A', strtotime($ag['time'])); ?></small>
                                    </td>
                                    <td style="white-space: pre-wrap; font-size: 13px; line-height: 1.5; color: #2d3748;">
                                        <?= htmlspecialchars($ag['agenda_details']); ?>
                                    </td>
                                    <td style="text-align: right; width: 12%;">
                                        <a href="meeting_details.php?id=<?= $ag['meeting_id']; ?>" class="btn btn-secondary btn-sm">
                                            View Meeting
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="padding: 30px; text-align: center; color: #718096;">
                    No agendas distributed yet.
                </p>
            <?php endif; ?>
        </div>
    </div>

<!-- TAB 3: DECISIONS -->
<?php elseif ($tab === 'decisions'): ?>
    <?php if (is_admin()): ?>
        <!-- Decisions Form for Admin -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h3>📝 Log Meeting Decisions & Minutes</h3>
            </div>
            <div class="card-body">
                <form action="meeting_actions.php" method="POST">
                    <input type="hidden" name="action" value="save_decisions">
                    <div class="form-group">
                        <label for="dec_meeting_id" class="form-label">Select Meeting Session <span style="color:#e53e3e;">*</span></label>
                        <select name="meeting_id" id="dec_meeting_id" class="form-control" required>
                            <option value="">-- Choose Meeting --</option>
                            <?php foreach ($all_meetings as $m): ?>
                                <option value="<?= $m['meeting_id']; ?>">
                                    #<?= $m['meeting_id']; ?> - <?= htmlspecialchars($m['type_name']); ?> (<?= date('M d, Y', strtotime($m['date'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="decisions_text" class="form-label">Ratified Decisions & Action Plans <span style="color:#e53e3e;">*</span></label>
                        <textarea name="decisions" id="decisions_text" class="form-control" rows="3" placeholder="1. Approved student request for lab extensions.&#10;2. Confirmed moderation dates." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Decisions & Mark Meeting Completed</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2>📝 Official Meeting Decisions & Ratified Minutes</h2>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if ($decisions_res && $decisions_res->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Meeting Type</th>
                                <th>Date & Time</th>
                                <th>Decisions & Action Points</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($dc = $decisions_res->fetch_assoc()): ?>
                                <tr>
                                    <td style="width: 25%;">
                                        <strong><?= htmlspecialchars($dc['type_name']); ?></strong>
                                    </td>
                                    <td style="width: 20%;">
                                        <?= date('M d, Y', strtotime($dc['date'])); ?><br>
                                        <small style="color: #718096;"><?= date('h:i A', strtotime($dc['time'])); ?></small>
                                    </td>
                                    <td style="white-space: pre-wrap; font-size: 13px; line-height: 1.5; color: #2d3748;">
                                        <?= htmlspecialchars($dc['decisions']); ?>
                                    </td>
                                    <td style="text-align: right; width: 12%;">
                                        <a href="meeting_details.php?id=<?= $dc['meeting_id']; ?>" class="btn btn-secondary btn-sm">
                                            View Meeting
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="padding: 30px; text-align: center; color: #718096;">
                    No decision records logged yet.
                </p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

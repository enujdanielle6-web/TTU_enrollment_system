<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

$type = $_GET['type'] ?? 'college';
$sectionId = (int)($_GET['id'] ?? 0);

if ($type === 'shs') {
    requirePermission('shs_sections.manage');
} else {
    requirePermission('college_sections.manage');
}

if ($sectionId <= 0) {
    $_SESSION['admin_error'] = 'Invalid Section ID.';
    header("Location: " . ($type === 'shs' ? 'shs_sections.php' : 'college_sections.php'));
    exit;
}

try {
    if ($type === 'shs') {
        $stmt = $pdo->prepare('
            SELECT s.id, s.section_code, s.capacity, s.schedule_type, p.code as program_code, "Senior High School" as category, s.grade_level as year_level, s.strand_id as program_id
            FROM shs_sections s 
            JOIN shs_strands p ON s.strand_id = p.id 
            WHERE s.id = ?
        ');
        $stmt->execute([$sectionId]);
        $section = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$section) {
            $_SESSION['admin_error'] = 'Section not found.';
            header('Location: shs_sections.php');
            exit;
        }

        // Auto-sync missing subjects from SHS Curriculum to Section Subjects
        $syncStmt = $pdo->prepare('
            INSERT INTO shs_section_subjects (shs_section_id, subject_id, capacity, day, start_time, end_time)
            SELECT ?, c.subject_id, ?, "TBA", "00:00:00", "00:00:00"
            FROM shs_curriculum c
            WHERE c.strand_id = ? AND c.grade_level = ?
              AND NOT EXISTS (
                  SELECT 1 FROM shs_section_subjects ss 
                  WHERE ss.shs_section_id = ? AND ss.subject_id = c.subject_id
              )
        ');
        $syncStmt->execute([$sectionId, $section['capacity'], $section['program_id'], $section['year_level'], $sectionId]);

        // Auto-remove subjects that are no longer in Curriculum
        $delStmt = $pdo->prepare('
            DELETE FROM shs_section_subjects 
            WHERE shs_section_id = ? 
              AND subject_id NOT IN (
                  SELECT subject_id FROM shs_curriculum 
                  WHERE strand_id = ? AND grade_level = ?
              )
        ');
        $delStmt->execute([$sectionId, $section['program_id'], $section['year_level']]);

        $subStmt = $pdo->prepare('
            SELECT ss.id, ss.subject_id, ss.capacity, ss.day, ss.start_time, ss.end_time, ss.room, ss.instructor, ss.delivery_mode, 
                   sub.subject_code, sub.subject_name, sub.units, c.semester
            FROM shs_section_subjects ss
            JOIN subjects sub ON ss.subject_id = sub.id
            LEFT JOIN shs_curriculum c ON c.subject_id = ss.subject_id AND c.strand_id = ? AND c.grade_level = ?
            WHERE ss.shs_section_id = ?
            ORDER BY c.semester ASC, sub.subject_code ASC
        ');
        $subStmt->execute([$section['program_id'], $section['year_level'], $sectionId]);
        $subjects = $subStmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $stmt = $pdo->prepare('
            SELECT s.id, s.section_code, s.capacity, s.schedule_type, p.code as program_code, "College" as category, s.year_level, s.program_id, s.semester, s.curriculum_id
            FROM college_sections s 
            JOIN college_programs p ON s.program_id = p.id 
            WHERE s.id = ?
        ');
        $stmt->execute([$sectionId]);
        $section = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$section) {
            $_SESSION['admin_error'] = 'Section not found.';
            header('Location: college_sections.php');
            exit;
        }

        if ($section['curriculum_id']) {
            // Auto-sync missing subjects from Curriculum to Section Subjects
            $syncStmt = $pdo->prepare('
                INSERT INTO college_section_subjects (college_section_id, subject_id, capacity, day, start_time, end_time)
                SELECT ?, ccs.subject_id, ?, "TBA", "00:00:00", "00:00:00"
                FROM college_curriculum_subjects ccs
                WHERE ccs.curriculum_id = ? AND ccs.year_level = ? AND (ccs.semester = ? OR ccs.semester IS NULL OR ccs.semester = "")
                  AND NOT EXISTS (
                      SELECT 1 FROM college_section_subjects css 
                      WHERE css.college_section_id = ? AND css.subject_id = ccs.subject_id
                  )
            ');
            $syncStmt->execute([$sectionId, $section['capacity'], $section['curriculum_id'], $section['year_level'], $section['semester'], $sectionId]);

            // Auto-remove subjects that are no longer in Curriculum
            $delStmt = $pdo->prepare('
                DELETE FROM college_section_subjects 
                WHERE college_section_id = ? 
                  AND subject_id NOT IN (
                      SELECT subject_id FROM college_curriculum_subjects 
                      WHERE curriculum_id = ? AND year_level = ? AND (semester = ? OR semester IS NULL OR semester = "")
                  )
            ');
            $delStmt->execute([$sectionId, $section['curriculum_id'], $section['year_level'], $section['semester']]);
        }

        // Fetch subjects using curriculum display_order
        $subStmt = $pdo->prepare('
            SELECT ss.id, ss.subject_id, ss.capacity, ss.day, ss.start_time, ss.end_time, ss.room, ss.instructor, ss.delivery_mode, 
                   sub.subject_code, sub.subject_name, sub.units, ? as semester, ccs.display_order
            FROM college_section_subjects ss
            JOIN subjects sub ON ss.subject_id = sub.id
            LEFT JOIN college_curriculum_subjects ccs 
              ON ccs.subject_id = ss.subject_id 
             AND ccs.curriculum_id = ? 
             AND ccs.year_level = ? 
             AND (ccs.semester = ? OR ccs.semester = "" OR ccs.semester IS NULL)
            WHERE ss.college_section_id = ?
            ORDER BY ccs.display_order ASC, sub.subject_code ASC
        ');
        $subStmt->execute([$section['semester'], $section['curriculum_id'], $section['year_level'], $section['semester'], $sectionId]);
        $subjects = $subStmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    $_SESSION['admin_error'] = 'Database error loading schedule builder.';
    header("Location: " . ($type === 'shs' ? 'shs_sections.php' : 'college_sections.php'));
    exit;
}

$pageTitle = 'Schedule Builder - Admin';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';

// Prepare subjects for JS
$jsSubjects = [];
foreach ($subjects as $s) {
    $sem = $s['semester'] ?: '1';
    $jsSubjects[] = [
        'id' => $s['id'],
        'subject_id' => $s['subject_id'],
        'subject_code' => $s['subject_code'],
        'subject_name' => $s['subject_name'],
        'day' => $s['day'],
        'start_time' => $s['start_time'],
        'end_time' => $s['end_time'],
        'room' => $s['room'],
        'instructor' => $s['instructor'],
        'delivery_mode' => $s['delivery_mode'],
        'semester' => $sem
    ];
}

$semesters = array_unique(array_column($jsSubjects, 'semester'));
sort($semesters);
if (empty($semesters)) $semesters = ['1'];
?>

<style>
.calendar-container {
    position: relative;
    border: 1px solid #dee2e6;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
}
.calendar-header {
    display: grid;
    grid-template-columns: 80px repeat(6, 1fr);
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}
.calendar-header > div {
    padding: 10px;
    text-align: center;
    font-weight: 600;
    border-right: 1px solid #dee2e6;
    font-size: 0.9rem;
}
.calendar-header > div:last-child { border-right: none; }

.calendar-body {
    display: grid;
    grid-template-columns: 80px repeat(6, 1fr);
    position: relative;
    height: 660px; /* 11 hours * 60px (7am-6pm) */
}
.time-col {
    border-right: 1px solid #dee2e6;
    background: #f8f9fa;
}
.time-slot {
    height: 60px;
    border-bottom: 1px solid #dee2e6;
    text-align: center;
    font-size: 0.75rem;
    color: #6c757d;
    padding-top: 5px;
    box-sizing: border-box;
}
.day-col {
    position: relative;
    border-right: 1px solid #dee2e6;
}
.day-col:last-child { border-right: none; }

.grid-lines {
    position: absolute;
    top: 0; left: 80px; right: 0; bottom: 0;
    pointer-events: none;
    z-index: 1;
}
.grid-line {
    height: 60px;
    border-bottom: 1px solid #f1f3f5;
    box-sizing: border-box;
}

.sched-block {
    position: absolute;
    left: 4px; right: 4px;
    background: #e7f1ff;
    border: 1px solid #0d6efd;
    border-left: 4px solid #0d6efd;
    border-radius: 4px;
    padding: 6px;
    font-size: 0.75rem;
    overflow: hidden;
    cursor: grab;
    transition: box-shadow 0.2s, background-color 0.2s;
    z-index: 10;
    line-height: 1.2;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.sched-block:active { cursor: grabbing; }
.sched-block:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    z-index: 11;
}
.sched-block.conflict {
    background: #ffe6e6 !important;
    border: 1px solid #dc3545 !important;
    border-left: 4px solid #dc3545 !important;
}
.sched-block .sub-code { font-weight: 700; color: #084298; margin-bottom: 2px; }
.sched-block.conflict .sub-code { color: #842029; }
.sched-block .sub-meta { color: #333; font-size: 0.7rem; }

.unscheduled-list {
    min-height: 200px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 10px;
    background: #f8f9fa;
}
.unscheduled-item {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 8px;
    cursor: grab;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.unscheduled-item:active { cursor: grabbing; }
.unscheduled-item .sub-code { font-weight: bold; color: #495057; }
</style>

<main class="py-5 bg-light min-vh-100">
    <div class="container-fluid px-lg-5">
        
        <div class="island island-hero mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">
                        <a href="<?= $type === 'shs' ? 'shs_sections.php' : 'college_sections.php' ?>" class="text-decoration-none text-muted me-2"><i class="bi bi-arrow-left"></i></a>
                        Schedule Builder
                    </h1>
                    <p class="text-muted mb-0">
                        Managing schedule for <span class="fw-bold text-primary"><?= htmlspecialchars($section['section_code'], ENT_QUOTES, 'UTF-8') ?></span>
                        (<?= htmlspecialchars($section['program_code'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($section['year_level'], ENT_QUOTES, 'UTF-8') ?>)
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary fw-medium" onclick="autoGenerate()">
                        <i class="bi bi-magic me-1"></i> Auto-Generate
                    </button>
                    <button type="button" class="btn btn-primary fw-medium" onclick="saveSchedule()">
                        <i class="bi bi-save me-1"></i> Save Schedule
                    </button>
                </div>
            </div>
        </div>

        <div id="alertContainer"></div>

        <div class="row">
            <!-- Sidebar: Unscheduled Subjects -->
            <div class="col-xl-3 mb-4">
                <div class="island">
                    <h5 class="fw-bold mb-3">Unscheduled</h5>
                    
                    <?php if ($type === 'shs'): ?>
                    <ul class="nav nav-pills mb-3" id="semTab" role="tablist">
                        <?php foreach ($semesters as $i => $sem): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $i===0?'active':'' ?>" data-bs-toggle="pill" data-bs-target="#sem<?= htmlspecialchars($sem) ?>" type="button" onclick="switchSemester('<?= htmlspecialchars($sem) ?>')">
                                Semester <?= htmlspecialchars($sem) ?>
                            </button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <div class="tab-content">
                        <?php foreach ($semesters as $i => $sem): ?>
                        <div class="tab-pane fade <?= $i===0?'show active':'' ?>" id="sem<?= htmlspecialchars($sem) ?>" role="tabpanel">
                            <div class="unscheduled-list" id="unscheduledList_<?= htmlspecialchars($sem) ?>" ondragover="allowDrop(event)" ondrop="dropToUnscheduled(event, '<?= htmlspecialchars($sem) ?>')">
                                <!-- Populated by JS -->
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Calendar Area -->
            <div class="col-xl-9">
                <div class="calendar-container">
                    <div class="calendar-header">
                        <div>Time</div>
                        <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                    </div>
                    <div class="calendar-body">
                        <!-- Grid Lines -->
                        <div class="grid-lines">
                            <?php for ($i=0; $i<11; $i++): ?>
                                <div class="grid-line"></div>
                            <?php endfor; ?>
                        </div>
                        
                        <!-- Time Column -->
                        <div class="time-col">
                            <?php
                            for ($h=7; $h<=17; $h++) {
                                $ap = $h >= 12 ? 'PM' : 'AM';
                                $hr = $h > 12 ? $h - 12 : $h;
                                echo "<div class='time-slot'>{$hr}:00 {$ap}</div>";
                            }
                            ?>
                        </div>

                        <!-- Day Columns -->
                        <?php 
                        $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                        foreach ($days as $d): ?>
                            <div class="day-col" id="col_<?= $d ?>" ondragover="allowDrop(event)" ondrop="dropToCalendar(event, '<?= $d ?>')">
                                <!-- Blocks appended by JS -->
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Block Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="editModalLabel">Edit Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-medium">Day</label>
                        <select class="form-select" id="edit_day">
                            <option value="">TBA</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-medium">Start Time</label>
                            <input type="time" class="form-control" id="edit_start" min="07:00" max="18:00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-medium">End Time</label>
                            <input type="time" class="form-control" id="edit_end" min="07:00" max="18:00">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-medium">Room</label>
                        <input type="text" class="form-control" id="edit_room" placeholder="e.g. Rm 101">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-medium">Instructor</label>
                        <input type="text" class="form-control" id="edit_instructor" placeholder="e.g. Dr. Smith">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-medium">Delivery Mode</label>
                        <select class="form-select" id="edit_mode">
                            <option value="Face-to-Face">Face-to-Face</option>
                            <option value="Online">Online</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-danger me-auto" onclick="unassignSubject()">Unassign</button>
                <button type="button" class="btn btn-outline-danger me-2 d-none" id="btnDeleteSession" onclick="deleteSession()">Delete Split</button>
                <button type="button" class="btn btn-secondary me-2" onclick="splitSession()">Split / Duplicate</button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4" onclick="saveEdit()">Apply</button>
            </div>
        </div>
    </div>
</div>

<script>
const type = '<?= $type ?>';
const sectionId = <?= $sectionId ?>;
let subjects = <?= json_encode($jsSubjects) ?>;
const CAL_START_HOUR = 7;
let currentSemester = '<?= $semesters[0] ?? "1" ?>';
let deleted_ids = [];
let newIdCounter = -1;

// Config
const CAL_PIXELS_PER_HOUR = 60;

let editModal = null;

function render() {
    // Clear all
    document.querySelectorAll('.day-col').forEach(c => c.innerHTML = '');
    document.querySelectorAll('.unscheduled-list').forEach(l => l.innerHTML = '');

    subjects.forEach(sub => {
        if (sub.semester !== currentSemester && type === 'shs') {
            return; // Only render current semester for SHS
        }
        if (sub.semester !== currentSemester && type === 'college') {
            // College handles 1 semester at a time, but curriculum could technically have null semester. We just render everything for college.
        }

        const isScheduled = sub.day && sub.day !== 'TBA' && sub.start_time && sub.end_time && sub.start_time !== '00:00:00';

        const el = document.createElement('div');
        el.id = 'sub_' + sub.id;
        el.draggable = true;
        el.ondragstart = dragStart;
        el.onclick = () => openEdit(sub.id);

        if (isScheduled) {
            el.className = 'sched-block' + (sub.conflict ? ' conflict' : '');
            
            const startStr = sub.start_time.substring(0,5);
            const endStr = sub.end_time.substring(0,5);
            
            // Calculate position
            const [sh, sm] = sub.start_time.split(':').map(Number);
            const [eh, em] = sub.end_time.split(':').map(Number);
            
            const top = ((sh - CAL_START_HOUR) + (sm/60)) * CAL_PIXELS_PER_HOUR;
            const height = ((eh - sh) + ((em - sm)/60)) * CAL_PIXELS_PER_HOUR;

            el.style.top = top + 'px';
            el.style.height = height + 'px';

            el.innerHTML = `
                <div class="sub-code">${sub.subject_code}</div>
                <div class="sub-meta">${startStr}-${endStr}</div>
                <div class="sub-meta">${sub.room || 'TBA'}</div>
                <div class="sub-meta text-truncate">${sub.instructor || 'TBA'}</div>
            `;
            const col = document.getElementById('col_' + sub.day);
            if (col) col.appendChild(el);
        } else {
            el.className = 'unscheduled-item';
            el.innerHTML = `
                <div class="sub-code">${sub.subject_code}</div>
                <div class="small text-muted">${sub.subject_name}</div>
            `;
            const list = document.getElementById('unscheduledList_' + currentSemester) || document.querySelector('.unscheduled-list');
            if (list) list.appendChild(el);
        }
    });
}

function switchSemester(sem) {
    currentSemester = sem;
    render();
}

function allowDrop(ev) {
    ev.preventDefault();
}

function dragStart(ev) {
    ev.dataTransfer.setData("id", ev.target.id.replace('sub_', ''));
}

function dropToUnscheduled(ev, sem) {
    ev.preventDefault();
    const id = parseInt(ev.dataTransfer.getData("id"));
    const sub = subjects.find(s => s.id === id);
    if (sub) {
        sub.day = 'TBA';
        sub.start_time = '00:00:00';
        sub.end_time = '00:00:00';
        render();
    }
}

function dropToCalendar(ev, day) {
    ev.preventDefault();
    const id = parseInt(ev.dataTransfer.getData("id"));
    const sub = subjects.find(s => s.id === id);
    if (!sub) return;

    // Calculate time based on Y offset
    const rect = ev.currentTarget.getBoundingClientRect();
    const y = ev.clientY - rect.top;
    
    // Snap to 30 mins (30px)
    const snappedY = Math.round(y / 30) * 30;
    
    const startHour = CAL_START_HOUR + Math.floor(snappedY / 60);
    const startMin = (snappedY % 60) === 30 ? 30 : 0;
    
    // Default duration 1.5 hrs
    let duration = 1.5;
    
    let endHour = startHour + Math.floor(duration);
    let endMin = startMin + ((duration % 1) * 60);
    if (endMin >= 60) {
        endHour++;
        endMin -= 60;
    }

    sub.day = day;
    sub.start_time = `${startHour.toString().padStart(2,'0')}:${startMin.toString().padStart(2,'0')}:00`;
    sub.end_time = `${endHour.toString().padStart(2,'0')}:${endMin.toString().padStart(2,'0')}:00`;
    
    detectLocalConflicts();
    render();
}

function openEdit(id) {
    const sub = subjects.find(s => s.id === id);
    if (!sub) return;
    
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_day').value = sub.day === 'TBA' ? '' : sub.day;
    document.getElementById('edit_start').value = sub.start_time && sub.start_time !== '00:00:00' ? sub.start_time.substring(0,5) : '';
    document.getElementById('edit_end').value = sub.end_time && sub.end_time !== '00:00:00' ? sub.end_time.substring(0,5) : '';
    document.getElementById('edit_room').value = sub.room || '';
    document.getElementById('edit_instructor').value = sub.instructor || '';
    document.getElementById('edit_mode').value = sub.delivery_mode || 'Face-to-Face';
    
    const count = subjects.filter(s => s.subject_code === sub.subject_code).length;
    const delBtn = document.getElementById('btnDeleteSession');
    if (delBtn) {
        delBtn.classList.toggle('d-none', count <= 1);
    }
    
    editModal.show();
}

function splitSession() {
    const id = parseInt(document.getElementById('edit_id').value);
    const sub = subjects.find(s => s.id === id);
    if (!sub) return;
    
    // Create a duplicate with a new negative ID
    let newSub = JSON.parse(JSON.stringify(sub));
    newSub.id = newIdCounter--;
    newSub.day = 'TBA';
    newSub.start_time = '00:00:00';
    newSub.end_time = '00:00:00';
    newSub.conflict = false;
    
    subjects.push(newSub);
    detectLocalConflicts();
    render();
    editModal.hide();
}

function deleteSession() {
    const id = parseInt(document.getElementById('edit_id').value);
    
    const sub = subjects.find(s => s.id === id);
    if (!sub) return;
    
    const count = subjects.filter(s => s.subject_code === sub.subject_code).length;
    if (count <= 1) {
        alert("You cannot delete the only session for this subject. Use 'Unassign' instead.");
        return;
    }
    
    if (confirm("Are you sure you want to permanently delete this split session?")) {
        if (id > 0) deleted_ids.push(id);
        subjects = subjects.filter(s => s.id !== id);
        detectLocalConflicts();
        render();
        editModal.hide();
    }
}

function saveEdit() {
    const id = parseInt(document.getElementById('edit_id').value);
    const sub = subjects.find(s => s.id === id);
    if (!sub) return;
    
    const d = document.getElementById('edit_day').value;
    const st = document.getElementById('edit_start').value;
    const et = document.getElementById('edit_end').value;
    
    if (d && st && et) {
        if (st >= et) {
            alert('Start time must be before end time.');
            return;
        }
        sub.day = d;
        sub.start_time = st + ':00';
        sub.end_time = et + ':00';
    } else {
        sub.day = 'TBA';
        sub.start_time = '00:00:00';
        sub.end_time = '00:00:00';
    }
    
    sub.room = document.getElementById('edit_room').value;
    sub.instructor = document.getElementById('edit_instructor').value;
    sub.delivery_mode = document.getElementById('edit_mode').value;
    
    detectLocalConflicts();
    render();
    editModal.hide();
}

function unassignSubject() {
    const id = parseInt(document.getElementById('edit_id').value);
    const sub = subjects.find(s => s.id === id);
    if (sub) {
        sub.day = 'TBA';
        sub.start_time = '00:00:00';
        sub.end_time = '00:00:00';
        detectLocalConflicts();
        render();
    }
    editModal.hide();
}

function detectLocalConflicts() {
    subjects.forEach(s => s.conflict = false);
    for (let i=0; i<subjects.length; i++) {
        for (let j=i+1; j<subjects.length; j++) {
            let s1 = subjects[i];
            let s2 = subjects[j];
            if (s1.semester !== s2.semester && type === 'shs') continue;
            
            if (s1.day && s1.day !== 'TBA' && s1.day === s2.day) {
                if (s1.start_time < s2.end_time && s1.end_time > s2.start_time) {
                    s1.conflict = true;
                    s2.conflict = true;
                }
            }
        }
    }
}

function autoGenerate() {
    // Fill empty slots from 7AM onwards
    const days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
    let currentDayIdx = 0;
    let currentHour = 7;
    
    subjects.forEach(sub => {
        if (sub.semester !== currentSemester && type === 'shs') return;
        
        if (!sub.day || sub.day === 'TBA' || !sub.start_time || sub.start_time === '00:00:00') {
            // Find slot
            let placed = false;
            while(!placed && currentDayIdx < days.length) {
                // Check if fits
                if (currentHour + 1.5 <= 17) { // Max 5PM
                    // check overlaps with ALREADY scheduled things this sem
                    let overlap = false;
                    let st = `${currentHour.toString().padStart(2,'0')}:00:00`;
                    let eh = currentHour + 1;
                    let em = 30;
                    let et = `${eh.toString().padStart(2,'0')}:${em}:00`;
                    
                    for (let s of subjects) {
                        if (s.semester === currentSemester && s.day === days[currentDayIdx] && s.start_time !== '00:00:00') {
                            if (st < s.end_time && et > s.start_time) {
                                overlap = true; break;
                            }
                        }
                    }
                    
                    if (!overlap) {
                        sub.day = days[currentDayIdx];
                        sub.start_time = st;
                        sub.end_time = et;
                        placed = true;
                        currentHour += 1.5;
                    } else {
                        currentHour += 1.5; // push forward
                    }
                } else {
                    // Next day
                    currentDayIdx++;
                    currentHour = 7;
                }
            }
        }
    });
    
    detectLocalConflicts();
    render();
}

function saveSchedule() {
    const btn = document.querySelector('button[onclick="saveSchedule()"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
    
    const payload = new URLSearchParams();
    payload.append('action', 'save_schedule');
    payload.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
    payload.append('type', type);
    payload.append('section_id', sectionId);
    payload.append('schedules', JSON.stringify(subjects));
    payload.append('deleted_ids', JSON.stringify(deleted_ids));
    
    fetch('schedule_builder_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: payload.toString()
    })
    .then(r => r.json())
    .then(data => {
        const c = document.getElementById('alertContainer');
        if (data.success) {
            c.innerHTML = `<div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i> ${data.message}</div>`;
        } else {
            c.innerHTML = `<div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i> ${data.message}</div>`;
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save me-1"></i> Save Schedule';
    })
    .catch(e => {
        alert('An error occurred while saving.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save me-1"></i> Save Schedule';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    editModal = new bootstrap.Modal(document.getElementById('editModal'));
    detectLocalConflicts();
    render();
});
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>

<?php
require_once __DIR__ . '/../../components/header.php';

require_once __DIR__ . '/../../components/admin_navbar.php';

// Prepare subjects for JS
$jsSubjects = [];
foreach ($subjects as $s) {
    $sem = $s['semester'] ?: '1';
    $jsSubjects[] = [
        'id' => $s['id'],
        'subject_id' => $s['subject_id'],
        'subject_code' => $s['subject_code'],
        'subject_name' => $s['subject_name'],
        'units' => $s['units'],
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
    background: #eef2fa;
    border: 1px solid #d0d7e6;
    border-left: 4px solid #0d6efd;
    border-radius: 6px;
    padding: 6px 8px;
    font-size: 0.75rem;
    overflow: hidden;
    cursor: grab;
    transition: all 0.2s ease;
    z-index: 10;
    line-height: 1.3;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    display: flex;
    flex-direction: column;
}
.sched-block:active { cursor: grabbing; opacity: 0.8; }
.sched-block:hover {
    box-shadow: 0 6px 12px rgba(13,110,253,0.15);
    transform: translateY(-2px);
    z-index: 11;
    border-color: #0d6efd;
}
.sched-block.conflict {
    background: #fff3f3 !important;
    border: 1px solid #dc3545 !important;
    border-left: 4px solid #dc3545 !important;
}
.sched-block .sub-code { font-weight: 700; color: #084298; font-size: 0.8rem; margin-bottom: 2px; }
.sched-block.conflict .sub-code { color: #842029; }
.sched-block .sub-meta { color: #495057; font-size: 0.7rem; display: flex; align-items: center; gap: 4px; margin-bottom: 1px; }
.sched-block .sub-meta i { font-size: 0.65rem; color: #6c757d; }

.unscheduled-list {
    min-height: 300px;
    border: 2px dashed #dee2e6;
    border-radius: 12px;
    padding: 12px;
    background: #f8f9fa;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.unscheduled-item {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 12px;
    cursor: grab;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    transition: all 0.2s ease;
    border-left: 4px solid #6c757d;
}
.unscheduled-item:active { cursor: grabbing; opacity: 0.8; }
.unscheduled-item:hover {
    border-left-color: #0d6efd;
    box-shadow: 0 4px 8px rgba(0,0,0,0.08);
    transform: translateX(2px);
}
.unscheduled-item .sub-code { font-weight: 700; color: #343a40; font-size: 0.9rem; }
.unscheduled-item .sub-name { font-size: 0.75rem; color: #6c757d; line-height: 1.2; margin-top: 2px; }
.unscheduled-item .sub-badges { margin-top: 6px; display: flex; gap: 4px; flex-wrap: wrap; }
</style>

<main class="py-5 bg-light min-vh-100">
    <div class="container-fluid px-lg-5">
        
        <div class="island island-hero mb-4 fade-in-up" style="animation-delay: 0.1s;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">
                        <a href="<?= esc($type === 'shs' ? 'shs_sections.php' : 'college_sections.php') ?>" class="text-decoration-none text-muted me-2"><i class="bi bi-arrow-left"></i></a>
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
                <div class="island fade-in-up" style="animation-delay: 0.2s;">
                    <h5 class="fw-bold mb-3">Unscheduled</h5>
                    
                    <?php if ($type === 'shs'): ?>
                    <ul class="nav nav-pills mb-3" id="semTab" role="tablist">
                        <?php foreach ($semesters as $i => $sem): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= esc($i===0?'active':'') ?>" data-bs-toggle="pill" data-bs-target="#sem<?= htmlspecialchars($sem) ?>" type="button" onclick="switchSemester('<?= htmlspecialchars($sem) ?>')">
                                Semester <?= htmlspecialchars($sem) ?>
                            </button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <div class="tab-content">
                        <?php foreach ($semesters as $i => $sem): ?>
                        <div class="tab-pane fade <?= esc($i===0?'show active':'') ?>" id="sem<?= htmlspecialchars($sem) ?>" role="tabpanel">
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
                            <div class="day-col" id="col_<?= esc($d) ?>" ondragover="allowDrop(event)" ondrop="dropToCalendar(event, '<?= esc($d) ?>')">
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
const type = '<?= esc($type) ?>';
const sectionId = <?= esc($sectionId) ?>;
let subjects = <?= json_encode($jsSubjects) ?>;
const CAL_START_HOUR = 7;
let currentSemester = '<?= esc($semesters[0] ?? "1") ?>';
let deleted_ids = [];
let newIdCounter = -1;

// Config
const CAL_PIXELS_PER_HOUR = 60;

let editModal = null;

function render() {
    // Clear all
    document.querySelectorAll('.day-col').forEach(c => c.innerHTML = '');
    document.querySelectorAll('.unscheduled-list').forEach(l => l.innerHTML = '');

    let unscheduledCount = 0;

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

            const roomText = sub.room ? sub.room : 'TBA';
            const instText = sub.instructor ? sub.instructor : 'TBA';
            const modeBadge = sub.delivery_mode === 'Online' ? '<span class="badge bg-info bg-opacity-10 text-info border border-info ms-auto py-0 px-1" style="font-size: 0.6rem;">Online</span>' : '';

            el.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div class="sub-code">${sub.subject_code}</div>
                    ${modeBadge}
                </div>
                <div class="sub-meta"><i class="bi bi-clock"></i> ${startStr}-${endStr}</div>
                <div class="sub-meta"><i class="bi bi-door-open"></i> ${roomText}</div>
                <div class="sub-meta text-truncate"><i class="bi bi-person"></i> ${instText}</div>
            `;
            const col = document.getElementById('col_' + sub.day);
            if (col) col.appendChild(el);
        } else {
            unscheduledCount++;
            el.className = 'unscheduled-item';
            el.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div class="sub-code">${sub.subject_code}</div>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">${sub.units} Units</span>
                </div>
                <div class="sub-name">${sub.subject_name}</div>
                <div class="sub-badges">
                    <span class="badge bg-light text-muted border"><i class="bi bi-easel2 me-1"></i>${sub.delivery_mode || 'F2F'}</span>
                </div>
            `;
            const list = document.getElementById('unscheduledList_' + currentSemester) || document.querySelector('.unscheduled-list');
            if (list) list.appendChild(el);
        }
    });

    // Handle empty state for unscheduled lists
    document.querySelectorAll('.unscheduled-list').forEach(list => {
        if (list.children.length === 0) {
            list.innerHTML = `
                <div class="text-center py-4 text-muted d-flex flex-column align-items-center justify-content-center h-100">
                    <i class="bi bi-check-circle-fill fs-2 text-success opacity-50 mb-2"></i>
                    <p class="mb-0 small fw-medium">All subjects scheduled</p>
                </div>
            `;
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
    
    const isScheduled = sub.day && sub.day !== 'TBA' && sub.start_time && sub.end_time && sub.start_time !== '00:00:00';
    let duration = 1.5;
    
    if (isScheduled) {
        let s_sh = parseInt(sub.start_time.split(':')[0]);
        let s_sm = parseInt(sub.start_time.split(':')[1]);
        let s_eh = parseInt(sub.end_time.split(':')[0]);
        let s_em = parseInt(sub.end_time.split(':')[1]);
        duration = ((s_eh * 60 + s_em) - (s_sh * 60 + s_sm)) / 60;
    } else {
        let totalOtherMinutes = 0;
        subjects.forEach(s => {
            if (s.subject_code === sub.subject_code && s.id !== sub.id && s.day && s.day !== 'TBA' && s.start_time !== '00:00:00') {
                let s_sh = parseInt(s.start_time.split(':')[0]);
                let s_sm = parseInt(s.start_time.split(':')[1]);
                let s_eh = parseInt(s.end_time.split(':')[0]);
                let s_em = parseInt(s.end_time.split(':')[1]);
                totalOtherMinutes += ((s_eh * 60 + s_em) - (s_sh * 60 + s_sm));
            }
        });

        let maxAllowedMinutes = (parseFloat(sub.units) || 0) * 60;
        if (maxAllowedMinutes > 0) {
            let remainingMinutes = maxAllowedMinutes - totalOtherMinutes;
            if (remainingMinutes <= 0) {
                alert(`Cannot schedule: Total scheduled time already reached the maximum allowed ${sub.units} units for this subject.`);
                return;
            }
            duration = remainingMinutes / 60;
            if (duration > 3) duration = 3; 
        } else {
            duration = 1.5;
        }
    }
    
    let endHour = startHour + Math.floor(duration);
    let endMin = startMin + Math.round((duration % 1) * 60);
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
    
    if (d || st || et) {
        if (!d || !st || !et) {
            alert('Please complete the schedule by providing Day, Start Time, and End Time, or leave them all empty to set as TBA.');
            return;
        }
        
        if (st >= et) {
            alert('Start time must be before end time.');
            return;
        }
        
        if (st < "07:00" || et > "18:00") {
            alert('Classes must be scheduled between 07:00 AM and 06:00 PM.');
            return;
        }

        let sh = parseInt(st.split(':')[0]);
        let sm = parseInt(st.split(':')[1]);
        let eh = parseInt(et.split(':')[0]);
        let em = parseInt(et.split(':')[1]);
        
        let diffMinutes = (eh * 60 + em) - (sh * 60 + sm);
        if (diffMinutes < 30) {
            alert('Minimum duration for a class is 30 minutes.');
            return;
        }
        if (diffMinutes > 360) {
            alert('Maximum duration for a single session is 6 hours.');
            return;
        }

        let totalOtherMinutes = 0;
        subjects.forEach(s => {
            if (s.subject_code === sub.subject_code && s.id !== sub.id && s.day && s.day !== 'TBA' && s.start_time !== '00:00:00') {
                let s_sh = parseInt(s.start_time.split(':')[0]);
                let s_sm = parseInt(s.start_time.split(':')[1]);
                let s_eh = parseInt(s.end_time.split(':')[0]);
                let s_em = parseInt(s.end_time.split(':')[1]);
                totalOtherMinutes += ((s_eh * 60 + s_em) - (s_sh * 60 + s_sm));
            }
        });

        let maxAllowedMinutes = (parseFloat(sub.units) || 0) * 60;
        if (maxAllowedMinutes > 0 && (totalOtherMinutes + diffMinutes) > maxAllowedMinutes) {
            alert(`Total scheduled time exceeds the maximum allowed ${sub.units} units (${maxAllowedMinutes / 60} hours) for this subject.`);
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
            let duration = parseFloat(sub.units) || 1.5;
            if (duration < 0.5) duration = 1.0; 

            while(!placed && currentDayIdx < days.length) {
                // Check if fits
                if (currentHour + duration <= 17) { // Max 5PM
                    // check overlaps with ALREADY scheduled things this sem
                    let overlap = false;
                    
                    let stHour = Math.floor(currentHour);
                    let stMin = Math.round((currentHour % 1) * 60);
                    let st = `${stHour.toString().padStart(2,'0')}:${stMin.toString().padStart(2,'0')}:00`;
                    
                    let endHourVal = currentHour + duration;
                    let eh = Math.floor(endHourVal);
                    let em = Math.round((endHourVal % 1) * 60);
                    let et = `${eh.toString().padStart(2,'0')}:${em.toString().padStart(2,'0')}:00`;
                    
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
                        currentHour += duration;
                    } else {
                        currentHour += 0.5; // push forward by 30 mins
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
    payload.append('csrf_token', '<?= esc($_SESSION['csrf_token']) ?>');
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



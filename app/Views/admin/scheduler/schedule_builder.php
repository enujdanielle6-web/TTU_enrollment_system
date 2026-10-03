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
        'faculty_user_id' => $s['faculty_user_id'] ?? null,
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
        
        <!-- Dossier Hero Header Strip -->
        <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <a href="<?= esc($type === 'shs' ? 'shs_sections.php' : 'college_sections.php') ?>" data-spa="false" class="btn btn-light border rounded-circle d-flex align-items-center justify-content-center shadow-xs text-muted" style="width: 44px; height: 44px;" title="Back to Sections">
                        <i class="bi bi-arrow-left fs-5"></i>
                    </a>
                    <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 52px; height: 52px; font-size: 1.5rem; flex-shrink: 0;">
                        <i class="bi bi-calendar2-range-fill"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <h1 class="h4 fw-bold text-dark mb-0">Visual Schedule Builder</h1>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                                <i class="bi bi-hash text-muted"></i><?= htmlspecialchars($section['section_code'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                                <?= htmlspecialchars($section['program_code'], ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($section['year_level'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <p class="text-muted small mb-0">Drag and drop subject blocks into time slots to construct class schedules.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-2 shadow-xs" onclick="autoGenerate()">
                        <i class="bi bi-magic text-primary"></i>
                        <span>Auto-Generate</span>
                    </button>
                    <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2" onclick="saveSchedule()">
                        <i class="bi bi-save"></i>
                        <span>Save Schedule</span>
                    </button>
                </div>
            </div>
        </div>

        <div id="alertContainer"></div>

        <div class="row">
            <!-- Sidebar: Unscheduled Subjects -->
            <div class="col-xl-3 mb-4">
                <div class="dossier-card fade-in-up" style="animation-delay: 0.15s;">
                    <div class="dossier-card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <div class="dossier-header-icon bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-inbox-fill"></i>
                            </div>
                            <h2 class="h6 fw-bold text-dark mb-0">Unscheduled Subjects</h2>
                        </div>
                    </div>
                    <div class="p-3">
                        <?php if ($type === 'shs'): ?>
                        <ul class="nav nav-pills mb-3" id="semTab" role="tablist">
                            <?php foreach ($semesters as $i => $sem): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= esc($i===0?'active':'') ?> rounded-pill px-3 py-1.5 small fw-medium" data-bs-toggle="pill" data-bs-target="#sem<?= htmlspecialchars($sem) ?>" type="button" onclick="switchSemester('<?= htmlspecialchars($sem) ?>')">
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
            </div>

            <!-- Calendar Area -->
            <div class="col-xl-9 mb-4">
                <div class="dossier-card fade-in-up" style="animation-delay: 0.2s;">
                    <div class="dossier-card-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-calendar3"></i>
                            </div>
                            <h2 class="h6 fw-bold text-dark mb-0">Weekly Timetable Grid (7:00 AM &ndash; 6:00 PM)</h2>
                        </div>
                        <div class="d-flex align-items-center gap-2 small text-muted">
                            <span class="d-inline-flex align-items-center"><span class="badge bg-primary rounded-circle p-1 me-1"></span> Scheduled</span>
                            <span class="d-inline-flex align-items-center ms-2"><span class="badge bg-danger rounded-circle p-1 me-1"></span> Conflict</span>
                        </div>
                    </div>
                    <div class="p-3 bg-light bg-opacity-25">
                        <div class="calendar-container shadow-xs">
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
                        <label class="form-label text-muted small fw-medium">Instructor (Assigned Faculty)</label>
                        <select class="form-select" id="edit_faculty_user_id">
                            <option value="">-- Unassigned / TBA --</option>
                            <?php foreach ($facultyList ?? [] as $fac): ?>
                                <option value="<?= esc($fac['id']) ?>" data-name="<?= htmlspecialchars($fac['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= esc($fac['full_name']) ?> (<?= esc($fac['employee_id']) ?> - <?= esc($fac['academic_rank'] ?? 'Instructor I') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" id="edit_instructor">
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
(function() {
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

const DAY_LABELS = {
    'M': 'Monday',
    'T': 'Tuesday',
    'W': 'Wednesday',
    'TH': 'Thursday',
    'F': 'Friday',
    'S': 'Saturday',
    'SU': 'Sunday'
};

function decomposeDays(dayString) {
    if (!dayString) return [];
    let cleaned = dayString.trim().toUpperCase();
    if (cleaned === 'TBA' || !cleaned) return [];
    
    const map = {
        'MONDAY': ['M'], 'MON': ['M'], 'M': ['M'],
        'TUESDAY': ['T'], 'TUE': ['T'], 'T': ['T'],
        'WEDNESDAY': ['W'], 'WED': ['W'], 'W': ['W'],
        'THURSDAY': ['TH'], 'THU': ['TH'], 'TH': ['TH'],
        'FRIDAY': ['F'], 'FRI': ['F'], 'F': ['F'],
        'SATURDAY': ['S'], 'SAT': ['S'], 'S': ['S'],
        'SUNDAY': ['SU'], 'SUN': ['SU'], 'SU': ['SU']
    };
    if (map[cleaned]) return map[cleaned];

    let days = [];
    if (cleaned.includes('TH')) {
        days.push('TH');
        cleaned = cleaned.replace(/TH/g, '');
    }
    if (cleaned.includes('SU')) {
        days.push('SU');
        cleaned = cleaned.replace(/SU/g, '');
    }
    for (let i = 0; i < cleaned.length; i++) {
        let char = cleaned[i];
        if (['M', 'T', 'W', 'F', 'S'].includes(char)) {
            days.push(char);
        }
    }
    return [...new Set(days)];
}

function haveCommonDays(day1, day2) {
    const d1 = decomposeDays(day1);
    const d2 = decomposeDays(day2);
    return d1.filter(d => d2.includes(d));
}

function formatDays(daysArray) {
    return daysArray.map(d => DAY_LABELS[d] || d).join(', ');
}

function normalizeDay(day) {
    if (!day) return null;
    const d = day.trim().toLowerCase();
    if (d === 'monday' || d === 'mon' || d === 'm') return 'Monday';
    if (d === 'tuesday' || d === 'tue' || d === 't') return 'Tuesday';
    if (d === 'wednesday' || d === 'wed' || d === 'w') return 'Wednesday';
    if (d === 'thursday' || d === 'thu' || d === 'th') return 'Thursday';
    if (d === 'friday' || d === 'fri' || d === 'f') return 'Friday';
    if (d === 'saturday' || d === 'sat' || d === 's') return 'Saturday';
    return day;
}

function render() {
    // Clear all
    document.querySelectorAll('.day-col').forEach(c => c.innerHTML = '');
    document.querySelectorAll('.unscheduled-list').forEach(l => l.innerHTML = '');

    let unscheduledCount = 0;

    subjects.forEach(sub => {
        if (type === 'shs' && sub.semester && currentSemester && sub.semester !== currentSemester) {
            return; // Only filter by semester for SHS multi-semester view
        }

        const normalizedDay = normalizeDay(sub.day);
        const col = normalizedDay ? document.getElementById('col_' + normalizedDay) : null;
        const isScheduled = col && sub.start_time && sub.end_time && sub.start_time !== '00:00:00';

        const el = document.createElement('div');
        el.id = 'sub_' + sub.id;
        el.draggable = true;
        el.ondragstart = dragStart;
        el.onclick = () => openEdit(sub.id);

        if (isScheduled) {
            el.className = 'sched-block' + (sub.conflict ? ' conflict' : '');
            if (sub.conflict && sub.conflictReasons && sub.conflictReasons.length > 0) {
                el.title = sub.conflictReasons.join('\n');
            } else {
                el.title = `${sub.subject_code} - Click to edit`;
            }
            
            const startStr = sub.start_time.substring(0,5);
            const endStr = sub.end_time.substring(0,5);
            
            // Calculate position
            const [sh, sm] = sub.start_time.split(':').map(Number);
            const [eh, em] = sub.end_time.split(':').map(Number);
            
            const top = ((sh - CAL_START_HOUR) + (sm/60)) * CAL_PIXELS_PER_HOUR;
            const height = Math.max(30, ((eh - sh) + ((em - sm)/60)) * CAL_PIXELS_PER_HOUR);

            el.style.top = top + 'px';
            el.style.height = height + 'px';

            const roomText = sub.room ? sub.room : 'TBA';
            const instText = sub.instructor ? sub.instructor : 'TBA';
            const modeBadge = sub.delivery_mode === 'Online' ? '<span class="badge bg-info bg-opacity-10 text-info border border-info ms-auto py-0 px-1" style="font-size: 0.6rem;">Online</span>' : '';
            const conflictBadge = sub.conflict 
                ? `<div class="badge bg-danger text-white py-0 px-1 mt-1 d-flex align-items-center gap-1" style="font-size: 0.65rem; width: fit-content;"><i class="bi bi-exclamation-triangle-fill"></i> Conflict</div>` 
                : '';

            el.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div class="sub-code">${sub.subject_code}</div>
                    ${modeBadge}
                </div>
                <div class="sub-meta"><i class="bi bi-clock"></i> ${startStr}-${endStr}</div>
                <div class="sub-meta"><i class="bi bi-door-open"></i> ${roomText}</div>
                <div class="sub-meta text-truncate"><i class="bi bi-person"></i> ${instText}</div>
                ${conflictBadge}
            `;
            col.appendChild(el);
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
            const list = document.getElementById('unscheduledList_' + (sub.semester || currentSemester)) || 
                         document.getElementById('unscheduledList_' + currentSemester) || 
                         document.querySelector('.unscheduled-list');
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

function updateConflictAlertBanner() {
    const alertBox = document.getElementById('alertContainer');
    if (!alertBox) return;

    const hasConflict = subjects.some(s => s.conflict && s.day && s.day !== 'TBA' && s.start_time && s.start_time !== '00:00:00');
    if (hasConflict) {
        // Collect reasons
        const reasons = [];
        subjects.forEach(s => {
            if (s.conflict && s.conflictReasons) {
                s.conflictReasons.forEach(r => {
                    if (!reasons.includes(r)) reasons.push(r);
                });
            }
        });
        const listHtml = reasons.length > 0
            ? `<ul class="mb-0 small ps-3 mt-1">${reasons.map(r => `<li>${r}</li>`).join('')}</ul>`
            : '';

        alertBox.innerHTML = `
            <div class="alert alert-warning alert-dismissible fade show shadow-xs rounded-12 mb-3 py-2.5 px-3 small" role="alert">
                <div class="d-flex align-items-start">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-6 mt-0.5 flex-shrink-0"></i>
                    <div class="w-100">
                        <strong>Schedule Conflict Detected:</strong> Overlapping classes are highlighted in red. You must resolve these conflicts before you can save the schedule.
                        ${listHtml}
                    </div>
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        `;
    } else {
        const warn = alertBox.querySelector('.alert-warning');
        if (warn) {
            alertBox.innerHTML = '';
        }
    }
}

function dropToUnscheduled(ev, sem) {
    ev.preventDefault();
    const id = parseInt(ev.dataTransfer.getData("id"));
    const sub = subjects.find(s => s.id === id);
    if (sub) {
        sub.day = 'TBA';
        sub.start_time = '00:00:00';
        sub.end_time = '00:00:00';
        detectLocalConflicts();
        render();
        updateConflictAlertBanner();
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
    updateConflictAlertBanner();
}

function openEdit(id) {
    const sub = subjects.find(s => s.id === id);
    if (!sub) return;
    
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_day').value = sub.day === 'TBA' ? '' : sub.day;
    document.getElementById('edit_start').value = sub.start_time && sub.start_time !== '00:00:00' ? sub.start_time.substring(0,5) : '';
    document.getElementById('edit_end').value = sub.end_time && sub.end_time !== '00:00:00' ? sub.end_time.substring(0,5) : '';
    document.getElementById('edit_room').value = sub.room || '';
    document.getElementById('edit_faculty_user_id').value = sub.faculty_user_id || '';
    document.getElementById('edit_instructor').value = sub.instructor || '';
    if (!sub.faculty_user_id && sub.instructor && sub.instructor !== 'TBA') {
        const sel = document.getElementById('edit_faculty_user_id');
        for (let opt of sel.options) {
            if (opt.getAttribute('data-name') === sub.instructor) {
                sel.value = opt.value;
                sub.faculty_user_id = parseInt(opt.value);
                break;
            }
        }
    }
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
    newSub.conflictReasons = [];
    
    subjects.push(newSub);
    detectLocalConflicts();
    render();
    updateConflictAlertBanner();
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
        updateConflictAlertBanner();
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

        // Check for immediate conflicts with other scheduled subjects in section
        let proposedConflicts = [];
        const newStart = st + ':00';
        const newEnd = et + ':00';
        const newRoom = document.getElementById('edit_room').value.trim();
        const facSelVal = document.getElementById('edit_faculty_user_id').value;
        const newFacId = facSelVal ? parseInt(facSelVal) : null;
        const newMode = document.getElementById('edit_mode').value;

        subjects.forEach(other => {
            if (other.id === sub.id) return;
            if (!other.day || other.day === 'TBA' || !other.start_time || other.start_time === '00:00:00') return;

            const common = haveCommonDays(d, other.day);
            if (common.length === 0) return;

            const overlap = (newStart < other.end_time && newEnd > other.start_time);
            if (!overlap) return;

            const daysLabel = formatDays(common);

            // Timetable check
            if (type !== 'shs' || other.semester === sub.semester) {
                proposedConflicts.push(`Timetable overlap with ${other.subject_code} (${other.start_time.substring(0,5)}-${other.end_time.substring(0,5)}) on ${daysLabel}`);
            }

            // Room check
            if (newRoom && other.room && newRoom.toUpperCase() !== 'TBA' && other.room.toUpperCase() !== 'TBA' && newMode !== 'Online' && other.delivery_mode !== 'Online') {
                if (newRoom.toLowerCase() === other.room.toLowerCase().trim()) {
                    proposedConflicts.push(`Room collision: Room '${newRoom}' is also used by ${other.subject_code} on ${daysLabel}`);
                }
            }

            // Faculty check
            if (newFacId && other.faculty_user_id && newFacId === other.faculty_user_id) {
                proposedConflicts.push(`Instructor collision: Assigned faculty also teaches ${other.subject_code} on ${daysLabel}`);
            }
        });

        if (proposedConflicts.length > 0) {
            const confirmMsg = "Warning: The proposed schedule conflicts with other subjects in this section:\n\n• " +
                proposedConflicts.join("\n• ") +
                "\n\nIf applied, this block will be marked as a conflict in red. You will NOT be able to save the schedule until all conflicts are resolved.\n\nDo you want to apply these times anyway?";
            if (!confirm(confirmMsg)) {
                return;
            }
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
    const facSel = document.getElementById('edit_faculty_user_id');
    const selectedOpt = facSel.selectedOptions ? facSel.selectedOptions[0] : null;
    sub.faculty_user_id = facSel.value ? parseInt(facSel.value) : null;
    sub.instructor = (facSel.value && selectedOpt) ? (selectedOpt.getAttribute('data-name') || 'TBA') : 'TBA';
    sub.delivery_mode = document.getElementById('edit_mode').value;
    
    detectLocalConflicts();
    render();
    updateConflictAlertBanner();
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
        updateConflictAlertBanner();
    }
    editModal.hide();
}

function detectLocalConflicts() {
    subjects.forEach(s => {
        s.conflict = false;
        s.conflictReasons = [];
    });

    const active = subjects.filter(s => s.day && s.day !== 'TBA' && s.start_time && s.end_time && s.start_time !== '00:00:00' && s.end_time !== '00:00:00');

    for (let i = 0; i < active.length; i++) {
        for (let j = i + 1; j < active.length; j++) {
            let s1 = active[i];
            let s2 = active[j];

            const commonDays = haveCommonDays(s1.day, s2.day);
            if (commonDays.length === 0) continue;

            const timesOverlap = (s1.start_time < s2.end_time && s1.end_time > s2.start_time);
            if (!timesOverlap) continue;

            const daysText = formatDays(commonDays);

            // 1. Student Timetable Conflict (Same Section Cohort)
            // For SHS, only conflict if in same semester
            const sameSem = (type !== 'shs') || (s1.semester === s2.semester);
            if (sameSem) {
                s1.conflict = true;
                s2.conflict = true;
                const reason1 = `Timetable Conflict: Overlaps with ${s2.subject_code} (${s2.start_time.substring(0,5)}-${s2.end_time.substring(0,5)}) on ${daysText}`;
                const reason2 = `Timetable Conflict: Overlaps with ${s1.subject_code} (${s1.start_time.substring(0,5)}-${s1.end_time.substring(0,5)}) on ${daysText}`;
                if (!s1.conflictReasons.includes(reason1)) s1.conflictReasons.push(reason1);
                if (!s2.conflictReasons.includes(reason2)) s2.conflictReasons.push(reason2);
            }

            // 2. Room Conflict (Physical room collision)
            const r1 = (s1.room || '').trim();
            const r2 = (s2.room || '').trim();
            const isR1Valid = r1 && r1.toUpperCase() !== 'TBA' && s1.delivery_mode !== 'Online';
            const isR2Valid = r2 && r2.toUpperCase() !== 'TBA' && s2.delivery_mode !== 'Online';
            if (isR1Valid && isR2Valid && r1.toLowerCase() === r2.toLowerCase()) {
                s1.conflict = true;
                s2.conflict = true;
                const roomReason1 = `Room Conflict: Room '${r1}' also assigned to ${s2.subject_code} on ${daysText}`;
                const roomReason2 = `Room Conflict: Room '${r2}' also assigned to ${s1.subject_code} on ${daysText}`;
                if (!s1.conflictReasons.includes(roomReason1)) s1.conflictReasons.push(roomReason1);
                if (!s2.conflictReasons.includes(roomReason2)) s2.conflictReasons.push(roomReason2);
            }

            // 3. Faculty / Instructor Conflict
            const f1 = s1.faculty_user_id;
            const f2 = s2.faculty_user_id;
            const inst1 = (s1.instructor || '').trim();
            const inst2 = (s2.instructor || '').trim();
            let sameFac = false;
            let facName = '';

            if (f1 && f2 && f1 === f2) {
                sameFac = true;
                facName = inst1 || `Faculty #${f1}`;
            } else if (inst1 && inst2 && inst1.toUpperCase() !== 'TBA' && inst2.toUpperCase() !== 'TBA' && inst1.toLowerCase() === inst2.toLowerCase()) {
                sameFac = true;
                facName = inst1;
            }

            if (sameFac) {
                s1.conflict = true;
                s2.conflict = true;
                const facReason1 = `Instructor Conflict: ${facName} also assigned to ${s2.subject_code} on ${daysText}`;
                const facReason2 = `Instructor Conflict: ${facName} also assigned to ${s1.subject_code} on ${daysText}`;
                if (!s1.conflictReasons.includes(facReason1)) s1.conflictReasons.push(facReason1);
                if (!s2.conflictReasons.includes(facReason2)) s2.conflictReasons.push(facReason2);
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
    updateConflictAlertBanner();
}

function saveSchedule() {
    detectLocalConflicts();
    
    // Find all scheduled subjects that have conflicts
    const conflicting = subjects.filter(s => s.conflict && s.day && s.day !== 'TBA' && s.start_time && s.start_time !== '00:00:00');
    if (conflicting.length > 0) {
        const uniqueReasons = [];
        conflicting.forEach(s => {
            (s.conflictReasons || []).forEach(r => {
                if (!uniqueReasons.includes(r)) uniqueReasons.push(r);
            });
        });

        const listItems = uniqueReasons.length > 0 
            ? uniqueReasons.map(r => `<li>${r}</li>`).join('') 
            : '<li>Overlapping class schedules detected on the timetable.</li>';

        const c = document.getElementById('alertContainer');
        c.innerHTML = `
            <div class="alert alert-danger shadow-sm rounded-12 fade-in-up mb-4">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-exclamation-octagon-fill fs-5 mt-0.5 text-danger flex-shrink-0"></i>
                    <div class="w-100">
                        <h6 class="fw-bold mb-1">Cannot Save Schedule: Conflicts Detected</h6>
                        <p class="small mb-2">Please resolve the following schedule collision(s) before saving:</p>
                        <ul class="mb-0 small ps-3">
                            ${listItems}
                        </ul>
                    </div>
                </div>
            </div>
        `;
        c.scrollIntoView({ behavior: 'smooth', block: 'start' });

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Schedule Conflicts Detected',
                html: `<div class="text-start small"><p class="mb-2">The schedule cannot be saved because the following conflicts exist:</p><ul class="mb-0 ps-3">${listItems}</ul></div>`,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Review and Fix'
            });
        }
        return;
    }

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
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved Successfully',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        } else {
            c.innerHTML = `<div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i> ${data.message}</div>`;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Schedule Conflict / Error',
                    text: data.message,
                    confirmButtonColor: '#dc3545'
                });
            }
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save me-1"></i> Save Schedule';
        c.scrollIntoView({ behavior: 'smooth', block: 'start' });
    })
    .catch(e => {
        alert('An error occurred while saving.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save me-1"></i> Save Schedule';
    });
}

function initScheduleBuilder() {
    const modalEl = document.getElementById('editModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        editModal = new bootstrap.Modal(modalEl);
    }
    detectLocalConflicts();
    render();
    updateConflictAlertBanner();
}

// Expose handlers globally for inline event attributes
window.render = render;
window.switchSemester = switchSemester;
window.allowDrop = allowDrop;
window.dragStart = dragStart;
window.dropToUnscheduled = dropToUnscheduled;
window.dropToCalendar = dropToCalendar;
window.openEdit = openEdit;
window.saveEdit = saveEdit;
window.unassignSubject = unassignSubject;
window.deleteSession = deleteSession;
window.splitSession = splitSession;
window.autoGenerate = autoGenerate;
window.saveSchedule = saveSchedule;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initScheduleBuilder);
} else {
    initScheduleBuilder();
}
})();
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>



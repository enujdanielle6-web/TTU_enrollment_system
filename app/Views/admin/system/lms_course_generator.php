<?php require_once __DIR__ . '/../../layout_header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">LMS Course Generator</h2>
        <a href="/sia/admin/dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-body p-4">
            <p class="text-muted">
                Use this tool to map uninitialized physical class sections to a specific Faculty user. 
                This will generate the required digital proxy record (`lms_courses`) that isolating course materials and allows Faculty members to access their classes.
            </p>

            <div class="table-responsive mt-4">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Level</th>
                            <th>Section Code</th>
                            <th>Subject</th>
                            <th>Old String Instructor</th>
                            <th>Map to Faculty User</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($unmapped_courses)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">All active sections have been mapped to an LMS Course.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($unmapped_courses as $course): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?= $course['academic_level'] === 'College' ? 'primary' : 'warning text-dark' ?>">
                                            <?= htmlspecialchars($course['academic_level']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($course['section_code']) ?></td>
                                    <td>
                                        <div class="fw-bold text-primary"><?= htmlspecialchars($course['subject_code']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($course['subject_name']) ?></small>
                                    </td>
                                    <td>
                                        <span class="text-secondary fst-italic">
                                            <i class="bi bi-person-fill"></i> <?= htmlspecialchars($course['old_instructor_string'] ?: 'Unassigned') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form action="/sia/admin/lms/generate" method="POST" class="d-flex gap-2">
                                            <input type="hidden" name="academic_level" value="<?= htmlspecialchars($course['academic_level']) ?>">
                                            <input type="hidden" name="section_id" value="<?= htmlspecialchars($course['section_id']) ?>">
                                            <input type="hidden" name="subject_id" value="<?= htmlspecialchars($course['subject_id']) ?>">
                                            
                                            <select name="faculty_user_id" class="form-select form-select-sm shadow-none border-secondary" required style="min-width: 200px;">
                                                <option value="">Select Faculty User...</option>
                                                <?php foreach ($faculty_users as $faculty): ?>
                                                    <option value="<?= htmlspecialchars($faculty['id']) ?>">
                                                        <?= htmlspecialchars($faculty['last_name'] . ', ' . $faculty['first_name'] . ' (' . $faculty['email'] . ')') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                    </td>
                                    <td>
                                            <button type="submit" class="btn btn-sm btn-primary shadow-sm rounded-3">
                                                <i class="bi bi-link-45deg"></i> Generate LMS Course
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layout_footer.php'; ?>

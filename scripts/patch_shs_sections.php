<?php
$file = 'app/Views/admin/scheduler/shs_sections.php';
$content = file_get_contents($file);

$searchForm = <<<'EOD'
            <select class="form-select" name="strand_id" id="programSelect" required>
              <option value="" selected disabled>Select a program...</option>
              <?php foreach ($programs as $prog): ?>
                <option value="<?= $prog['id'] ?>" data-category="Senior High School">
                  <?= htmlspecialchars($prog['code'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Please select an academic program.</div>
          </div>
          
          <div class="row">
EOD;

$replaceForm = <<<'EOD'
            <select class="form-select" name="strand_id" id="programSelect" required onchange="filterCurricula()">
              <option value="" selected disabled>Select a program...</option>
              <?php foreach ($programs as $prog): ?>
                <option value="<?= $prog['id'] ?>" data-category="Senior High School">
                  <?= htmlspecialchars($prog['code'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Please select an academic program.</div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Curriculum Version <span class="text-danger">*</span></label>
            <select class="form-select" name="curriculum_id" id="curriculumSelect" required disabled>
              <option value="" selected disabled>Select program first...</option>
            </select>
            <div class="invalid-feedback">Please select a curriculum.</div>
          </div>
          
          <div class="row">
EOD;

$content = str_replace($searchForm, $replaceForm, $content);

$searchScript = <<<'EOD'
<script>
document.addEventListener('DOMContentLoaded', function() {
EOD;

$replaceScript = <<<'EOD'
<script>
const allCurricula = <?= json_encode($curricula ?? []) ?>;

function filterCurricula() {
    const strandId = document.getElementById('programSelect').value;
    const curriculumSelect = document.getElementById('curriculumSelect');
    
    curriculumSelect.innerHTML = '<option value="" selected disabled>Select curriculum...</option>';
    
    if (!strandId) {
        curriculumSelect.disabled = true;
        return;
    }
    
    const filtered = allCurricula.filter(c => String(c.strand_id) === String(strandId));
    
    if (filtered.length === 0) {
        curriculumSelect.innerHTML = '<option value="" selected disabled>No active curricula found</option>';
        curriculumSelect.disabled = true;
    } else {
        filtered.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.curriculum_name + ' (v' + c.version + ') - ' + (c.effective_academic_year || 'Any');
            curriculumSelect.appendChild(opt);
        });
        curriculumSelect.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', function() {
EOD;

$content = str_replace($searchScript, $replaceScript, $content);

file_put_contents($file, $content);
echo "shs_sections view updated.\n";

<?php require_once __DIR__ . '/layout_header.php'; ?>

<main class="py-4 bg-light min-vh-100">
  <div class="container-fluid px-lg-4">

    <!-- Hero Header Strip -->
    <div class="dossier-hero-strip mb-4 fade-in-up">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-chat-left-text-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Messages &amp; Class Forums</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                Direct Communications
              </span>
            </div>
            <p class="text-muted small mb-0">
              Communicate with students across your enrolled class rosters and exchange academic inquiries.
            </p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-primary rounded-pill px-3 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-1.5 hover-lift" data-bs-toggle="modal" data-bs-target="#newMessageModal">
            <i class="bi bi-pencil-square"></i>
            <span>New Message</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Main Chat Workspace Card -->
    <div class="dossier-card border-0 shadow-sm rounded-4 bg-white overflow-hidden fade-in-up" style="min-height: 600px;">
      <div class="row g-0 h-100">
        <!-- Left Pane: Conversations List -->
        <div class="col-lg-4 border-end d-flex flex-column" style="min-height: 600px;">
          <div class="p-3 border-bottom bg-light bg-opacity-50">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
              <input type="text" id="threadSearchInput" class="form-control border-start-0 ps-1" placeholder="Search conversations or students...">
            </div>
          </div>
          
          <div class="flex-grow-1 overflow-auto" id="threadsListContainer" style="max-height: 540px;">
            <?php if (empty($threads)): ?>
              <div class="p-4 text-center text-muted d-flex flex-column align-items-center justify-content-center h-100" style="min-height: 380px;">
                <div class="d-flex align-items-center justify-content-center bg-light text-primary rounded-circle mb-3 shadow-xs" style="width: 64px; height: 64px; font-size: 1.75rem;">
                  <i class="bi bi-chat-square-dots opacity-75"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Inbox Zero</h6>
                <p class="text-muted small mb-3" style="max-width: 220px;">
                  No unread inquiries or direct messages at the moment.
                </p>
                <button class="btn btn-sm btn-primary rounded-pill px-3 py-1.5 fw-semibold shadow-xs" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                  <i class="bi bi-plus-lg me-1"></i>Start Thread
                </button>
              </div>
            <?php else: ?>
              <div class="list-group list-group-flush" id="threadsList">
                <?php foreach ($threads as $t): 
                  $isActive = ($activeThreadId === (int)$t['id']);
                  $other = !empty($t['other_participants'][0]) ? $t['other_participants'][0] : null;
                  $contactName = $other ? trim(($other['first_name'] ?? '') . ' ' . ($other['last_name'] ?? '')) : 'Direct Message';
                  $contactInitial = strtoupper(substr($contactName, 0, 1));
                  $unread = (int)($t['unread_count'] ?? 0);
                  $lastMsg = !empty($t['last_message_body']) ? htmlspecialchars($t['last_message_body']) : 'Conversation started';
                  $timeStr = !empty($t['last_message_at']) ? date('M j, g:i A', strtotime($t['last_message_at'])) : '';
                ?>
                  <a href="/sia/lms/faculty/messages.php?thread_id=<?= (int)$t['id'] ?>" 
                     class="list-group-item list-group-item-action p-3 border-bottom transition-all thread-item <?= $isActive ? 'bg-primary bg-opacity-10 border-start border-primary border-4' : '' ?>"
                     data-contact-name="<?= esc(strtolower($contactName)) ?>"
                     data-subject="<?= esc(strtolower($t['subject'] ?? '')) ?>"
                     data-course="<?= esc(strtolower($t['subject_code'] ?? '')) ?>">
                    <div class="d-flex align-items-start gap-3">
                      <div class="avatar-circle rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0 shadow-xs" style="width: 44px; height: 44px; font-size: 1rem;">
                        <?= esc($contactInitial) ?>
                      </div>
                      <div class="flex-grow-1 min-w-0">
                        <div class="d-flex justify-content-between align-items-baseline mb-1">
                          <h6 class="mb-0 fw-bold text-dark text-truncate small" style="max-width: 170px;">
                            <?= esc($contactName) ?>
                          </h6>
                          <small class="text-muted text-nowrap" style="font-size: 0.72rem;"><?= esc($timeStr) ?></small>
                        </div>
                        <div class="d-flex align-items-center gap-1 mb-1">
                          <?php if (!empty($t['subject_code'])): ?>
                            <span class="badge bg-light text-secondary border px-1.5 py-0.5 rounded" style="font-size: 0.68rem;">
                              <?= esc($t['subject_code']) ?> <?= !empty($t['section_code']) ? '· ' . esc($t['section_code']) : '' ?>
                            </span>
                          <?php endif; ?>
                          <span class="text-dark small fw-medium text-truncate flex-grow-1" style="font-size: 0.78rem;">
                            <?= esc($t['subject'] ?? 'Conversation') ?>
                          </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                          <p class="text-muted small mb-0 text-truncate" style="max-width: 200px; font-size: 0.75rem;">
                            <?= $lastMsg ?>
                          </p>
                          <?php if ($unread > 0): ?>
                            <span class="badge bg-primary rounded-pill px-2 py-0.5 small"><?= $unread ?></span>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Right Pane: Active Thread Area -->
        <div class="col-lg-8 d-flex flex-column bg-light bg-opacity-25" style="min-height: 600px;">
          <?php if (empty($activeThread)): ?>
            <div class="flex-grow-1 d-flex align-items-center justify-content-center flex-column text-muted p-5 text-center" style="min-height: 500px;">
              <div class="bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center mb-3 border" style="width: 76px; height: 76px;">
                <i class="bi bi-envelope-paper text-primary fs-3"></i>
              </div>
              <h5 class="fw-bold text-dark mb-2">Select a conversation thread</h5>
              <p class="text-muted small mb-4" style="max-width: 380px;">
                Click on an ongoing thread from the roster on the left, or compose a new announcement or student message.
              </p>
              <button class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold shadow-xs" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                <i class="bi bi-plus-lg me-1"></i> Compose New Message
              </button>
            </div>
          <?php else: 
            $threadOther = !empty($activeThread['other_participants'][0]) ? $activeThread['other_participants'][0] : null;
            $threadContactName = $threadOther ? trim(($threadOther['first_name'] ?? '') . ' ' . ($threadOther['last_name'] ?? '')) : 'Student';
            $threadContactEmail = $threadOther['email'] ?? '';
            $threadInitial = strtoupper(substr($threadContactName, 0, 1));
          ?>
            <!-- Thread Header -->
            <div class="p-3 bg-white border-bottom shadow-xs d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar-circle rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center shadow-xs" style="width: 44px; height: 44px; font-size: 1.1rem;">
                  <?= esc($threadInitial) ?>
                </div>
                <div>
                  <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <?= esc($threadContactName) ?>
                    <?php if (!empty($activeThread['subject_code'])): ?>
                      <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">
                        <?= esc($activeThread['subject_code']) ?> <?= !empty($activeThread['section_code']) ? '· ' . esc($activeThread['section_code']) : '' ?>
                      </span>
                    <?php endif; ?>
                  </h6>
                  <div class="d-flex align-items-center gap-2 small text-muted">
                    <span><?= esc($threadContactEmail) ?></span>
                    <span>&bull;</span>
                    <span class="fw-medium text-dark"><?= esc($activeThread['subject'] ?? 'Discussion') ?></span>
                  </div>
                </div>
              </div>
              <div>
                <a href="/sia/lms/faculty/messages.php?thread_id=<?= (int)$activeThread['id'] ?>" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1 text-muted hover-lift" title="Refresh messages">
                  <i class="bi bi-arrow-clockwise"></i>
                </a>
              </div>
            </div>

            <!-- Messages Stream -->
            <div class="flex-grow-1 p-3 p-md-4 overflow-auto bg-light bg-opacity-50" id="chatMessagesContainer" style="max-height: 440px; min-height: 380px;">
              <?php if (empty($activeMessages)): ?>
                <div class="text-center text-muted my-5 py-5">
                  <i class="bi bi-chat-dots fs-1 opacity-25"></i>
                  <p class="small mt-2 mb-0">No messages in this conversation yet. Send the first message below!</p>
                </div>
              <?php else: ?>
                <div class="d-flex flex-column gap-3">
                  <?php foreach ($activeMessages as $m): 
                    $isMine = !empty($m['is_mine']);
                    $senderName = trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? ''));
                  ?>
                    <?php if ($isMine): ?>
                      <!-- Outgoing message (Faculty) -->
                      <div class="d-flex flex-column align-items-end mb-2 fade-in">
                        <div class="p-3 bg-primary text-white rounded-4 rounded-bottom-end-0 shadow-xs" style="max-width: 75%; word-break: break-word;">
                          <div class="small fw-normal" style="line-height: 1.5; white-space: pre-wrap;"><?= htmlspecialchars($m['body']) ?></div>
                        </div>
                        <div class="d-flex align-items-center gap-1.5 mt-1 text-muted" style="font-size: 0.72rem;">
                          <span><?= esc($m['formatted_time']) ?></span>
                          <i class="bi bi-check2-all text-primary"></i>
                        </div>
                      </div>
                    <?php else: ?>
                      <!-- Incoming message (Student) -->
                      <div class="d-flex align-items-start gap-2.5 mb-2 fade-in">
                        <div class="avatar-circle rounded-circle bg-white border text-primary fw-bold d-flex align-items-center justify-content-center shadow-xs flex-shrink-0" style="width: 34px; height: 34px; font-size: 0.85rem;">
                          <?= esc($m['initials'] ?? 'S') ?>
                        </div>
                        <div class="d-flex flex-column align-items-start" style="max-width: 75%;">
                          <div class="p-3 bg-white border rounded-4 rounded-bottom-start-0 text-dark shadow-xs" style="word-break: break-word;">
                            <div class="small fw-normal" style="line-height: 1.5; white-space: pre-wrap;"><?= htmlspecialchars($m['body']) ?></div>
                          </div>
                          <div class="d-flex align-items-center gap-2 mt-1 text-muted" style="font-size: 0.72rem;">
                            <span class="fw-semibold text-secondary"><?= esc($senderName) ?></span>
                            <span>&bull;</span>
                            <span><?= esc($m['formatted_time']) ?></span>
                          </div>
                        </div>
                      </div>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- Message Composer Box -->
            <div class="p-3 bg-white border-top">
              <form action="/sia/lms/faculty/messages/send" method="POST" id="replyMessageForm" class="d-flex align-items-center gap-2">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="thread_id" value="<?= (int)$activeThread['id'] ?>">
                <div class="flex-grow-1 position-relative">
                  <textarea name="body" id="replyBodyInput" class="form-control rounded-pill ps-3 pe-4 py-2 border shadow-xs" placeholder="Type a message to <?= esc($threadContactName) ?>... (Press Enter to send)" rows="1" required style="resize: none; font-size: 0.9rem; line-height: 1.5; max-height: 120px;"></textarea>
                </div>
                <button type="submit" id="btnSendReply" class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm flex-shrink-0 hover-lift" style="width: 42px; height: 42px;">
                  <i class="bi bi-send-fill" style="font-size: 1rem; margin-left: 2px;"></i>
                </button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</main>

<!-- New Message Modal -->
<div class="modal fade" id="newMessageModal" tabindex="-1" aria-labelledby="newMessageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom p-4">
        <div class="d-flex align-items-center gap-2">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 36px; height: 36px;">
            <i class="bi bi-pencil-square"></i>
          </div>
          <h5 class="modal-title fw-bold text-dark mb-0" id="newMessageModalLabel">New Message</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/sia/lms/faculty/messages/send" method="POST" id="newThreadForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="lms_course_id" id="modalCourseId" value="">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label text-dark small fw-bold">Recipient (Enrolled Student)</label>
            <?php if (empty($contacts)): ?>
              <div class="alert alert-warning py-2 small mb-0 rounded-3">
                <i class="bi bi-exclamation-triangle me-1"></i> No enrolled students found in your active course rosters.
              </div>
            <?php else: ?>
              <select name="recipient_id" id="modalRecipientSelect" class="form-select rounded-3" required>
                <option value="" disabled selected>-- Select a student from your classes --</option>
                <?php foreach ($contacts as $c): ?>
                  <option value="<?= (int)$c['user_id'] ?>" data-course-id="<?= (int)$c['course_id'] ?>">
                    <?= esc($c['name']) ?> &mdash; <?= esc($c['course_code']) ?> (<?= esc($c['section_code']) ?>) &bull; <?= esc($c['student_number']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
          </div>
          <div class="mb-3">
            <label class="form-label text-dark small fw-bold">Subject</label>
            <input type="text" name="subject" class="form-control rounded-3" placeholder="Consultation topic, inquiry, follow-up..." required value="Academic Consultation">
          </div>
          <div class="mb-2">
            <label class="form-label text-dark small fw-bold">Message Content</label>
            <textarea name="body" class="form-control rounded-3" rows="4" placeholder="Write your message here..." required></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light border-top p-3 rounded-bottom-4">
          <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" id="btnSubmitNewMessage" <?= empty($contacts) ? 'disabled' : '' ?>>
            <i class="bi bi-send me-1"></i> Send Message
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function() {
  function initMessagesModule() {
    // 1. Thread List Search Filter
    const searchInput = document.getElementById('threadSearchInput');
    const threadItems = document.querySelectorAll('.thread-item');
    if (searchInput && threadItems.length > 0) {
      searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        threadItems.forEach(item => {
          const name = item.getAttribute('data-contact-name') || '';
          const subject = item.getAttribute('data-subject') || '';
          const course = item.getAttribute('data-course') || '';
          if (name.includes(query) || subject.includes(query) || course.includes(query)) {
            item.style.display = '';
          } else {
            item.style.display = 'none';
          }
        });
      });
    }

    // 2. Auto-scroll chat messages to bottom
    const chatContainer = document.getElementById('chatMessagesContainer');
    if (chatContainer) {
      chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    // 3. Sync course ID from selected recipient in modal
    const recipientSelect = document.getElementById('modalRecipientSelect');
    const courseIdInput = document.getElementById('modalCourseId');
    if (recipientSelect && courseIdInput) {
      recipientSelect.addEventListener('change', function() {
        const selectedOpt = this.options[this.selectedIndex];
        const courseId = selectedOpt ? selectedOpt.getAttribute('data-course-id') : '';
        courseIdInput.value = courseId || '';
      });
    }

    // 4. Handle "Enter" key to submit reply form (without Shift)
    const replyInput = document.getElementById('replyBodyInput');
    const replyForm = document.getElementById('replyMessageForm');
    const btnSend = document.getElementById('btnSendReply');
    if (replyInput && replyForm) {
      replyInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          if (replyInput.value.trim().length > 0) {
            if (btnSend) btnSend.disabled = true;
            replyForm.submit();
          }
        }
      });
    }

    // 5. Debounce send button on submit
    if (replyForm && btnSend) {
      replyForm.addEventListener('submit', function() {
        btnSend.disabled = true;
        btnSend.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
      });
    }

    const newThreadForm = document.getElementById('newThreadForm');
    const btnNewMessage = document.getElementById('btnSubmitNewMessage');
    if (newThreadForm && btnNewMessage) {
      newThreadForm.addEventListener('submit', function() {
        btnNewMessage.disabled = true;
        btnNewMessage.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Sending...';
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMessagesModule);
  } else {
    initMessagesModule();
  }
  document.addEventListener('spa:navigated', initMessagesModule);
})();
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>

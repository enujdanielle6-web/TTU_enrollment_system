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
    <div class="dossier-card border-0 shadow-sm rounded-4 bg-white overflow-hidden fade-in-up" style="min-height: 580px;">
      <div class="row g-0 h-100">
        <!-- Left Pane: Conversations List -->
        <div class="col-lg-4 border-end d-flex flex-column">
          <div class="p-3 border-bottom bg-light bg-opacity-50">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control border-start-0 ps-1" placeholder="Search conversations or students...">
            </div>
          </div>
          
          <div class="flex-grow-1 p-3 text-center text-muted d-flex flex-column align-items-center justify-content-center" style="min-height: 400px;">
            <div class="d-flex align-items-center justify-content-center bg-light text-primary rounded-circle mb-3 shadow-xs" style="width: 64px; height: 64px; font-size: 1.75rem;">
              <i class="bi bi-chat-square-dots opacity-75"></i>
            </div>
            <h6 class="fw-bold text-dark mb-1">Inbox Zero</h6>
            <p class="text-muted small mb-3" style="max-width: 220px;">
              No unread inquiries or direct messages at the moment.
            </p>
            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1.5 fw-semibold" data-bs-toggle="modal" data-bs-target="#newMessageModal">
              <i class="bi bi-plus-lg me-1"></i>Start Thread
            </button>
          </div>
        </div>

        <!-- Right Pane: Active Thread Area -->
        <div class="col-lg-8 d-flex flex-column bg-light bg-opacity-25">
          <div class="flex-grow-1 d-flex align-items-center justify-content-center flex-column text-muted p-5 text-center" style="min-height: 500px;">
            <div class="bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center mb-3 border" style="width: 72px; height: 72px;">
              <i class="bi bi-envelope-paper text-primary fs-3"></i>
            </div>
            <h5 class="fw-bold text-dark mb-2">Select a conversation thread</h5>
            <p class="text-muted small mb-0" style="max-width: 360px;">
              Click on an ongoing thread from the roster on the left, or compose a new announcement or student message.
            </p>
          </div>
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
          <h5 class="modal-title fw-bold text-dark mb-0" id="newMessageModalLabel">New Faculty Message</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-3">
          <label class="form-label text-dark small fw-bold">Recipient (Student or Section)</label>
          <input type="text" class="form-control rounded-3" placeholder="Search by student name or section code...">
        </div>
        <div class="mb-3">
          <label class="form-label text-dark small fw-bold">Subject</label>
          <input type="text" class="form-control rounded-3" placeholder="Course inquiry, consultation topic...">
        </div>
        <div class="mb-2">
          <label class="form-label text-dark small fw-bold">Message Content</label>
          <textarea class="form-control rounded-3" rows="4" placeholder="Write your message here..."></textarea>
        </div>
      </div>
      <div class="modal-footer bg-light border-top p-3 rounded-bottom-4">
        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-dismiss="modal">
          <i class="bi bi-send me-1"></i> Send Message
        </button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>

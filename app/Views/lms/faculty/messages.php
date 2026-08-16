<?php require_once __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4 h-100 d-flex flex-column" style="min-height: calc(100vh - 80px);">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">Messages & Forums</h3>
            <p class="text-muted mb-0">Communicate with students and colleagues.</p>
        </div>
        <button class="btn btn-primary fw-semibold shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#newMessageModal">
            <i class="bi bi-pencil-square"></i> Compose
        </button>
    </div>

    <!-- Main Chat Interface Placeholder -->
    <div class="row g-4 flex-grow-1">
        <!-- Sidebar: Conversations -->
        <div class="col-lg-4 d-flex flex-column">
            <div class="lms-card p-0 border-0 shadow-sm flex-grow-1 overflow-hidden d-flex flex-column">
                <div class="p-3 border-bottom bg-light">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" placeholder="Search students or faculty...">
                    </div>
                </div>
                <div class="flex-grow-1 overflow-auto bg-white">
                    <div class="p-4 text-center text-muted mt-5">
                        <i class="bi bi-chat-dots opacity-25" style="font-size: 3rem;"></i>
                        <p class="mt-3 mb-0 fw-semibold text-dark">Inbox Zero</p>
                        <small>No unread messages or active threads.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Window: Chat Area -->
        <div class="col-lg-8 d-flex flex-column">
            <div class="lms-card p-0 border-0 shadow-sm flex-grow-1 overflow-hidden d-flex flex-column bg-light">
                <div class="flex-grow-1 d-flex align-items-center justify-content-center flex-column text-muted p-5">
                    <div class="bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-envelope-open text-primary fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Select a thread</h5>
                    <p class="text-center" style="max-width: 300px;">Click on a student's message to reply, or start a new conversation.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Message Modal -->
<div class="modal fade" id="newMessageModal" tabindex="-1" aria-labelledby="newMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="newMessageModalLabel">New Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">To</label>
                    <input type="text" class="form-control" placeholder="Search for a student or instructor...">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Message</label>
                    <textarea class="form-control" rows="4" placeholder="Type your message here..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4 fw-semibold shadow-sm" data-bs-dismiss="modal">Send Message</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>

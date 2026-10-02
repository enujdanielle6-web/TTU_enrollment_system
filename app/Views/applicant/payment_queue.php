<?php
require_once __DIR__ . '/../components/header.php';
?>

<?php require_once __DIR__ . '/../components/applicant_navbar.php'; ?>

<main id="spa-main" class="py-5 bg-light min-vh-100">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 col-xl-7">
        
        <!-- Queue Card -->
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden fade-in-up">
          <div class="card-header bg-primary text-white p-4 text-center position-relative overflow-hidden" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
            <div class="position-absolute top-0 end-0 p-3 opacity-25">
              <i class="bi bi-clock-history" style="font-size: 6rem; line-height: 1;"></i>
            </div>
            <div class="position-relative z-1">
              <span class="badge bg-white text-primary px-3 py-1.5 rounded-pill fw-semibold mb-2 shadow-sm">
                <i class="bi bi-shield-check me-1"></i> High-Traffic Protection Active
              </span>
              <h1 class="h3 fw-bold mb-1">Triple T University Virtual Payment Room</h1>
              <p class="mb-0 text-white-50 small">Automated concurrency queue ensuring safe, double-entry free payment checkout.</p>
            </div>
          </div>

          <div class="card-body p-4 p-md-5 text-center">
            
            <!-- Animated Waiting Indicator -->
            <div class="mb-4">
              <div class="position-relative d-inline-block">
                <div class="spinner-grow text-primary" style="width: 5rem; height: 5rem;" role="status">
                  <span class="visually-hidden">Waiting...</span>
                </div>
                <div class="position-absolute top-50 start-50 translate-middle">
                  <i class="bi bi-people-fill text-white fs-3"></i>
                </div>
              </div>
            </div>

            <!-- Position Display -->
            <div class="bg-light border rounded-4 p-4 mb-4">
              <span class="text-uppercase text-muted fw-bold small tracking-wider">Your Position In Line</span>
              <div class="display-3 fw-bold text-primary my-2" id="queue-position-display">
                #<?= esc((string)$position); ?>
              </div>
              <p class="text-muted small mb-0" id="queue-status-message">
                Please keep this browser window open. Your position updates automatically in real-time.
              </p>
            </div>

            <!-- Throughput Capacity Metrics -->
            <div class="row g-3 mb-4 text-start">
              <div class="col-6">
                <div class="p-3 rounded-3 bg-white border">
                  <div class="text-muted small mb-1"><i class="bi bi-cpu me-1 text-primary"></i> System Capacity</div>
                  <div class="fw-bold text-dark"><?= esc((string)$maxConcurrency); ?> simultaneous slots</div>
                </div>
              </div>
              <div class="col-6">
                <div class="p-3 rounded-3 bg-white border">
                  <div class="text-muted small mb-1"><i class="bi bi-hourglass-split me-1 text-primary"></i> Checkout Window</div>
                  <div class="fw-bold text-dark"><?= esc((string)$sessionDuration); ?> minutes per slot</div>
                </div>
              </div>
            </div>

            <!-- Instructions Notice -->
            <div class="alert alert-info border-0 rounded-4 text-start small mb-4 d-flex align-items-start gap-3">
              <i class="bi bi-info-circle-fill fs-5 text-info mt-0.5"></i>
              <div>
                <strong>How the virtual queue works:</strong> To prevent duplicate transactions during peak enrollment hours, checkout slots are allocated sequentially. As soon as a slot opens, this screen will instantly advance you to the official PayMongo checkout gateway.
              </div>
            </div>

            <!-- Actions -->
            <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
              <form action="<?= BASE_PATH ?>/applicant/payment_queue_leave.php" method="POST" onsubmit="return confirm('Are you sure you want to leave the payment queue? You will forfeit your position in line.');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="session_token" value="<?= htmlspecialchars((string)$sessionToken, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn btn-outline-secondary rounded-pill px-4">
                  <i class="bi bi-x-circle me-1"></i> Leave Queue & Return to Portal
                </button>
              </form>
            </div>

          </div>
          <div class="card-footer bg-light border-0 py-3 text-center text-muted small">
            <span class="d-inline-flex align-items-center gap-1.5">
              <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
              Live connection active &bull; Checking status every 3 seconds...
            </span>
          </div>
        </div>

      </div>
    </div>
  </div>
</main>

<script>
(function() {
  const sessionToken = <?= json_encode((string)$sessionToken); ?>;
  const statusDisplay = document.getElementById('queue-position-display');
  const messageDisplay = document.getElementById('queue-status-message');
  let pollInterval = null;

  async function checkQueueStatus() {
    if (!sessionToken) return;

    try {
      const response = await fetch('<?= BASE_PATH ?>/applicant/payment_queue_status.php?token=' + encodeURIComponent(sessionToken), {
        headers: { 'Accept': 'application/json' }
      });

      if (!response.ok) {
        console.warn('Queue status check returned status:', response.status);
        return;
      }

      const data = await response.json();

      if (data.status === 'active') {
        // Promoted to active payment slot!
        clearInterval(pollInterval);
        statusDisplay.innerHTML = '<span class="text-success fs-1"><i class="bi bi-check-circle-fill"></i> Ready!</span>';
        messageDisplay.innerText = "It's your turn! Preparing your secure checkout session...";
        
        // Brief visual pause before redirecting to assessment or checkout
        setTimeout(() => {
          if (data.checkout_url) {
            window.location.href = data.checkout_url;
          } else {
            window.location.href = '<?= BASE_PATH ?>/applicant/assessment.php?session_token=' + encodeURIComponent(sessionToken);
          }
        }, 800);
      } else if (data.status === 'waiting') {
        if (data.position !== undefined) {
          statusDisplay.innerText = '#' + data.position;
        }
      } else if (data.status === 'expired') {
        clearInterval(pollInterval);
        statusDisplay.innerHTML = '<span class="text-danger fs-2"><i class="bi bi-clock-history"></i> Expired</span>';
        messageDisplay.innerText = "Your checkout reservation has expired. Please rejoin the queue.";
        setTimeout(() => { window.location.href = '<?= BASE_PATH ?>/applicant/assessment.php'; }, 2000);
      } else if (data.status === 'completed') {
        clearInterval(pollInterval);
        statusDisplay.innerHTML = '<span class="text-success fs-2"><i class="bi bi-receipt"></i> Completed</span>';
        messageDisplay.innerText = "Payment confirmed! Redirecting to your statement...";
        setTimeout(() => { window.location.href = '<?= BASE_PATH ?>/applicant/assessment.php'; }, 1500);
      }
    } catch (err) {
      console.error('Error polling queue status:', err);
    }
  }

  // Poll every 3 seconds
  pollInterval = setInterval(checkQueueStatus, 3000);
})();
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

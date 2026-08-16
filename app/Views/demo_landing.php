<?php
$pageTitle = 'Demo Landing - Triple T University';
require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/components/navbar.php';
?>

<style>
/* AURORA HERO SECTION */
.aurora-hero {
    position: relative;
    overflow: hidden;
    background-color: #0f172a; /* Deep Slate Blue */
    min-height: 100vh;
    display: flex;
    align-items: center;
    padding-top: 80px; /* Offset for navbar */
    color: #ffffff;
}

.aurora-bg {
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: 
        radial-gradient(circle at 50% 50%, rgba(14, 165, 233, 0.4), transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.4), transparent 50%),
        radial-gradient(circle at 20% 80%, rgba(236, 72, 153, 0.3), transparent 50%);
    filter: blur(80px);
    animation: auroraRotate 25s linear infinite;
    z-index: 0;
}

@keyframes auroraRotate {
    0% { transform: rotate(0deg) scale(1); }
    50% { transform: rotate(180deg) scale(1.1); }
    100% { transform: rotate(360deg) scale(1); }
}

.hero-content {
    position: relative;
    z-index: 1;
}

.glass-btn {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    border-radius: 50px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.glass-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
    color: white;
}

.glass-btn-primary {
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border: none;
}

.glass-btn-primary:hover {
    background: linear-gradient(135deg, #2563eb, #7c3aed);
}

/* PARALLAX ABOUT SECTION */
.parallax-section {
    position: relative;
    background-image: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.9)), url('/sia/images/TTU_CAMPUS.png');
    background-attachment: fixed;
    background-position: center;
    background-repeat: no-repeat;
    background-size: cover;
    padding: 100px 0;
    color: white;
}

.glass-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 24px;
    padding: 3rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

/* CARDS SECTION */
.modern-features {
    background-color: #f8fafc;
    padding: 100px 0;
}

.feature-card {
    background: white;
    border-radius: 20px;
    padding: 2.5rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
    border: none;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
}

.feature-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #e0e7ff, #f3e8ff);
    color: #4f46e5;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 1.5rem;
}
</style>

<main>
    <!-- HERO SECTION WITH AURORA BACKGROUND -->
    <section class="aurora-hero">
        <div class="aurora-bg"></div>
        <div class="container hero-content text-center">
            <span class="badge bg-white bg-opacity-25 border border-white border-opacity-50 rounded-pill px-3 py-2 mb-4 fs-6 fw-normal">
                ✨ The Future of Education is Here
            </span>
            <h1 class="display-3 fw-bold mb-4" style="letter-spacing: -1px;">
                Experience <span style="background: linear-gradient(135deg, #60a5fa, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Triple T University</span>
            </h1>
            <p class="lead mb-5 mx-auto opacity-75" style="max-width: 600px;">
                A next-generation learning platform built to empower minds and transform futures. Join our vibrant digital campus today.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="/sia/auth/register.php" class="glass-btn glass-btn-primary shadow-lg">
                    Begin Application <i class="bi bi-arrow-right"></i>
                </a>
                <a href="#explore" class="glass-btn">
                    <i class="bi bi-play-circle"></i> Explore Campus
                </a>
            </div>
        </div>
    </section>

    <!-- PARALLAX ABOUT SECTION -->
    <section id="explore" class="parallax-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="glass-card text-center">
                        <h2 class="fw-bold mb-4">A Campus Beyond Boundaries</h2>
                        <p class="fs-5 opacity-75 mb-0">
                            Our state-of-the-art facilities and hybrid learning environments are designed to bring out the best in you. 
                            Whether you are learning on campus or through our advanced LMS, you are always connected.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CLEAN MODERN CARDS -->
    <section class="modern-features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Everything You Need</h2>
                <p class="text-muted">A fully integrated academic experience.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-laptop"></i>
                        </div>
                        <h4 class="fw-bold">Smart LMS</h4>
                        <p class="text-muted mb-0">Access courses, track your progress, and interact with faculty through our seamless online portal.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-rocket"></i>
                        </div>
                        <h4 class="fw-bold">Fast Enrollment</h4>
                        <p class="text-muted mb-0">Skip the lines. Our 10-step digital enrollment process makes registering for classes a breeze.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-globe"></i>
                        </div>
                        <h4 class="fw-bold">Connected Community</h4>
                        <p class="text-muted mb-0">Join clubs, attend virtual seminars, and stay engaged with a university that never sleeps.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/components/footer.php'; ?>

<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_header.php'; 

// Basic Calendar Logic
$date = "$year-$month-01";
$firstDayOfMonth = date('w', strtotime($date));
$daysInMonth = date('t', strtotime($date));
$monthName = date('F', strtotime($date));

$prevMonth = date('m', strtotime("$date -1 month"));
$prevYear = date('Y', strtotime("$date -1 month"));
$nextMonth = date('m', strtotime("$date +1 month"));
$nextYear = date('Y', strtotime("$date +1 month"));

// Group events by day
$eventsByDay = [];
foreach ($events as $event) {
    $day = (int)date('d', strtotime($event['date']));
    $eventsByDay[$day][] = $event;
}

?>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/sia/lms/student/dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Calendar</li>
        </ol>
    </nav>

    <div class="lms-card p-0 border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="bg-light border-bottom p-4 d-flex justify-content-between align-items-center">
            <h4 class="mb-0 fw-bold text-dark"><?= $monthName ?> <?= $year ?></h4>
            <div class="btn-group">
                <a href="/sia/lms/student/calendar?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-outline-secondary bg-white"><i class="bi bi-chevron-left"></i></a>
                <a href="/sia/lms/student/calendar?month=<?= date('m') ?>&year=<?= date('Y') ?>" class="btn btn-outline-secondary bg-white">Today</a>
                <a href="/sia/lms/student/calendar?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-outline-secondary bg-white"><i class="bi bi-chevron-right"></i></a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered mb-0" style="table-layout: fixed; min-width: 800px;">
                <thead class="table-light text-center">
                    <tr>
                        <th style="width: 14.28%">Sun</th>
                        <th style="width: 14.28%">Mon</th>
                        <th style="width: 14.28%">Tue</th>
                        <th style="width: 14.28%">Wed</th>
                        <th style="width: 14.28%">Thu</th>
                        <th style="width: 14.28%">Fri</th>
                        <th style="width: 14.28%">Sat</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php
                        $dayCount = 1;
                        // Blank days
                        for ($i = 0; $i < $firstDayOfMonth; $i++) {
                            echo '<td class="bg-light"></td>';
                        }
                        
                        // Days of month
                        for ($i = $firstDayOfMonth; $i < 7; $i++) {
                            $isToday = ($dayCount == date('d') && $month == date('m') && $year == date('Y'));
                            echo '<td style="height: 120px; vertical-align: top;" class="' . ($isToday ? 'bg-primary bg-opacity-10' : '') . '">';
                            echo '<div class="fw-bold mb-2 ' . ($isToday ? 'text-primary' : 'text-secondary') . '">' . $dayCount . '</div>';
                            
                            if (isset($eventsByDay[$dayCount])) {
                                foreach ($eventsByDay[$dayCount] as $ev) {
                                    echo '<div class="badge bg-' . $ev['color'] . ' bg-opacity-10 text-' . $ev['color'] . ' border border-' . $ev['color'] . ' border-opacity-25 w-100 text-start text-truncate mb-1 p-2" title="' . htmlspecialchars($ev['title']) . '">';
                                    echo '<i class="bi ' . ($ev['type'] === 'assignment' ? 'bi-journal-text' : 'bi-ui-checks') . ' me-1"></i>';
                                    echo htmlspecialchars($ev['time'] . ' ' . $ev['title']);
                                    echo '</div>';
                                }
                            }
                            echo '</td>';
                            $dayCount++;
                        }
                        echo '</tr>';

                        // Remaining weeks
                        while ($dayCount <= $daysInMonth) {
                            echo '<tr>';
                            for ($i = 0; $i < 7; $i++) {
                                if ($dayCount <= $daysInMonth) {
                                    $isToday = ($dayCount == date('d') && $month == date('m') && $year == date('Y'));
                                    echo '<td style="height: 120px; vertical-align: top;" class="' . ($isToday ? 'bg-primary bg-opacity-10' : '') . '">';
                                    echo '<div class="fw-bold mb-2 ' . ($isToday ? 'text-primary' : 'text-secondary') . '">' . $dayCount . '</div>';
                                    
                                    if (isset($eventsByDay[$dayCount])) {
                                        foreach ($eventsByDay[$dayCount] as $ev) {
                                            echo '<div class="badge bg-' . $ev['color'] . ' bg-opacity-10 text-' . $ev['color'] . ' border border-' . $ev['color'] . ' border-opacity-25 w-100 text-start text-truncate mb-1 p-2" title="' . htmlspecialchars($ev['title']) . '">';
                                            echo '<i class="bi ' . ($ev['type'] === 'assignment' ? 'bi-journal-text' : 'bi-ui-checks') . ' me-1"></i>';
                                            echo htmlspecialchars($ev['time'] . ' ' . $ev['title']);
                                            echo '</div>';
                                        }
                                    }
                                    echo '</td>';
                                    $dayCount++;
                                } else {
                                    echo '<td class="bg-light"></td>';
                                }
                            }
                            echo '</tr>';
                        }
                        ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_footer.php'; ?>

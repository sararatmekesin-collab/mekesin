<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Bangkok');

// เตรียมข้อมูลตัวอย่างเพื่อให้หน้าแดชบอร์ดยังแสดงผลได้เมื่อฐานข้อมูลยังไม่มีข้อมูล
$sampleRecords = [
    ['id' => 'REC-2026-0148', 'title' => 'ปรับปรุงข้อมูลผู้ใช้งาน', 'category' => 'ข้อมูลสมาชิก', 'status' => 'completed', 'updated_at' => '21 พ.ย. 2569'],
    ['id' => 'REC-2026-0147', 'title' => 'ตรวจสอบคำขอแก้ไขข้อมูล', 'category' => 'คำขอแก้ไข', 'status' => 'pending', 'updated_at' => '20 พ.ย. 2569'],
    ['id' => 'REC-2026-0146', 'title' => 'นำเข้ารายการข้อมูลใหม่', 'category' => 'นำเข้าข้อมูล', 'status' => 'processing', 'updated_at' => '20 พ.ย. 2569'],
    ['id' => 'REC-2026-0145', 'title' => 'จัดเก็บเอกสารประจำเดือน', 'category' => 'เอกสาร', 'status' => 'completed', 'updated_at' => '19 พ.ย. 2569'],
];

$stats = [
    ['label' => 'ข้อมูลทั้งหมด', 'value' => 2486, 'change' => '+12.8%', 'icon' => 'bi-collection', 'tone' => 'teal'],
    ['label' => 'รอตรวจสอบ', 'value' => 38, 'change' => 'ต้องดำเนินการ', 'icon' => 'bi-hourglass-split', 'tone' => 'amber'],
    ['label' => 'ดำเนินการแล้ว', 'value' => 1924, 'change' => '+8.2%', 'icon' => 'bi-check2-circle', 'tone' => 'blue'],
    ['label' => 'ผู้ใช้งานทั้งหมด', 'value' => 156, 'change' => '+4.6%', 'icon' => 'bi-people', 'tone' => 'coral'],
];
$records = $sampleRecords;

// อ่านข้อมูลผ่าน PDO หากมีไฟล์เชื่อมต่อและตาราง records ตามโครงสร้างที่กำหนด
$connectionFile = __DIR__ . '/config/conn_pdo.php';
if (is_file($connectionFile)) {
    try {
        require_once $connectionFile;
        if (isset($pdo) && $pdo instanceof PDO) {
            $counts = $pdo->query(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending,
                        COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) AS completed
                 FROM records"
            )->fetch(PDO::FETCH_ASSOC);

            if ($counts && (int) $counts['total'] > 0) {
                $stats[0]['value'] = (int) $counts['total'];
                $stats[1]['value'] = (int) $counts['pending'];
                $stats[2]['value'] = (int) $counts['completed'];
                try {
                    $stats[3]['value'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
                } catch (PDOException $exception) {
                    error_log('Dashboard user count query failed: ' . $exception->getMessage());
                }
            }

            $stmt = $pdo->query(
                'SELECT id, title, category, status, updated_at
                 FROM records
                 ORDER BY updated_at DESC
                 LIMIT 8'
            );
            $databaseRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($databaseRecords) {
                $records = $databaseRecords;
            }
        } else {
            error_log('Dashboard: config/conn_pdo.php did not provide a PDO instance in $pdo.');
        }
    } catch (Throwable $exception) {
        error_log('Dashboard database read failed: ' . $exception->getMessage());
    }
}

$userName = 'ผู้ดูแลระบบ';
$userRole = 'ผู้ดูแลระบบ';
$avatarText = function_exists('mb_substr') ? mb_substr($userName, 0, 1, 'UTF-8') : substr($userName, 0, 1);
$thaiMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
$today = new DateTimeImmutable('now');
$thaiDate = $today->format('j') . ' ' . $thaiMonths[(int) $today->format('n') - 1] . ' ' . ((int) $today->format('Y') + 543);
$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$statusLabels = [
    'completed' => ['เสร็จสิ้น', 'success'],
    'pending' => ['รอตรวจสอบ', 'warning'],
    'processing' => ['กำลังดำเนินการ', 'info'],
    'cancelled' => ['ยกเลิก', 'muted'],
];
$weeklyValues = [42, 66, 51, 82, 61, 93, 73];
$weekDays = ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.', 'อา.'];
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f2a43">
    <title>ภาพรวมระบบ | Mekesin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="top-accent" aria-hidden="true"></div>
    <div class="page-progress" id="pageProgress" aria-hidden="true"></div>
    <div class="sidebar-backdrop" id="sidebarBackdrop" tabindex="-1" aria-hidden="true"></div>

    <aside class="sidebar" id="sidebar" aria-label="เมนูหลัก">
        <div class="brand">
            <a class="brand-link" href="index.php" aria-label="Mekesin หน้าภาพรวม">
                <span class="brand-mark"><i class="bi bi-grid-1x2-fill" aria-hidden="true"></i></span>
                <span class="brand-copy"><strong>Mekesin</strong><small>DATA MANAGEMENT</small></span>
            </a>
            <button class="icon-button sidebar-close" id="sidebarClose" type="button" aria-label="ปิดเมนู"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>

        <nav class="side-navigation" aria-label="เมนูจัดการระบบ">
            <div class="nav-group">
                <p class="nav-heading">เมนูหลัก</p>
                <span class="active-indicator" id="activeIndicator" aria-hidden="true"></span>
                <a class="side-link active" href="index.php" aria-current="page" data-tooltip="ภาพรวม">
                    <i class="bi bi-grid-1x2" aria-hidden="true"></i><span>ภาพรวม</span>
                </a>
                <button class="side-link accordion-toggle" type="button" aria-expanded="false" aria-controls="dataSubmenu" data-tooltip="จัดการข้อมูล">
                    <i class="bi bi-folder2-open" aria-hidden="true"></i><span>จัดการข้อมูล</span><i class="bi bi-chevron-down accordion-chevron" aria-hidden="true"></i>
                </button>
                <div class="submenu" id="dataSubmenu" aria-hidden="true">
                    <a href="records.php"><span class="submenu-dot"></span>รายการข้อมูล</a>
                    <a href="categories.php"><span class="submenu-dot"></span>หมวดหมู่</a>
                    <a href="imports.php"><span class="submenu-dot"></span>นำเข้า / ส่งออก</a>
                </div>
                <a class="side-link" href="reports.php" data-tooltip="รายงาน">
                    <i class="bi bi-bar-chart-line" aria-hidden="true"></i><span>รายงาน</span>
                </a>
            </div>

            <div class="nav-group">
                <p class="nav-heading">ตั้งค่าระบบ</p>
                <a class="side-link" href="users.php" data-tooltip="ผู้ใช้งาน">
                    <i class="bi bi-people" aria-hidden="true"></i><span>ผู้ใช้งาน</span>
                </a>
                <a class="side-link" href="settings.php" data-tooltip="ตั้งค่าทั่วไป">
                    <i class="bi bi-sliders2" aria-hidden="true"></i><span>ตั้งค่าทั่วไป</span>
                </a>
            </div>
        </nav>

        <div class="sidebar-bottom">
            <div class="help-card">
                <span class="help-icon"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
                <div><strong>ต้องการความช่วยเหลือ?</strong><small>ทีมงานพร้อมดูแลคุณ</small></div>
                <a href="support.php" aria-label="ติดต่อทีมช่วยเหลือ"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
            </div>
            <div class="sidebar-user">
                <span class="avatar"><?= $escape($avatarText) ?></span>
                <span class="user-copy"><strong><?= $escape($userName) ?></strong><small><?= $escape($userRole) ?></small></span>
                <button class="icon-button user-more" type="button" aria-label="ตัวเลือกบัญชี" data-tooltip="ตัวเลือกบัญชี"><i class="bi bi-three-dots" aria-hidden="true"></i></button>
            </div>
        </div>
    </aside>

    <div class="app-shell" id="appShell">
        <header class="topbar">
            <div class="topbar-start">
                <button class="icon-button hamburger-button" id="hamburgerButton" type="button" aria-label="เปิดเมนู" aria-controls="sidebar" aria-expanded="false">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <button class="icon-button desktop-toggle" id="desktopToggle" type="button" aria-label="ย่อหรือขยายเมนู" aria-controls="sidebar" aria-expanded="true">
                    <i class="bi bi-layout-sidebar" aria-hidden="true"></i>
                </button>
                <div class="page-title"><span>พื้นที่ทำงาน</span><h1>ภาพรวมระบบ</h1></div>
            </div>
            <div class="topbar-actions">
                <label class="search-box">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="recordSearch" placeholder="ค้นหารายการ..." aria-label="ค้นหารายการล่าสุด">
                    <kbd>/</kbd>
                </label>
                <div class="dropdown-wrap">
                    <button class="icon-button notification-button" id="notificationButton" type="button" aria-label="การแจ้งเตือน 3 รายการ" aria-expanded="false" aria-controls="notificationMenu">
                        <i class="bi bi-bell" aria-hidden="true"></i><span class="notification-dot" aria-hidden="true"></span>
                    </button>
                    <div class="floating-menu notification-menu" id="notificationMenu" hidden>
                        <div class="menu-title"><strong>การแจ้งเตือน</strong><span>3 ใหม่</span></div>
                        <a href="records.php"><i class="bi bi-inbox"></i><span><strong>มี 8 รายการรอตรวจสอบ</strong><small>เมื่อสักครู่</small></span></a>
                        <a href="reports.php"><i class="bi bi-graph-up-arrow"></i><span><strong>รายงานประจำสัปดาห์พร้อมแล้ว</strong><small>วันนี้ 09:20 น.</small></span></a>
                        <a href="users.php"><i class="bi bi-person-plus"></i><span><strong>มีผู้ใช้งานใหม่</strong><small>เมื่อวาน</small></span></a>
                    </div>
                </div>
                <div class="dropdown-wrap account-wrap">
                    <button class="account-button" id="accountButton" type="button" aria-expanded="false" aria-controls="accountMenu">
                        <span class="avatar avatar-small"><?= $escape($avatarText) ?></span>
                        <span class="account-name"><?= $escape($userName) ?></span><i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="floating-menu account-menu" id="accountMenu" hidden>
                        <div class="account-menu-user"><strong><?= $escape($userName) ?></strong><small><?= $escape($userRole) ?></small></div>
                        <a href="profile.php"><i class="bi bi-person"></i>โปรไฟล์ของฉัน</a>
                        <a href="settings.php"><i class="bi bi-gear"></i>ตั้งค่าบัญชี</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="main-content">
            <section class="welcome-row stagger-item" aria-labelledby="welcomeTitle">
                <div>
                    <p class="eyebrow"><span class="live-dot"></span> ภาพรวมการทำงาน</p>
                    <h2 id="welcomeTitle">สวัสดี, <?= $escape($userName) ?> <span class="wave" aria-hidden="true">✦</span></h2>
                    <p class="welcome-subtitle">นี่คือสิ่งที่เกิดขึ้นในระบบของคุณวันนี้</p>
                </div>
                <div class="date-chip"><i class="bi bi-calendar3" aria-hidden="true"></i><span><?= $escape($thaiDate) ?></span></div>
            </section>

            <section class="stats-grid" aria-label="สถิติภาพรวม">
                <?php foreach ($stats as $index => $stat): ?>
                    <article class="stat-card stagger-item" style="--stagger: <?= (int) ($index + 1) ?>;">
                        <div class="stat-card-top">
                            <span class="stat-icon <?= $escape($stat['tone']) ?>"><i class="bi <?= $escape($stat['icon']) ?>" aria-hidden="true"></i></span>
                            <span class="stat-trend <?= $index === 1 ? 'trend-neutral' : '' ?>">
                                <?php if ($index !== 1): ?><i class="bi bi-arrow-up-right" aria-hidden="true"></i><?php endif; ?>
                                <?= $escape($stat['change']) ?>
                            </span>
                        </div>
                        <p class="stat-label"><?= $escape($stat['label']) ?></p>
                        <p class="stat-value"><span data-count="<?= (int) $stat['value'] ?>">0</span><span class="stat-unit"><?= $index === 3 ? 'คน' : 'รายการ' ?></span></p>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="insight-grid" aria-label="ภาพรวมกิจกรรม">
                <article class="panel chart-panel stagger-item" style="--stagger: 5;">
                    <div class="panel-heading">
                        <div><p class="panel-kicker">ACTIVITY</p><h3>กิจกรรมในระบบ</h3></div>
                        <button class="select-button" type="button" aria-label="ช่วงเวลา 7 วันล่าสุด">7 วันล่าสุด <i class="bi bi-chevron-down" aria-hidden="true"></i></button>
                    </div>
                    <div class="chart-summary"><strong>1,284</strong><span class="chart-growth"><i class="bi bi-arrow-up-right" aria-hidden="true"></i> 14.6%</span><small>เทียบกับสัปดาห์ก่อน</small></div>
                    <div class="bar-chart" role="img" aria-label="กราฟกิจกรรมเจ็ดวันล่าสุด">
                        <?php foreach ($weeklyValues as $index => $value): ?>
                            <div class="bar-column"><div class="bar-track"><span class="chart-bar <?= $index === 5 ? 'bar-highlight' : '' ?>" style="--bar-height: <?= (int) $value ?>%; --bar-delay: <?= (float) ($index * 0.08) ?>s;"></span></div><span class="bar-label"><?= $escape($weekDays[$index]) ?></span></div>
                        <?php endforeach; ?>
                    </div>
                </article>
                <article class="panel summary-panel stagger-item" style="--stagger: 6;">
                    <div class="panel-heading">
                        <div><p class="panel-kicker">QUICK OVERVIEW</p><h3>สถานะข้อมูล</h3></div>
                        <span class="summary-menu-icon"><i class="bi bi-three-dots" aria-hidden="true"></i></span>
                    </div>
                    <div class="completion-ring" role="img" aria-label="ข้อมูลเสร็จสิ้น 78 เปอร์เซ็นต์"><div><strong>78<span>%</span></strong><small>เสร็จสิ้น</small></div></div>
                    <div class="legend-list">
                        <div><span class="legend-name"><i class="legend-dot dot-complete"></i>เสร็จสิ้น</span><strong>1,924</strong></div>
                        <div><span class="legend-name"><i class="legend-dot dot-pending"></i>รอตรวจสอบ</span><strong>38</strong></div>
                        <div><span class="legend-name"><i class="legend-dot dot-process"></i>กำลังดำเนินการ</span><strong>524</strong></div>
                    </div>
                </article>
            </section>

            <section class="panel records-panel stagger-item" style="--stagger: 7;" aria-labelledby="recordsTitle">
                <div class="panel-heading records-heading">
                    <div><p class="panel-kicker">LATEST UPDATES</p><h3 id="recordsTitle">รายการล่าสุด</h3><p class="panel-description">ติดตามข้อมูลที่มีการเปลี่ยนแปลงในระบบ</p></div>
                    <a class="button button-outline" href="records.php"><span>ดูรายการทั้งหมด</span><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
                <div class="table-scroll">
                    <div class="table-loading" id="tableLoading" aria-label="กำลังโหลดรายการ">
                        <?php for ($row = 0; $row < 4; $row++): ?><div class="skeleton-row"><span></span><span></span><span></span><span></span></div><?php endfor; ?>
                    </div>
                    <table class="records-table" id="recordsTable">
                        <thead><tr><th scope="col">รายการ</th><th scope="col">หมวดหมู่</th><th scope="col">สถานะ</th><th scope="col">อัปเดตล่าสุด</th><th scope="col"><span class="sr-only">การทำงาน</span></th></tr></thead>
                        <tbody>
                            <?php foreach ($records as $record):
                                $statusKey = strtolower((string) ($record['status'] ?? ''));
                                $statusInfo = $statusLabels[$statusKey] ?? [(string) ($record['status'] ?? 'ไม่ระบุ'), 'muted'];
                                $updatedAt = $record['updated_at'] ?? '';
                                if ($updatedAt instanceof DateTimeInterface) {
                                    $updatedAt = $updatedAt->format('d/m/Y');
                                }
                            ?>
                                <tr>
                                    <td><div class="record-name"><span class="record-file-icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span><span><strong><?= $escape($record['title'] ?? 'ไม่มีชื่อรายการ') ?></strong><small><?= $escape($record['id'] ?? '') ?></small></span></div></td>
                                    <td><?= $escape($record['category'] ?? 'ทั่วไป') ?></td>
                                    <td><span class="status-badge status-<?= $escape($statusInfo[1]) ?>"><i aria-hidden="true"></i><?= $escape($statusInfo[0]) ?></span></td>
                                    <td class="date-cell"><?= $escape($updatedAt) ?></td>
                                    <td><a class="row-action" href="record.php?id=<?= rawurlencode((string) ($record['id'] ?? '')) ?>" aria-label="ดูรายละเอียด <?= $escape($record['title'] ?? 'รายการ') ?>"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="no-results" id="noResults" hidden>ไม่พบรายการที่ตรงกับคำค้นหา</p>
                </div>
            </section>

            <footer class="page-footer"><span>© <?= (int) $today->format('Y') ?> Mekesin Data Management</span><span>ออกแบบเพื่อการทำงานที่ราบรื่น <i class="bi bi-heart-fill" aria-hidden="true"></i></span></footer>
        </main>
    </div>
    <noscript><style>.table-loading{display:none!important}.records-table{display:table!important}</style></noscript>
    <script src="js/main.js" defer></script>
</body>
</html>

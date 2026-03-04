<?php
include 'config.php';

// 1. ดึงข้อมูลเซนเซอร์ล่าสุด
$result = $conn->query("SELECT * FROM sensor_data ORDER BY id DESC LIMIT 1");
$data = $result->fetch_assoc();

// 2. ดึงประวัติ 5 รายการล่าสุด
$history = $conn->query("SELECT * FROM sensor_data ORDER BY id DESC LIMIT 5");

// 3. ดึงสถานะการควบคุม
$control = $conn->query("SELECT relay, buzzer FROM device_control WHERE id=1");
$c = $control->fetch_assoc();

// --- Logic การตรวจสอบ ---
$threshold_dark = 800; // เลขเยอะคือมืด
$is_dark = ($data['light'] > $threshold_dark); 
$is_motion = ($data['motion'] == 1);
$is_intruder = ($is_dark && $is_motion);

// Auto-Buzzer: ถ้าเข้าเงื่อนไขบุกรุก ให้สั่งเปิด Buzzer ใน DB ทันที
if ($is_intruder && $c['buzzer'] == 0) {
    $conn->query("UPDATE device_control SET buzzer = 1 WHERE id = 1");
    // แก้ไข: เปลี่ยนจาก index.php เป็น dashboard.php
    echo "<script>window.location.href='dashboard.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Advanced IoT Security System</title>
    <meta http-equiv="refresh" content="5">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --danger: #ff4757; --success: #2ed573; --dark: #2f3542; --gray: #f1f2f6; --warning: #ffa502;
        }
        body { font-family: 'Kanit', sans-serif; background: #eef2f3; margin: 0; padding: 20px; color: var(--dark); }
        .grid-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 1000px; margin: auto; }
        
        .card { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s; }
        .full-width { grid-column: span 2; }
        
        /* การ์ดแจ้งเตือนผู้บุกรุก */
        .alert-active { background: var(--danger) !important; color: white; animation: blink 1s infinite; }
        @keyframes blink { 50% { opacity: 0.8; } }

        /* การแสดงผล Motion (ปรับปรุงใหม่) */
        .motion-status {
            padding: 15px; border-radius: 10px; font-weight: bold; text-align: center; margin-top: 15px;
            font-size: 1.1rem; border: 2px solid transparent;
        }
        .motion-normal { background: #dff9fb; color: #130f40; border-color: #badc58; }
        .motion-detected { background: #ffeaa7; color: #d35400; border-color: var(--warning); animation: pulse 1s infinite; }
        .motion-danger { background: var(--danger); color: white; border-color: #c0392b; animation: pulse 0.5s infinite; }

        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.03); } 100% { transform: scale(1); } }

        /* มาตรวัดแสง */
        .gauge-bar { background: var(--gray); height: 12px; border-radius: 6px; margin-top: 10px; overflow: hidden; }
        .gauge-fill { height: 100%; background: var(--warning); transition: 1s ease-in-out; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--gray); }

        .btn-group { display: flex; gap: 10px; margin-top: 10px; }
        .btn { flex: 1; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; color: white; transition: 0.3s; text-decoration: none; text-align: center; }
        .btn-on { background: var(--success); box-shadow: 0 4px #26af5a; }
        .btn-off { background: var(--danger); box-shadow: 0 4px #c0392b; }
        .btn-gray { background: #a4b0be; opacity: 0.6; }

        h2 { margin-top: 0; font-size: 1.2rem; border-left: 4px solid #1e90ff; padding-left: 10px; }
    </style>
</head>
<body>

<div class="grid-container">
    
    <div class="card full-width <?= $is_intruder ? 'alert-active' : '' ?>">
        <h1 style="margin:0; font-size: 1.5rem; text-align: center;">
            <?= $is_intruder ? '🚨 ตรวจพบผู้บุกรุก! (Intruder Alert)' : '🏠 สถานะบ้านปัจจุบัน: ปกติ' ?>
        </h1>
    </div>

    <div class="card">
        <h2>📊 ข้อมูลจากเซนเซอร์</h2>
        <div style="font-size: 1.1rem; line-height: 2;">
            🌡️ อุณหภูมิ: <b><?= $data['temperature'] ?> °C</b><br>
            💧 ความชื้น: <b><?= $data['humidity'] ?> %</b>
        </div>
        
        <div style="margin-top:15px;">
            <span>💡 ระดับความมืด: <b><?= $data['light'] ?></b></span>
            <div class="gauge-bar">
                <div class="gauge-fill" style="width: <?= min(($data['light']/1024)*100, 100) ?>%;"></div>
            </div>
            <small><?= $is_dark ? '<b style="color:var(--danger)">สภาวะ: มืดสนิท</b>' : 'สภาวะ: แสงสว่างปกติ' ?></small>
        </div>

        <div class="motion-status <?= $is_intruder ? 'motion-danger' : ($is_motion ? 'motion-detected' : 'motion-normal') ?>">
            <?= $is_intruder ? '⚠️ ALERT: พบผู้บุกรุก!' : ($is_motion ? '🔎 ตรวจพบความเคลื่อนไหว' : '✅ ไม่พบการเคลื่อนไหว') ?>
        </div>
    </div>

    <div class="card">
        <h2>🎮 การควบคุมอุปกรณ์</h2>
        <p>Buzzer: <b><?= $c['buzzer'] ? 'กำลังส่งเสียง' : 'เงียบ' ?></b></p>
        <div class="btn-group">
            <a href="update_control.php?relay=<?= $c['relay'] ?>&buzzer=1" class="btn <?= $c['buzzer'] ? 'btn-on' : 'btn-gray' ?>">เปิดเสียง</a>
            <a href="update_control.php?relay=<?= $c['relay'] ?>&buzzer=0" class="btn <?= !$c['buzzer'] ? 'btn-off' : 'btn-gray' ?>">ปิดเสียง</a>
        </div>
        
        <p style="margin-top:15px;">Relay (สวิตช์ไฟ): <b><?= $c['relay'] ? 'เปิด' : 'ปิด' ?></b></p>
        <div class="btn-group">
            <a href="update_control.php?relay=1&buzzer=<?= $c['buzzer'] ?>" class="btn <?= $c['relay'] ? 'btn-on' : 'btn-gray' ?>">เปิดไฟ</a>
            <a href="update_control.php?relay=0&buzzer=<?= $c['buzzer'] ?>" class="btn <?= !$c['relay'] ? 'btn-off' : 'btn-gray' ?>">ปิดไฟ</a>
        </div>
    </div>

    <div class="card full-width">
        <h2>📜 ประวัติการตรวจจับล่าสุด</h2>
        <table>
            <thead>
                <tr>
                    <th>เวลาบันทึก</th>
                    <th>อุณหภูมิ/ชื้น</th>
                    <th>ค่าแสง</th>
                    <th>การเคลื่อนไหว</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $history->fetch_assoc()): ?>
                <tr>
                    <td><?= date('H:i:s', strtotime($row['created_at'] ?? 'now')) ?></td>
                    <td><?= $row['temperature'] ?>° / <?= $row['humidity'] ?>%</td>
                    <td><?= $row['light'] ?></td>
                    <td>
                        <?= $row['motion'] == 1 ? '<span style="color:red; font-weight:bold;">🔴 พบการเคลื่อนไหว</span>' : '⚪ ปกติ' ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (Notification.permission !== "granted") {
            Notification.requestPermission();
        }
    });

    function sendPushNotification() {
        if (Notification.permission === "granted") {
            new Notification("🚨 เตือนภัยผู้บุกรุก!", {
                body: "ตรวจพบการเคลื่อนไหวในสภาวะมืดสนิท! ตรวจสอบด่วน",
                icon: "https://cdn-icons-png.flaticon.com/512/595/595067.png"
            });
        }
    }

    <?php if ($is_intruder): ?>
        sendPushNotification();
    <?php endif; ?>
</script>

</body>
</html>
<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply_iacuc@kku.ac.th',
    'senderName' => 'ระบบรายงานความก้าวหน้าโครงการวิจัย (IACUC)',
    // ชื่อระบบเต็ม แสดงที่ navbar ด้านบน (views/layouts/_topbar.php) — เก็บไว้จุดเดียวกันร่วมกับ
    // senderName ด้านบน กันไม่ให้ต้อง hardcode ข้อความยาวซ้ำในหลายไฟล์
    'systemFullName' => 'ระบบรายงานความก้าวหน้าโครงการวิจัยที่ได้รับการรับรองจรรยาบรรณการดำเนินการต่อสัตว์เพื่องานทางวิทยาศาสตร์',
    // ใช้ประกอบลิงก์แบบ absolute ในอีเมล (เช่น cron รายเดือนที่ไม่มี HTTP request context
    // ให้ derive host จากคำขอ) — ตั้งผ่าน env ตอน deploy จริง ค่า default ใช้ตอน dev
    'appBaseUrl' => getenv('APP_BASE_URL') ?: 'http://localhost:8580',
];

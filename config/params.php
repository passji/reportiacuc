<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply_iacuc@kku.ac.th',
    'senderName' => 'ระบบรายงานความก้าวหน้าโครงการวิจัย (IACUC)',
    // ใช้ประกอบลิงก์แบบ absolute ในอีเมล (เช่น cron รายเดือนที่ไม่มี HTTP request context
    // ให้ derive host จากคำขอ) — ตั้งผ่าน env ตอน deploy จริง ค่า default ใช้ตอน dev
    'appBaseUrl' => getenv('APP_BASE_URL') ?: 'http://localhost:8580',
];

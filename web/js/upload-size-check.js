(function () {
    'use strict';

    // ตรวจขนาดไฟล์แนบฝั่งเบราว์เซอร์ทันทีที่เลือกไฟล์ (ก่อน submit) — ไฟล์ที่ใหญ่เกินกำหนดจะถูกล้างออกจากช่อง
    // และแสดงข้อความ error ใต้ช่องนั้น แต่นี่เป็นแค่ UX: ฝั่งเซิร์ฟเวอร์ตรวจซ้ำเสมอ
    // (ReportController::pdfFileRule) เผื่อ JS ถูกข้าม ใช้ event delegation เพราะช่องไฟล์ใน
    // แถวที่เพิ่มด้วยปุ่ม "+ เพิ่ม..." (dynamic-rows.js) ถูก clone มาทีหลังโหลดหน้า

    var form = document.getElementById('progress-report-form');
    if (!form) {
        return;
    }
    var maxBytes = parseInt(form.getAttribute('data-max-upload-bytes'), 10);
    if (!maxBytes) {
        return;
    }
    var maxMb = Math.floor(maxBytes / 1048576);

    function formatMb(bytes) {
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    // ใช้ .invalid-feedback เดิมถ้ามี (dynamic-rows.js clearRow() เคลียร์ข้อความ/สถานะ is-invalid ของ
    // แถวที่ clone ให้อยู่แล้ว) ไม่มีก็สร้างต่อท้ายช่อง input
    function feedbackFor(input) {
        var next = input.nextElementSibling;
        if (next && next.classList.contains('invalid-feedback')) {
            return next;
        }
        var el = document.createElement('div');
        el.className = 'invalid-feedback';
        input.insertAdjacentElement('afterend', el);
        return el;
    }

    form.addEventListener('change', function (e) {
        var input = e.target;
        if (!input || input.type !== 'file') {
            return;
        }

        var tooBig = [];
        for (var i = 0; i < input.files.length; i++) {
            if (input.files[i].size > maxBytes) {
                tooBig.push('"' + input.files[i].name + '" (' + formatMb(input.files[i].size) + ')');
            }
        }

        var feedback = feedbackFor(input);
        if (tooBig.length === 0) {
            input.classList.remove('is-invalid');
            feedback.textContent = '';
            return;
        }

        input.value = '';
        input.classList.add('is-invalid');
        feedback.style.display = 'block';
        feedback.textContent = 'ไฟล์ใหญ่เกินกำหนด (ต้องไม่เกิน ' + maxMb + ' MB ต่อไฟล์): ' + tooBig.join(', ')
            + ' — ระบบไม่รับไฟล์นี้ กรุณาเลือกไฟล์ใหม่';
    });
})();

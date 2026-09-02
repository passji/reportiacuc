(function () {
    'use strict';

    // ปฏิทินสำหรับ input.thai-date-input ทั้งหมด — ใช้ flatpickr (self-host ไว้ที่
    // web/js/vendor/flatpickr.min.js ไม่ผูก CDN ตามแนวทางเดียวกับไลบรารีอื่นในโปรเจกต์นี้)
    // ค่าที่โชว์/พิมพ์ในช่องยังเป็น วว/ดด/ปปปป แบบ พ.ศ. เหมือนเดิม (formatDate/parseDate ด้านล่าง
    // แปลง Date object จริงของ JS ให้เป็นสตริง พ.ศ. ตอนแสดง และแปลงกลับตอนอ่านค่า) ฝั่งเซิร์ฟเวอร์ยัง
    // ตรวจสอบ/แปลงค่าซ้ำเสมอ (ดู ProgressReport::validateThaiDate() / ReportIpFiling::validateThaiDate())
    // เผื่อ JS พังหรือถูกปิด ผู้ใช้ยังพิมพ์เองได้ตรงๆ เหมือนเดิมด้วย (allowInput: true)

    var thaiLocale = {
        weekdays: {
            shorthand: ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'],
            longhand: ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'],
        },
        months: {
            shorthand: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'],
            longhand: [
                'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม',
            ],
        },
        firstDayOfWeek: 0,
        rangeSeparator: ' — ',
        weekAbbreviation: 'สัปดาห์',
        scrollTitle: 'เลื่อนเปลี่ยน',
        toggleTitle: 'คลิกเพื่อสลับ',
    };

    function pad2(n) {
        return String(n).padStart(2, '0');
    }

    function formatBuddhist(date) {
        return pad2(date.getDate()) + '/' + pad2(date.getMonth() + 1) + '/' + (date.getFullYear() + 543);
    }

    function parseBuddhist(input) {
        var m = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec(String(input).trim());
        if (!m) {
            return undefined;
        }
        var day = parseInt(m[1], 10);
        var month = parseInt(m[2], 10);
        var yearBuddhist = parseInt(m[3], 10);
        var date = new Date(yearBuddhist - 543, month - 1, day);
        // new Date() "ปัดเศษ" วันที่เกินจริงให้เองแทนที่จะ error (เช่น 31/02 กลายเป็น 3 มีนาคม) —
        // เช็คย้อนกลับว่าค่าที่ได้ตรงกับที่พิมพ์จริงไหม ถ้าไม่ตรงถือว่า parse ไม่ได้ ให้ flatpickr
        // เก็บข้อความดิบไว้ก่อน (ฝั่งเซิร์ฟเวอร์จะ reject ให้เองตอน submit)
        if (date.getDate() !== day || date.getMonth() !== month - 1) {
            return undefined;
        }
        return date;
    }

    // หัวปฏิทิน (ช่องปีตัวเลข) ของ flatpickr โชว์ปีจาก Date object จริง (ค.ศ.) เสมอ ไม่ผ่าน
    // formatDate ด้านบน — ต้องเขียนทับเป็น พ.ศ. เองทุกครั้งที่เปิด/เปลี่ยนเดือน/เปลี่ยนปี ไม่งั้นหัว
    // ปฏิทินจะโชว์คนละปีกับตัวเลขในช่อง input
    function syncYearDisplay(instance) {
        if (instance.currentYearElement) {
            instance.currentYearElement.value = instance.currentYear + 543;
        }
    }

    function attachDatePicker(el) {
        if (el._flatpickr) {
            return; // ผูกไว้แล้ว ไม่ต้องผูกซ้ำ
        }
        flatpickr(el, {
            allowInput: true,
            dateFormat: 'd/m/Y',
            locale: thaiLocale,
            formatDate: function (date) {
                return formatBuddhist(date);
            },
            parseDate: function (dateStr) {
                return parseBuddhist(dateStr);
            },
            onReady: syncYearDisplay,
            onOpen: syncYearDisplay,
            onMonthChange: syncYearDisplay,
            onYearChange: syncYearDisplay,
        });
    }

    // ผูกปฏิทินให้ input.thai-date-input ทุกตัวที่อยู่ใต้ root (รวม root เองถ้า root คือ input นั้น
    // โดยตรง) — เรียกครั้งแรกตอนโหลดหน้า และเรียกซ้ำจาก dynamic-rows.js ทุกครั้งที่เพิ่มแถวใหม่ (เช่น
    // แถวผลงานตีพิมพ์/ทรัพย์สินทางปัญญาข้อ 6.1/6.2) เพราะ cloneNode(true) ก็อปปี้แค่ตัว input ไปแต่ไม่
    // ก็อปปี้การผูก flatpickr ของ JS ไปด้วย ต้องผูกใหม่ให้แถวที่เพิ่งเพิ่มเอง
    function initThaiDatePickers(root) {
        if (typeof flatpickr === 'undefined') {
            return; // โหลดไลบรารีไม่สำเร็จ — ยังพิมพ์ วว/ดด/ปปปป เองตรงๆ ได้อยู่ ฟอร์มไม่พัง
        }
        root = root || document;
        if (root.matches && root.matches('input.thai-date-input')) {
            attachDatePicker(root);
        }
        root.querySelectorAll('input.thai-date-input').forEach(attachDatePicker);
    }

    window.ThaiDatePicker = { init: initThaiDatePickers };

    document.addEventListener('DOMContentLoaded', function () {
        initThaiDatePickers(document);
    });
})();

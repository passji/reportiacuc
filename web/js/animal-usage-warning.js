(function () {
    'use strict';

    // เตือน (ไม่บล็อกการกรอก/ส่งข้อมูล) เมื่อ "จำนวนสัตว์ที่ใช้จริง" (ข้อ 3.2) เกินกว่า
    // "จำนวนที่ได้รับอนุมัติ" (ข้อ 3.1) — เทียบกันได้เฉพาะตอนหน่วยเป็น "ตัว" เหมือนข้อ 3.1
    // เท่านั้น (หน่วยอื่น เช่น มล./ล./กก. คนละมิติกัน เทียบกันไม่ได้ ตามที่ใช้ในหน้าอื่นของระบบ)
    function checkAnimalUsage() {
        var warning = document.getElementById('animal-usage-warning');
        var unitField = document.getElementById('progressreport-animal_used_unit');
        if (!warning || !unitField) {
            return;
        }

        var requestedMale = parseInt(document.getElementById('progressreport-animal_requested_male').value, 10) || 0;
        var requestedFemale = parseInt(document.getElementById('progressreport-animal_requested_female').value, 10) || 0;
        var usedMale = parseInt(document.getElementById('progressreport-animal_used_male').value, 10) || 0;
        var usedFemale = parseInt(document.getElementById('progressreport-animal_used_female').value, 10) || 0;

        var exceeds = unitField.value === 'head'
            && (usedMale > requestedMale || usedFemale > requestedFemale);

        warning.classList.toggle('d-none', !exceeds);
    }

    document.addEventListener('DOMContentLoaded', function () {
        checkAnimalUsage();
        ['progressreport-animal_used_male', 'progressreport-animal_used_female', 'progressreport-animal_used_unit']
            .forEach(function (id) {
                var el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', checkAnimalUsage);
                    el.addEventListener('change', checkAnimalUsage);
                }
            });
    });
})();

(function () {
    'use strict';

    // ทับดัชนีเดิม (oldIndex) เป็นดัชนีใหม่ (newIndex) ในทุก name="X[oldIndex][...]" และ
    // id="x-oldIndex-..." / label[for] ที่อยู่ในแถวที่ clone มา
    function reindexRow(row, oldIndex, newIndex) {
        var oldBracket = '[' + oldIndex + ']';
        var newBracket = '[' + newIndex + ']';
        var oldDash = '-' + oldIndex + '-';
        var newDash = '-' + newIndex + '-';

        row.querySelectorAll('[name], [id], label[for]').forEach(function (el) {
            if (el.hasAttribute('name')) {
                el.setAttribute('name', el.getAttribute('name').split(oldBracket).join(newBracket));
            }
            if (el.hasAttribute('id')) {
                el.setAttribute('id', el.getAttribute('id').split(oldDash).join(newDash));
            }
            if (el.tagName === 'LABEL' && el.hasAttribute('for')) {
                el.setAttribute('for', el.getAttribute('for').split(oldDash).join(newDash));
            }
        });
        row.setAttribute('data-index', String(newIndex));
    }

    function clearRow(row) {
        row.querySelectorAll('input[type="text"], input[type="number"], input[type="date"], textarea').forEach(function (el) {
            // input.thai-date-input ที่ผูก flatpickr ไว้แล้วต้องเคลียร์ผ่าน ._flatpickr.clear() แทน
            // การเซ็ต .value ตรงๆ — ไม่งั้น flatpickr จะยังจำวันที่เดิมไว้ในสถานะภายใน (selectedDates)
            // แล้วโชว์วันที่เดิมซ้ำตอนเปิดปฏิทินรอบถัดไป ทั้งที่ช่อง input มองเห็นว่าง
            if (el._flatpickr) {
                el._flatpickr.clear();
            } else {
                el.value = '';
            }
        });
        // input[type=file] เบราว์เซอร์ไม่ clone ค่าไฟล์ที่เลือกไว้อยู่แล้ว (ด้าน security) แต่เคลียร์
        // .value ซ้ำให้ชัดเจนไว้เผื่อบาง browser/edge case
        row.querySelectorAll('input[type="file"]').forEach(function (el) {
            el.value = '';
        });
        row.querySelectorAll('select').forEach(function (el) {
            el.selectedIndex = 0;
        });
        row.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
        row.querySelectorAll('.invalid-feedback').forEach(function (el) {
            el.textContent = '';
        });
    }

    // อัปเดตเลขลำดับที่โชว์หัวแต่ละแถว (เช่น "ผลงานที่ 6.1.2") ตามตำแหน่งจริงในตอนนี้ — ไม่ผูกกับ
    // data-index (ซึ่งใช้แค่กับ name/id ของ field ไม่จำเป็นต้องเรียงต่อเนื่องหลังลบแถวกลาง) เรียกทุก
    // ครั้งหลัง add/remove ให้เลขที่โชว์ผู้ใช้ถูกต้องเสมอ ไม่ต้องมี container ไหนใช้ data-number-prefix
    // เป็นพิเศษ แค่มี .row-number อยู่ในแถวก็พอ
    function renumberRows(container) {
        var rows = container.querySelectorAll(':scope > .dynamic-row');
        rows.forEach(function (row, idx) {
            var numberEl = row.querySelector('.row-number');
            if (numberEl) {
                numberEl.textContent = String(idx + 1);
            }
        });
    }

    document.addEventListener('click', function (e) {
        var addBtn = e.target.closest('[data-row-add]');
        if (addBtn) {
            e.preventDefault();
            var container = document.getElementById(addBtn.getAttribute('data-row-add'));
            if (!container) {
                return;
            }
            var rows = container.querySelectorAll(':scope > .dynamic-row');
            if (!rows.length) {
                return;
            }
            var lastRow = rows[rows.length - 1];
            var oldIndex = lastRow.getAttribute('data-index');
            var newIndex = rows.length;

            var clone = lastRow.cloneNode(true);
            reindexRow(clone, oldIndex, newIndex);
            clearRow(clone);
            container.appendChild(clone);
            renumberRows(container);
            // แถวที่เพิ่งเพิ่ม (clone) ยังไม่มีปฏิทิน flatpickr ผูกอยู่ (cloneNode ไม่ก็อปปี้การผูก JS)
            // ต้องผูกใหม่ให้ input.thai-date-input ในแถวนี้เอง — ดู web/js/thai-date-input.js
            if (window.ThaiDatePicker) {
                window.ThaiDatePicker.init(clone);
            }
            return;
        }

        var removeBtn = e.target.closest('[data-row-remove]');
        if (removeBtn) {
            e.preventDefault();
            var row = removeBtn.closest('.dynamic-row');
            if (!row || !row.parentElement) {
                return;
            }
            var parent = row.parentElement;
            var siblingRows = parent.querySelectorAll(':scope > .dynamic-row');
            if (siblingRows.length > 1) {
                row.remove();
            } else {
                clearRow(row);
            }
            renumberRows(parent);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('#publications-list, #ip-filings-list').forEach(renumberRows);
    });
})();

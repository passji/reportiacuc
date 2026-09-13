(function () {
    'use strict';

    // สกัดการ submit จริงของฟอร์มรายงาน — โผล่หน้าตรวจสอบ (modal) ก่อนเสมอ ต้องติ๊กครบ 2 checkbox
    // แล้วกด "ยืนยันและส่งรายงาน" เท่านั้นถึงจะส่งจริง ใช้ event 'beforeSubmit' ของ yii.activeForm.js
    // (ไฟล์ assets ของ Yii2 เอง) ซึ่งจะยิงก็ต่อเมื่อ client-side validation ของฟอร์มผ่านหมดแล้วเท่านั้น
    // — จึงไม่ต้องเขียน validation ซ้ำเองที่นี่ แค่ "ดักไว้ก่อน submit จริง" อีกชั้นเดียว
    //
    // ตอนกด "ยืนยันและส่งรายงาน" เรียก form.submit() แบบ native DOM (ไม่ใช่ผ่าน jQuery) โดยตั้งใจ —
    // native submit() ไม่ยิง event 'submit' เลย จึงข้าม client validation/beforeSubmit ของ
    // yii.activeForm.js ไปเลย ไม่วนกลับมาเปิด modal ซ้ำอีกรอบ

    function buildPreviewRecap() {
        var source = document.getElementById('report-form-fields');
        var recap = document.getElementById('report-preview-recap');
        if (!source || !recap) {
            return;
        }

        var clone = source.cloneNode(true);

        // เปลี่ยน id ทุกตัวในโคลนให้ไม่ชนกับของจริงบนหน้า (document.getElementById ใน
        // report-form.js/dynamic-rows.js จะได้ยังหาตัวจริงเจอ ไม่หลงไปเจอโคลนที่ปิด disabled ไว้)
        clone.querySelectorAll('[id]').forEach(function (el) {
            el.id = 'preview-' + el.id;
        });
        clone.querySelectorAll('label[for]').forEach(function (label) {
            label.setAttribute('for', 'preview-' + label.getAttribute('for'));
        });

        // เอา name ออกจากทุก input/select/textarea ในโคลนด้วย (ไม่ใช่แค่ id) — สำคัญมากสำหรับ
        // radio: โคลนนี้ถูกแทรกเข้าไปใน <form> เดียวกับของจริง (ดู create.php) ถ้ายังใช้ name เดิม
        // (เช่น "ProgressReport[objective_changed]") เบราว์เซอร์จะมองว่าโคลนกับของจริงอยู่ใน radio
        // group เดียวกัน พอโคลนถูก set checked=true ด้านล่าง เบราว์เซอร์จะไป "เคลียร์" ตัวจริงที่เคย
        // checked ไว้ทิ้งทันที (native browser behavior บังคับให้ checked ได้แค่ตัวเดียวต่อ 1 name
        // ในฟอร์มเดียวกัน) ทำให้ข้อมูลที่ผู้ใช้เลือกไว้จริงหายไปตอนเปิด preview พอดี — เอา name ออก
        // ทั้งหมดกันปัญหานี้เด็ดขาด (ปลอดภัยเพราะ control พวกนี้ disabled อยู่แล้ว ไม่ต้องส่งค่าไปไหน)
        clone.querySelectorAll('[name]').forEach(function (el) {
            el.removeAttribute('name');
        });

        // sync ค่าปัจจุบันจริงจาก input ต้นฉบับเข้าโคลน — cloneNode(true) ก็อปปี้แค่ attribute ตอน
        // render ครั้งแรก ไม่ใช่ค่าที่ผู้ใช้เพิ่งพิมพ์/เลือกเปลี่ยนภายหลัง (โดยเฉพาะ select กับ
        // radio/checkbox ที่ "checked/selected" runtime ไม่ตรงกับ attribute เดิมในทุกกรณี)
        var sourceControls = source.querySelectorAll('input, select, textarea');
        var cloneControls = clone.querySelectorAll('input, select, textarea');
        sourceControls.forEach(function (el, i) {
            var c = cloneControls[i];
            if (!c) {
                return;
            }
            if (el.type === 'file') {
                var wrapper = document.createElement('div');
                wrapper.className = 'form-control bg-body-secondary';
                var names = [];
                for (var f = 0; f < el.files.length; f++) {
                    names.push(el.files[f].name);
                }
                wrapper.textContent = names.length ? names.join(', ') : 'ไม่มีไฟล์แนบ';
                c.replaceWith(wrapper);
                return;
            }
            if (el.type === 'checkbox' || el.type === 'radio') {
                c.checked = el.checked;
            } else {
                c.value = el.value;
            }
            c.disabled = true;
        });

        // ปุ่มเพิ่ม/ลบแถวในหน้าพรีวิวไม่มีประโยชน์ (ดูอย่างเดียว แก้ไม่ได้) เอาออกกันสับสน
        clone.querySelectorAll('[data-row-add], [data-row-remove]').forEach(function (btn) {
            btn.remove();
        });

        recap.innerHTML = '';
        recap.appendChild(clone);
    }

    function resetCertifyCheckboxes() {
        var dataBox = document.getElementById('report-certify-data');
        var trueBox = document.getElementById('report-certify-true');
        var confirmBtn = document.getElementById('report-preview-confirm-btn');
        if (dataBox) {
            dataBox.checked = false;
        }
        if (trueBox) {
            trueBox.checked = false;
        }
        if (confirmBtn) {
            confirmBtn.disabled = true;
        }
    }

    function updateConfirmButtonState() {
        var dataBox = document.getElementById('report-certify-data');
        var trueBox = document.getElementById('report-certify-true');
        var confirmBtn = document.getElementById('report-preview-confirm-btn');
        if (!dataBox || !trueBox || !confirmBtn) {
            return;
        }
        confirmBtn.disabled = !(dataBox.checked && trueBox.checked);
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof jQuery === 'undefined' || typeof bootstrap === 'undefined') {
            return; // โหลดไลบรารีไม่ครบ — ไม่บล็อกการ submit ปกติเพื่อไม่ให้ผู้ใช้ส่งรายงานไม่ได้เลย
        }

        var $form = jQuery('#progress-report-form');
        var modalEl = document.getElementById('report-preview-modal');
        var confirmBtn = document.getElementById('report-preview-confirm-btn');
        var dataBox = document.getElementById('report-certify-data');
        var trueBox = document.getElementById('report-certify-true');
        if (!$form.length || !modalEl || !confirmBtn || !dataBox || !trueBox) {
            return;
        }

        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        $form.on('beforeSubmit', function () {
            buildPreviewRecap();
            resetCertifyCheckboxes();
            modal.show();
            return false;
        });

        dataBox.addEventListener('change', updateConfirmButtonState);
        trueBox.addEventListener('change', updateConfirmButtonState);

        confirmBtn.addEventListener('click', function () {
            if (!dataBox.checked || !trueBox.checked) {
                return;
            }
            modal.hide();
            document.getElementById('progress-report-form').submit();
        });
    });
})();

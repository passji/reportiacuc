(function () {
    'use strict';

    // ตรวจฟอร์มรายงาน "ทุกข้อ" ก่อนส่ง แล้วสรุปรายการข้อผิดพลาดทั้งหมดไว้ในกล่องแดงเหนือปุ่ม "ส่งรายงาน"
    // (คลิกที่รายการเพื่อเลื่อนไปยังช่องนั้นได้) — แบ่งเป็น 2 ส่วนทำงานคู่กัน:
    //   1) ช่องที่ผูกกับ rule ของ Yii (required/integer/string max ฯลฯ) — yii.activeForm.js ตรวจให้เองแล้วส่งผล
    //      มาทาง event 'afterValidate' เราแค่รวบรวมข้อความมาแสดงเป็นรายการเดียว
    //   2) สิ่งที่ Yii ตรวจฝั่ง client ไม่ได้/ไม่ครอบคลุม ตรวจเพิ่มเองที่นี่: รูปแบบวันที่ พ.ศ., ความยาวของช่องใน
    //      แถวผลงาน 6.1/6.2 ที่เพิ่มด้วยปุ่ม "+ เพิ่ม" (clone ภายหลัง Yii ไม่รู้จัก), และไฟล์แนบ (ชนิด/ขนาด/
    //      จำนวน/ขนาดรวมของทั้ง request) — กฎชุดเดียวกับฝั่งเซิร์ฟเวอร์ (ReportController/โมเดล) ซึ่งยังตรวจซ้ำเสมอ
    //      เผื่อ JS ถูกข้าม นี่เป็นแค่การบอกผู้ใช้ให้เร็วและครบก่อนส่ง (ไม่ต้องรอโหลดหน้าใหม่แล้วต้องเลือกไฟล์ซ้ำ)

    var form = document.getElementById('progress-report-form');
    if (!form || typeof jQuery === 'undefined') {
        return;
    }
    var $form = jQuery(form);
    var summary = document.getElementById('report-error-summary');
    var maxFileBytes = parseInt(form.getAttribute('data-max-upload-bytes'), 10) || 0;
    var maxPostBytes = parseInt(form.getAttribute('data-max-post-bytes'), 10) || 0;
    var MAX_ATTACHMENTS = 10;

    // ความยาวสูงสุดของช่องในแถว 6.1/6.2 (ตรงกับคอลัมน์ DB และ rules() ของ ReportPublication/ReportIpFiling)
    var ROW_MAX = {
        article_title: 16000, journal_name: 255, doi: 255, db_other: 255,
        quartile: 20, impact_factor: 20, issue: 50, page: 50, pub_month: 20, pub_year: 10,
        registration_no: 100, asset_name: 16000
    };

    // กลุ่มปุ่ม radio ที่บังคับเลือก — ฟอร์มสร้าง radio ด้วย id=false ทำให้ yii.activeForm.js หา input ของกลุ่มไม่เจอ
    // จึงไม่ตรวจ required ฝั่ง client ให้ (ฝั่งเซิร์ฟเวอร์ตรวจเสมอ) ต้องตรวจเองตรงนี้ ชื่อให้ตรงกับ attributeLabels()
    var REQUIRED_RADIOS = {
        objective_changed: 'ข้อ 1: การเปลี่ยนแปลงวัตถุประสงค์ของโครงการ',
        method_changed: 'ข้อ 4.1: การเปลี่ยนแปลงวิธีการทดลอง',
        adverse_event: 'ข้อ 4.3: เหตุการณ์ไม่พึงประสงค์',
        personnel_changed: 'ข้อ 4.4: การเปลี่ยนแปลงผู้ปฏิบัติงาน',
        alt_method_found: 'ข้อ 4.5: วิธีการทดแทนการใช้สัตว์ทดลอง',
        has_publication: 'ข้อ 6: การตีพิมพ์ เผยแพร่ หรือนำไปใช้ประโยชน์'
    };

    var extraErrors = [];

    function mb(bytes) {
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function isHidden(el) {
        return !!el.closest('.d-none');
    }

    function labelOf(el) {
        var box = el.closest('div[class*="field-"]');
        var label = box ? box.querySelector('label') : null;
        return label ? label.textContent.replace(/\s+/g, ' ').replace(/\*$/, '').trim() : (el.getAttribute('name') || 'ช่องข้อมูล');
    }

    function rowPrefix(el) {
        var row = el.closest('.dynamic-row');
        var title = row ? row.querySelector('.fw-bold.text-primary') : null;
        return title ? title.textContent.replace(/\s+/g, ' ').trim() + ': ' : '';
    }

    function validThaiDate(value) {
        value = value.trim();
        if (value === '') {
            return true;
        }
        var y, m, d;
        var iso = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
        var th = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec(value);
        if (iso) {
            y = +iso[1]; m = +iso[2]; d = +iso[3];
        } else if (th) {
            d = +th[1]; m = +th[2]; y = +th[3] - 543;
        } else {
            return false;
        }
        if (y < 1) {
            return false;
        }
        var dt = new Date(2000, m - 1, d);
        dt.setFullYear(y);
        return dt.getFullYear() === y && dt.getMonth() === m - 1 && dt.getDate() === d;
    }

    function feedbackFor(el) {
        var next = el.nextElementSibling;
        if (next && next.classList.contains('invalid-feedback')) {
            return next;
        }
        var box = document.createElement('div');
        box.className = 'invalid-feedback';
        el.insertAdjacentElement('afterend', box);
        return box;
    }

    function clearMark(el) {
        el.classList.remove('is-invalid');
        el.removeAttribute('data-js-extra');
        var fb = el.nextElementSibling;
        if (!fb || !fb.classList.contains('invalid-feedback')) {
            fb = el.querySelector(':scope > .invalid-feedback'); // กลุ่ม radio: ข้อความอยู่ใน container เอง
        }
        if (fb) {
            fb.textContent = '';
        }
    }

    function clearExtraMarks() {
        form.querySelectorAll('[data-js-extra]').forEach(clearMark);
    }

    function addExtra(el, message) {
        el.classList.add('is-invalid');
        el.setAttribute('data-js-extra', '1');
        var box = feedbackFor(el);
        box.textContent = message;
        box.style.display = 'block';
        extraErrors.push({ el: el, text: rowPrefix(el) + message });
    }

    function runExtraChecks(skip) {
        extraErrors = [];
        clearExtraMarks();

        // 0) กลุ่ม radio ที่ยังไม่ได้เลือก
        Object.keys(REQUIRED_RADIOS).forEach(function (key) {
            var radios = form.querySelectorAll('input[type="radio"][name$="[' + key + ']"]');
            if (!radios.length || form.querySelector('input[type="radio"][name$="[' + key + ']"]:checked')) {
                return;
            }
            var box = radios[0].closest('div[class*="field-"]') || radios[0].parentElement;
            var message = REQUIRED_RADIOS[key] + ' ต้องเลือกคำตอบ';
            box.setAttribute('data-js-extra', '1');
            var fb = box.querySelector(':scope > .invalid-feedback');
            if (!fb) {
                fb = document.createElement('div');
                fb.className = 'invalid-feedback';
                box.appendChild(fb);
            }
            fb.textContent = message;
            fb.style.display = 'block';
            extraErrors.push({ el: radios[0], text: message });
        });

        // 1) รูปแบบวันที่ (วว/ดด/ปปปป พ.ศ.) — เฉพาะช่องที่มองเห็นอยู่ (ช่องที่ซ่อนตามสถานะ ฝั่งเซิร์ฟเวอร์ก็ไม่บังคับ)
        form.querySelectorAll('input.thai-date-input').forEach(function (el) {
            if (isHidden(el) || skip.indexOf(el) !== -1 || validThaiDate(el.value)) {
                return;
            }
            addExtra(el, labelOf(el) + ': รูปแบบวันที่ไม่ถูกต้อง กรุณากรอกเป็น วว/ดด/ปปปป (พ.ศ.) เช่น 01/12/2569');
        });

        // 2) ความยาวช่องในแถวผลงาน 6.1/6.2 — ฝั่งเซิร์ฟเวอร์ตรวจเฉพาะแถวที่ไม่ว่าง และเมื่อตอบว่า "มีการตีพิมพ์"
        var hasPub = form.querySelector('input[type="radio"][name$="[has_publication]"]:checked');
        if (hasPub && hasPub.value === 'yes') {
            form.querySelectorAll('#publications-list .dynamic-row, #ip-filings-list .dynamic-row').forEach(function (row) {
                var isPub = !!row.closest('#publications-list');
                var keyFields = isPub ? ['article_title'] : ['registration_no', 'asset_name'];
                var filled = keyFields.some(function (name) {
                    var f = row.querySelector('[name$="[' + name + ']"]');
                    return f && f.value.trim() !== '';
                });
                if (!filled) {
                    return;
                }
                row.querySelectorAll('input[type="text"], textarea').forEach(function (el) {
                    var m = /\[(\w+)\]$/.exec(el.getAttribute('name') || '');
                    var max = m ? ROW_MAX[m[1]] : 0;
                    if (!max || skip.indexOf(el) !== -1 || el.value.length <= max) {
                        return;
                    }
                    addExtra(el, labelOf(el) + ': ยาวเกินกำหนด (ไม่เกิน ' + max + ' ตัวอักษร — ตอนนี้ ' + el.value.length + ' ตัวอักษร)');
                });
            });
        }

        // 3) ไฟล์แนบ — ชนิด/ขนาดต่อไฟล์/จำนวน และขนาดรวมของทั้ง request (เกิน post_max_size แล้ว PHP ทิ้งฟอร์มเงียบๆ)
        var totalBytes = 0;
        form.querySelectorAll('input[type="file"]').forEach(function (el) {
            var files = el.files || [];
            var isAttachments = el.getAttribute('name') === 'attachments[]';
            var label = isAttachments ? 'เอกสารแนบ (PDF)' : labelOf(el);
            for (var i = 0; i < files.length; i++) {
                totalBytes += files[i].size;
            }
            if (isHidden(el)) {
                return;
            }
            if (isAttachments && files.length > MAX_ATTACHMENTS) {
                addExtra(el, label + ': แนบได้ไม่เกิน ' + MAX_ATTACHMENTS + ' ไฟล์ (เลือกมา ' + files.length + ' ไฟล์)');
            }
            for (var j = 0; j < files.length; j++) {
                var f = files[j];
                var looksPdf = /\.pdf$/i.test(f.name) && (f.type === '' || f.type === 'application/pdf');
                if (!looksPdf) {
                    addExtra(el, label + ': ไฟล์ "' + f.name + '" ไม่ใช่ PDF (รองรับเฉพาะนามสกุล .pdf)');
                } else if (maxFileBytes && f.size > maxFileBytes) {
                    addExtra(el, label + ': ไฟล์ "' + f.name + '" ใหญ่เกินกำหนด (' + mb(f.size) + ' — ต้องไม่เกิน ' + mb(maxFileBytes) + ' ต่อไฟล์)');
                }
            }
        });
        if (maxPostBytes && totalBytes > maxPostBytes) {
            var attach = form.querySelector('input[name="attachments[]"]');
            if (attach) {
                addExtra(attach, 'ขนาดไฟล์แนบรวมทุกช่องใหญ่เกินกำหนด (' + mb(totalBytes) + ' — รวมกันต้องไม่เกิน ' + mb(maxPostBytes) + ') กรุณาลดจำนวน/ขนาดไฟล์');
            }
        }
    }

    function scrollToEl(el) {
        // ช่องที่อยู่ในส่วนที่ซ่อนอยู่ (offsetParent เป็น null) เลื่อนไปหาไม่ได้ — ขึ้นไปหา element ครอบที่มองเห็นแทน
        var target = el;
        while (target.offsetParent === null && target.parentElement) {
            target = target.parentElement;
        }
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        try {
            el.focus({ preventScroll: true });
        } catch (e) { /* ช่องที่โฟกัสไม่ได้ (เช่น กลุ่ม radio) ข้ามไป */ }
    }

    function render(items) {
        if (!summary) {
            return;
        }
        summary.innerHTML = '';
        if (!items.length) {
            summary.classList.add('d-none');
            return;
        }

        var head = document.createElement('div');
        head.className = 'fw-bold mb-1';
        head.textContent = 'ยังส่งรายงานไม่ได้ — พบข้อผิดพลาด ' + items.length
            + ' รายการ กรุณาแก้ไขตามรายการด้านล่าง แล้วกด "ส่งรายงาน" อีกครั้ง';
        summary.appendChild(head);

        var ul = document.createElement('ul');
        ul.className = 'mb-0 ps-3';
        items.forEach(function (item) {
            var li = document.createElement('li');
            var a = document.createElement('a');
            a.href = '#';
            a.className = 'alert-link';
            a.textContent = item.text;
            a.addEventListener('click', function (e) {
                e.preventDefault();
                if (item.el) {
                    scrollToEl(item.el);
                }
            });
            li.appendChild(a);
            ul.appendChild(li);
        });
        summary.appendChild(ul);
        summary.classList.remove('d-none');
        summary.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function findInput(attr) {
        try {
            return form.querySelector(attr.input) || form.querySelector(attr.container);
        } catch (e) {
            return null;
        }
    }

    // afterValidate ยิงทั้งตอน validate ทีละช่อง (blur/change) และตอนกดส่ง — สนใจเฉพาะตอนกดส่ง
    $form.on('afterValidate', function (event, messages, errorAttributes) {
        var data = $form.data('yiiActiveForm');
        if (!data || !data.submitting) {
            return;
        }

        var items = [];
        var seen = [];
        (errorAttributes || []).forEach(function (attr) {
            var el = findInput(attr);
            var msgs = messages[attr.id] || [];
            if (el) {
                seen.push(el);
            }
            msgs.forEach(function (msg) {
                items.push({ el: el, text: (el ? rowPrefix(el) : '') + msg });
            });
        });

        runExtraChecks(seen);
        extraErrors.forEach(function (e) {
            items.push(e);
        });

        // เรียงตามลำดับที่ปรากฏบนหน้าฟอร์ม (จากบนลงล่าง) ให้ผู้ใช้แก้ไล่ไปทีละข้อได้
        items.sort(function (x, y) {
            if (!x.el || !y.el || x.el === y.el) {
                return 0;
            }
            return x.el.compareDocumentPosition(y.el) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1;
        });

        render(items);
    });

    // แก้ช่องที่เราเป็นคนติดธงผิดแล้ว ให้ธง/ข้อความหายทันที (กลุ่ม radio ติดธงที่ container)
    function clearFlagFor(target) {
        if (!target || !target.closest) {
            return;
        }
        var flagged = target.hasAttribute('data-js-extra') ? target : target.closest('[data-js-extra]');
        if (flagged) {
            clearMark(flagged);
        }
    }
    form.addEventListener('input', function (e) {
        clearFlagFor(e.target);
    });
    form.addEventListener('change', function (e) {
        clearFlagFor(e.target);
    });

    // เปิดหน้ามาพร้อมรายการ error จากเซิร์ฟเวอร์ (ส่งแล้วไม่ผ่าน) — เลื่อนมาที่กล่องสรุปให้เห็นทันที
    document.addEventListener('DOMContentLoaded', function () {
        if (summary && !summary.classList.contains('d-none')) {
            summary.scrollIntoView({ block: 'center' });
        }
    });

    // ให้ report-preview.js ถามว่ามีข้อผิดพลาดที่ตรวจเพิ่มเองค้างอยู่ไหม — ถ้ามีห้ามเปิดหน้าตรวจสอบ/ส่งจริง
    window.ReportValidate = {
        hasExtraErrors: function () {
            return extraErrors.length > 0;
        }
    };
})();

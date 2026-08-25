(function () {
    'use strict';

    // อ่านค่าปัจจุบันของ input ที่ควบคุมการโชว์/ซ่อน — รองรับทั้ง radio group
    // (ProgressReport[attr] หลายตัว) และ input เดี่ยว เช่น select/text (id="progressreport-attr")
    function currentValue(controllerId) {
        var checkedRadio = document.querySelector(
            'input[type="radio"][name$="[' + controllerId + ']"]:checked'
        );
        if (checkedRadio) {
            return checkedRadio.value;
        }

        var single = document.getElementById('progressreport-' + controllerId.toLowerCase());
        return single ? single.value : null;
    }

    function applyToggles() {
        document.querySelectorAll('[data-show-when]').forEach(function (el) {
            var spec = el.getAttribute('data-show-when').split(':');
            var controllerId = spec[0];
            var allowedValues = (spec[1] || '').split(',');
            var current = currentValue(controllerId);

            el.classList.toggle('d-none', allowedValues.indexOf(current) === -1);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        applyToggles();
        document.addEventListener('change', applyToggles);
    });
})();

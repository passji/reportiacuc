(function () {
    'use strict';

    // แทนที่ native confirm() ด้วย SweetAlert2 — ใช้กับฟอร์มใดก็ได้ที่ใส่
    // data-confirm-message="..." ไว้ (และ data-confirm-icon="..." เพื่อเปลี่ยนไอคอนได้ ค่าเริ่มต้น
    // "question") โดยไม่ต้องเขียน onsubmit ทีละฟอร์ม — form.submit() ไม่ทำให้เกิด submit event ซ้ำ
    // (ตาม spec ของ HTMLFormElement.submit()) จึงไม่ต้องกัน infinite loop เอง
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-confirm-message]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                Swal.fire({
                    text: form.dataset.confirmMessage,
                    icon: form.dataset.confirmIcon || 'question',
                    showCancelButton: true,
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#858796',
                    reverseButtons: true,
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
})();

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
            return;
        }

        var removeBtn = e.target.closest('[data-row-remove]');
        if (removeBtn) {
            e.preventDefault();
            var row = removeBtn.closest('.dynamic-row');
            if (!row || !row.parentElement) {
                return;
            }
            var siblingRows = row.parentElement.querySelectorAll(':scope > .dynamic-row');
            if (siblingRows.length > 1) {
                row.remove();
            } else {
                clearRow(row);
            }
        }
    });
})();

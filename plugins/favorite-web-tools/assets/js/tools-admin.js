/**
 * Favorite Web Tools - Admin JavaScript
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // Auto-format JSON schema fields on blur if valid
        const jsonFields = document.querySelectorAll('textarea[name$="_schema"]');
        jsonFields.forEach(function (field) {
            field.addEventListener('blur', function () {
                const val = field.value.trim();
                if (val !== '') {
                    try {
                        const parsed = JSON.parse(val);
                        field.value = JSON.stringify(parsed, null, 2);
                    } catch (e) {
                        // Keep raw if invalid JSON
                    }
                }
            });
        });
    });
})();


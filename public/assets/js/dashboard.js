'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const accountSelect = document.querySelector('[data-account-select]');

    if (accountSelect) {
        accountSelect.addEventListener('change', () => {
            accountSelect.form?.requestSubmit();
        });
    }
});

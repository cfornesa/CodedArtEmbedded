/**
 * CodedArt Admin Interface JavaScript
 * Client-side functionality for admin panel
 */

// Initialize admin interface
document.addEventListener('DOMContentLoaded', function() {
    initDeleteConfirmations();
    initFormValidation();
    initImagePreview();
    initSortable();
});

/**
 * Initialize delete confirmation modals
 */
function initDeleteConfirmations() {
    const deleteButtons = document.querySelectorAll('.btn-delete');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const itemName = this.dataset.name || 'this item';
            const deleteUrl = this.href;

            showModal({
                title: 'Confirm Deletion',
                message: `Are you sure you want to delete "${itemName}"? This action cannot be undone.`,
                type: 'danger',
                buttons: [
                    {
                        text: 'Cancel',
                        class: 'btn-secondary',
                        onClick: closeModal
                    },
                    {
                        text: 'Delete',
                        class: 'btn-danger',
                        onClick: function() {
                            window.location.href = deleteUrl;
                        }
                    }
                ]
            });
        });
    });
}

/**
 * Show modal dialog
 * @param {Object} options Modal options
 */
function showModal(options) {
    const modal = document.getElementById('confirmModal');
    if (!modal) {
        createModal();
        return showModal(options);
    }

    const titleEl = modal.querySelector('.modal-title');
    const messageEl = modal.querySelector('.modal-message');
    const footerEl = modal.querySelector('.modal-footer');

    titleEl.textContent = options.title || 'Confirm';
    messageEl.textContent = options.message || '';

    // Clear and rebuild footer buttons
    footerEl.innerHTML = '';
    options.buttons.forEach(button => {
        const btn = document.createElement('button');
        btn.textContent = button.text;
        btn.className = 'btn ' + button.class;
        btn.addEventListener('click', button.onClick);
        footerEl.appendChild(btn);
    });

    modal.classList.add('show');
}

/**
 * Close modal dialog
 */
function closeModal() {
    const modal = document.getElementById('confirmModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

/**
 * Create modal element if it doesn't exist
 */
function createModal() {
    const modal = document.createElement('div');
    modal.id = 'confirmModal';
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"></h3>
            </div>
            <div class="modal-body">
                <p class="modal-message"></p>
            </div>
            <div class="modal-footer"></div>
        </div>
    `;

    document.body.appendChild(modal);

    // Close on background click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
}

/**
 * Initialize form validation
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');

    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

/**
 * Validate form fields
 * @param {HTMLFormElement} form Form element
 * @returns {boolean} Valid status
 */
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');

    // Clear previous errors
    form.querySelectorAll('.error-message').forEach(el => el.remove());
    form.querySelectorAll('.form-control').forEach(el => el.classList.remove('error'));

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            showFieldError(field, 'This field is required');
        }
    });

    // Email validation
    const emailFields = form.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            isValid = false;
            showFieldError(field, 'Please enter a valid email address');
        }
    });

    // URL validation
    const urlFields = form.querySelectorAll('input[data-type="url"]');
    urlFields.forEach(field => {
        if (field.value && !isValidUrl(field.value)) {
            isValid = false;
            showFieldError(field, 'Please enter a valid URL');
        }
    });

    // Password confirmation
    const passwordField = form.querySelector('input[name="password"]');
    const confirmField = form.querySelector('input[name="password_confirm"]');
    if (passwordField && confirmField && passwordField.value !== confirmField.value) {
        isValid = false;
        showFieldError(confirmField, 'Passwords do not match');
    }

    return isValid;
}

/**
 * Show field error message
 * @param {HTMLElement} field Form field
 * @param {string} message Error message
 */
function showFieldError(field, message) {
    field.classList.add('error');

    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    errorDiv.style.color = '#dc3545';
    errorDiv.style.fontSize = '14px';
    errorDiv.style.marginTop = '5px';

    field.parentNode.appendChild(errorDiv);
}

/**
 * Validate email format
 * @param {string} email Email address
 * @returns {boolean} Valid status
 */
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Validate URL format
 * @param {string} url URL
 * @returns {boolean} Valid status
 */
function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}

/**
 * Initialize image preview
 */
function initImagePreview() {
    const imageUrlFields = document.querySelectorAll('input[data-preview]');

    imageUrlFields.forEach(field => {
        const previewId = field.dataset.preview;
        const previewEl = document.getElementById(previewId);

        if (previewEl) {
            field.addEventListener('blur', function() {
                if (this.value && isValidUrl(this.value)) {
                    previewEl.src = this.value;
                    previewEl.style.display = 'block';
                } else {
                    previewEl.style.display = 'none';
                }
            });
        }
    });
}

/**
 * Initialize sortable lists
 */
function initSortable() {
    const sortableTables = document.querySelectorAll('table[data-sortable]');

    sortableTables.forEach(table => {
        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        let draggedRow = null;

        tbody.querySelectorAll('tr').forEach(row => {
            row.draggable = true;

            row.addEventListener('dragstart', function(e) {
                draggedRow = this;
                this.style.opacity = '0.5';
                e.dataTransfer.effectAllowed = 'move';
            });

            row.addEventListener('dragend', function() {
                this.style.opacity = '';
                draggedRow = null;
            });

            row.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';

                if (draggedRow && draggedRow !== this) {
                    const rect = this.getBoundingClientRect();
                    const midpoint = rect.top + rect.height / 2;

                    if (e.clientY < midpoint) {
                        this.parentNode.insertBefore(draggedRow, this);
                    } else {
                        this.parentNode.insertBefore(draggedRow, this.nextSibling);
                    }
                }
            });
        });

        // Add save button
        const saveButton = document.createElement('button');
        saveButton.textContent = 'Save Sort Order';
        saveButton.className = 'btn btn-primary mt-2';
        saveButton.addEventListener('click', function() {
            saveSortOrder(table);
        });

        table.parentNode.insertBefore(saveButton, table.nextSibling);
    });
}

/**
 * Save sort order to server
 * @param {HTMLTableElement} table Table element
 */
function saveSortOrder(table) {
    const artType = table.dataset.artType;
    const rows = table.querySelectorAll('tbody tr');
    const sortData = {};

    rows.forEach((row, index) => {
        const id = row.dataset.id;
        if (id) {
            sortData[id] = index;
        }
    });

    // Send AJAX request
    fetch(`sort-order.php?type=${artType}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(sortData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Sort order saved successfully!', 'success');
        } else {
            showAlert('Failed to save sort order: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showAlert('An error occurred while saving sort order', 'danger');
        console.error(error);
    });
}

/**
 * Show alert message
 * @param {string} message Alert message
 * @param {string} type Alert type (success, danger, warning, info)
 */
function showAlert(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;

    const container = document.querySelector('.admin-container') || document.body;
    container.insertBefore(alert, container.firstChild);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.3s ease';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

/**
 * Auto-dismiss alerts
 */
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.cursor = 'pointer';
        alert.addEventListener('click', function() {
            this.style.opacity = '0';
            this.style.transition = 'opacity 0.3s ease';
            setTimeout(() => this.remove(), 300);
        });
    });
}, 100);

/**
 * Show/hide password toggle
 */
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const input = document.querySelector(this.dataset.target);
        if (input) {
            if (input.type === 'password') {
                input.type = 'text';
                this.textContent = 'Hide';
            } else {
                input.type = 'password';
                this.textContent = 'Show';
            }
        }
    });
});

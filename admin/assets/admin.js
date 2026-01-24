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
    initSlugHelpers();
    initLivePreview();
    initDynamicListInputs();
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
 * Initialize slug preview and availability checking.
 */
function initSlugHelpers() {
    const slugForms = document.querySelectorAll('[data-slug-check-url]');

    slugForms.forEach(form => {
        const titleInput = form.querySelector(form.dataset.slugTitleSelector || '#title');
        const slugInput = form.querySelector(form.dataset.slugInputSelector || '#slug');
        const slugPreview = form.querySelector(form.dataset.slugPreviewSelector || '#slug-preview');
        const slugStatus = form.querySelector(form.dataset.slugStatusSelector || '#slug-status');
        const slugFeedback = form.querySelector(form.dataset.slugFeedbackSelector || '#slug-feedback');
        const slugType = form.dataset.slugType || '';
        const checkUrl = form.dataset.slugCheckUrl;
        const excludeId = form.dataset.slugExcludeId || '';
        let slugCheckTimeout = null;

        if (!titleInput || !slugInput || !slugPreview || !slugStatus || !slugFeedback || !checkUrl) {
            return;
        }

        const updateSlugPreview = () => {
            if (!titleInput.value) {
                slugPreview.style.display = 'none';
                return;
            }

            if (!slugInput.value) {
                const previewSlug = titleInput.value
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '')
                    .substring(0, 200);

                if (previewSlug) {
                    slugPreview.style.display = 'inline';
                    slugPreview.querySelector('code').textContent = previewSlug;
                } else {
                    slugPreview.style.display = 'none';
                }
            } else {
                slugPreview.style.display = 'none';
            }
        };

        const checkSlugAvailability = () => {
            const slug = slugInput.value.trim();

            if (slugCheckTimeout) {
                clearTimeout(slugCheckTimeout);
            }

            if (slug === '') {
                slugStatus.style.display = 'none';
                slugFeedback.style.display = 'none';
                slugInput.style.borderColor = '';
                return;
            }

            slugStatus.innerHTML = '⏳';
            slugStatus.style.display = 'block';
            slugStatus.style.color = '#6c757d';
            slugFeedback.style.display = 'none';

            slugCheckTimeout = setTimeout(() => {
                const url = `${checkUrl}?slug=${encodeURIComponent(slug)}&type=${encodeURIComponent(slugType)}${excludeId ? `&exclude_id=${encodeURIComponent(excludeId)}` : ''}`;

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.valid && data.available) {
                            slugStatus.innerHTML = '✓';
                            slugStatus.style.color = '#28a745';
                            slugFeedback.textContent = data.message;
                            slugFeedback.style.color = '#28a745';
                            slugFeedback.style.display = 'inline';
                            slugInput.style.borderColor = '#28a745';
                        } else {
                            slugStatus.innerHTML = '✗';
                            slugStatus.style.color = '#dc3545';
                            slugFeedback.textContent = data.message;
                            slugFeedback.style.color = '#dc3545';
                            slugFeedback.style.display = 'inline';
                            slugInput.style.borderColor = '#dc3545';
                        }
                    })
                    .catch(error => {
                        console.error('Slug check error:', error);
                        slugStatus.style.display = 'none';
                        slugFeedback.style.display = 'none';
                        slugInput.style.borderColor = '';
                    });
            }, 500);
        };

        titleInput.addEventListener('input', updateSlugPreview);
        titleInput.addEventListener('keyup', updateSlugPreview);
        slugInput.addEventListener('input', () => {
            updateSlugPreview();
            checkSlugAvailability();
        });
        slugInput.addEventListener('blur', checkSlugAvailability);

        if (form.dataset.slugAutoInit === '1') {
            updateSlugPreview();
        }
    });
}

/**
 * Initialize live preview iframe debounced updates.
 */
function initLivePreview() {
    const previewForms = document.querySelectorAll('[data-live-preview]');

    previewForms.forEach(form => {
        const previewSection = document.querySelector(form.dataset.livePreviewSection || '#live-preview-section');
        const previewIframe = document.querySelector(form.dataset.livePreviewIframe || '#live-preview-iframe');
        const loadingIndicator = document.querySelector(form.dataset.livePreviewLoading || '#live-preview-loading');
        const toggleButton = form.querySelector('[data-live-preview-toggle]');
        const scrollButton = form.querySelector('[data-live-preview-scroll]');
        const url = form.dataset.livePreviewUrl;
        const debounceDelay = parseInt(form.dataset.livePreviewDebounce, 10) || 500;
        const initialDelay = parseInt(form.dataset.livePreviewInitialDelay, 10) || 1000;
        let livePreviewTimeout = null;
        let livePreviewHidden = false;

        if (!previewSection || !previewIframe || !url) {
            return;
        }

        const updateLivePreview = () => {
            if (livePreviewHidden) return;

            if (livePreviewTimeout) {
                clearTimeout(livePreviewTimeout);
            }

            livePreviewTimeout = setTimeout(() => {
                if (loadingIndicator) loadingIndicator.style.display = 'block';

                const formData = new FormData(form);

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams(formData)
                })
                    .then(response => response.text())
                    .then(html => {
                        if (loadingIndicator) loadingIndicator.style.display = 'none';

                        const blob = new Blob([html], { type: 'text/html' });
                        const blobUrl = URL.createObjectURL(blob);
                        previewIframe.src = blobUrl;
                    })
                    .catch(error => {
                        console.error('Live preview error:', error);
                        if (loadingIndicator) loadingIndicator.style.display = 'none';
                    });
            }, debounceDelay);
        };

        const toggleLivePreview = () => {
            livePreviewHidden = !livePreviewHidden;

            if (livePreviewHidden) {
                previewSection.style.display = 'none';
                previewIframe.src = '';
            } else {
                previewSection.style.display = 'block';
                updateLivePreview();
            }
        };

        const scrollToLivePreview = () => {
            previewSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        if (toggleButton) {
            toggleButton.addEventListener('click', toggleLivePreview);
        }

        if (scrollButton) {
            scrollButton.addEventListener('click', scrollToLivePreview);
        }

        if (form.dataset.livePreviewGlobal === 'true') {
            window.updateLivePreview = updateLivePreview;
            window.toggleLivePreview = toggleLivePreview;
            window.scrollToLivePreview = scrollToLivePreview;
        }

        setTimeout(() => {
            updateLivePreview();
        }, initialDelay);
    });
}

/**
 * Initialize dynamic list inputs (add/remove rows).
 */
function initDynamicListInputs() {
    const listContainers = document.querySelectorAll('[data-dynamic-list]');

    listContainers.forEach(container => {
        const rowClass = container.dataset.rowClass || 'dynamic-list-row';
        const inputName = container.dataset.inputName;
        const inputPlaceholder = container.dataset.inputPlaceholder || '';
        const addButton = document.querySelector(container.dataset.addButtonSelector);

        if (!inputName) {
            return;
        }

        const buildRow = (value = '') => {
            const row = document.createElement('div');
            row.className = rowClass;
            row.style.display = 'flex';
            row.style.gap = '10px';
            row.style.alignItems = 'center';
            row.innerHTML = `
                <input
                    type="url"
                    name="${inputName}"
                    class="form-control"
                    placeholder="${inputPlaceholder}"
                    value="${value}"
                >
                <button type="button" class="btn btn-sm btn-danger" data-dynamic-list-remove>
                    Remove
                </button>
            `;
            return row;
        };

        const updateRemoveButtons = () => {
            const rows = container.querySelectorAll(`.${rowClass}`);
            rows.forEach(row => {
                const removeButton = row.querySelector('[data-dynamic-list-remove]');
                if (removeButton) {
                    removeButton.style.display = rows.length > 1 ? 'inline-flex' : 'none';
                }
            });
        };

        if (addButton) {
            addButton.addEventListener('click', () => {
                container.appendChild(buildRow());
                updateRemoveButtons();
            });
        }

        container.addEventListener('click', event => {
            const removeButton = event.target.closest('[data-dynamic-list-remove]');
            if (!removeButton) return;

            const row = removeButton.closest(`.${rowClass}`);
            if (row) {
                row.remove();
                updateRemoveButtons();
            }
        });

        updateRemoveButtons();
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

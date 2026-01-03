// quill-manager.js
class QuillManager {
    constructor() {
        this.quillInstances = new Map();
        this.initializedForms = new Set();
        this.init();
    }

    init() {
        // Initialize all Quill editors on page load
        this.initializeEditors();

        // Handle dynamic modals
        this.setupModalHandlers();

        // Handle form submissions
        this.setupFormHandlers();
    }

    initializeEditors(container = document) {
        // Find all Quill editor containers
        const editors = container.querySelectorAll('.quill-rich-text');

        editors.forEach(editorEl => {
            const editorId = editorEl.id;

            if (!editorId || this.quillInstances.has(editorId)) {
                return;
            }

            // Find associated hidden input
            const hiddenInputId = editorEl.dataset.hiddenInput || editorId.replace('_editor', '');
            const hiddenInput = document.getElementById(hiddenInputId);

            if (!hiddenInput) {
                console.warn(`No hidden input found for editor: ${editorId}`);
                return;
            }

            // Initialize Quill
            const quill = new Quill(editorEl, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [2, 3, false] }],
                        ['bold', 'italic', 'underline'],
                        ['link', 'blockquote'],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        ['clean']
                    ]
                },
                placeholder: editorEl.dataset.placeholder || 'Write here...'
            });

            // Set initial value if any
            if (hiddenInput.value) {
                quill.root.innerHTML = hiddenInput.value;
            }

            // Sync on every change
            quill.on('text-change', () => {
                hiddenInput.value = quill.root.innerHTML;
            });

            // Store instance
            this.quillInstances.set(editorId, {
                instance: quill,
                hiddenInputId: hiddenInputId
            });
        });
    }

    setupModalHandlers() {
        // Handle Bootstrap modals
        document.addEventListener('shown.bs.modal', (event) => {
            const modal = event.target;
            this.initializeEditors(modal);
        });

        // Handle modal hide - ensure data is saved
        document.addEventListener('hide.bs.modal', (event) => {
            const modal = event.target;
            this.saveAllEditorsInContainer(modal);
        });
    }

    setupFormHandlers() {
        // Handle form submissions
        document.addEventListener('submit', (event) => {
            const form = event.target;

            // Skip if already handled or not containing Quill editors
            if (this.initializedForms.has(form) || !form.querySelector('.quill-rich-text')) {
                return;
            }

            // Prevent default to handle manually
            event.preventDefault();
            this.handleFormSubmit(form);
        });
    }

    saveAllEditorsInContainer(container) {
        const editors = container.querySelectorAll('.quill-rich-text');

        editors.forEach(editorEl => {
            const editorId = editorEl.id;
            const quillData = this.quillInstances.get(editorId);

            if (quillData) {
                const hiddenInput = document.getElementById(quillData.hiddenInputId);
                if (hiddenInput) {
                    hiddenInput.value = quillData.instance.root.innerHTML;
                }
            }
        });
    }

    handleFormSubmit(form) {
        // Mark as handled
        this.initializedForms.add(form);

        // Save all editors in this form
        this.saveAllEditorsInContainer(form);

        // Validate required fields
        const isValid = this.validateForm(form);

        if (!isValid) {
            this.initializedForms.delete(form);
            return;
        }

        // Submit form normally
        form.submit();
    }

    validateForm(form) {
        let isValid = true;

        // Check all Quill editors in form
        const editors = form.querySelectorAll('.quill-rich-text');

        editors.forEach(editorEl => {
            const editorId = editorEl.id;
            const quillData = this.quillInstances.get(editorId);

            if (quillData) {
                const hiddenInput = document.getElementById(quillData.hiddenInputId);

                if (hiddenInput && hiddenInput.hasAttribute('required')) {
                    const plainText = quillData.instance.getText().trim();

                    if (plainText === '') {
                        alert(`Please enter ${editorEl.dataset.label || 'description'}`);
                        editorEl.focus();
                        isValid = false;
                    }
                }
            }
        });

        return isValid;
    }

    // Public methods for manual control
    getQuillInstance(editorId) {
        const data = this.quillInstances.get(editorId);
        return data ? data.instance : null;
    }

    saveEditorContent(editorId) {
        const data = this.quillInstances.get(editorId);
        if (data) {
            const hiddenInput = document.getElementById(data.hiddenInputId);
            if (hiddenInput) {
                hiddenInput.value = data.instance.root.innerHTML;
                return hiddenInput.value;
            }
        }
        return null;
    }

    clearEditor(editorId) {
        const quill = this.getQuillInstance(editorId);
        if (quill) {
            quill.setContents([]);
            this.saveEditorContent(editorId);
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    window.quillManager = new QuillManager();
});
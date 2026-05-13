let easyMDE;

function toggleSearch() {
    const modal = document.getElementById('search-modal');
    modal.classList.toggle('hidden');
    if (!modal.classList.contains('hidden')) {
        document.getElementById('search-input').focus();
    }
}

function showModal() {
    document.getElementById('generic-modal').classList.remove('hidden');
}

function hideModal() {
    document.getElementById('generic-modal').classList.add('hidden');
}

// Close modals when clicking outside
document.addEventListener('mousedown', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        if (e.target.id === 'generic-modal') hideModal();
        if (e.target.id === 'search-modal') toggleSearch();
    }
});

// Sidebar toggle
document.getElementById('toggle-sidebar')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('collapsed');
});

// Keyboard Shortcuts
document.addEventListener('keydown', (e) => {
    // Ctrl+K or Cmd+K for Search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        toggleSearch();
    }

    // Ctrl+Y or Cmd+Y for New Note
    if ((e.ctrlKey || e.metaKey) && e.key === 'y') {
        e.preventDefault();
        const activeItem = document.querySelector('.tree-header.active');
        if (activeItem) {
            const id = activeItem.getAttribute('data-id');
            const type = activeItem.getAttribute('data-type');
            // Assuming we need the path for notes, or id for notebooks
            // But show_create_note expects 'path' parameter
            const path = activeItem.closest('.tree-item').getAttribute('data-path');
            htmx.ajax('GET', `api.php?action=show_create_note&path=${path}`, '#modal-body');
            showModal();
        } else {
            alert('Por favor, selecione um caderno ou nota primeiro.');
        }
    }

    // Escape key to close modals and search
    if (e.key === 'Escape') {
        hideModal();
        const searchModal = document.getElementById('search-modal');
        if (!searchModal.classList.contains('hidden')) {
            toggleSearch();
        }
    }
});

let isInitialLoad = true;

// Hash and Selection handling
function syncSelection() {
    const hash = decodeURIComponent(window.location.hash.substring(1));
    
    document.querySelectorAll('.tree-header').forEach(h => h.classList.remove('active'));
    
    if (!hash) return;
    
    const treeItem = document.querySelector(`.tree-item[data-path="${hash}"]`);
    if (treeItem) {
        const header = treeItem.querySelector('.tree-header');
        if (header) {
            header.classList.add('active');
            
            // Expand all parents
            let current = treeItem;
            while (current && current.classList.contains('tree-item')) {
                current.classList.remove('collapsed');
                const icon = current.querySelector(':scope > .tree-header > .toggle-icon');
                if (icon) {
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-chevron-down');
                }
                current = current.parentElement.closest('.tree-item');
            }
            
            header.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
}

// Listen for hash changes (manual or via code)
window.addEventListener('hashchange', syncSelection);

// Global listener for tree settle
document.addEventListener('htmx:afterSettle', (e) => {
    if (e.detail.target.id === 'notebooks-list') {
        if (isInitialLoad) {
            // On first load, collapse all that aren't part of the active path
            const hash = decodeURIComponent(window.location.hash.substring(1));
            document.querySelectorAll('#notebooks-list > .tree-item').forEach(nb => {
                const nbPath = nb.getAttribute('data-path');
                // If it's not the active notebook and not a parent of the active note
                if (nbPath !== hash && !hash.startsWith(nbPath + '/')) {
                    nb.classList.add('collapsed');
                    const icon = nb.querySelector('.toggle-icon');
                    if (icon) {
                        icon.classList.remove('fa-chevron-down');
                        icon.classList.add('fa-chevron-right');
                    }
                }
            });
            isInitialLoad = false;
        }
        syncSelection();
    }
});

window.addEventListener('load', () => {
    setTimeout(() => {
        const hash = window.location.hash.substring(1);
        if (hash && typeof htmx !== 'undefined') {
            if (hash.indexOf('/') === -1) {
                htmx.ajax('GET', `api.php?action=view_notebook&id=${hash}`, '#editor-content');
            } else {
                htmx.ajax('GET', `api.php?action=view_note&path=${hash}`, '#editor-content');
            }
        }
        syncSelection();
    }, 200);
});


// HTMX events
document.addEventListener('htmx:beforeSwap', (e) => {
    if (e.detail.target.id === 'editor-content') {
        if (easyMDE) {
            try {
                easyMDE.toTextArea();
            } catch (e) {}
            easyMDE = null;
        }
    }
});

document.addEventListener('htmx:afterSwap', (e) => {
    if (e.detail.target.id === 'editor-content') {
        const textarea = document.getElementById('markdown-editor');
        if (textarea) {
            // Check if already initialized (safety)
            if (textarea.nextSibling && textarea.nextSibling.classList && textarea.nextSibling.classList.contains('Editor-wrapper')) {
                return;
            }
            
            setTimeout(() => {
                initEditor('markdown-editor');
            }, 50);
        }
    }
});

function initEditor(elementId) {
    const el = document.getElementById(elementId);
    if (!el || el.EasyMDE) return;

    easyMDE = new EasyMDE({
        element: el,
        spellChecker: false,
        autosave: { enabled: false },
        status: ["lines", "words", "cursor"],
        minHeight: "400px",
        toolbar: ["bold", "italic", "heading", "|", "quote", "unordered-list", "ordered-list", "|", "link", "image", "table", "|", "guide"],
        previewRender: function(plainText) {
            // Ensure images have the correct base path if needed
            return this.parent.markdown(plainText);
        }
    });
    
    // Mark as initialized
    el.EasyMDE = easyMDE;
}

function toggleEditorView() {
    if (!easyMDE) return;
    const btn = document.getElementById('btn-toggle-view');
    const isPreview = easyMDE.isPreviewActive();
    
    if (isPreview) {
        easyMDE.togglePreview();
        btn.innerHTML = "<i class='fas fa-eye'></i> Visual";
        btn.style.borderColor = "var(--accent-purple)";
        btn.style.color = "var(--accent-purple)";
    } else {
        easyMDE.togglePreview();
        btn.innerHTML = "<i class='fas fa-code'></i> Editor";
        btn.style.borderColor = "var(--accent-pink)";
        btn.style.color = "var(--accent-pink)";
    }
}

function toggleNode(ev, icon) {
    ev.stopPropagation();
    const item = icon.closest('.tree-item');
    item.classList.toggle('collapsed');
    icon.classList.toggle('fa-chevron-down');
    icon.classList.toggle('fa-chevron-right');
}

// Drag and Drop Handlers
function drag(ev) {
    ev.stopPropagation();
    ev.dataTransfer.setData("text", ev.currentTarget.getAttribute('data-path'));
}

function allowDrop(ev) {
    ev.preventDefault();
    ev.currentTarget.classList.add('drag-over');
}

function drop(ev) {
    ev.preventDefault();
    ev.stopPropagation();
    ev.currentTarget.classList.remove('drag-over');
    const sourcePath = ev.dataTransfer.getData("text");
    const targetPath = ev.currentTarget.getAttribute('data-path');
    
    if (sourcePath && targetPath && sourcePath !== targetPath) {
        htmx.ajax('POST', 'api.php?action=move_item', {
            target: '#notebooks-list',
            values: { source_path: sourcePath, target_path: targetPath }
        });
    }
}

// Add event listeners for clearing drag-over class
document.addEventListener('dragleave', (e) => {
    if (e.target.classList.contains('tree-item')) {
        e.target.classList.remove('drag-over');
    }
});


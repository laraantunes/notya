let easyMDE;
let originalContent = "";

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

function createNewNote() {
    const activeItem = document.querySelector('.tree-header.active');
    if (activeItem) {
        const path = activeItem.closest('.tree-item').getAttribute('data-path');
        htmx.ajax('GET', `api.php?action=show_create_note&path=${path}`, '#modal-body');
        showModal();
    } else {
        alert('Por favor, selecione um caderno ou nota primeiro.');
    }
}

function createNewNotebook() {
    const action = () => {
        htmx.ajax('GET', 'api.php?action=show_create_notebook', '#modal-body');
        showModal();
    };

    if (isNoteDirty()) {
        customConfirm(
            "Alterações não salvas",
            "Você tem alterações não salvas nesta nota. Deseja sair sem salvar?",
            () => {
                originalContent = easyMDE.value(); // Reset state
                action();
            }
        );
    } else {
        action();
    }
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
        createNewNote();
    }

    // Ctrl+S or Cmd+S for Save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        const saveBtn = document.querySelector('button[hx-post*="action=save_note"]');
        if (saveBtn) htmx.trigger(saveBtn, 'click');
    }

    // Ctrl+O or Cmd+O for Toggle View
    if ((e.ctrlKey || e.metaKey) && e.key === 'o') {
        e.preventDefault();
        toggleEditorView();
    }

    // Ctrl+E or Cmd+E for Toggle Sidebar
    if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
        e.preventDefault();
        document.getElementById('sidebar')?.classList.toggle('collapsed');
    }

    // Ctrl+U or Cmd+U for New Notebook
    if ((e.ctrlKey || e.metaKey) && e.key === 'u') {
        e.preventDefault();
        createNewNotebook();
    }

    // Ctrl+D or Cmd+D for Media Modal
    if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
        e.preventDefault();
        const activeItem = document.querySelector('.tree-header.active');
        const path = activeItem?.closest('.tree-item')?.getAttribute('data-path');
        if (path) {
            htmx.ajax('GET', `api.php?action=show_media&path=${path}`, '#modal-body');
            showModal();
        }
    }

    // Ctrl+P or Cmd+P for Profile Settings
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        htmx.ajax('GET', 'api.php?action=show_user_edit', '#modal-body');
        showModal();
    }

    // Ctrl+J or Cmd+J for Help
    if ((e.ctrlKey || e.metaKey) && e.key === 'j') {
        e.preventDefault();
        htmx.ajax('GET', 'api.php?action=show_help', '#modal-body');
        showModal();
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
    if (window.innerWidth <= 768) {
        document.getElementById('sidebar')?.classList.add('collapsed');
    }
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
document.addEventListener('htmx:confirm', (e) => {
    // If navigating to another note or notebook in the editor
    if (e.detail.target.id === 'editor-content' && isNoteDirty()) {
        e.preventDefault();
        customConfirm(
            "Alterações não salvas",
            "Você tem alterações não salvas nesta nota. Deseja sair sem salvar?",
            () => {
                originalContent = easyMDE.value(); // Reset dirty state to allow navigation
                e.detail.issueRequest();
            }
        );
    }
});

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
            return this.parent.markdown(plainText);
        }
    });
    
    el.EasyMDE = easyMDE;
    originalContent = easyMDE.value();
}

function navigateTo(path, type) {
    const action = type === 'note' ? 'view_note' : 'view_notebook';
    const param = type === 'note' ? 'path' : 'id';
    const url = `api.php?action=${action}&${param}=${encodeURIComponent(path)}`;

    if (isNoteDirty()) {
        customConfirm(
            "Alterações não salvas",
            "Você tem alterações não salvas nesta nota. Deseja sair sem salvar?",
            () => {
                originalContent = easyMDE.value(); // Reset state
                window.location.hash = path;
                htmx.ajax('GET', url, '#editor-content');
            }
        );
    } else {
        window.location.hash = path;
        htmx.ajax('GET', url, '#editor-content');
    }
}

function isNoteDirty() {
    if (!easyMDE) return false;
    return easyMDE.value() !== originalContent;
}

function customConfirm(title, message, onYes, onNo) {
    const modal = document.getElementById('confirm-modal');
    if (!modal) return;
    document.getElementById('confirm-title').innerText = title;
    document.getElementById('confirm-message').innerText = message;
    modal.classList.remove('hidden');
    
    const yesBtn = document.getElementById('confirm-yes');
    const noBtn = document.getElementById('confirm-no');
    
    const cleanup = () => {
        modal.classList.add('hidden');
        const newYes = yesBtn.cloneNode(true);
        const newNo = noBtn.cloneNode(true);
        yesBtn.parentNode.replaceChild(newYes, yesBtn);
        noBtn.parentNode.replaceChild(newNo, noBtn);
    };
    
    document.getElementById('confirm-yes').onclick = () => { cleanup(); onYes(); };
    document.getElementById('confirm-no').onclick = () => { cleanup(); if(onNo) onNo(); };
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


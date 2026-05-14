<header id="global-header">
    <div class="header-left">
        <button id="toggle-sidebar" class="icon-btn" title="Alternar Menu (Ctrl+E)"><i class="fas fa-bars"></i></button>
        <h2 class="logo" style="color: var(--accent-pink); font-size: 1.5rem; cursor: pointer; margin-left: 10px; display: flex; align-items: center; gap: 10px;" 
            hx-get="api.php?action=show_home" hx-target="#editor-content" 
            onclick="window.location.hash = ''; document.querySelectorAll('.tree-header').forEach(h => h.classList.remove('active'));">
            <img src="img/favicon.png" alt="Notya Logo" style="height: 30px; border-radius: 4px;">
            Notya
        </h2>
    </div>
    <div class="header-center">
        <div id="current-path" style="color: var(--text-secondary); font-size: 0.9rem;">/ Início</div>
    </div>
    <div class="header-right">
        <button onclick="createNewNotebook()" class="icon-btn" title="Novo Caderno (Ctrl+U)"><i class="fas fa-plus"></i></button>
        <button onclick="createNewNote()" class="icon-btn" title="Nova Nota (Ctrl+Y)"><i class="fas fa-file"></i></button>
        <button onclick="toggleSearch()" class="icon-btn" title="Pesquisar (Ctrl+K)"><i class="fas fa-search"></i></button>
        <button hx-get="api.php?action=show_user_edit" hx-target="#modal-body" onclick="showModal()" class="icon-btn" title="Configurações (Ctrl+P)"><i class="fas fa-user-cog"></i></button>
        <button hx-get="api.php?action=show_help" hx-target="#modal-body" onclick="showModal()" class="icon-btn" title="Ajuda (Ctrl+J)"><i class="fas fa-question-circle"></i></button>
        <a href="index.php?page=logout" class="icon-btn" style="color: var(--text-secondary);" title="Sair"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</header>

<div id="app-body">
    <aside id="sidebar">
        <div class="sidebar-content" id="notebooks-list" hx-get="api.php?action=list_notebooks" hx-trigger="load">
            <!-- Notebooks tree will load here -->
        </div>
        <div class="sidebar-footer" style="padding: 10px; border-top: 1px solid var(--glass-border);">
            <button onclick="createNewNotebook()" style="width: 100%;" title="Novo Caderno (Ctrl+U)">
                <i class="fas fa-plus"></i> Novo Caderno
            </button>
        </div>
    </aside>

    <main id="content">
        <div id="editor-wrapper">
            <div id="editor-content" hx-get="api.php?action=show_home" hx-trigger="load">
                <!-- Content will be injected here -->
            </div>
        </div>
    </main>
</div>

<!-- Generic Modal -->
<div id="generic-modal" class="modal-overlay hidden">
    <div class="modal-content">
        <div id="modal-body">
            <!-- Dynamic content -->
        </div>
        <div style="margin-top: 20px; text-align: right;">
            <button onclick="hideModal()" style="background: var(--text-secondary);">Cancelar</button>
        </div>
    </div>
</div>

<div id="toast">URL copiada!</div>

<!-- Confirm Modal -->
<div id="confirm-modal" class="modal-overlay hidden">
    <div class="modal-content" style="max-width: 400px; text-align: center;">
        <h3 id="confirm-title" style="color: var(--accent-purple); border-bottom: 1px solid var(--glass-border); padding-bottom: 10px; margin-bottom: 20px;">Confirmação</h3>
        <p id="confirm-message" style="margin-bottom: 30px; color: var(--text-secondary); line-height: 1.5;"></p>
        <div style="display: flex; gap: 15px; justify-content: center;">
            <button id="confirm-yes" style="background: linear-gradient(45deg, var(--accent-purple), var(--accent-pink)); flex: 1;">Sim</button>
            <button id="confirm-no" style="background: var(--glass-bg); color: var(--text-secondary); border: 1px solid var(--glass-border); flex: 1;">Não</button>
        </div>
    </div>
</div>

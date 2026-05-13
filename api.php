<?php
require_once 'auth.php';

$action = $_GET['action'] ?? '';

define('NOTEBOOKS_DIR', __DIR__ . '/data/notebooks');

function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                    rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                else
                    unlink($dir . DIRECTORY_SEPARATOR . $object);
            }
        }
        rmdir($dir);
    }
}

function get_notebooks() {
    if (!is_dir(NOTEBOOKS_DIR)) mkdir(NOTEBOOKS_DIR, 0755, true);
    $notebooks = [];
    $dirs = scandir(NOTEBOOKS_DIR);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        $info_file = NOTEBOOKS_DIR . "/$dir/info.json";
        if (file_exists($info_file)) {
            $notebooks[] = json_decode(file_get_contents($info_file), true);
        }
    }
    return $notebooks;
}

function get_notes_in_path($path) {
    if (!is_dir($path)) return [];
    $notes = [];
    $files = scandir($path);
    foreach ($files as $file) {
        if (str_ends_with($file, '.md')) {
            $id = substr($file, 0, -3);
            $content = file_get_contents("$path/$file");
            $lines = explode("\n", $content);
            $title = ltrim($lines[0], '# ');
            if (empty($title)) $title = "Nota sem título";

            $child_dir = "$path/$id";
            $has_children = false;
            if (is_dir($child_dir)) {
                $child_files = scandir($child_dir);
                foreach ($child_files as $cf) {
                    if (str_ends_with($cf, '.md')) {
                        $has_children = true;
                        break;
                    }
                }
            }

            $notes[] = [
                'id' => $id,
                'title' => $title,
                'has_children' => $has_children,
                'path' => "$path/$file"
            ];
        }
    }
    return $notes;
}

function render_tree($current_path, $rel_path = "") {
    $notes = get_notes_in_path($current_path);
    $html = "";
    foreach ($notes as $note) {
        $full_rel_path = $rel_path ? "$rel_path/{$note['id']}" : $note['id'];
        $html .= "<div class='tree-item' draggable='true' ondragstart='drag(event)' ondragover='allowDrop(event)' ondrop='drop(event)' data-path='$full_rel_path' data-type='note'>";
        $html .= "<div class='tree-header' data-id='{$note['id']}' hx-get='api.php?action=view_note&path=$full_rel_path' hx-target='#editor-content' hx-on:click='window.location.hash = \"$full_rel_path\"'>";
        
        if ($note['has_children']) {
            $html .= "<i class='fas fa-chevron-down toggle-icon' onclick='toggleNode(event, this)'></i>";
        } else {
            $html .= "<i class='fas fa-file-alt' style='margin-right: 8px; opacity: 0.5;'></i>";
        }
        
        $html .= "<span>{$note['title']}</span>";
        $html .= "<div class='tree-actions'>";
        $html .= "<i class='fas fa-plus' hx-get='api.php?action=show_create_note&path=$full_rel_path' hx-target='#modal-body' onclick='showModal(); event.stopPropagation();'></i>";
        $html .= "<i class='fas fa-times' hx-delete='api.php?action=delete_note&path=$full_rel_path' hx-confirm='Tem certeza que deseja excluir esta nota?' hx-target='#notebooks-list' onclick='event.stopPropagation();'></i>";
        $html .= "</div>";
        $html .= "</div>";
        if ($note['has_children']) {
            $html .= "<div class='tree-children'>";
            $html .= render_tree("$current_path/{$note['id']}", $full_rel_path);
            $html .= "</div>";
        }
        $html .= "</div>";

    }
    return $html;
}

function find_note_by_rel_path($rel_path) {
    // rel_path format: notebook-id/note-id/note-id...
    $parts = explode('/', $rel_path);
    if (empty($parts)) return null;

    $current_fs_path = NOTEBOOKS_DIR;
    $note_file = "";
    
    // First part is notebook
    $current_fs_path .= "/" . $parts[0];
    
    if (count($parts) == 1) {
        // Just notebook requested? No, notes are children of notebooks.
        return ['type' => 'notebook', 'path' => $current_fs_path];
    }

    // Traverse notes
    for ($i = 1; $i < count($parts); $i++) {
        $note_file = $current_fs_path . "/" . $parts[$i] . ".md";
        if ($i < count($parts) - 1) {
            $current_fs_path .= "/" . $parts[$i];
        }
    }
    
    $title = end($parts); // Fallback
    if (is_file($note_file)) {
        $content = file_get_contents($note_file);
        $lines = explode("\n", $content);
        $title = ltrim($lines[0], '# ');
    }
    
    return [
        'type' => 'note',
        'file' => $note_file,
        'folder' => $current_fs_path . "/" . end($parts),
        'id' => end($parts),
        'title' => $title
    ];
}

function get_breadcrumb($rel_path, $is_notebook = false) {
    if (empty($rel_path)) return "/ Início";
    
    $parts = explode('/', $rel_path);
    $breadcrumb = ["<span style='cursor:pointer' hx-get='api.php?action=show_home' hx-target='#editor-content' onclick='window.location.hash=\"\"'>Início</span>"];
    $current_acc = "";
    
    foreach ($parts as $index => $id) {
        // Skip the last part if we want to hide the current item
        if ($index === count($parts) - 1 && !$is_notebook) {
            continue;
        }
        
        $current_acc = $current_acc ? "$current_acc/$id" : $id;
        if ($index === 0) {
            // It's the notebook
            $info_file = NOTEBOOKS_DIR . "/$id/info.json";
            $title = is_file($info_file) ? json_decode(file_get_contents($info_file), true)['title'] : $id;
            
            // If it's a notebook and it's the LAST part, and we are NOT viewing a note, we might hide it too?
            // User said "current note id". Let's hide the last part always if it's the current item.
            if ($is_notebook && $index === count($parts) - 1) continue;

            $breadcrumb[] = "<span style='cursor:pointer' hx-get='api.php?action=view_notebook&id=$id' hx-target='#editor-content' hx-on:click='window.location.hash=\"$id\"'>$title</span>";
        } else {
            // It's a note
            $note = find_note_by_rel_path($current_acc);
            $title = (isset($note['title'])) ? $note['title'] : $id;
            $breadcrumb[] = "<span style='cursor:pointer' hx-get='api.php?action=view_note&path=$current_acc' hx-target='#editor-content' hx-on:click='window.location.hash=\"$current_acc\"'>$title</span>";
        }
    }
    
    return implode(" <i class='fas fa-chevron-right' style='font-size: 0.7rem; margin: 0 5px; opacity: 0.5;'></i> ", $breadcrumb);
}

function render_shortcuts_box() {
    $html = "<div style='display: inline-block; text-align: left; background: var(--glass-bg); padding: 30px; border-radius: 15px; border: 1px solid var(--glass-border); margin-top: 20px;'>";
    $html .= "<h3 style='color: var(--accent-purple); margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;'>Atalhos de Teclado</h3>";
    $html .= "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";
    $html .= "<div><kbd style='background: var(--accent-pink); color: black; padding: 2px 6px; border-radius: 4px; font-weight: bold;'>Ctrl + K</kbd></div><div style='color: white;'>Pesquisar Notas</div>";
    $html .= "<div><kbd style='background: var(--accent-pink); color: black; padding: 2px 6px; border-radius: 4px; font-weight: bold;'>Ctrl + Y</kbd></div><div style='color: white;'>Nova Nota (no item ativo)</div>";
    $html .= "<div><kbd style='background: var(--accent-pink); color: black; padding: 2px 6px; border-radius: 4px; font-weight: bold;'>Esc</kbd></div><div style='color: white;'>Fechar Janelas</div>";
    $html .= "</div>";
    $html .= "</div>";
    return $html;
}

function render_notebook_list() {
    $notebooks = get_notebooks();
    $html = "";
    foreach ($notebooks as $nb) {
        $html .= "<div class='tree-item' ondragover='allowDrop(event)' ondrop='drop(event)' data-path='{$nb['id']}' data-type='notebook'>";
        $html .= "<div class='tree-header' hx-get='api.php?action=view_notebook&id={$nb['id']}' hx-target='#editor-content' hx-on:click='window.location.hash = \"{$nb['id']}\"'>";
        $html .= "<i class='fas fa-chevron-down toggle-icon' onclick='toggleNode(event, this)'></i>";
        $html .= "<i class='fas fa-book' style='color: var(--accent-purple); margin-right: 8px;'></i>";
        $html .= "<span>{$nb['title']}</span>";
        $html .= "<div class='tree-actions'>";
        $html .= "<i class='fas fa-plus' hx-get='api.php?action=show_create_note&path={$nb['id']}' hx-target='#modal-body' onclick='showModal(); event.stopPropagation();'></i>";
        $html .= "<i class='fas fa-times' hx-delete='api.php?action=delete_notebook&id={$nb['id']}' hx-confirm='Excluir caderno e todas as notas? Isto não pode ser desfeito.' hx-target='#notebooks-list' onclick='event.stopPropagation();'></i>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "<div class='tree-children'>";
        $html .= render_tree(NOTEBOOKS_DIR . "/" . $nb['id'], $nb['id']);
        $html .= "</div>";
        $html .= "</div>";
    }
    return $html;
}

switch ($action) {
    case 'setup':
        if (hasUsers()) exit("Setup already done.");
        if (registerUser($_POST['username'], $_POST['password'])) {
            echo "<script>window.location.href='index.php?page=login';</script>";
        }
        break;

    case 'login':
        if (login($_POST['username'], $_POST['password'])) {
            echo "<script>window.location.href='index.php';</script>";
        } else {
            echo "Login failed.";
        }
        break;

    case 'list_notebooks':
        requireAuth();
        echo render_notebook_list();
        break;


    case 'view_notebook':
        requireAuth();
        $id = $_GET['id'] ?? '';
        $info_file = NOTEBOOKS_DIR . "/$id/info.json";
        if (is_file($info_file)) {
            $info = json_decode(file_get_contents($info_file), true);
            echo "<div id='current-path' hx-swap-oob='true'>" . get_breadcrumb($id, true) . "</div>";
            echo "<div style='text-align: center; padding: 60px 20px;'>";
            echo "<h1 style='color: var(--accent-purple); font-size: 3rem;'>{$info['title']}</h1>";
            echo "<p style='color: var(--text-secondary);'>Selecione ou crie uma nota neste caderno para começar a escrever.</p>";
            echo render_shortcuts_box();
            echo "</div>";
        }
        break;


    case 'show_create_notebook':
        requireAuth();
        echo "<h3>Criar Novo Caderno</h3>";
        echo "<form hx-post='api.php?action=create_notebook' hx-target='#notebooks-list' hx-on::after-request='hideModal()'>";
        echo "<div class='form-group'><label>Título do Caderno</label><input type='text' name='title' required autofocus></div>";
        echo "<button type='submit'>Criar</button>";
        echo "</form>";
        break;


    case 'create_notebook':
        requireAuth();
        $id = generate_uuid();
        $path = NOTEBOOKS_DIR . "/$id";
        mkdir($path, 0755, true);
        file_put_contents("$path/info.json", json_encode(['id' => $id, 'title' => $_POST['title']]));
        echo render_notebook_list();
        break;


    case 'show_create_note':
        requireAuth();
        $path = $_GET['path'] ?? '';
        echo "<h3>Nova Nota</h3>";
        echo "<form hx-post='api.php?action=create_note' hx-target='#notebooks-list' hx-on::after-request='hideModal()'>";
        echo "<input type='hidden' name='parent_path' value='$path'>";
        echo "<div class='form-group'><label>Título</label><input type='text' name='title' required autofocus></div>";
        echo "<button type='submit'>Criar</button>";
        echo "</form>";
        break;



    case 'create_note':
        requireAuth();
        $parent_path = $_POST['parent_path'] ?? '';
        $title = $_POST['title'] ?? 'Untitled';
        $id = generate_uuid();
        
        $fs_path = str_replace('\\', '/', NOTEBOOKS_DIR . "/" . $parent_path);
        if (!is_dir($fs_path)) mkdir($fs_path, 0755, true);
        
        file_put_contents("$fs_path/$id.md", "# $title\n\n");
        echo render_notebook_list();
        break;




    case 'view_note':
        requireAuth();
        $rel_path = $_GET['path'] ?? '';
        $note = find_note_by_rel_path($rel_path);
        if ($note && $note['type'] === 'note') {
            $content = file_get_contents($note['file']);
            $lines = explode("\n", $content);
            $title = ltrim(array_shift($lines), '# ');
            $remaining_content = implode("\n", $lines);
            
            echo "<div id='current-path' hx-swap-oob='true'>" . get_breadcrumb($rel_path) . "</div>";
            echo "<div class='editor-header' style='margin-bottom: 20px;'>";
            echo "<div style='display: flex; justify-content: space-between; align-items: center; gap: 20px;'>";
            echo "<input type='text' id='note-title' value=\"$title\" style='font-size: 1.5rem; font-weight: bold; flex: 1; background: transparent; border: none; border-bottom: 1px solid var(--glass-border); padding: 5px; color: var(--accent-pink);' placeholder='Título da Nota'>";
            echo "<div style='display: flex; gap: 10px;'>";
            echo "<button onclick='showModal()' hx-get='api.php?action=show_media&path=$rel_path' hx-target='#modal-body' style='background: var(--glass-bg); color: var(--accent-pink);'><i class='fas fa-paperclip'></i> Mídia</button>";
            echo "<button hx-post='api.php?action=save_note&path=$rel_path' hx-vals='js:{content: easyMDE.value(), title: document.getElementById(\"note-title\").value}' hx-target='#notebooks-list'>Salvar Nota</button>";
            echo "</div>";
            echo "</div>";

            echo "</div>";
            echo "<textarea id='markdown-editor'>$remaining_content</textarea>";
        }
        break;


    case 'show_media':
        requireAuth();
        $rel_path = $_GET['path'] ?? '';
        $note = find_note_by_rel_path($rel_path);
        echo "<h3>Mídia desta nota</h3>";
        echo "<div style='margin-bottom: 20px;'>";
        echo "<form hx-post='api.php?action=upload_media&path=$rel_path' hx-encoding='multipart/form-data' hx-target='#media-list'>";
        echo "<input type='file' name='media_file' required>";
        echo "<button type='submit'>Enviar</button>";
        echo "</form>";
        echo "</div>";
        echo "<div id='media-list' hx-get='api.php?action=list_media&path=$rel_path' hx-trigger='load'>";
        echo "</div>";
        break;


    case 'list_media':
        requireAuth();
        $rel_path = $_GET['path'] ?? '';
        $note = find_note_by_rel_path($rel_path);
        if ($note && is_dir($note['folder'])) {
            $files = scandir($note['folder']);
            echo "<ul style='list-style: none; padding: 0;'>";
            foreach ($files as $file) {
                if ($file == "." || $file == ".." || str_ends_with($file, '.md')) continue;
                echo "<li style='display: flex; justify-content: space-between; padding: 5px; border-bottom: 1px solid var(--glass-border);'>";
                echo "<span>$file</span>";
                echo "<i class='fas fa-trash' hx-delete='api.php?action=delete_media&path=$rel_path&file=$file' hx-confirm='Excluir arquivo?' hx-target='#media-list' style='cursor: pointer; color: var(--accent-pink);'></i>";
                echo "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>Nenhum arquivo de mídia ainda.</p>";
        }
        break;


    case 'upload_media':
        requireAuth();
        $rel_path = $_GET['path'] ?? '';
        $note = find_note_by_rel_path($rel_path);
        if ($note && isset($_FILES['media_file'])) {
            if (!is_dir($note['folder'])) mkdir($note['folder'], 0755, true);
            $target = $note['folder'] . "/" . basename($_FILES['media_file']['name']);
            move_uploaded_file($_FILES['media_file']['tmp_name'], $target);
        }
        echo "<script>htmx.trigger('#media-list', 'load');</script>";
        break;

    case 'delete_media':
        requireAuth();
        $rel_path = $_GET['path'] ?? '';
        $file = $_GET['file'] ?? '';
        $note = find_note_by_rel_path($rel_path);
        if ($note && $file) {
            $target = $note['folder'] . "/" . $file;
            if (file_exists($target)) unlink($target);
        }
        echo "<script>htmx.trigger('#media-list', 'load');</script>";
        break;


    case 'save_note':
        requireAuth();
        $rel_path = $_GET['path'] ?? '';
        $content = $_POST['content'] ?? '';
        $title = $_POST['title'] ?? 'Untitled Note';
        $note = find_note_by_rel_path($rel_path);
        if ($note && $note['type'] === 'note') {
            $full_content = "# " . $title . "\n" . $content;
            file_put_contents($note['file'], $full_content);
            // Return updated list to reflect title change in sidebar
            echo render_notebook_list();
        }
        break;


    case 'delete_note':
        requireAuth();
        $rel_path = $_GET['path'] ?? '';
        $note = find_note_by_rel_path($rel_path);
        if ($note && $note['type'] === 'note') {
            // Transfer children to parent logic
            $note_folder = $note['folder'];
            $parent_folder = dirname($note['file']);
            
            if (is_dir($note_folder)) {
                $files = scandir($note_folder);
                foreach ($files as $file) {
                    if ($file != "." && $file != "..") {
                        rename("$note_folder/$file", "$parent_folder/$file");
                    }
                }
                rmdir($note_folder);
            }
            unlink($note['file']);
        }
        echo render_notebook_list();
        break;

    case 'delete_notebook':
        requireAuth();
        $id = $_GET['id'] ?? '';
        rrmdir(NOTEBOOKS_DIR . "/$id");
        echo render_notebook_list();
        break;


    case 'show_home':
        echo "<div id='current-path' hx-swap-oob='true'>/ Início</div>";
        echo "<div style='text-align: center; padding: 60px 20px;'>";
        echo "<img src='img/favicon.png' alt='Notya Logo' style='height: 120px; border-radius: 20px; margin-bottom: 20px; box-shadow: var(--neon-shadow);'>";
        echo "<h1 style='color: var(--accent-pink); font-size: 5rem; text-shadow: var(--neon-shadow); margin-bottom: 10px;'>Notya</h1>";
        echo "<p style='color: var(--text-secondary); font-size: 1.2rem; margin-bottom: 20px;'>Organize seus pensamentos com estilo.</p>";
        
        echo render_shortcuts_box();
        
        echo "<p style='margin-top: 40px; color: var(--text-secondary); font-size: 0.9rem;'>Selecione uma nota na barra lateral para começar.</p>";
        echo "</div>";
        break;


    case 'search':
        requireAuth();
        $query = strtolower($_GET['search'] ?? '');
        if (empty($query)) exit;
        
        // Recursive search in all notebooks
        $results = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(NOTEBOOKS_DIR));
        foreach ($it as $file) {
            if ($file->isDir()) continue;
            $filename = $file->getFilename();
            if (str_ends_with($filename, '.md')) {
                $content = file_get_contents($file->getPathname());
                $title = ltrim(explode("\n", $content)[0], '# ');
                if (strpos(strtolower($title), $query) !== false || strpos(strtolower($content), $query) !== false) {
                    $abs_path = str_replace('\\', '/', $file->getPathname());
                    $base_dir = str_replace('\\', '/', NOTEBOOKS_DIR . '/');
                    $rel_fs_path = str_replace($base_dir, '', $abs_path);
                    $rel_path = str_replace('.md', '', $rel_fs_path);
                    
                    $results[] = ['title' => $title, 'path' => $rel_path, 'type' => 'note'];
                }
            } elseif ($filename === 'info.json') {
                $info = json_decode(file_get_contents($file->getPathname()), true);
                if (strpos(strtolower($info['title']), $query) !== false) {
                    $results[] = ['title' => $info['title'], 'path' => $info['id'], 'type' => 'notebook'];
                }
            }
        }
        
        foreach ($results as $res) {
            $icon = ($res['type'] === 'notebook') ? 'fas fa-book' : 'fas fa-file-alt';
            $param = ($res['type'] === 'note') ? "path" : "id";
            echo "<div class='search-item' onclick='toggleSearch(); window.location.hash = \"{$res['path']}\"' hx-get='api.php?action=view_{$res['type']}&$param={$res['path']}' hx-target='#editor-content' style='padding: 10px; cursor: pointer; border-bottom: 1px solid var(--glass-border);'>";
            echo "<i class='$icon' style='margin-right: 10px; color: var(--accent-purple);'></i>";
            echo "<span>{$res['title']}</span>";
            echo "</div>";
        }
        break;

    case 'show_user_edit':
        requireAuth();
        echo "<h3>Configurações de Usuário</h3>";
        echo "<form hx-post='api.php?action=change_password' hx-target='#password-msg'>";
        echo "<div class='form-group'><label>Nova Senha</label><input type='password' name='new_password' required></div>";
        echo "<button type='submit'>Atualizar Senha</button>";
        echo "</form>";
        echo "<div id='password-msg' style='margin-top: 10px;'></div>";
        break;


    case 'move_item':
        requireAuth();
        $source_path = $_POST['source_path'] ?? '';
        $target_path = $_POST['target_path'] ?? '';
        
        if ($source_path === $target_path) exit;
        
        $source = find_note_by_rel_path($source_path);
        $target = find_note_by_rel_path($target_path);
        
        if ($source && $source['type'] === 'note' && $target) {
            $source_file = $source['file'];
            $source_folder = $source['folder'];
            
            $target_dir = ($target['type'] === 'notebook') ? $target['path'] : $target['folder'];
            if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
            
            $new_file = $target_dir . "/" . $source['id'] . ".md";
            $new_folder = $target_dir . "/" . $source['id'];
            
            rename($source_file, $new_file);
            if (is_dir($source_folder)) {
                rename($source_folder, $new_folder);
            }
        }
        echo render_notebook_list();
        break;

}


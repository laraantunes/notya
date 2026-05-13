<div class="auth-container">
    <div class="auth-card">
        <h2>Bem-vindo ao Notya</h2>
        <p>Crie sua conta de administrador para começar.</p>
        <form hx-post="api.php?action=setup" hx-target="#setup-message">
            <div class="form-group">
                <label>Usuário</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirmar Senha</label>
                <input type="password" name="password_confirm" required>
            </div>
            <button type="submit">Inicializar Sistema</button>
        </form>
        <div id="setup-message"></div>
    </div>
</div>

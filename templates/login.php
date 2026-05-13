<div class="auth-container">
    <div class="auth-card">
        <h1 class="logo" style="margin-bottom: 20px; font-size: 3rem; color: var(--accent-purple); text-shadow: var(--neon-shadow);">Notya</h1>
        <h2>Entrar</h2>
        <form hx-post="api.php?action=login" hx-target="#login-message">
            <div class="form-group">
                <label>Usuário</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Desbloquear Notas</button>
        </form>
        <div id="login-message"></div>
    </div>
</div>

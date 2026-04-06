<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page expirée - ProxiPro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .error-container { text-align: center; max-width: 500px; }
        .error-code { font-size: 6rem; font-weight: 800; color: #e2e8f0; line-height: 1; }
        .error-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 16px 0 8px; }
        .error-message { color: #64748b; margin-bottom: 24px; line-height: 1.6; }
        .error-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #4f46e5; }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover { background: #cbd5e1; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">419</div>
        <h1 class="error-title">Page expirée</h1>
        <p class="error-message">Votre session a expiré. Veuillez rafraîchir la page et réessayer.</p>
        <div class="error-actions">
            <a href="javascript:location.reload()" class="btn btn-primary">Rafraîchir la page</a>
            <a href="/" class="btn btn-secondary">← Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>

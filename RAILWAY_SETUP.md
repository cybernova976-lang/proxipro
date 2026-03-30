# Configuration Railway - ProxiPro

## Variables d'environnement à configurer sur Railway

Après avoir ajouté un service PostgreSQL sur Railway, configurez ces variables dans votre service web :

### Base de données (fournies automatiquement par Railway PostgreSQL)
- `DATABASE_URL` — fournie automatiquement quand vous liez le service PostgreSQL
- `DB_CONNECTION=pgsql`
- `DB_HOST` — depuis la variable Railway `PGHOST`
- `DB_PORT` — depuis la variable Railway `PGPORT`
- `DB_DATABASE` — depuis la variable Railway `PGDATABASE`
- `DB_USERNAME` — depuis la variable Railway `PGUSER`
- `DB_PASSWORD` — depuis la variable Railway `PGPASSWORD`

Ou utilisez la référence Railway :
```
DB_CONNECTION=pgsql
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_DATABASE=${{Postgres.PGDATABASE}}
DB_USERNAME=${{Postgres.PGUSER}}
DB_PASSWORD=${{Postgres.PGPASSWORD}}
```

### Application
- `APP_NAME=ProxiPro`
- `APP_ENV=production`
- `APP_KEY` — générer avec `php artisan key:generate --show`
- `APP_DEBUG=false`
- `APP_URL=https://proxipro-production.up.railway.app`

### Sessions et Cache
- `SESSION_DRIVER=cookie`
- `CACHE_STORE=file`
- `QUEUE_CONNECTION=sync`

## Étapes de déploiement

1. Créer un service PostgreSQL dans votre projet Railway
2. Lier le service PostgreSQL à votre service web
3. Ajouter toutes les variables d'environnement ci-dessus
4. Redéployer — les migrations s'exécuteront automatiquement au démarrage

# Tarmac API + espace client

Laravel 13 (Inertia + Vue 3) backend for the Tarmac mobile app: Sanctum API under `/api/v1` and the web client space.

## Database: PostgreSQL on Neon

1. Create a Neon project, then copy **two** connection strings from the Neon console:
    - the **pooled** one (host contains `-pooler`), used by the app at runtime;
    - the **direct** one (same host without `-pooler`), used for migrations.
2. Fill these keys in `.env`:

    ```dotenv
    DB_CONNECTION=pgsql
    DB_URL=postgresql://USER:PASSWORD@ep-xxx-pooler.REGION.aws.neon.tech/neondb?sslmode=require&channel_binding=require
    DB_DIRECT_URL=postgresql://USER:PASSWORD@ep-xxx.REGION.aws.neon.tech/neondb?sslmode=require&channel_binding=require
    DB_SSLMODE=require
    ```

3. Run migrations through the direct endpoint (PgBouncer pooling does not play well with DDL):

    ```bash
    php artisan migrate --database=pgsql_direct
    ```

    This also seeds the DTC knowledge base (idempotent seed migration). `php artisan db:seed` adds a demo user (`test@example.com` / `password`) with sample data.

Tests run on in-memory SQLite (`phpunit.xml`) and never touch Neon: `php artisan test --compact`.

## Other keys

| Key                                    | Purpose                                                                                                       |
| -------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| `REVENUECAT_WEBHOOK_SECRET`            | Bearer secret expected on `POST /api/v1/webhooks/revenuecat` (product ids are mapped in `config/tarmac.php`). |
| `ANTHROPIC_API_KEY`, `ANTHROPIC_MODEL` | Optional: Claude explains trouble codes missing from the knowledge base (answers are cached).                 |
| `STRIPE_KEY`, `STRIPE_SECRET`          | Optional: web checkout stays disabled while empty.                                                            |

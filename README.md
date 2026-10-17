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
| `OTA_PROJECT`, `OTA_DISK`              | OTA project id (default `tarmac`) and the filesystem disk holding release files (default `local`).            |
| `OTA_PUBLISH_TOKEN`                    | Bearer token of the publishing API `/api/v1/admin/releases` (publishing is disabled while empty).             |

## Over-the-air releases

The app's `nativephp/mobile-ota` plugin points at this API instead of Bifrost:

- `GET /api/v1/apps/{project}/{arc}/{fingerprint}[/{heldReleaseUuid}]?algorithm=1[&shell_built_at=ISO]` offers the latest active OTA bundle built for that native shell (`arc` = `production` or `staging`, anything else means `production`).
- `GET /api/v1/app-builds/{project}/android/latest?version_code=N[&channel=staging]` offers the latest active APK above `N`.
- Download links are signed and expire after 30 minutes; object storage disks redirect to a pre-signed bucket URL, local disks stream the file.

Publish with `POST /api/v1/admin/releases` (multipart `file`, `kind` = `ota|apk`, `fingerprint`, `version`, `version_code` for APKs, optional `channel`, `notes`, `mandatory`, `native_signature`). The server computes the SHA-256 and size itself. An OTA bundle is refused when an active APK with the same fingerprint declares a different `native_signature`: native changes need a new APK.

Rollback: `POST /api/v1/admin/releases/{uuid}/deactivate` stops offering a release, so devices that have not installed it get the previous active one. Devices that already installed it stay on it (a held release newer than the latest active one counts as up to date); publish a fixed release to move them forward. `.../activate` offers it again.

# TaskForge API

Production-like REST API backend for task and project management (simplified Jira / Linear / Trello).

## Status

- [x] Laravel 11 project initialized
- [x] Docker development stack (nginx + php-fpm + postgres + redis)
- [x] Auth via Laravel Sanctum (token-based)
- [x] API versioning: `/api/v1/...`

## Tech Stack

- PHP 8.3
- Laravel 11
- PostgreSQL
- Redis
- Docker (nginx + php-fpm)
- Laravel Sanctum
- PHPUnit
- OpenAPI / Swagger
- GitHub Actions

## Architecture

High-level layering:

- **HTTP layer**: Controllers + FormRequests + API Resources
- **Application layer**: Actions + DTOs (use-cases)
- **Domain layer**: Eloquent Models + Enums + Policies (RBAC)
- **Infrastructure**: PostgreSQL/Redis, Events/Listeners/Jobs, queues

Key decisions:

- Workspace-scoped routes: `/api/v1/workspaces/{workspace}/...`
- Policy-first authorization for RBAC (`owner/admin/member/viewer`)
- Activity Log as first-class feature (domain events -> activity records)

## API

Current endpoints:

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `GET  /api/v1/auth/me` (requires `auth:sanctum`)
- `POST /api/v1/auth/logout` (requires `auth:sanctum`)

Swagger UI:

- `GET /api/docs`

## Getting Started

### Requirements

- Docker Engine + Docker Compose

### Setup

1. Create `.env` from `.env.example`.
2. Build and start containers:

   ```bash
   sudo docker compose up -d --build
   ```

3. Run migrations:

   ```bash
   sudo docker compose exec -T app php artisan migrate --force
   ```

4. Open:

- API base URL: `http://localhost:8080/api/v1`

## Docker Setup

Services:

- `nginx` (port `8080`)
- `app` (PHP-FPM 8.3)
- `postgres` (port `5432`)
- `redis` (port `6379`)

## Tests

Run tests:

```bash
sudo docker compose exec -T app php artisan test
```

## CI (GitHub Actions)

Pipeline:

- Install dependencies (composer)
- Run migrations
- Run tests (PHPUnit)

## Screenshots

Placeholders (will be added):

- Swagger UI
- Example API responses

### Swagger UI

![Swagger UI](docs/screenshots/swagger-ui.png)

## Roadmap

- [x] Project bootstrap (Laravel 11)
- [x] Docker environment (nginx + php-fpm + postgres + redis)
- [x] Sanctum auth (register/login/me/logout)
- [x] Domain model + migrations: Workspace, Project, Task, Comment, Label, Invitation, ActivityLog, WorkspaceMember
- [x] RBAC (Policies): `owner/admin/member/viewer`
- [x] REST endpoints: Workspaces, Projects, Tasks, Comments, Labels, Invitations, Activity
- [x] Activity Log system (events -> activity)
- [x] OpenAPI/Swagger
- [x] GitHub Actions CI
- [ ] README screenshots

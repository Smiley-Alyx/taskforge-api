# TaskForge API

Production-like REST API backend for multi-workspace task management (a simplified Jira/Linear-style backend). Built as a portfolio project with clean Laravel conventions, RBAC, audit trail, test coverage, Docker-first setup and OpenAPI.

## Features

- Multi-workspace model (Workspace → Projects → Tasks)
- Tasks with filtering, sorting, pagination and bulk update
- Labels (CRUD) + many-to-many labels on tasks (attach/detach)
- Comments (CRUD) with author/admin rules
- Invitations (create, accept/decline by token) with `declined_at`
- Role-based access control: `owner / admin / member / viewer`
- Activity log (domain events → persisted audit log)
- OpenAPI/Swagger docs

## Tech Stack

- PHP 8.3
- Laravel 11
- PostgreSQL 16
- Redis 7
- Docker (nginx + php-fpm)
- Laravel Sanctum
- PHPUnit Feature Tests
- L5 Swagger (swagger-php attributes)
- GitHub Actions CI

## Quickstart (Docker)

```bash
sudo docker compose up -d --build
sudo docker compose exec -T app php artisan migrate --force
sudo docker compose exec -T app php artisan l5-swagger:generate
```

Open:

- API base URL: `http://localhost:8080/api/v1`
- Swagger UI: `http://localhost:8080/api/docs`
- OpenAPI JSON: `http://localhost:8080/docs?api-docs.json`

## Architecture (high level)

- **Controllers**: orchestration (authz, request → service → resource)
- **Form Requests**: validation + auth where appropriate
- **Policies + WorkspaceRoleResolver**: RBAC and isolation by workspace
- **API Resources**: consistent JSON shape
- **Services**:
  - `WorkspaceRoleResolver` — resolves role in a workspace
  - `TaskIndexQuery` — query builder for tasks index filters/sort/pagination
- **Activity Log pipeline**: `ActivityOccurred` → listener/job → `activity_logs`

## API highlights

Auth:

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `GET  /api/v1/auth/me`
- `POST /api/v1/auth/logout`

Workspaces:

- `GET    /api/v1/workspaces`
- `POST   /api/v1/workspaces`
- `GET    /api/v1/workspaces/{workspace}`
- `PATCH  /api/v1/workspaces/{workspace}`
- `DELETE /api/v1/workspaces/{workspace}`

Projects:

- `GET    /api/v1/workspaces/{workspace}/projects`
- `POST   /api/v1/workspaces/{workspace}/projects`
- `GET    /api/v1/workspaces/{workspace}/projects/{project}`
- `PATCH  /api/v1/workspaces/{workspace}/projects/{project}`
- `DELETE /api/v1/workspaces/{workspace}/projects/{project}`

Tasks:

- `GET    /api/v1/workspaces/{workspace}/projects/{project}/tasks`
  - filters: `status`, `priority`, `assignee_id`, `due_date` (alias), `due_from`, `due_to`
  - sorting: `sort=id,-id,priority,...`
  - pagination: `per_page`
- `POST   /api/v1/workspaces/{workspace}/projects/{project}/tasks`
- `PATCH  /api/v1/workspaces/{workspace}/tasks/{task}`
- `DELETE /api/v1/workspaces/{workspace}/tasks/{task}`
- `PATCH  /api/v1/workspaces/{workspace}/tasks/bulk`

Comments:

- `GET    /api/v1/workspaces/{workspace}/tasks/{task}/comments`
- `POST   /api/v1/workspaces/{workspace}/tasks/{task}/comments`
- `PATCH  /api/v1/workspaces/{workspace}/comments/{comment}`
- `DELETE /api/v1/workspaces/{workspace}/comments/{comment}`

Labels:

- `GET    /api/v1/workspaces/{workspace}/labels`
- `POST   /api/v1/workspaces/{workspace}/labels`
- `PATCH  /api/v1/workspaces/{workspace}/labels/{label}`
- `DELETE /api/v1/workspaces/{workspace}/labels/{label}`
- `POST   /api/v1/workspaces/{workspace}/tasks/{task}/labels` (attach)
- `DELETE /api/v1/workspaces/{workspace}/tasks/{task}/labels/{label}` (detach)

Invitations:

- `GET    /api/v1/workspaces/{workspace}/invitations`
- `POST   /api/v1/workspaces/{workspace}/invitations`
- `DELETE /api/v1/workspaces/{workspace}/invitations/{invitation}`
- `POST   /api/v1/invitations/{token}/accept`
- `POST   /api/v1/invitations/{token}/decline`

Activity Log:

- `GET /api/v1/workspaces/{workspace}/activity`
- `GET /api/v1/activity?workspace_id=...`

## RBAC

- **Owner**: full control (including workspace delete)
- **Admin**: manage workspace settings, projects, members, invitations, bulk actions
- **Member**: work on tasks (create/update/delete), comments
- **Viewer**: read-only access

## ERD (text)

- `workspaces` 1—N `projects`
- `projects` 1—N `tasks`
- `tasks` 1—N `comments`
- `workspaces` 1—N `labels`
- `tasks` N—M `labels` via `label_task`
- `workspaces` 1—N `workspace_members`
- `workspaces` 1—N `invitations`
- `workspaces` 1—N `activity_logs`

## Tests

```bash
sudo docker compose exec -T app php artisan test
```

## Code style

```bash
./vendor/bin/pint --test
```

## CI

GitHub Actions pipeline runs:

- Pint (`pint --test`)
- DB migrations
- Feature tests (`php artisan test`)

## Screenshots

### Swagger UI

![Swagger UI](docs/screenshots/swagger-ui.jpeg)

## Future improvements

- Export OpenAPI schemas for response bodies (full DTO-level schemas)
- Add seeders for demo workspace/project/tasks
- Add additional task filters (e.g. full-text search)
- Add rate limiting and request IDs for tracing

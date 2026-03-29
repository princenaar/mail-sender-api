# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# First-time setup
composer setup

# Run dev server (Laravel + queue worker + logs + Vite in parallel)
composer dev

# Run all tests
composer test

# Run a single test file
php artisan test tests/Feature/MailControllerTest.php

# Run a specific test method
php artisan test --filter test_send_email_successfully

# Code style (Laravel Pint)
vendor/bin/pint

# Tail logs
php artisan pail
```

## Architecture

This is a **Laravel 13.2 / PHP 8.4** REST API whose sole purpose is to accept email-send requests and relay them via SMTP. It acts as a bridge between a Spring Boot application and an LWS SMTP server.

```
Spring Boot → POST /api/v1/mails/send → MailController
                                               ↓ dispatches
                                         SendMailJob (queued)
                                               ↓
                                         MailService → LWS SMTP → Recipient
                                               ↓
                                         MailLog (mail_logs table)

Browser → GET /logs → MailLogController → mail_logs → Blade view
```

### Request flow

**API path (async)**

1. `ApiTokenMiddleware` — validates `Authorization: Bearer <MAIL_API_TOKEN>` header; returns 401 on mismatch.
2. `SendMailRequest` — validates the JSON body (rules below).
3. `MailController` — creates a `MailLog` record with `status=pending`, dispatches `SendMailJob` to the queue, returns **202 Accepted** immediately.
4. `SendMailJob` (background) — calls `MailService`, updates `MailLog` to `sent` or `failed`.
5. `MailService` — builds the Mailable, renders Blade template if `html` provided, decodes Base64 attachments in-memory (no disk storage), sends via `Mail::send()`.

**Web path**

1. `GET /logs` → `MailLogController@index` — no auth (internal tool).
2. Paginates `MailLog::latest()->paginate(25)` and renders `resources/views/logs/index.blade.php`.

### Key files to implement

| File | Role |
|------|------|
| `app/Http/Middleware/ApiTokenMiddleware.php` | Bearer token auth |
| `app/Http/Requests/SendMailRequest.php` | Input validation |
| `app/Http/Controllers/Api/MailController.php` | Dispatches `SendMailJob`, creates pending `MailLog` |
| `app/Services/MailService.php` | Email building, Blade rendering & sending |
| `routes/api.php` | `POST /api/v1/mails/send` |
| `app/Jobs/SendMailJob.php` | Queued job; calls MailService, updates MailLog status |
| `app/Enums/MailStatus.php` | Enum: `Pending`, `Sent`, `Failed` |
| `app/Models/MailLog.php` | Eloquent model for `mail_logs` table |
| `database/migrations/xxxx_create_mail_logs_table.php` | Mail history schema |
| `app/Http/Controllers/MailLogController.php` | Web controller; paginates MailLog for view |
| `routes/web.php` | `GET /logs` |
| `resources/views/emails/generic.blade.php` | HTML email wrapper template |
| `resources/views/logs/index.blade.php` | Mail history page |

### Validation rules

| Field | Rule |
|-------|------|
| `to` | required, array of valid emails |
| `subject` | required |
| `text` or `html` | at least one required |
| `cc`, `bcc` | optional arrays of emails |
| `attachments[].name` | string |
| `attachments[].content` | Base64-encoded file |
| `attachments[].mime` | MIME type string |

Attachment size limit: 5 MB per file (recommended).

### Response shape

| HTTP code | Meaning |
|-----------|---------|
| 202 | Job queued: `{"status": "queued", "mail_log_id": 42}` |
| 401 | Unauthorized: `{"status": "error", "message": "Unauthorized"}` |
| 422 | Validation failed: `{"status": "error", "message": "Validation failed", "errors": {...}}` |
| 500 | Send error: `{"status": "error", "message": "Failed to send email"}` |

### Queue

- Driver: `database` (default). The `jobs`, `job_batches`, and `failed_jobs` tables are created by the default Laravel migrations — no additional migration needed.
- `SendMailJob` implements `ShouldQueue` with `$tries = 3`, `$backoff = [10, 60]` (seconds between retries), and `#[Timeout(30)]` attribute (SMTP sends should complete well within 30 s).
- `failed(Throwable $e)` on `SendMailJob` writes `status=failed` and `error_message` to the `MailLog` record.
- `composer dev` already starts a queue worker in parallel — no extra step needed locally.
- Production: run `php artisan queue:work --tries=3` as a supervised daemon (Supervisor or systemd).

### Blade templates

- `resources/views/emails/generic.blade.php` — minimal HTML email shell that wraps `$htmlContent` in a standards-compliant structure.
- Used by `MailService` when the request contains an `html` field. Variables: `$subject`, `$htmlContent`.
- Requests with only a `text` field bypass Blade entirely (plain-text email, no view rendered).
- Future structured templates (e.g. specific notification types) should be registered as Mailable classes in `app/Mail/`.

### Mail history

**Schema (`mail_logs`):**

| Column | Type |
|--------|------|
| `id` | bigint PK |
| `to` | JSON |
| `cc` | JSON, nullable |
| `bcc` | JSON, nullable |
| `subject` | string |
| `text_body` | longtext, nullable |
| `html_body` | longtext, nullable |
| `status` | enum: `pending` / `sent` / `failed`, default `pending` |
| `error_message` | text, nullable |
| `sent_at` | timestamp, nullable |
| `created_at` / `updated_at` | timestamps |

`MailLog` casts `to`/`cc`/`bcc` as `array`; `status` cast to `MailStatus` enum.

**Web page (`GET /logs`):**

- No auth middleware — this is an internal operations tool. If publicly hosted, protect it with HTTP Basic auth at the web server level.
- Table columns: Subject, Recipients (to/cc/bcc), Status badge (pending=yellow, sent=green, failed=red), Sent at, content preview.
- Pagination via `$logs->links()` (Tailwind, Laravel 13 default).

### Environment variables

```env
MAIL_API_TOKEN=          # static bearer token for auth
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=
```

`QUEUE_CONNECTION` defaults to `database` (set in `config/queue.php`). No additional queue env vars needed.

## MCP tools (laravel-boost)

`laravel/boost` and `laravel/mcp` are installed. When working in Claude Code, the `laravel-boost` MCP server provides live tools:

| Tool | Use for |
|------|---------|
| `application-info` | Confirm exact PHP/Laravel/package versions before writing version-specific code |
| `database-schema` | Inspect the live schema — use before writing migrations or queries |
| `search-docs` | Fetch version-matched Laravel docs (queues, mail, etc.) |
| `last-error` | Read the last Laravel exception from the running app |
| `read-log-entries` | Tail `storage/logs/laravel.log` without leaving Claude Code |

Always call `application-info` at the start of a session and `search-docs` before implementing queue/mail features to get Laravel 13.x-specific API details.

### Testing

Tests use in-memory SQLite and the `array` mail driver (configured in `phpunit.xml`).

| Test class | Covers |
|------------|--------|
| `tests/Feature/MailControllerTest.php` | Returns 202, `Queue::fake()` asserts job dispatched, pending MailLog created |
| `tests/Feature/SendMailJobTest.php` | MailLog updated to `sent`; on exception → `failed` with error_message |
| `tests/Feature/MailLogControllerTest.php` | GET /logs returns 200, pagination, status display |
| `tests/Unit/MailServiceTest.php` | Blade rendering path, plain-text path, attachment decoding |

Use `Queue::fake()` in `MailControllerTest` to assert dispatch without executing the job. The `sync` queue driver in `phpunit.xml` makes jobs execute inline in `SendMailJobTest`. All Feature tests touching `mail_logs` require the `RefreshDatabase` trait.

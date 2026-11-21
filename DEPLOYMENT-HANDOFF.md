# Deployment handoff — 2026-11-09

Work paused at the user's request. No further deployment action is needed until they resume.

## Hosting and persistent data

- Hostinger SSH is available; domain directory: `/home/u828142386/domains/miresume.in`.
- Original application: `app/`; original web link: `public_html -> app/public`.
- Production uses MySQL. Users, resumes, sessions, and other records remain in the existing database.
- Uploaded resume photos are on the local disk at `app/storage/app/private`; database backups alone do not preserve them.
- Server has PHP 8.3.36 and Composer 2.9.8. Node/npm are unavailable, so build frontend assets locally.
- Preserve production `.env`, its existing `APP_KEY`, and `public/.user.ini`.
- Do not run `composer setup`, regenerate the application key, replace the database with a local copy, or run destructive migration commands.

## Backups completed

- User exported the production database through phpMyAdmin using Quick / SQL.
- User confirmed CREATE TABLE and INSERT statements, including saved resume/session data. A restore test was not performed.
- User created an application archive under `$HOME/miresume-backups/app-<timestamp>.tar.gz`.
- `tar -tzf` successfully read that archive. Exact timestamp was not recorded here.
- These backups precede deployment; users may have made changes since. Do not restore them casually.

## Local release preparation

- Original live commit: `6bfb6e8` (Include candidate and template in resume download filenames).
- Watermark changes committed as `76df364` (adding the watermark and other things).
- User created local branch `release/2026-11-09`.
- Changes add MiResume branding to PDF previews/downloads and DOCX footers and centralize PDF document generation.
- `npm ci` and `npm run build` succeeded.
- Initial `composer ci:check` failed because formatting scanned tracked generated assets.
- Agent added `public/build/**` to `fmt.ignorePatterns` in `vite.config.ts`. This change remained uncommitted at handoff.
- Full `composer ci:check` subsequently passed outside the sandbox (parallel Pint needed a local TCP listener): frontend lint/format/types, PHP formatting and static analysis, then 263 tests passed, 4 skipped, 10,245 assertions. Test runner reported 267 total tests.
- No database migration changes relative to the original live commit.
- Generated archive: `/tmp/miresume-release-2026-11-09.tar.gz`, approximately 2.9 MB. Temporary path may disappear; recreate if necessary.
- Archive contains tracked working-tree files including the formatter fix and built frontend. Excludes `.github/`, `tests/`, credentials, databases, user uploads, vendor, and node_modules. It has no `.git` directory. Frontend build files are tracked in this repository.

## Server preparation and switch completed by user

1. Uploaded archive into `releases/` and extracted to `releases/2026-11-09/`.
2. Copied `app/.env` into the release; set mode 600.
3. Copied `app/public/.user.ini` into the release's public directory.
4. Ran Composer install with `--no-dev --prefer-dist --optimize-autoloader --no-interaction`.
   - Dependencies extracted and autoload generated.
   - Composer's `package:discover --ansi` script failed because Hostinger disables `proc_open`.
   - Manually ran `php artisan package:discover --no-ansi -vvv`; it succeeded.
   - Do not claim the original Composer command exited successfully. Confirm any further setup needed when resuming.
5. In release directory, renamed initial storage to `storage.release-defaults` and created:
   `storage -> /home/u828142386/domains/miresume.in/app/storage`.
6. `php artisan migrate:status --no-ansi` succeeded; all six migrations were already applied. No migrations were run.
7. `config:cache --no-ansi` and `route:cache --no-ansi` succeeded.
   - `artisan about --only=environment --no-ansi` failed due to disabled `proc_open`.
8. Direct Laravel bootstrap checks confirmed production environment, debug OFF, MySQL driver, build manifest present, storage/views writable, and public/hot absent.
9. Provided maintenance-mode switch commands using a temporary symlink and `mv -Tf`:
   `public_html -> releases/2026-11-09/public`.
   User reported the website working afterward; actual command output was not supplied.

## Unresolved issue and current live-state uncertainty

- User reported an older saved resume did not populate/render properly, including work descriptions.
- Unknown whether the issue affects editor inputs, preview/PDF, or both.
- Agent inspected editor/model/rendering code: experience descriptions use `description`; editor autosaves after changes. No root cause established and no application fix made.
- Agent advised avoiding editing/saving affected resumes until investigation, and advised against restoring the database backup because it could overwrite newer data.
- Agent supplied rollback commands, but the user did NOT confirm running them:

```bash
cd /home/u828142386/domains/miresume.in
ln -s app/public public_html.restore
mv -Tf public_html.restore public_html
```

- Therefore current live symlink target is UNKNOWN. First verify with `readlink public_html` when resuming.
- Ask whether the affected resume works on the original release and where descriptions are missing. Inspect stored data read-only before changing normalization or making database writes. Use redacted evidence; do not request credentials or full private resume dumps.
- Original and new code differ primarily in export branding, not description loading. This does not establish that deployment is unrelated to the issue.

## Future deployment approach

- Local feature/release branch -> checks -> frontend build -> commit/push -> fresh server release directory -> preserve config/uploads -> locked Composer install -> startup checks -> switch symlink -> verify and retain rollback release.
- A Git push alone does not deploy this site; no automated pipeline was configured.
- Review schema changes for compatibility with rollback before running migrations.
- IMPORTANT: do not delete original `app/`, even if it is not serving the website. The new release still relies on its storage. A proper shared storage layout was discussed but NOT implemented.
- Give the user one small step at a time, clearly distinguishing local commands from Hostinger SSH commands. They found multi-step deployment instructions confusing.
- User asked to record the work and resume later; no deployment automation was implemented.

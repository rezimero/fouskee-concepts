# CI quality gate

## Purpose

Every pull request targeting `develop` or `main`, and every push to those
branches, runs the repository quality gate.

## Jobs

### Laravel / MySQL

- PHP 8.5
- Composer metadata validation
- deterministic dependency installation from `composer.lock`
- isolated MySQL 8.0 test database
- migration rebuild
- database-level audit trigger installation
- Laravel Pint
- Laravel test suite
- Composer security advisory audit

### React / TypeScript

- Node.js 24
- deterministic `npm ci`
- ESLint
- TypeScript/Vite production build
- npm high-severity dependency audit

### Source quality

The shared PowerShell rule in `scripts/quality/check-source-lines.ps1`
rejects PHP, JavaScript, and TypeScript source files over 200 lines in the
core application source trees.

## Security posture

The workflow grants only `contents: read`.

The CI MySQL root password is an ephemeral credential scoped to the temporary
GitHub Actions service container. It is not a Fouskee Concepts secret and is
destroyed with the runner.

No production, staging, or local developer database credentials are placed in
GitHub Actions.

## Branch policy

The intended repository rule is:

- feature branches merge into `develop` through pull requests;
- `develop` integrates tested work;
- release pull requests move `develop` into `main`;
- required CI checks should be enabled in GitHub branch rules once the checks
  have completed successfully at least once.

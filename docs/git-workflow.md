# Git Workflow

## Branches

| Branch | Purpose | Rules |
|---|---|---|
| `main` | Production. Always deployable | Protected. Merge only from `develop` (release) or `hotfix/*` via PR. CI must pass |
| `develop` | Integration of finished features | Protected. Merge via PR. CI must pass |
| `feature/<area>-<short-name>` | New work, e.g. `feature/cart-quantity-update` | Branch from `develop` |
| `fix/<short-name>` | Non-urgent bug fix | Branch from `develop` |
| `hotfix/<short-name>` | Urgent production fix | Branch from `main`, merge into `main` **and** `develop` |
| `docs/…`, `chore/…` | Docs or tooling only | Branch from `develop` |

Planned feature areas: `auth`, `catalog`, `cart`, `checkout`, `orders`, `payments`, `inventory`,
`billing`, `admin`, `delivery`, `ui`.

## Commits: Conventional Commits

```
<type>(<scope>): <short summary in imperative mood>

feat(cart): allow changing item quantity
fix(payments): reject duplicate UTR
docs(erd): add cod_settlements table
chore(ci): cache composer packages
```
Types: `feat`, `fix`, `refactor`, `test`, `docs`, `style`, `chore`, `perf`, `security`.

Keep commits small and focused. Never commit `.env*` (except `.env.example`), uploads, `vendor/`,
`node_modules/`, or `public/build/`.

## Flow

```
develop ─┬─> feature/x ── commits ── push ── PR → develop ── review ── CI green ── squash merge
         │
release: develop ── PR → main ── CI green ── merge ── tag vX.Y.Z ── deploy
```

1. `git switch develop && git pull`
2. `git switch -c feature/cart-quantity-update`
3. Work, then run `composer check` (Pint + Larastan + Pest) and fix everything.
4. Push and open a PR into `develop` using the PR template checklist.
5. Squash-merge after review and green CI, then delete the branch.

## Releases

Semantic version tags on `main`: `v0.1.0` (first UAT), `v1.0.0` (go-live).
Production is updated only from a tagged `main`, never edited directly on the server.

## GitHub settings (once the repo exists)

- Private repository
- Branch protection on `main` and `develop`: require PR, require status check **CI / Lint, analyse & test**,
  block force-push and deletion
- Dependabot security alerts on
- VPS access through a **read-only deploy key** (see deployment.md)

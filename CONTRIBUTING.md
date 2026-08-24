# Contributing to Orchestra

## Conventional Commits doctrine

Orchestra uses the [Conventional Commits](https://www.conventionalcommits.org/) specification for every commit and pull-request title. This maintains a machine-readable history for changelogs, release automation, and semantic versioning.

### Required format

```text
<type>(<optional scope>): <short imperative description>
```

Use a lowercase description with no trailing period. Keep the subject concise; put implementation detail and context in the commit body or pull request.

### Allowed types

| Type | Use for |
| --- | --- |
| `feat` | A new user-facing capability. |
| `fix` | A bug fix, including security fixes. |
| `docs` | Documentation-only changes. |
| `refactor` | Code changes that neither add a feature nor fix a bug. |
| `perf` | A measurable performance improvement. |
| `test` | Adding or correcting tests. |
| `build` | Build system or dependency changes. |
| `ci` | Continuous-integration configuration or automation. |
| `chore` | Maintenance that does not affect application behaviour. |
| `revert` | Reverting an earlier commit. |

Use an optional scope where it clarifies the change, for example: `inventory`, `transactions`, `ledgers`, `shopfront`, `imports`, `auth`, `docs`, `deps`, `ci`, or `security`.

### Examples

```text
feat(inventory): add batch-level stock adjustments
fix(shopfront): preserve cart items after sign-in
fix(security): rotate exposed development credential
docs: establish Conventional Commits doctrine
refactor(ledgers): extract balance recalculation service
build(deps): upgrade Laravel framework
```

### Breaking changes

Mark a breaking change with `!` after the type or scope, and explain it in a `BREAKING CHANGE:` footer.

```text
feat(api)!: replace legacy order endpoint

BREAKING CHANGE: clients must use /api/v2/orders.
```

## Pull requests

Every pull-request title must use the same Conventional Commits format as its commits. Keep pull requests focused, provide validation notes, and describe user-visible or operational effects.

Do not merge a pull request whose title does not follow this doctrine. Use `fix(security): ...` for security fixes, and avoid exposing sensitive details in public discussion.

## Development workflow

1. Create a focused branch from `main`.
2. Make small, reviewable commits using the required format.
3. Run relevant tests and build checks.
4. Open a pull request using the required title format and template.
5. Address review feedback with additional conventional commits.

# Builder editor assets

The WordPress plugin serves committed `../assets/editor.js` and `../assets/editor.css`.
After changing editor sources, regenerate and commit these outputs together. Do not
hand-edit the bundles or bypass the CI asset-diff gate.

Verified build toolchain: Bun **1.4.4**, Node **24.22.1**. CI pins Bun and installs the
committed `bun.lock` with `--frozen-lockfile`; no dependency update is needed to rebuild.
From the repository root, with a clean editor dependency installation:

```sh
bun install --cwd plugins/wpfasty-builder/editor --frozen-lockfile
bun run --cwd plugins/wpfasty-builder/editor check
bun run --cwd plugins/wpfasty-builder/editor test
bun run --cwd plugins/wpfasty-builder/editor build
git diff -- plugins/wpfasty-builder/assets
```

CI first compares generated assets with Git, then rebuilds and checks both output
SHA256 hashes, so stale artifacts and nondeterministic rebuilds fail independently.

The November 2026 failure at `3851a20` / `1affafb` was stale committed JavaScript:
rebuilding from a fresh locked installation reproduced the failed CI's added
`editor.js` bytes exactly (151,767 characters versus 151,620 committed). Svelte
diagnostics, editor tests, and compilation passed; the asset consistency check
correctly rejected the older bundle. No router, UI, or provider changes are involved.

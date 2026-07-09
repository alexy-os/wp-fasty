# Local WordPress (Docker + WSL)

Stack: **WordPress PHP 8.2 + Apache** and **MySQL 8** via Docker Compose.
Theme and UI8Kit Latte primitives are bind-mounted from this monorepo.

| URL | http://localhost:8080 |
| DB (inside Docker) | `db:3306` / user `wp` / pass `wp` / db `wordpress` |

## Prerequisites (once)

In **WSL Debian** (Docker Desktop → Settings → Resources → WSL integration → Debian ON):

```bash
# Docker
docker version
docker compose version

# Optional on host (for regenerating Latte primitives)
# Install bun: https://bun.sh
bun --version
```

Prefer cloning the repo **inside the Linux filesystem** (faster than `/mnt/d/...`):

```bash
mkdir -p ~/code && cd ~/code
# if not cloned yet:
# git clone https://github.com/godudy/wpfasty.git
cd ~/code/wpfasty   # or: cd /mnt/d/WPFasty/wpfasty
```

## Commands (copy-paste)

### 1) Generate Latte primitives (host)

```bash
cd ~/code/wpfasty   # adjust path

# Needs UI8Kit Codegen (sibling, UI8KIT_CODEGEN, or packages/ui8kit-codegen)
bun run ui:primitives
```

If codegen is only on the Windows drive:

```bash
export UI8KIT_CODEGEN=/mnt/d/UI8Kit/Codegen
bun run ui:primitives
```

### 2) Start WordPress + MySQL

```bash
cd ~/code/wpfasty
docker compose up -d
docker compose ps
```

First boot pulls images (~1–2 min). Then open:

**http://localhost:8080**

Complete the WordPress install wizard (any site title / admin user).

### 3) Install PHP dependencies

```bash
cd ~/code/wpfasty
bun run ui:install
```

`ui:install` uses `composer` if available, otherwise **Docker** (`dev:composer`), otherwise downloads `tools/composer.phar`.

Equivalent Docker-only:

```bash
docker compose --profile tools run --rm composer
# or: bun run dev:composer
```

This runs `composer install` in:

- `.workspaces/ui8kit-latte`
- `themes/latte` (path repo → `ui8kit/latte-primitives`)
- `themes/wp-fasty-ym` (legacy experimental, if present)

### 4) Activate the theme

**UI:** Appearance → Themes → **Latte** → Activate  

**or CLI:**

```bash
docker compose --profile tools run --rm wpcli theme activate latte
docker compose --profile tools run --rm wpcli theme list
```

### 5) Front page (optional)

```bash
docker compose --profile tools run --rm wpcli option update show_on_front page
# create a page in admin and set it as homepage, or:
docker compose --profile tools run --rm wpcli post create \
  --post_type=page --post_title='Home' --post_status=publish --porcelain
# then: Settings → Reading → Homepage
```

## Day-to-day

```bash
# Start / stop
docker compose up -d
docker compose stop
docker compose down          # keep DB volume
docker compose down -v       # WIPE database + WP files volume

# Logs
docker compose logs -f wordpress

# Clear Latte cache after template changes
rm -rf themes/latte/~cache
# or:
docker compose --profile tools run --rm wpcli eval 'echo "ok";'

# Regenerate primitives after brick changes
bun run ui:primitives
docker compose --profile tools run --rm composer
```

## How mounts work

| Host | Container |
|------|-----------|
| `themes/latte` | `/var/www/html/wp-content/themes/latte` |
| `themes/wp-fasty-ym` | `/var/www/html/wp-content/themes/wp-fasty-ym` (legacy) |
| `.workspaces` | `/var/www/html/wp-content/.workspaces` |

Relative path `../../.workspaces/ui8kit-latte` from the theme stays valid
(same as in the monorepo). Edits on the host appear in WordPress immediately.

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| `Missing vendor/autoload.php` | `docker compose --profile tools run --rm composer` |
| Theme missing / white screen | `bun run ui:primitives` then composer again; check `docker compose logs wordpress` |
| Port 8080 busy | `WP_PORT=8081 docker compose up -d` |
| Permission on `~cache` | `mkdir -p themes/latte/~cache && chmod 777 themes/latte/~cache` |
| Slow on `/mnt/d` | Clone repo into `~/code/wpfasty` inside WSL |
| DB not ready | Wait for healthcheck; `docker compose ps` |

## Reset everything

```bash
docker compose down -v
docker compose up -d
docker compose --profile tools run --rm composer
# re-run WP install wizard at http://localhost:8080
```

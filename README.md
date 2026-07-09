# ARK ASA Server Manager

A Laravel application for managing ARK: Survival Ascended dedicated servers. The app connects to the game server via SSH, giving you full control from a web interface.

## Architecture

The project is split into two independent Docker Compose stacks that communicate over a shared network:

| Stack | File | Purpose |
|---|---|---|
| **App** | `docker-compose.yml` | Laravel app with Octane, Reverb, queue worker, and ARK manager |
| **ARK Server** | `docker-compose.ark.yml` | SteamCMD + Wine/Proton game server with SSH access |

### App Stack (`docker-compose.yml`)

Each process runs as its own container:

| Service | Description | Port |
|---|---|---|
| `app` | Octane (Swoole) HTTP server | 8000 |
| `reverb` | WebSocket server | 8080 |
| `queue` | Background job processing | — |
| `scheduler` | Task scheduler | — |
| `ark-manager` | Custom ARK management command | — |
| `pgsql` | PostgreSQL 18 database | 5432 |
| `redis` | Redis 7 cache/queue broker | 6379 |

### ARK Server Stack (`docker-compose.ark.yml`)

A minimal Linux container built on `steamcmd/steamcmd:ubuntu-22` with:

- **SteamCMD** for downloading/updating the ARK server
- **Wine/Proton GE** for running the Windows ARK server binary on Linux
- **XAudio2** runtime dependency
- **OpenSSH server** so the Laravel app can execute commands remotely

No opinionated game config scripts are included — the Laravel app manages everything over SSH.

#### Exposed Ports

| Port | Protocol | Description |
|---|---|---|
| 7777 | UDP | Main game port |
| 7778 | UDP | Secondary game port |
| 27015 | UDP | Steam query port |
| 27020 | TCP | RCON port |

#### Volumes

| Volume | Container Path | Contents |
|---|---|---|
| `ark-server-data` | `/ark-server` | Game server files, logs, and backups |

## Prerequisites

- Docker and Docker Compose
- At least 16 GB RAM, 4 CPU cores, 20 GB SSD (for the ARK server)

## Quick Start

### 1. Clone and configure

```bash
git clone <repo-url> ark-asa-server
cd ark-asa-server
cp .env.example .env
```

Edit `.env` and set at minimum:

- `APP_KEY` — generate with `php artisan key:generate --show`
- `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` — any unique strings

### 2. Build and run

Run both stacks together:

```bash
docker compose -f docker-compose.yml -f docker-compose.ark.yml up --build -d
```

Or run them independently:

```bash
# App only (point ARK_SERVER_HOST to an external server in .env)
docker compose up --build -d

# ARK server only
docker compose -f docker-compose.ark.yml up --build -d
```

The app will be available at `http://localhost:8000`.

### 3. Verify SSH connectivity

```bash
docker exec -u app ark-asa-app ssh ark-sa@ark-server
```

## Environment Variables

### App

| Variable | Default | Description |
|---|---|---|
| `APP_PORT` | `8000` | Host port for the web app |
| `REVERB_SERVER_PORT` | `8080` | Host port for WebSockets |
| `APP_KEY` | — | Laravel app key (required) |
| `DB_CONNECTION` | `pgsql` | Database driver |
| `DB_DATABASE` | `ark_asa` | Database name |
| `DB_USERNAME` | `ark` | Database user |
| `DB_PASSWORD` | `secret` | Database password |
| `DB_FORWARD_PORT` | `5432` | Host port for PostgreSQL |
| `REDIS_HOST` | `redis` | Redis hostname |
| `REDIS_FORWARD_PORT` | `6379` | Host port for Redis |
| `REVERB_APP_ID` | — | Reverb app identifier |
| `REVERB_APP_KEY` | — | Reverb app key |
| `REVERB_APP_SECRET` | — | Reverb app secret |
| `ARK_SERVER_HOST` | `ark-server` | Hostname/IP of the ARK server |
| `ARK_SERVER_SSH_PORT` | `22` | SSH port on the ARK server |
| `ARK_SERVER_SSH_USER` | `ark-sa` | SSH user on the ARK server |

### ARK Server

| Variable | Default | Description |
|---|---|---|
| `TZ` | `America/New_York` | Container timezone |
| `GAME_PORT` | `7777` | Host mapping for game port |
| `GAME_PORT_2` | `7778` | Host mapping for secondary game port |
| `QUERY_PORT` | `27015` | Host mapping for Steam query port |
| `RCON_PORT` | `27020` | Host mapping for RCON port |
| `ARK_SERVER_SSH_PORT` | `2222` | Host mapping for SSH port |

## Local Development

For local development without Docker, use the standard Laravel dev server:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan wayfinder:generate --with-form --no-interaction
composer run dev
```

### Development with Docker (Windows)

The app container bind-mounts your local source code so file changes are reflected immediately via Octane's `--watch` mode (using polling for Windows compatibility). Dependencies (`vendor`, `node_modules`, `storage`) live in shared named volumes inside the containers to avoid Windows/Linux filesystem incompatibilities.

All containers share the same volumes and read your local `.env` file directly.

```bash
# First time: build and start all services
docker compose up --build -d

# Rebuild after dependency changes (composer.json or package.json)
docker compose up -d --build -V

# Restart without rebuilding (e.g. after .env changes)
docker compose up -d
```

On startup, the first container to start will:
1. Run `composer install`
2. Run `npm install`
3. Run `php artisan migrate --force`
4. Run `npm run build` (generates Wayfinder types + Vite assets)

Other containers wait for setup to complete, then start their command. Dependencies persist in named volumes across restarts — they only reinstall when volumes are removed (`-V` flag or `docker compose down -v`).

## Project Structure

```
├── docker/
│   ├── app/
│   │   ├── Dockerfile          # Laravel app image
│   │   ├── start-container     # Entrypoint script
│   │   └── php.ini             # PHP configuration
│   └── ark-server/
│       ├── Dockerfile          # ARK game server image
│       ├── entrypoint.sh       # Entrypoint script
│       └── sshd_config         # SSH daemon config
├── docker-compose.yml          # App stack (app, reverb, queue, scheduler, pgsql, redis)
├── docker-compose.ark.yml      # ARK server stack
└── .env.example
```


`docker compose exec app rm -rf /var/www/html/storage/.setup-lock /var/www/html/storage/.setup-ready`
`php artisan wayfinder:generate --with-form --no-interaction`
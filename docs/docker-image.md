# HRMS Docker Image

This repository builds the single-tenant HRMS product image. It does not own demo provisioning, Docker Compose topology, client domains, database creation, expiry cleanup, or autoscaling policy. Those responsibilities belong in the separate marketing/provisioning application.

## Build

```sh
docker build -t hrms:latest .
```

Tag and push the same image for each environment:

```sh
docker tag hrms:latest registry.example.com/hrms:2026-07-11
docker push registry.example.com/hrms:2026-07-11
```

## Required Runtime Environment

Each provisioned instance must provide its own environment variables:

- `APP_KEY`
- `APP_URL`
- `ASSET_URL` when the public asset origin differs from the container's internal origin, for example `http://localhost:8080` for local port mapping
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `CACHE_STORE`
- `QUEUE_CONNECTION`
- `SESSION_DRIVER`
- mail settings
- object storage settings when file uploads are enabled

The image defaults to `APP_ENV=production` and `APP_DEBUG=false`.

The runtime image serves Laravel through nginx on port `80` and forwards PHP requests to php-fpm inside the same container.

## Provisioning Contract

The external provisioning app should create the database and deploy this image with the instance-specific environment. After the container can reach its database, run:

```sh
php artisan migrate --force
php artisan app:setup --force \
  --super-admin-name="Client Owner" \
  --super-admin-email="owner@example.com" \
  --super-admin-password="generated-secret" \
  --admin-name="Client Admin" \
  --admin-email="admin@example.com" \
  --admin-password="generated-secret"
```

For demos, the provisioning app may use generated credentials, short-lived domains, small autoscaling limits, and automatic deletion. For production clients, use durable infrastructure, warm instances, backups, monitoring, and object storage.

## What Stays Outside This Repo

- `docker-compose.yml` or platform-specific service topology.
- Cloud Run, ECS, Fly.io, or Kubernetes provisioning code.
- Demo request forms and landing pages.
- Tenant tables or tenant-aware business logic.
- Per-client expiry and cleanup workers.

The only contract for this app is: given environment variables and a reachable database, the image can boot, migrate, and initialize itself.

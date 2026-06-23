# Observability

This directory contains baseline Grafana and Loki assets for the Symfony booking and payment flows.

## Assumptions

- Application logs are shipped as JSON to Loki.
- Each log stream has stable labels from Docker discovery such as `compose_project`, `service` and `container`.
- Monolog JSON formatter keeps application fields under `context` and processor fields under `extra`.

## Files

- `grafana/booking-payments-dashboard.json`
  - Starter dashboard for booking, payment, Stripe and membership flows.
- `grafana/provisioning/`
  - Grafana datasource and dashboard provisioning.
- `loki/rules/fake/alerts.yaml`
  - Starter Loki alert rules for booking failures, Stripe failures, refund spikes and membership anomalies.
- `loki/config.yml`
  - Single-node Loki config with 14-day retention.
- `alertmanager/config.yml`
  - Alertmanager template with a mandatory generic webhook receiver.
- `alertmanager/entrypoint.sh`
  - Renders the webhook URL from the environment before Alertmanager starts.
- `promtail/config.yml`
  - Promtail config for scraping Docker container logs.
- `docker-compose.observability.yml`
  - Local `Loki + Promtail + Alertmanager + Grafana` stack for validating logs and alerts end to end.

## Required log fields

These assets expect the following structured fields in logs:

- `context.domain`
- `context.operation`
- `context.outcome`
- `context.booking_id`
- `context.payment_id`
- `context.membership_id`
- `context.membership_plan_id`
- `context.stripe_payment_intent_id`
- `context.duration_ms`
- `extra.request_id`
- `extra.correlation_id`

The log shipper should add low-cardinality labels such as `service`, `env` and `container`. Keep high-cardinality values such as `request_id`, `booking_id` and `correlation_id` inside the JSON payload.

## Local startup

Create the local environment file and replace every placeholder:

```bash
cp .env.example .env
```

Required values:

- `GRAFANA_ADMIN_PASSWORD`: a non-default local password;
- `ALERTMANAGER_WEBHOOK_URL`: an HTTP(S) endpoint that accepts Alertmanager webhook payloads;
- `OBSERVABILITY_BIND_ADDRESS`: keep `127.0.0.1` unless remote access is explicitly protected by a firewall and authentication.

A webhook running on the Docker host can be addressed as `http://host.docker.internal:<port>/<path>`.

Start the stack from `observability/`:

```bash
docker compose -f docker-compose.observability.yml config --quiet
docker compose -f docker-compose.observability.yml up -d
```

Then open:

- Grafana: `http://localhost:3001`
- Loki: `http://localhost:3100`
- Alertmanager: `http://localhost:9093`

Use `GRAFANA_ADMIN_USER` and `GRAFANA_ADMIN_PASSWORD` from the local `.env` file to sign in.

## Notes

- Promtail uses Docker service discovery and maps `com.docker.compose.service` to the Loki label `service`.
- The dashboard queries assume Symfony logs are emitted from Compose services such as `php-fpm` or `messenger-worker`.
- Alert rules from `loki/rules/fake/alerts.yaml` are loaded by Loki ruler and should appear in Grafana under `Alerting` as data source-managed alerts from the Loki datasource.
- Loki uses local filesystem storage and deletes data older than 14 days through the compactor.
- Because `auth_enabled: false` is used in this local setup, Loki runs in single-tenant mode and expects ruler files under the synthetic tenant directory `fake/`.
- This stack is intended for local validation. Internet-facing deployment requires authentication, TLS and network isolation.

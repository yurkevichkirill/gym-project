# Observability

This directory contains baseline Grafana and Loki assets for the Symfony booking and payment flows.

## Assumptions

- Application logs are shipped as JSON to Loki.
- Each log stream has stable labels from Docker discovery such as `compose_project`, `service` and `container`.
- Monolog JSON formatter keeps application fields under `context` and processor fields under `extra`.

## Files

- `grafana/booking-payments-dashboard.json`
  - Starter dashboard for booking, payment, stripe and membership flows.
- `grafana/provisioning/`
  - Grafana datasource and dashboard provisioning.
- `loki/rules/fake/alerts.yaml`
  - Starter Loki alert rules for booking failures, stripe failures, refund spikes and membership anomalies.
- `loki/config.yml`
  - Minimal local Loki config.
- `alertmanager/config.yml`
  - Local Alertmanager config for receiving Loki alerts.
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

## Recommended next step

Make sure your log shipper adds at least:

- `service`
- `env`
- `container`

as Loki labels, while keeping high-cardinality values like `request_id` and `booking_id` inside the JSON payload only.

## Local startup

From `observability/` run:

```bash
docker compose -f docker-compose.observability.yml up -d
```

Then open:

- Grafana: `http://localhost:3001`
- Loki: `http://localhost:3100`
- Alertmanager: `http://localhost:9093`

Default Grafana credentials:

- user: `admin`
- password: `admin`

## Notes

- Promtail uses Docker service discovery and maps `com.docker.compose.service` to the Loki label `service`.
- The dashboard queries assume your Symfony logs are emitted from Compose services like `php-fpm` or `messenger-worker`.
- High-cardinality values such as `request_id` and `correlation_id` stay in JSON payload fields and should not become Loki labels.
- Alert rules from `loki/rules/fake/alerts.yaml` are loaded by Loki ruler and should appear in Grafana under `Alerting` as data source-managed alerts from the Loki datasource.
- Because `auth_enabled: false` is used in this local setup, Loki runs in single-tenant mode and expects ruler files under the synthetic tenant directory `fake/`.

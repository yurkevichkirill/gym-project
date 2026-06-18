CREATE TABLE IF NOT EXISTS analytics.booking_events
(
    event_id String,
    event_time DateTime,
    event_type LowCardinality(String),
    client_id Nullable(UInt64),
    trainer_id Nullable(UInt64),
    booking_id Nullable(UInt64),
    price Nullable(Decimal(12, 2)),
    payment_method Nullable(String)
)
ENGINE = MergeTree
ORDER BY (event_time, event_type, event_id);

CREATE TABLE IF NOT EXISTS analytics.membership_events
(
    event_id String,
    event_time DateTime,
    event_type LowCardinality(String),
    client_id Nullable(UInt64),
    membership_id Nullable(UInt64),
    plan_id Nullable(UInt64),
    price Nullable(Decimal(12, 2)),
    payment_method Nullable(String)
)
ENGINE = MergeTree
ORDER BY (event_time, event_type, event_id);

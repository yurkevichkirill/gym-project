# API Coverage

Inventory date: 2026-06-27. Source of truth: Symfony controllers, DTOs, resolvers, queries, mappers, voters, and security attributes under `symfony/src`. Frontend scope is limited to `nextjs/`.

Coverage statuses:

- COMPLETE: endpoint is available through UI and typed frontend integration.
- PARTIAL: wrapper or UI exists, but the full contract is not covered.
- MISSING: frontend integration is absent.
- NOT_FOR_FRONTEND: browser must not call this endpoint.
- BLOCKED_BY_API: safe frontend implementation needs an API contract change.

## Current PR Domain: Trainer Payments

| Endpoint | Contract | Frontend coverage | Status |
| --- | --- | --- | --- |
| `GET /api/trainer/payments/` | `ROLE_TRAINER`; query `clientId`, `minAmount`, `maxAmount`, `isRefund`, `status`, `minCreatedAt`, `maxCreatedAt`, `sort`, `page`, `limit`; response `CollectionResponse<PaymentResponseDTO>`; errors `400`, `401`, `403`, `404` | `/me/trainer-payments` -> `PaymentsCatalog forcedScope=TRAINER` -> `PaymentStore` -> `getPaymentsForScope(..., TRAINER)` | COMPLETE |
| `GET /api/trainer/payments/{id}/` | `ROLE_TRAINER`; path `id`; `PaymentVoter::VIEW_OWN`; response `ItemResponse<PaymentResponseDTO>`; errors `401`, `403`, `404` | `/me/trainer-payments/[id]` -> `PaymentDetails forcedScope=TRAINER` -> `PaymentStore.loadPayment(..., TRAINER)` -> `getPaymentForScope(..., TRAINER)` | COMPLETE |

Frontend chain covered in this PR:

`nextjs/src/app/me/trainer-payments/page.tsx` -> `RoleGuard(ROLE_TRAINER)` -> `PaymentsCatalog` -> `PaymentsFilters` / `PaymentCatalogCard` -> `PaymentStore` -> `getPaymentsForScope` -> `apiClient` -> `Trainer\PaymentController::getAll()` -> `ResolvedPaymentsRequestDTO` -> `PaymentResponseDTO`.

`nextjs/src/app/me/trainer-payments/[id]/page.tsx` -> `RoleGuard(ROLE_TRAINER)` -> `PaymentDetails` -> `PaymentStore.loadPayment` -> `getPaymentForScope` -> `apiClient` -> `Trainer\PaymentController::get()` -> `PaymentVoter::VIEW_OWN` -> `PaymentResponseDTO`.

Contract notes:

- URLs keep the Symfony trailing slash through `apiClient` calls: `/trainer/payments/` and `/trainer/payments/{id}/`.
- Query state is kept in the browser URL and empty values are omitted.
- `isRefund` is serialized as `true` or `false`; date filters use `YYYY-MM-DD` as required by `Assert\Date`.
- `limit` is capped to `100` in frontend parsing and form validation, matching the Symfony DTO range.
- Trainer payment views are read-only. Stripe intent creation remains client-only through `POST /api/payments/{id}/intent/` and is not exposed in trainer payment UI.
- `PaymentResponseDTO` contains `trainer` but not `client`; for trainer-owned payment views, client identity cannot be displayed safely without a backend contract change. The UI shows a clear placeholder and keeps client filtering by ID available.

## Full API Inventory Summary

| Domain | Endpoints | Coverage status |
| --- | --- | --- |
| Authentication | login, refresh, logout, registration, activation, current user | COMPLETE |
| Public | trainers, trainer details, membership plans, training types, worktimes | COMPLETE |
| Contact | `POST /api/contact/` | PARTIAL in `main`; typed wrapper/documentation is handled by PR #32 |
| Client profile | `GET/PATCH/DELETE /api/me/`, visit, top-up | PARTIAL |
| Client bookings | list/detail/create/cancel | COMPLETE |
| Client memberships | list/detail/create/cancel/freeze/unfreeze/renew/terminate | COMPLETE |
| Client payments | list/detail/Stripe intent | COMPLETE |
| Trainer profile | get/update/photo/delete | COMPLETE |
| Trainer worktimes | list/create/update/delete | COMPLETE |
| Trainer trainings | list/detail/update/cancel/complete | COMPLETE |
| Trainer payments | list/detail | COMPLETE in this PR |
| Admin clients | CRUD, restore, block, unblock, visit, import | MISSING |
| Admin trainers | list/detail/create/update/photo/delete/restore/block/unblock | MISSING |
| Admin bookings | list/detail/create-for-client/cancel; `/api/coffee/` admin/debug route | MISSING |
| Admin memberships | list/detail/create-for-client/cancel/freeze/unfreeze/renew/terminate | MISSING |
| Admin membership plans | create/update/delete | MISSING |
| Admin payments | list/detail | MISSING |
| Admin training types | create/update/photo/delete | MISSING |
| Admin trainings | list/update/cancel/complete | MISSING |
| Admin worktimes | create/update/delete | MISSING |

## Not For Frontend

| Endpoint or operation | Reason | Status |
| --- | --- | --- |
| `POST /api/webhooks/stripe/` | Stripe server-to-server webhook; browser must never call it | NOT_FOR_FRONTEND |
| Messenger handlers under `symfony/src/*/MessageHandler` | async backend processing only | NOT_FOR_FRONTEND |
| CLI commands under `symfony/src/*/Command` and scheduled cleanup | operational backend tasks only | NOT_FOR_FRONTEND |
| Import processing internals under `ImportJob*` services/handlers | admin import endpoint may be frontend-facing, but handlers are backend-only | NOT_FOR_FRONTEND |

## Next Recommended Domain

Admin clients: this is the largest high-value missing admin area and should be handled in its own focused PR because it includes list/detail/mutations, account state transitions, visit write-off, and import flow.

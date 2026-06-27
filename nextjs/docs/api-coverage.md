# API Coverage

Inventory date: 2026-06-27. Source of truth: Symfony controllers, DTOs, query classes, mappers, voters, and security attributes under `symfony/src`. Frontend scope is limited to `nextjs/`.

Coverage statuses:

- COMPLETE: endpoint is available through UI and typed frontend integration.
- PARTIAL: wrapper or UI exists, but the full contract is not covered.
- MISSING: frontend integration is absent.
- NOT_FOR_FRONTEND: browser must not call this endpoint.
- BLOCKED_BY_API: safe frontend implementation needs an API contract change.

## Current PR Domain: Admin Clients

| Endpoint | Contract | Frontend coverage | Status |
| --- | --- | --- | --- |
| `GET /api/clients/` | `ROLE_ADMIN`; `GetClientsRequestDTO`; filters `minAge`, `maxAge`, `minBalance`, `maxBalance`, `isDeleted`; sort fields `firstName`, `lastName`, `balance`, `age`, `createdAt`, `updatedAt`, `deletedAt`; pagination `page`, `limit <= 100`; response `CollectionResponse<ClientResponseDTO>` | `/admin/clients` -> `AdminClientsPage` -> `AdminClientsStore` -> `getAdminClients` | COMPLETE |
| `GET /api/clients/{id}/` | `ROLE_ADMIN`; response `ItemResponse<ClientResponseDTO>`; errors `401`, `403`, `404` | `/admin/clients/[id]` -> `AdminClientDetailsPage` -> `loadClient` -> `getAdminClient` | COMPLETE |
| `POST /api/clients/` | `CreateClientRequestDTO`: `age`, `firstName`, `lastName`, `email`, `phone`, `password`; response `201 ItemResponse<ClientResponseDTO>`; errors `400`, `401`, `403`, `422` | create form on `/admin/clients` -> `createAdminClient` | COMPLETE |
| `PATCH /api/clients/{id}/` | `AdminUpdateClientRequestDTO`: optional `age`, `firstName`, `lastName`, `email`, `phone`, `password`; response `ItemResponse<ClientResponseDTO>` | edit form on `/admin/clients/[id]` -> `updateAdminClient` | COMPLETE |
| `DELETE /api/clients/{id}/` | soft delete; `204`; conflict if already deleted | confirmed action on details page -> `deleteAdminClient` | COMPLETE |
| `POST /api/clients/{id}/restore/` | restore soft-deleted client; response `ItemResponse<ClientResponseDTO>`; conflict if not deleted | confirmed action on details page -> `restoreAdminClient` | COMPLETE |
| `POST /api/clients/{id}/block/` | block account; response `ItemResponse<ClientResponseDTO>`; conflict if already blocked | confirmed action on details page -> `blockAdminClient` | COMPLETE |
| `POST /api/clients/{id}/unblock/` | unblock account; response `ItemResponse<ClientResponseDTO>`; conflict if not blocked | confirmed action on details page -> `unblockAdminClient` | COMPLETE |
| `POST /api/clients/{id}/visit/` | register visit/write-off; response `ItemResponse<MembershipResponseDTO>`; errors `400`, `403`, `404` | confirmed action on details page -> `registerAdminClientVisit` | COMPLETE |
| `POST /api/import/clients/` | `CreateClientImportBatch` JSON `{ clients: CreateClientImport[] }`; response `202 ItemResponse<ClientImportResponseDTO>` | import rows form on `/admin/clients` -> `importAdminClients` | PARTIAL |

Frontend chain covered in this PR:

`nextjs/src/app/admin/clients/page.tsx` -> `AdminClientsPage` -> `AdminClientsFilters` / `AdminClientCreateForm` / `AdminClientsImportForm` -> `AdminClientsStore` -> `nextjs/src/api/admin/clients.api.ts` -> `apiClient` -> `Admin\ClientController`.

`nextjs/src/app/admin/clients/[id]/page.tsx` -> `AdminClientDetailsPage` -> edit form / `ConfirmDialog` actions -> `AdminClientsStore` -> `nextjs/src/api/admin/clients.api.ts` -> `Admin\ClientController`.

Contract notes:

- All URLs preserve Symfony trailing slashes.
- Empty filters are omitted from query strings and API requests.
- `isDeleted` is serialized as `true` or `false` only when selected.
- Dangerous actions `delete`, `restore`, `block`, `unblock`, and `visit` require confirmation.
- Visit registration is intentionally non-optimistic and does not fake membership state.
- Import is JSON batch entry, not multipart upload. The frontend queues rows and reports `jobId`, `status`, and `count` from the API response.

Blocker:

- Import job progress cannot be followed safely after `POST /api/import/clients/`; no user-facing endpoint for import job status/result was found. The form therefore only reports the queued job response and does not invent polling or local processing state.

## Full API Inventory Summary

| Domain | Endpoints | Coverage status |
| --- | --- | --- |
| Authentication | login, refresh, logout, registration, activation, current user | COMPLETE |
| Public | trainers, trainer details, membership plans, training types, worktimes | COMPLETE |
| Contact | `POST /api/contact/` | PARTIAL in `main`; typed wrapper/documentation handled by PR #32 |
| Client profile | `GET/PATCH/DELETE /api/me/`, visit, top-up | PARTIAL |
| Client bookings | list/detail/create/cancel | COMPLETE |
| Client memberships | list/detail/create/cancel/freeze/unfreeze/renew/terminate | COMPLETE |
| Client payments | list/detail/Stripe intent | COMPLETE |
| Trainer profile | get/update/photo/delete | COMPLETE |
| Trainer worktimes | list/create/update/delete | COMPLETE |
| Trainer trainings | list/detail/update/cancel/complete | COMPLETE |
| Trainer payments | list/detail | PARTIAL in `main`; dedicated route handled by PR #33 |
| Admin clients | list/detail/create/update/delete/restore/block/unblock/visit/import | COMPLETE for synchronous client endpoints; import progress BLOCKED_BY_API |
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
| Import processing internals under `ImportJob*` services/handlers | backend-only; only queue endpoint is browser-facing | NOT_FOR_FRONTEND |

## Next Recommended Domain

Admin trainers: it mirrors admin client account management and adds trainer photo upload, so it should be handled in a separate PR.

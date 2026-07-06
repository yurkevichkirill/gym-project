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
| Admin clients | list/detail/create/update/delete/restore/block/unblock/visit/import plus create booking and assign membership on detail | COMPLETE for synchronous client endpoints; import progress BLOCKED_BY_API |
| Admin trainers | list/detail/create/update/photo/delete/restore/block/unblock | COMPLETE |
| Admin worktimes | list/create/update/delete scoped to trainer detail | COMPLETE |
| Admin bookings | list/detail/create-for-client/cancel; `/api/coffee/` admin/debug route | COMPLETE for business endpoints; `/api/coffee/` NOT_FOR_FRONTEND |
| Admin memberships | list/detail/create-for-client/cancel/freeze/unfreeze/renew/terminate | COMPLETE |
| Admin membership plans | list/detail/create/update/delete | COMPLETE |
| Admin payments | list/detail | COMPLETE |
| Admin training types | list/detail/create/update/photo/delete | COMPLETE |
| Admin trainings | list/update/cancel/complete | COMPLETE; no detail route because backend has no admin GET by ID |

## Admin Cabinet Coverage

| Endpoint | Symfony controller | Request DTO | Response DTO | Frontend route | Scene/component | Store | API wrapper | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `GET /api/admin/trainers/` | `Admin/TrainerController.php` | `ResolvedTrainersRequestAdminDTO` | `CollectionResponse<TrainerResponsePrivateDTO>` | `/admin/trainers` | `AdminTrainersPage` | `AdminTrainersStore` | `getAdminTrainers` | COMPLETE |
| `GET /api/admin/trainers/{id}/` | `Admin/TrainerController.php` | route id | `ItemResponse<TrainerResponsePrivateDTO>` | `/admin/trainers/[id]` | `AdminTrainerDetailsPage` | `AdminTrainersStore` | `getAdminTrainer` | COMPLETE |
| `POST /api/trainers/` | `Admin/TrainerController.php` | `CreateTrainerRequestDTO` | `ItemResponse<TrainerResponsePrivateDTO>` | `/admin/trainers` | `AdminTrainerCreateForm` | `AdminTrainersStore` | `createAdminTrainer` | COMPLETE |
| `PATCH /api/trainers/{id}/` | `Admin/TrainerController.php` | `AdminUpdateTrainerRequestDTO` | `ItemResponse<TrainerResponsePrivateDTO>` | `/admin/trainers/[id]` | `AdminTrainerDetailsPage` | `AdminTrainersStore` | `updateAdminTrainer` | COMPLETE |
| `POST /api/trainers/{id}/photo/` | `Admin/TrainerController.php` | multipart `photo` | `ItemResponse<TrainerResponsePrivateDTO>` | `/admin/trainers/[id]` | `AdminTrainerDetailsPage` | `AdminTrainersStore` | `uploadAdminTrainerPhoto` | COMPLETE |
| `DELETE /api/trainers/{id}/` | `Admin/TrainerController.php` | route id | `204` | `/admin/trainers` | `AdminTrainersPage` | `AdminTrainersStore` | `deleteAdminTrainer` | COMPLETE |
| `POST /api/trainers/{id}/restore/` | `Admin/TrainerController.php` | route id | `ItemResponse<TrainerResponsePrivateDTO>` | `/admin/trainers` | `AdminTrainersPage` | `AdminTrainersStore` | `restoreAdminTrainer` | COMPLETE |
| `POST /api/trainers/{id}/block/` | `Admin/TrainerController.php` | route id | `ItemResponse<TrainerResponsePrivateDTO>` | `/admin/trainers` | `AdminTrainersPage` | `AdminTrainersStore` | `blockAdminTrainer` | COMPLETE |
| `POST /api/trainers/{id}/unblock/` | `Admin/TrainerController.php` | route id | `ItemResponse<TrainerResponsePrivateDTO>` | `/admin/trainers` | `AdminTrainersPage` | `AdminTrainersStore` | `unblockAdminTrainer` | COMPLETE |
| `GET /api/worktime/` | `Public/TrainerWorkTimeController.php` | `ResolvedWorktimesRequestDTO` | `CollectionResponse<WorkTimeResponseDTO>` | `/admin/trainers/[id]` | `AdminTrainerDetailsPage` | `AdminWorktimesStore` | `getAdminWorktimes` | COMPLETE |
| `GET /api/worktime/{id}/` | `Public/TrainerWorkTimeController.php` | route id | `ItemResponse<WorkTimeResponseDTO>` | n/a | n/a | n/a | n/a | PARTIAL |
| `POST /api/trainers/{id}/worktime/` | `Admin/TrainerWorkTimeController.php` | `CreateWorkTimeRequestDTO` | `ItemResponse<WorkTimeResponseDTO>` | `/admin/trainers/[id]` | `AdminTrainerDetailsPage` | `AdminWorktimesStore` | `createAdminTrainerWorktime` | COMPLETE |
| `PATCH /api/admin/worktime/{id}/` | `Admin/TrainerWorkTimeController.php` | `UpdateWorkTimeRequestDTO` | `ItemResponse<WorkTimeResponseDTO>` | `/admin/trainers/[id]` | `AdminTrainerDetailsPage` | `AdminWorktimesStore` | `updateAdminWorktime` | COMPLETE |
| `DELETE /api/admin/worktime/{id}/` | `Admin/TrainerWorkTimeController.php` | route id | `204` | `/admin/trainers/[id]` | `AdminTrainerDetailsPage` | `AdminWorktimesStore` | `deleteAdminWorktime` | COMPLETE |
| `GET /api/bookings/` | `Admin/BookingController.php` | `ResolvedBookingsRequestDTO` | `CollectionResponse<BookingAdminResponseDTO>` | `/admin/bookings` | `AdminBookingsPage` | `AdminBookingsStore` | `getAdminBookings` | COMPLETE |
| `GET /api/bookings/{id}/` | `Admin/BookingController.php` | route id | `ItemResponse<BookingAdminResponseDTO>` | `/admin/bookings/[id]` | `AdminBookingDetailsPage` | `AdminBookingsStore` | `getAdminBooking` | COMPLETE |
| `POST /api/clients/{id}/bookings/` | `Admin/BookingController.php` | `BookingRequestDTO` | `ItemResponse<BookingAdminResponseDTO>` | `/admin/clients/[id]` | `AdminClientDetailsPage` | `AdminBookingsStore` | `createAdminClientBooking` | COMPLETE |
| `POST /api/bookings/{id}/cancel/` | `Admin/BookingController.php` | route id | `ItemResponse<BookingAdminResponseDTO>` | `/admin/bookings`, `/admin/bookings/[id]` | `AdminBookingsPage`, `AdminBookingDetailsPage` | `AdminBookingsStore` | `cancelAdminBooking` | COMPLETE |
| `GET/POST /api/coffee/` | `Admin/BookingController.php` | n/a | debug response | n/a | n/a | n/a | n/a | NOT_FOR_FRONTEND |
| `GET /api/memberships/` | `Admin/MembershipController.php` | `ResolvedMembershipsRequestDTO` | `CollectionResponse<MembershipResponseDTO>` | `/admin/memberships` | `AdminMembershipsPage` | `AdminMembershipsStore` | `getAdminMemberships` | COMPLETE |
| `GET /api/memberships/{id}/` | `Admin/MembershipController.php` | route id | `ItemResponse<MembershipResponseDTO>` | `/admin/memberships/[id]` | `AdminMembershipDetailsPage` | `AdminMembershipsStore` | `getAdminMembership` | COMPLETE |
| `POST /api/clients/{id}/membership/` | `Admin/MembershipController.php` | `CreateMembershipRequestDTO` | `ItemResponse<MembershipResponseDTO>` | `/admin/clients/[id]` | `AdminClientDetailsPage` | `AdminMembershipsStore` | `createAdminClientMembership` | COMPLETE |
| `POST /api/memberships/{id}/cancel/` | `Admin/MembershipController.php` | route id | `ItemResponse<MembershipResponseDTO>` | `/admin/memberships`, `/admin/memberships/[id]` | membership pages | `AdminMembershipsStore` | `cancelAdminMembership` | COMPLETE |
| `POST /api/memberships/{id}/freeze/` | `Admin/MembershipController.php` | route id | `ItemResponse<MembershipResponseDTO>` | `/admin/memberships`, `/admin/memberships/[id]` | membership pages | `AdminMembershipsStore` | `freezeAdminMembership` | COMPLETE |
| `POST /api/memberships/{id}/unfreeze/` | `Admin/MembershipController.php` | route id | `ItemResponse<MembershipResponseDTO>` | `/admin/memberships`, `/admin/memberships/[id]` | membership pages | `AdminMembershipsStore` | `unfreezeAdminMembership` | COMPLETE |
| `POST /api/memberships/{id}/renew/` | `Admin/MembershipController.php` | route id | `ItemResponse<MembershipResponseDTO>` | `/admin/memberships`, `/admin/memberships/[id]` | membership pages | `AdminMembershipsStore` | `renewAdminMembership` | COMPLETE |
| `POST /api/memberships/{id}/terminate/` | `Admin/MembershipController.php` | route id | `ItemResponse<MembershipResponseDTO>` | `/admin/memberships`, `/admin/memberships/[id]` | membership pages | `AdminMembershipsStore` | `terminateAdminMembership` | COMPLETE |
| `GET /api/membership/plans/` | `Public/MembershipPlanController.php` | `GetMembershipPlansRequestDTO` | `CollectionResponse<MembershipPlanResponseDTO>` | `/admin/membership-plans` | `AdminMembershipPlansPage` | `AdminMembershipPlansStore` | `getAdminMembershipPlans` | COMPLETE |
| `GET /api/membership/plans/{id}/` | `Public/MembershipPlanController.php` | route id | `ItemResponse<MembershipPlanResponseDTO>` | `/admin/membership-plans/[id]` | `AdminMembershipPlanDetailsPage` | `AdminMembershipPlansStore` | `getAdminMembershipPlan` | COMPLETE |
| `POST /api/membership/plans/` | `Admin/MembershipPlanController.php` | `CreateMembershipPlanRequestDTO` | `ItemResponse<MembershipPlanResponseDTO>` | `/admin/membership-plans` | `AdminPlanCreateForm` | `AdminMembershipPlansStore` | `createAdminMembershipPlan` | COMPLETE |
| `PATCH /api/membership/plans/{id}/` | `Admin/MembershipPlanController.php` | `UpdateMembershipPlanRequestDTO` | `ItemResponse<MembershipPlanResponseDTO>` | `/admin/membership-plans/[id]` | `AdminMembershipPlanDetailsPage` | `AdminMembershipPlansStore` | `updateAdminMembershipPlan` | COMPLETE |
| `DELETE /api/membership/plans/{id}/` | `Admin/MembershipPlanController.php` | route id | `204` | `/admin/membership-plans` | `AdminMembershipPlansPage` | `AdminMembershipPlansStore` | `deleteAdminMembershipPlan` | COMPLETE |
| `GET /api/payments/` | `Admin/PaymentController.php` | `ResolvedPaymentsRequestDTO` | `CollectionResponse<PaymentResponseDTO>` | `/admin/payments` | `AdminPaymentsPage` | `AdminPaymentsStore` | `getAdminPayments` | COMPLETE |
| `GET /api/payments/{id}/` | `Admin/PaymentController.php` | route id | `ItemResponse<PaymentResponseDTO>` | `/admin/payments/[id]` | `AdminPaymentDetailsPage` | `AdminPaymentsStore` | `getAdminPayment` | COMPLETE |
| `GET /api/training/types/` | `Public/TrainingTypeController.php` | `GetTrainingTypesRequestDTO` | `CollectionResponse<TrainingTypeResponseDTO>` | `/admin/training-types` | `AdminTrainingTypesPage` | `AdminTrainingTypesStore` | `getAdminTrainingTypes` | COMPLETE |
| `GET /api/training/types/{id}/` | `Public/TrainingTypeController.php` | route id | `ItemResponse<TrainingTypeResponseDTO>` | `/admin/training-types/[id]` | `AdminTrainingTypeDetailsPage` | `AdminTrainingTypesStore` | `getAdminTrainingType` | COMPLETE |
| `POST /api/training/types/` | `Admin/TrainingTypeController.php` | `CreateTrainingTypeRequestDTO` | `ItemResponse<TrainingTypeResponseDTO>` | `/admin/training-types` | `AdminTrainingTypeCreateForm` | `AdminTrainingTypesStore` | `createAdminTrainingType` | COMPLETE |
| `PATCH /api/training/types/{id}/` | `Admin/TrainingTypeController.php` | `UpdateTrainingTypeRequestDTO` | `ItemResponse<TrainingTypeResponseDTO>` | `/admin/training-types/[id]` | `AdminTrainingTypeDetailsPage` | `AdminTrainingTypesStore` | `updateAdminTrainingType` | COMPLETE |
| `POST /api/training/types/{id}/photo/` | `Admin/TrainingTypeController.php` | multipart `photo` | `ItemResponse<TrainingTypeResponseDTO>` | `/admin/training-types/[id]` | `AdminTrainingTypeDetailsPage` | `AdminTrainingTypesStore` | `uploadAdminTrainingTypePhoto` | COMPLETE |
| `DELETE /api/training/types/{id}/` | `Admin/TrainingTypeController.php` | route id | `204` | `/admin/training-types` | `AdminTrainingTypesPage` | `AdminTrainingTypesStore` | `deleteAdminTrainingType` | COMPLETE |
| `GET /api/admin/trainings/` | `Admin/TrainingController.php` | `ResolvedTrainingsRequestDTO` | `CollectionResponse<TrainingResponseDTO>` | `/admin/trainings` | `AdminTrainingsPage` | `AdminTrainingsStore` | `getAdminTrainings` | COMPLETE |
| `PATCH /api/admin/trainings/{id}/` | `Admin/TrainingController.php` | `TrainingUpdateRequestDTO` | `ItemResponse<TrainingResponseDTO>` | `/admin/trainings` | `AdminTrainingsPage` | `AdminTrainingsStore` | `updateAdminTraining` | COMPLETE |
| `POST /api/admin/trainings/{id}/cancel/` | `Admin/TrainingController.php` | route id | `ItemResponse<TrainingResponseDTO>` | `/admin/trainings` | `AdminTrainingsPage` | `AdminTrainingsStore` | `cancelAdminTraining` | COMPLETE |
| `POST /api/admin/trainings/{id}/complete/` | `Admin/TrainingController.php` | route id | `ItemResponse<TrainingResponseDTO>` | `/admin/trainings` | `AdminTrainingsPage` | `AdminTrainingsStore` | `completeAdminTraining` | COMPLETE |

Contract notes:

- Admin list filters, sorting, page and limit are stored in URL query parameters.
- Empty query values are omitted before API calls.
- Boolean filters serialize only as `true` or `false` when selected.
- Photo/image uploads use multipart field `photo` and do not set `Content-Type` manually.
- Mutations synchronize from the response or refetch authoritative backend data; critical optimistic updates are not used.
- Training detail route is intentionally absent because `Admin/TrainingController.php` exposes no admin GET by ID.
- Import progress remains `BLOCKED_BY_API` because no user-facing import job status endpoint exists.

## Not For Frontend

| Endpoint or operation | Reason | Status |
| --- | --- | --- |
| `POST /api/webhooks/stripe/` | Stripe server-to-server webhook; browser must never call it | NOT_FOR_FRONTEND |
| `GET/POST /api/coffee/` | debug/easter-egg route, not an administrative business function | NOT_FOR_FRONTEND |
| Messenger handlers under `symfony/src/*/MessageHandler` | async backend processing only | NOT_FOR_FRONTEND |
| CLI commands under `symfony/src/*/Command` and scheduled cleanup | operational backend tasks only | NOT_FOR_FRONTEND |
| Import processing internals under `ImportJob*` services/handlers | backend-only; only queue endpoint is browser-facing | NOT_FOR_FRONTEND |

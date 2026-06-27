# API Coverage

Inventory date: 2026-06-27. Source of truth: Symfony controllers, DTOs, resolvers, queries, mappers, voters, and security attributes under `symfony/src`. Frontend scope is limited to `nextjs/`.

Global contract notes:

- All listed Symfony routes include the trailing slash shown in the URL.
- Authenticated browser calls use HttpOnly cookies through `apiClient`; public catalog calls use `publicApiClient`.
- Collection endpoints commonly support `page`, `itemsPerPage`, `sort`, and domain filters through request DTO resolvers/query DTOs. Frontend wrappers must omit empty or undefined query values.
- Date/time fields are serialized by Symfony DTO mappers as strings from PHP `DateTimeInterface` values; frontend must treat them as backend timezone data and avoid client-only timezone reinterpretation for mutations.
- Money values are numeric decimal values in response/request DTOs; Stripe intent creation returns a client secret and must never call the Stripe webhook from the browser.
- Upload endpoints use multipart `file` fields; frontend must not set `Content-Type` manually and must enforce backend-compatible mime/size checks before submit.

Coverage statuses:

- COMPLETE: endpoint is available through UI and typed frontend integration.
- PARTIAL: wrapper or UI exists, but the full contract is not covered.
- MISSING: frontend integration is absent.
- NOT_FOR_FRONTEND: browser must not call this endpoint.
- BLOCKED_BY_API: safe frontend implementation needs an API contract change.

## Authentication

| Endpoint | Contract | Frontend coverage | Status |
| --- | --- | --- | --- |
| `POST /api/login/` | `LoginUserRequestDTO` -> `LoginUserResponseDTO`; sets auth cookies; 400/401/422/429/500 possible | `nextjs/src/api/auth/auth.api.ts`, `AuthStore`, authorization scene | COMPLETE |
| `POST /api/refresh/` | cookie refresh; no request body | `apiClient` automatic refresh | COMPLETE |
| `POST /api/logout/` | clears auth cookies | `AuthStore` | COMPLETE |
| `POST /api/client/registration/` | `CreateClientRequestDTO`; creates inactive client | registration scene | COMPLETE |
| `POST /api/clients/activate/` | `ClientActivateRequestDTO` | activate route/form | COMPLETE |
| `GET /api/auth/me/` | `CurrentUserResponseDTO`; `ROLE_USER` | auth store/session UX | COMPLETE |

## Public

| Endpoint | Contract | Frontend coverage | Status |
| --- | --- | --- | --- |
| `GET /api/trainers/` | public trainer collection; `GetTrainersRequestDTO`, sort/filter/pagination | public trainers catalog and home sections | COMPLETE |
| `GET /api/trainers/{id}/` | `TrainerResponseDTO` | trainer details page | COMPLETE |
| `GET /api/membership/plans/` | `GetMembershipPlansRequestDTO`, sort/filter/pagination | membership plan catalog/home | COMPLETE |
| `GET /api/membership/plans/{id}/` | `MembershipPlanResponseDTO` | membership plan details | COMPLETE |
| `GET /api/training/types/` | `GetTrainingTypesRequestDTO`, sort/filter/pagination | training types catalog/home | COMPLETE |
| `GET /api/training/types/{id}/` | `TrainingTypeResponseDTO` | training type details | COMPLETE |
| `GET /api/worktime/` | `GetWorktimesRequestDTO`, sort/filter/pagination | worktime catalog and booking form | COMPLETE |
| `GET /api/worktime/{id}/` | `WorkTimeResponseDTO` | worktime details | COMPLETE |

## Contact

| Endpoint | Contract | Frontend coverage | Status |
| --- | --- | --- | --- |
| `POST /api/contact/` | `ContactRequestDTO`: `name` required max 100, `email` required email max 254, `message` required max 2000; response `204`; errors `422`, `429`, `503` | `nextjs/src/api/contact/contact.api.ts`, `nextjs/src/scenes/contactUs/index.tsx` | COMPLETE |

Contact chain covered in this PR:

`nextjs/src/app/page.tsx` -> `nextjs/src/scenes/contactUs/index.tsx` -> React Hook Form -> `submitContactRequest` -> `apiClient` -> `POST /api/contact/` -> `ContactController::submit()` -> `ContactRequestDTO`.

## Client

| Endpoint group | Routes | Frontend coverage | Status |
| --- | --- | --- | --- |
| Profile | `GET/PATCH/DELETE /api/me/`, `POST /api/me/visit/`, `POST /api/me/topup/` | client profile scenes/stores; top-up initiates payment | PARTIAL |
| Bookings | `GET/POST /api/me/bookings/`, `GET /api/me/bookings/{id}/`, `POST /api/me/bookings/{id}/cancel/` | bookings list/details/create/cancel | COMPLETE |
| Memberships | `GET /api/me/memberships/`, `GET /api/me/memberships/{id}/`, `POST /api/me/membership/`, cancel/freeze/unfreeze/renew/terminate actions | membership list/details/actions | COMPLETE |
| Payments | `GET /api/me/payments/`, `GET /api/me/payments/{id}/`, `POST /api/payments/{id}/intent/` | payments catalog/details and Stripe intent flow | COMPLETE |

## Trainer

| Endpoint group | Routes | Frontend coverage | Status |
| --- | --- | --- | --- |
| Profile | `GET/PATCH/DELETE /api/trainer/me/`, `POST /api/trainer/me/photo/` | trainer personal profile forms | COMPLETE |
| Worktimes | `GET/POST /api/trainer/me/worktime/`, `PATCH/DELETE /api/worktime/{id}/` | trainer worktime UI/store | COMPLETE |
| Trainings | `GET /api/me/trainings/`, `GET/PATCH /api/trainings/{id}/`, `POST /api/trainings/{id}/cancel/`, `POST /api/trainings/{id}/complete/` | trainer trainings catalog/details/actions | COMPLETE |
| Payments | `GET /api/trainer/payments/`, `GET /api/trainer/payments/{id}/` | no trainer payment route found in `nextjs/src/app`; profile links to `/me/payments` which is client payment UI | MISSING |

## Admin

Admin routes require `ROLE_ADMIN`; only an `admin` route shell exists in frontend. These are independent domains and should be implemented in separate PRs.

| Endpoint group | Routes | Frontend coverage | Status |
| --- | --- | --- | --- |
| Clients | `GET/POST /api/clients/`, `GET/PATCH/DELETE /api/clients/{id}/`, restore/block/unblock/visit, `POST /api/import/clients/` | no admin clients UI/wrapper | MISSING |
| Trainers | `GET /api/admin/trainers/`, `GET /api/admin/trainers/{id}/`, `POST /api/trainers/`, `PATCH/DELETE /api/trainers/{id}/`, photo/restore/block/unblock | no admin trainers UI/wrapper | MISSING |
| Bookings | `GET /api/bookings/`, `GET /api/bookings/{id}/`, `POST /api/clients/{id}/bookings/`, cancel; `GET/POST /api/coffee/` debug/admin-only route | no admin bookings UI/wrapper | MISSING |
| Memberships | `GET /api/memberships/`, `GET /api/memberships/{id}/`, `POST /api/clients/{id}/membership/`, cancel/freeze/unfreeze/renew/terminate | no admin memberships UI/wrapper | MISSING |
| Membership plans | `POST /api/membership/plans/`, `PATCH/DELETE /api/membership/plans/{id}/` | public read UI only; no admin mutation UI | MISSING |
| Payments | `GET /api/payments/`, `GET /api/payments/{id}/` | no admin payments UI/wrapper | MISSING |
| Training types | `POST /api/training/types/`, `PATCH/DELETE /api/training/types/{id}/`, `POST /api/training/types/{id}/photo/` | public read UI only; no admin mutation UI | MISSING |
| Trainings | `GET /api/admin/trainings/`, `PATCH /api/admin/trainings/{id}/`, cancel/complete | no admin trainings UI/wrapper | MISSING |
| Worktimes | `POST /api/trainers/{id}/worktime/`, `PATCH/DELETE /api/admin/worktime/{id}/` | no admin worktime UI/wrapper | MISSING |

## Not For Frontend

| Endpoint or operation | Reason | Status |
| --- | --- | --- |
| `POST /api/webhooks/stripe/` | Stripe server-to-server webhook; browser must never call it | NOT_FOR_FRONTEND |
| Messenger handlers under `symfony/src/*/MessageHandler` | async backend processing only | NOT_FOR_FRONTEND |
| CLI commands under `symfony/src/*/Command` and scheduled cleanup | operational backend tasks only | NOT_FOR_FRONTEND |
| Import processing internals under `ImportJob*` services/handlers | admin import endpoint may be frontend-facing, but handlers are backend-only | NOT_FOR_FRONTEND |

## Next Recommended Domain

`Trainer payments` is the next smallest user-facing gap: Symfony exposes `GET /api/trainer/payments/` and `GET /api/trainer/payments/{id}/`, but the frontend currently has no dedicated trainer payments route/wrapper/store.

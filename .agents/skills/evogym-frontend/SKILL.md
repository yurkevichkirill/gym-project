---
name: evogym-frontend
description: Implement or review EvoGym Next.js frontend UI in the existing project style. Use for pages, scenes, forms, cards, filters, sorting, pagination, responsive layouts, loading/empty/error states, and frontend API integration under nextjs/. Do not use for backend-only, infrastructure, or generic non-EvoGym tasks.
---

# EvoGym frontend implementation skill

Use this skill whenever a task creates, changes, or reviews frontend code in `nextjs/`.

## Required preparation

1. Read the repository root `AGENTS.md` and every additional `AGENTS.md` that applies to the files being changed.
2. Inspect the real route, scene, shared UI, hook/store, API wrapper, types, and API client involved in the task.
3. When data is involved, trace the complete chain:
   `page/route -> scene/component -> hook/store -> API wrapper -> API client -> Symfony route -> controller -> request/response DTO`.
4. Do not invent routes, fields, enums, stores, hooks, components, dependencies, or backend behavior.
5. Modify only `nextjs/` unless the current user request explicitly permits another scope. Reading `symfony/` to verify the API contract is allowed.

## Visual source of truth

Before styling, read [references/ui-reference.md](references/ui-reference.md) and inspect the current source files listed there.

Use two canonical visual families:

- **Authenticated cabinet UI:** `/me`, especially `nextjs/src/scenes/clientPersonal/client/index.tsx`, `nextjs/src/shared/Section.tsx`, and the client personal sections.
- **Catalog and data controls:** the trainers block on `/`, especially `nextjs/src/scenes/ourTrainers/index.tsx`, `Trainers.tsx`, and `OurTrainer.tsx`.

The repository files are the source of truth. The reference document is a navigation aid, not permission to copy stale values blindly.

When examples conflict:

1. Prefer reusable exports from `nextjs/src/shared`.
2. Prefer the newer consistent section/card patterns over isolated legacy class strings.
3. Preserve the existing page family: cabinet screens should look like `/me`; public catalogs should look like `OurTrainers`.
4. Reuse the design language, not existing bugs or accessibility gaps.

## Layout rules

### Cabinet pages and sections

- Use the warm neutral page background and established top spacing from `/me`.
- Keep the main content centered, responsive, and width-constrained; do not create full-width desktop forms without a project precedent.
- Compose pages from vertically separated sections rather than one oversized component.
- Reuse `Section` from `@/shared/Section` for standard cabinet groups.
- Use responsive stacks first, then enhance with `sm:` or `md:` grids and rows.
- Keep section actions in the section header when they affect the whole section; keep item actions inside the item card.

### Catalogs, filters, and sorting

- Follow `OurTrainers` for filter toggles, collapsible filter panels, responsive form grids, result counts, refresh indicators, empty/error states, and pagination placement.
- Store filters, sort, page, and limit in URL search parameters whenever the API supports them.
- Remove empty/default query parameters rather than serializing blank, `undefined`, or `null` values.
- Reset `page` when filters, sorting, or page size changes.
- Use server-side filtering, sorting, and pagination when exposed by the API.
- Abort or invalidate stale requests and prevent older responses from replacing newer state.

## Color, typography, and surface rules

- Use only the established Tailwind theme and semantic colors from `nextjs/src/app/globals.css` unless the task explicitly requires a new token.
- Primary text and brand-dark surfaces use the existing gray palette; do not introduce arbitrary hex colors in components.
- Use `secondary-500` for the main positive/action emphasis and `primary-500` for active, hover, focus, and destructive emphasis where the current UI does so.
- Use white or translucent-white cards on `gray-20` page backgrounds, subtle `gray-100` borders, and restrained shadows.
- Use rounded surfaces consistently: larger radii for sections/cards, smaller radii for dense form controls when matching the catalog UI.
- Keep headings bold and compact. Use the existing `DM Sans` body typography and `Montserrat` only where an existing component already establishes it.

## Reusable UI primitives

Before writing new class strings, check for an existing shared component or exported class constant.

Prefer these existing exports from `@/shared/Section` when applicable:

- `Section`
- `primaryActionClassName`
- `secondaryActionClassName`
- `previewCardClassName`
- `statusBadgeClassName`
- `loadingStateClassName`
- `emptyStateClassName`
- `errorStateClassName`
- `successStateClassName`

Also inspect and reuse relevant components from `nextjs/src/shared/ui`, including state, confirmation, and pagination components, before creating alternatives.

Do not create a parallel button, card, modal, status, pagination, or form-control system for one task.

## Forms and controls

- Use React Hook Form for API-backed forms unless the existing local component pattern clearly requires controlled state.
- Model only real request DTO fields and real enum values.
- Match the filter input/select styling documented in `references/ui-reference.md` for dense catalog controls.
- Show field validation beside the field and a general API error at form level.
- Use visible labels, correct input types, `inputMode` where useful, and meaningful placeholders only when they add information.
- Disable repeat submission and preserve/recover user input after API errors.
- Do not manually set `Content-Type` for `FormData`.

## Buttons and interactions

- Every button must have an explicit `type`.
- Primary actions should match `primaryActionClassName`; secondary/navigation actions should match `secondaryActionClassName` unless a documented variant is required.
- Include keyboard-visible focus styles and disabled states.
- Confirm dangerous or irreversible operations with the existing `ConfirmDialog` pattern.
- Do not perform critical optimistic updates. Synchronize from the mutation response or refetch authoritative backend data.
- Use `framer-motion` only when it improves continuity and follows an existing pattern. Respect reduced-motion preferences for collapsible or entrance animations.

## Cards, lists, and statuses

- Use `previewCardClassName` for compact cabinet list items.
- Use subtle borders and spacing to separate metadata from actions.
- Use responsive `grid` layouts for comparable cards and `flex flex-col gap-*` for heterogeneous content.
- Status labels must come from real frontend enum/display helpers; do not duplicate backend enum strings across components.
- Render dates, money, nullable fields, and image URLs through existing formatting/resolution helpers.
- Use `next/image` and meaningful `alt` text for content images; provide a stable empty-image state.

## Loading, empty, error, and refresh behavior

Every remote-data view must cover the states that apply:

- initial loading;
- loaded empty;
- recoverable error with retry;
- background refresh without removing already loaded content;
- not found or access denied when relevant.

Use the existing shared state components or `Section` state class constants. Add `role="status"`, `aria-live`, or `role="alert"` where the existing patterns demonstrate them. Never expose backend stack traces or internal error details.

## Responsive and accessible behavior

- Start with a mobile vertical layout; add multi-column layouts at existing project breakpoints.
- Avoid fixed widths that overflow small screens.
- Keep interactive targets approximately the established `min-h-10` or `min-h-11` size.
- Preserve logical DOM order when the visual layout changes.
- Use labels, headings, `aria-expanded`, `aria-controls`, dialog semantics, and descriptive link/button labels where appropriate.
- Do not rely on color alone to communicate status.

## Architecture and safety constraints

- Preserve Next.js App Router conventions and add `'use client'` only when hooks, browser APIs, MobX observation, or client interactions require it.
- Reuse MobX stores, existing hooks, API wrappers, clients, and types.
- Do not fetch in render or introduce duplicate requests.
- Guard against stale updates, race conditions, uncleaned timers/subscriptions, and repeated mutation submission.
- Frontend role/status checks are UX only and never replace backend authorization.
- Do not store or decode HttpOnly authentication tokens in browser storage.
- Do not call payment webhooks from the browser.
- Do not add dependencies until `nextjs/package.json` and `nextjs/pnpm-lock.yaml` have been checked and the dependency is necessary.
- Do not use `any`, `@ts-ignore`, `@ts-expect-error`, or broad lint suppression when proper typing is possible.

## Implementation workflow

1. State the diagnosis, minimal plan, and expected files.
2. Classify the UI as cabinet, catalog, or a deliberate combination of both.
3. Inspect the canonical reference files and current shared primitives.
4. Verify the real API contract and permission model.
5. Implement the smallest cohesive change while preserving surrounding architecture and visual style.
6. Review mobile, keyboard, loading, empty, error, retry, disabled, and destructive-action behavior.
7. Run from `nextjs/`:
   - `pnpm lint`
   - `pnpm typecheck`
   - `pnpm build`
8. Report exact changed files, the frontend-to-Symfony chain, checks actually run, and remaining risks or blockers.

Do not claim a check or CI workflow passed unless its concrete result was inspected.
# EvoGym frontend UI reference

This document points Codex to the current project sources that define the desired frontend style. Always inspect the current files before implementing changes because the repository code remains authoritative.

## Canonical routes

### Authenticated cabinet: `/me`

Route and composition:

- `nextjs/src/app/me/page.tsx`
- `nextjs/src/scenes/personal/index.tsx`
- `nextjs/src/scenes/clientPersonal/client/index.tsx`

The client cabinet page establishes this outer shell:

```tsx
<div className="bg-gray-20 pt-32 pb-20">
    <div className="mx-auto flex w-11/12 max-w-5xl flex-col gap-6 sm:w-5/6">
        {/* cabinet sections */}
    </div>
</div>
```

Use it as the default reference for authenticated profile, account, booking, membership, payment, and management pages.

Important cabinet examples:

- `nextjs/src/scenes/clientPersonal/client/Client.tsx`
- `nextjs/src/scenes/clientPersonal/client/VisitSection.tsx`
- `nextjs/src/scenes/clientPersonal/bookings/index.tsx`
- `nextjs/src/scenes/clientPersonal/bookings/Booking.tsx`
- `nextjs/src/scenes/clientPersonal/membership/index.tsx`
- `nextjs/src/scenes/clientPersonal/payment/index.tsx`

### Public trainer catalog on `/`

Route and composition:

- `nextjs/src/app/page.tsx`
- `nextjs/src/scenes/ourTrainers/index.tsx`
- `nextjs/src/scenes/ourTrainers/Trainers.tsx`
- `nextjs/src/scenes/ourTrainers/OurTrainer.tsx`

Use this block as the reference for:

- section introductions;
- filter/sort toggles;
- collapsible filters;
- URL-backed filter state;
- result counts and refresh indicators;
- public catalog grids;
- empty/error/retry states;
- pagination below results;
- restrained entrance animations.

## Theme tokens

Source: `nextjs/src/app/globals.css`.

Current project colors:

```css
--color-gray-20: #F8F4EB;
--color-gray-50: #EFE6E6;
--color-gray-100: #DFCCCC;
--color-gray-500: #5E0000;
--color-primary-100: #FFE1E0;
--color-primary-300: #FFA6A3;
--color-primary-500: #FF6B66;
--color-secondary-400: #FFCD5B;
--color-secondary-500: #FFC132;
```

Typography:

- body: `DM Sans`;
- optional display family already configured: `Montserrat`;
- default body text color: `text-gray-500`.

Project breakpoints configured in the Tailwind theme:

- `xs`: 480px;
- `sm`: 768px;
- `md`: 1060px.

Do not add raw component hex colors when an existing theme token expresses the intended role.

## Shared cabinet primitives

Source: `nextjs/src/shared/Section.tsx`.

### Standard section

```tsx
<section className="rounded-3xl border border-gray-100 bg-white/95 p-5 shadow-sm sm:p-6">
```

Header pattern:

```tsx
<div className="mb-5 flex flex-col gap-3 border-b border-gray-50 pb-4 sm:flex-row sm:items-start sm:justify-between">
    <div className="flex items-center gap-3">
        <span className="h-7 w-1 rounded-full bg-secondary-500" aria-hidden="true" />
        <h3 className="text-xl font-bold text-gray-500">Section title</h3>
    </div>
</div>
```

Prefer the actual `Section` component rather than reproducing this markup.

### Primary action

Use `primaryActionClassName`:

```text
inline-flex min-h-11 items-center justify-center rounded-xl bg-secondary-500 px-5 py-2.5 text-sm font-semibold text-gray-900 transition hover:bg-primary-500 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 disabled:cursor-not-allowed disabled:opacity-50
```

### Secondary action

Use `secondaryActionClassName`:

```text
inline-flex min-h-10 items-center justify-center rounded-xl border border-gray-100 bg-white px-4 py-2 text-sm font-semibold text-gray-500 transition hover:border-primary-300 hover:bg-primary-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500
```

### Preview card

Use `previewCardClassName`:

```text
rounded-2xl border border-gray-100 bg-white p-4 text-gray-900 shadow-sm transition hover:border-primary-300 hover:shadow-md focus-within:border-primary-300 focus-within:shadow-md
```

### Standard state surfaces

Use the exports from `@/shared/Section` instead of restating them:

- `loadingStateClassName`: dashed subtle neutral card;
- `emptyStateClassName`: dashed warm neutral card;
- `errorStateClassName`: red bordered alert surface;
- `successStateClassName`: emerald bordered success surface;
- `statusBadgeClassName`: compact rounded status pill base.

## Cabinet composition patterns

### Summary metric cards

Reference: `nextjs/src/scenes/clientPersonal/client/VisitSection.tsx`.

```tsx
<div className="grid items-stretch gap-4 sm:grid-cols-2">
    <div className="flex min-h-32 flex-col rounded-2xl border border-gray-100 bg-gray-20/70 p-4">
        <p className="text-xs font-semibold uppercase text-gray-600">Label</p>
        <p className="mt-2 text-xl font-bold text-gray-900">Value</p>
        <p className="mt-auto pt-3 text-sm text-gray-600">Supporting text</p>
    </div>
</div>
```

### List card actions

Reference: `nextjs/src/scenes/clientPersonal/bookings/Booking.tsx`.

- Metadata appears above.
- Status badge aligns to the opposite side with wrapping support.
- Actions are separated by `border-t border-gray-50` and start after `mt-4 pt-4`.
- Use `flex flex-wrap gap-3` so actions remain usable on narrow screens.

### Section-level navigation

Reference: bookings and memberships overview sections.

- Put “View all” or history links in the `Section` `action` slot.
- Keep counts next to the navigation label when already available from pagination metadata.
- Keep preview lists short on the cabinet overview; route complete datasets to dedicated pages.

## Trainer catalog control patterns

Source: `nextjs/src/scenes/ourTrainers/index.tsx`.

### Section shell

```text
mx-auto min-h-full w-5/6 scroll-mt-24 py-20
```

Use the exact current section width only for public landing-page sections. Dedicated app pages should generally use their established page container.

### Filter toolbar

The toolbar uses:

```text
mb-8 flex flex-wrap items-center justify-between gap-4 border-y border-gray-100 py-4
```

Filter toggle behavior:

- explicit `type="button"`;
- `aria-expanded` and `aria-controls`;
- icon plus text;
- active-state badge when URL filters exist;
- minimum interactive height;
- focus ring with project colors.

### Filter panel

The panel is a collapsible form with:

```text
mb-10 border-b border-gray-100 pb-6
```

Form grid:

```text
grid gap-5 sm:grid-cols-2 lg:grid-cols-3
```

Labels:

```text
flex flex-col gap-2 text-sm font-semibold text-gray-500
```

Inputs and selects:

```text
rounded-md border border-gray-100 bg-gray-20 px-3 py-2 font-normal text-gray-500 transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20
```

Optional placeholder style:

```text
placeholder:text-gray-500/60
```

Field errors:

```text
font-normal text-primary-500
```

Actions:

- primary apply action uses `bg-secondary-500`, then `hover:bg-primary-500 hover:text-white`;
- clear action uses a neutral border and subtle hover background;
- stack vertically on mobile and switch to a wrapping row at `sm`.

When adding new catalog filters, preserve the existing React Hook Form and URL synchronization pattern rather than adding independent local-only filters.

### Result feedback

- Show total result count before the grid when pagination metadata exists.
- During background refresh, keep existing cards visible and reduce opacity rather than replacing them with a blank loader.
- Use `role="status"` with `aria-live="polite"` for refresh messages.
- Show retryable errors without destroying previously loaded data.

### Trainer grid and cards

Current grid:

```text
grid grid-cols-[repeat(auto-fit,minmax(250px,300px))] justify-center gap-10
```

Card image:

```text
relative aspect-[3/4] overflow-hidden rounded-2xl border bg-gray-100
```

Use `next/image`, `object-cover`, a useful `sizes` value, meaningful alt text, and a visible fallback when no image is available.

The current trainer card has a few isolated class choices that are not canonical shared primitives. Preserve the visual intent, but do not copy invalid/nonstandard sizing or omit focus behavior in new components.

## Shared UI to inspect before creating alternatives

Directory: `nextjs/src/shared/ui`.

At minimum, check for existing implementations of:

- loading state;
- empty state;
- error/retry state;
- pagination controls;
- confirmation dialog.

Known components used by the references include:

- `LoadingState`
- `EmptyState`
- `ErrorState`
- `PaginationControls`
- `ConfirmDialog`

## Choosing the correct reference

Use the `/me` family when the task is primarily:

- authenticated profile or cabinet UI;
- account details;
- booking, membership, payment, worktime, or management summaries;
- forms embedded in a card/section;
- status-heavy personal data.

Use the trainer-catalog family when the task is primarily:

- a searchable/filterable collection;
- server-side sorting or pagination;
- a public catalog;
- URL-backed data exploration;
- a responsive image-card grid.

Combine them deliberately for authenticated list pages: use the `/me` page shell and section surfaces, but adopt the trainer catalog's filter, URL, request-race, result-feedback, and pagination behavior.

## Quality checklist

Before finishing a frontend change, verify:

- real API fields and enum values are used;
- no fake data or invented endpoint is present;
- shared components and class exports were considered first;
- mobile and desktop layouts both work;
- keyboard focus is visible;
- buttons have explicit types;
- dangerous actions are confirmed;
- loading, empty, error, retry, and refresh states are covered;
- filters/sort/page are in the URL where applicable;
- stale requests cannot overwrite newer results;
- authoritative data is synchronized after mutations;
- `pnpm lint`, `pnpm typecheck`, and `pnpm build` results are reported honestly.
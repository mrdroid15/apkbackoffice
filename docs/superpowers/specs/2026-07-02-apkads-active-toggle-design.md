# Apkads active toggle — design

Date: 2026-07-02
Status: approved (API-off behavior defaulted to 404 per recommendation; user AFK at confirmation)

## Goal

Add a per-ad on/off toggle so an apkad can be disabled without deleting it.
When an ad is off, the public lookup API behaves as if the ad does not exist.

## Decisions

- Scope: the toggle disables the **whole ad**, not just the image
  (user corrected an earlier image-only proposal).
- Admin UX: toggle in the create/edit form **and** a clickable toggle column
  in the list table for one-click enable/disable.
- API behavior when off: `GET /api?package=X` returns the existing
  404 `package not found` response. Apps already handle 404, so no client
  changes are needed.

## Changes

1. **Migration** — add `is_active` boolean to `apkads`, `default(true)`.
   Existing rows stay live; no backfill needed.
2. **Model `app/Models/Apkads.php`** — add `is_active` to `$fillable`,
   cast to `boolean`.
3. **Form `app/Filament/Resources/Apkads/Schemas/ApkadsForm.php`** —
   `Toggle::make('is_active')` labeled "Active", default on. Image upload
   stays required.
4. **Table `app/Filament/Resources/Apkads/Tables/ApkadsTable.php`** —
   `ToggleColumn::make('is_active')` for inline flipping.
5. **API `routes/web.php`** — the `/api` lookup adds
   `->where('is_active', true)`; a disabled ad falls into the existing
   404 branch.

## Out of scope

- The privacy-policy page (`/{slug}`) stays available for disabled ads —
  store listings may still link to it.
- No global kill switch.

## Verification

- Toggle off → `GET /api?package=X` returns 404.
- Toggle on → normal 200 response with presigned image URL.
- New ads default to active.

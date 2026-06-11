# Meal Planner — Feature Plan

## Already Done

These two TODO items are fully implemented (checked off in README):

- **Add a shopping list** — `GET /grocery-list/{mealPlan}` renders `GroceryList.jsx`, which aggregates and groups all ingredients from a meal plan's recipes with client-side checkbox tracking.
- **Allow a user to choose from different units of measurement for an ingredient** — `IngredientUnit` model, `ingredient_units` table, and `UnitConverter` utility are all in place. The `recipe_ingredients` pivot stores quantity and unit per recipe.

---

## Remaining Work

### 1. Normalize ingredients (partial — wire it up)

**Status**: `IngredientNormalizationService` and `NormalizeIngredientsCommand` exist but the service is never called during recipe creation or import.

**Plan**:
- Inject `IngredientNormalizationService` into `RecipeImportService` and call `normalize()` before ingredient lookup/creation.
- Also call it in the `RecipeController::store` and `update` paths when ingredients are persisted.
- The artisan command can stay for backfilling existing data.
- Consider deduplication: after normalization, check if an ingredient with that name already exists before inserting a new row (currently likely creates duplicates).

**Effort**: Small — the hard part is already written.

---

### 2. Double-sided recipe image import

**Status**: `TesseractOcrService` processes a single image. The import form accepts one file.

**Plan**:
- Update the import form (`RecipeImport.jsx`) to accept multiple image uploads (two at most, labelled "front" and "back").
- Update `RecipeImportController` and `RecipeImportService` to accept an array of image paths, run OCR on each, and concatenate the raw text before passing it to the parsing logic.
- No changes to the OCR service itself — just process both images sequentially and merge text.
- Add a short UI hint ("Upload both sides of the recipe card").

**Effort**: Small-to-medium. The parsing logic already handles long text; merging two OCR outputs is the main change.

---

### 3. Recipe rating system

**Status**: Nothing exists.

**Plan**:
- Migration: `recipe_ratings` table — `id`, `recipe_id` (FK), `user_id` (FK), `rating` (tinyint 1–5), `timestamps`. Unique constraint on `(recipe_id, user_id)` so one rating per user per recipe.
- Model: `RecipeRating` with `belongsTo(Recipe)` and `belongsTo(User)`.
- Add `hasMany(RecipeRating)` to `Recipe`, plus a computed average rating attribute.
- Controller: `RecipeRatingController` with a single `store`/`update` action (`POST /recipes/{recipe}/rating`). Upsert on the unique constraint.
- UI: Star rating component on the recipe show page. Show the user's current rating (pre-filled) and the recipe's average below it.
- Policy: Any authenticated user can rate any recipe (including their own, or restrict — your call).

**Effort**: Medium. Straightforward but touches model, migration, controller, and frontend.

---

### 4. Instagram import

**Status**: Not implemented.

**Assessment**: This is the riskiest item on the list. Instagram's API requires app review and only exposes content from accounts that have explicitly authorized your app — it won't work for scraping arbitrary recipes. Web scraping public Instagram posts is fragile, frequently breaks with DOM changes, and violates Instagram's ToS.

**Realistic options**:
- **Skip or defer** — the value is low relative to the maintenance burden.
- **Clipboard paste** — let users paste the caption text from an Instagram post into a text field, then parse it the same way URL imports parse description text. No scraping, no API. Framed as "paste recipe text."
- **If you still want scraping** — use a headless browser (e.g., Browsershot/Puppeteer) to render the public post page and extract the caption. Accept it will break periodically.

**Recommendation**: Implement the clipboard/paste approach. It's low-effort, avoids ToS issues, and covers the actual use case (someone sees a recipe on Instagram and wants to save it).

---

### 5. NativePHP Mobile / Hosted Backend POC

**Status**: NativePHP Mobile package is installed and a hosted-backend POC entry point exists at `/mobile`.

**Assessment**: For the current product shape, Laravel Cloud should stay the source of truth. The first POC should prove the existing hosted Inertia app works well on a phone before committing to an offline/on-device Laravel architecture.

**Implemented POC path**:
- `/mobile` is authenticated, so a guest opening the mobile entry point is sent through the existing login flow and redirected back after login.
- The page links the target mobile journeys: create recipe, image import, meal plans, and grocery list.
- NativePHP `start_url` now defaults to `/mobile`.
- The accidental NativePHP SQLite/session override is now opt-in via `NATIVEPHP_USE_LOCAL_STORAGE=true`, so setting `NATIVEPHP_APP_ID` alone does not move the app away from the hosted backend.
- Mobile hamburger navigation now exposes the main app sections instead of only Dashboard.

**Plan**:
1. Deploy this branch to a Laravel Cloud preview and open `/mobile` on iOS and Android browsers.
2. Run the core POC path manually: login, create recipe, image import, create/open meal plan, open grocery list.
3. Decide whether a native shell is actually needed after the mobile web pass.
4. If a NativePHP shell is still desired, decide whether to track generated `nativephp/` projects and patch them for a remote webview, or use NativePHP for on-device Laravel only.

**Main risk**: NativePHP Mobile's generated shell is oriented around running Laravel on-device via `php://127.0.0.1`. A Laravel Cloud hosted-backend shell is a different architecture and likely requires generated iOS/Android project changes or a different webview wrapper.

**Effort**: Small for the hosted mobile web POC; medium if turning this into a native app-store shell.

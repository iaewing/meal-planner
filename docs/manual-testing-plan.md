# Manual Testing Plan

Minimal surface-area smoke test for recently shipped features. One logged-in session, ~15 minutes.

## Prerequisites

1. Run migrations: `php artisan migrate`
2. Start the app and queue worker (URL imports are queued):
   ```bash
   php artisan serve
   php artisan queue:work
   ```
3. Log in as one user.
4. Have ready:
   - One recipe website URL (e.g. AllRecipes)
   - One Instagram post or reel URL with a recipe caption (optional)
   - Two photos of a recipe card (front + back), or any two clear recipe images

## Smoke Checks

Do these in order. Stop when all pass.

### 1. Import + toast

| # | Action | Pass if |
|---|--------|---------|
| 1 | **Recipes → Import → Queue Import** with a recipe URL | Green toast: import queued |
| 2 | Wait for queue worker, refresh **Recipes** | New recipe appears |
| 3 | **Import → Recipe Card** with front + back images | Redirects to edit; green toast on landing |

### 2. Recipe detail

Open any recipe with whole-number ingredients (e.g. 8 cups).

| # | Action | Pass if |
|---|--------|---------|
| 4 | View recipe show page | Quantities show as `8`, not `8.00` |
| 5 | Click stars to rate (e.g. 4★) | Stars stick after refresh |
| 6 | **Recipes** index | Rated recipe shows stars on its card |

### 3. Meal plan → grocery list

Use a meal plan with **two recipes sharing an ingredient in different units** (e.g. one uses `tbsp`, one uses `cup`).

| # | Action | Pass if |
|---|--------|---------|
| 7 | Open meal plan → **Grocery List** | Shared ingredient merged into one readable line (e.g. `1.19 cup`, not `19 tbsp`) |
| 8 | Same list | Whole amounts show as `8`, not `8.00` |

### 4. Instagram import (optional)

| # | Action | Pass if |
|---|--------|---------|
| 9 | Queue import with an Instagram post/reel URL | Toast on queue **or** recipe appears after worker runs; if caption is not public, failure is clear (no silent garbage recipe) |

## Coverage

If all checks pass, you have verified:

- URL and image import
- Toast notifications
- Quantity formatting
- Recipe ratings
- Grocery aggregation and display units
- Instagram best-effort import

## Failure Follow-ups

Only run these if a smoke check fails.

| Failure | One extra check |
|---------|-----------------|
| URL import never appears | Queue worker running? Check `storage/logs/laravel.log` |
| Image import errors | Both front **and** back selected? |
| Grocery list wrong | Same ingredient, compatible units (`cup`/`tbsp`, not `cup`/`g`) |
| Instagram fails | Try a different public post; many require login |
| Rating does not stick | Hard refresh; confirm you own the recipe |

## Out of Scope

Not required for this pass:

- Auth registration / password reset
- Profile edits
- Ingredient admin UI
- Meal plan randomizer
- Recipe search
- Multi-image upload on edit
- Production S3 image uploads

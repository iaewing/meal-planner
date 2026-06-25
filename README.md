## Implementation Plan

### Done

- [x] Get all tests passing
- [x] Add a meal plan randomizer
- [x] Add recipe importing from recipe websites
- [x] Allow a user to add an image to an existing recipe
- [x] Allow a user to click anywhere on the recipe card
- [x] Add recipe search
- [x] Fix production image uploads

### In Progress

- [ ] Normalize ingredients
  - Deduplicate ingredients by normalized name.
  - Decide how strict normalization should be for variants such as "tomato", "tomatoes", and "cherry tomatoes".
  - Keep enough original text to preserve recipe readability.

- [ ] Finish grocery list correctness
  - Current grocery list UI and route exist, but aggregation is naive.
  - Convert compatible units before summing quantities.
  - Group incompatible units separately instead of silently combining them.
  - Handle duplicate recipes in a meal plan.
  - Add feature tests for grocery list aggregation.

- [ ] Finish ingredient unit support
  - Current ingredient unit models, migrations, UI, and converter exist.
  - Ensure recipe ingredients consistently reference `ingredient_unit_id`.
  - Define fallback behavior for unknown or incompatible conversions.
  - Add controller tests for adding units and changing defaults.

### Later

- [ ] Allow users to create a recipe from images of a double-sided recipe card
- [ ] Add recipe rating system
- [ ] Investigate Instagram import

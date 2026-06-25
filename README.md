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
  - [x] Extract grocery aggregation out of the controller.
  - [x] Convert compatible units before summing quantities.
  - [x] Group incompatible units separately instead of silently combining them.
  - [x] Handle duplicate recipes in a meal plan.
  - [x] Add feature tests for grocery list aggregation.
  - [ ] Choose display units deliberately instead of using the first unit encountered.

- [ ] Finish ingredient unit support
  - Current ingredient unit models, migrations, UI, and converter exist.
  - [x] Ensure recipe ingredients created or edited in the app reference `ingredient_unit_id` when the unit is known.
  - [x] Ensure imported recipes use the same recipe ingredient attach path.
  - Define fallback behavior for unknown or incompatible conversions.
  - Add controller tests for adding units and changing defaults.

### Later

- [ ] Allow users to create a recipe from images of a double-sided recipe card
- [ ] Add recipe rating system
- [ ] Investigate Instagram import
- [ ] Add toast for when a recipe upload/import completes
- [ ] Intelligently display ingredient quantities. Current `8.00 Tablespoons Unsalted Butter` can and should be displayed to the user as `8 Tablespoons Unsalted Butter`

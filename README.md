## Implementation Plan

### Done

- [x] Get all tests passing
- [x] Add a meal plan randomizer
- [x] Add recipe importing from recipe websites
- [x] Allow a user to add an image to an existing recipe
- [x] Allow a user to click anywhere on the recipe card
- [x] Add recipe search
- [x] Fix production image uploads
- [x] Finish grocery list correctness
- [x] Finish ingredient unit support

### In Progress

- [ ] Normalize ingredients
  - Deduplicate ingredients by normalized name.
  - Decide how strict normalization should be for variants such as "tomato", "tomatoes", and "cherry tomatoes".
  - Keep enough original text to preserve recipe readability.

### Later

- [ ] Allow users to create a recipe from images of a double-sided recipe card
- [ ] Add recipe rating system
- [ ] Investigate Instagram import
- [ ] Add toast for when a recipe upload/import completes
- [ ] Intelligently display ingredient quantities. Current `8.00 Tablespoons Unsalted Butter` can and should be displayed to the user as `8 Tablespoons Unsalted Butter`

# Testing Guide

This document provides instructions for running the test suite for the Dashboard Feed and Post components.

## Prerequisites

- PHP 8.2+
- Composer
- Database configured in `.env.testing` (or uses sqlite memory by default in `phpunit.xml`)

## Setup

1.  Install dependencies:
    ```bash
    composer install
    ```

2.  (Optional) Create a testing database if not using sqlite:
    ```bash
    touch database/database.sqlite
    ```

## Running Tests

To run the full test suite:

```bash
php artisan test
```

To run only the tests for the Dashboard components:

```bash
php artisan test tests/Feature/Livewire/Dashboard
```

## Test Coverage

### Feeds Component (`Tests\Feature\Livewire\Dashboard\FeedsTest`)
- **Rendering:** Verifies the component loads successfully.
- **Initial Load:** Checks if recent feeds are loaded correctly.
- **Pagination:** Verifies `loadMore` appends feeds.
- **Comments:**
    - Adding new comments.
    - Replying to comments (nested).
    - Deleting comments (with authorization checks).
    - Editing comments (with authorization checks).
- **Reactions:** Toggling emojis on feeds.
- **Validation:** Enforcing required fields and max length.

### Posts Component (`Tests\Feature\Livewire\Dashboard\PostsTest`)
- **Rendering:** Verifies component render.
- **Pinned Posts:** Ensures pinned posts appear in the "Pins" section.
- **Regular Posts:** Ensures pinned posts are excluded from the main list.
- **Selection:** Verifies clicking a post dispatches the `open-post-panel` event.
- **Caching:** Verifies `selectPost` utilizes caching for performance.

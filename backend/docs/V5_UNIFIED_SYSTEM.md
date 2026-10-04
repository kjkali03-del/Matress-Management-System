# Wonder Godoro Point — V5 Unified System

## What changed

- Unified Customer Inbox colors with the main Wonder Godoro Point navy/gold/white system.
- Removed the Inbox's separate ivory/maroon visual direction through a final system-theme override layer.
- Added visible Inbox conversation/message entrance animations with reduced-motion support.
- Added a single `public/css/wgp-system.css` design-token layer used by Admin, Login and Inbox.
- Added stronger system-level reveal and navigation motion.
- Improved Product Catalogue persistence flow: products are created in a database transaction and the user is redirected to the saved product record.
- Added Product Catalogue search and Active/Inactive filters so saved products can be found later.
- Added a persistence notice to the Product form.
- Added a Product Catalogue feature test covering database persistence and later retrieval.
- Orders now load saved active catalogue products with category/stock information and auto-fill product name, size and price when a saved product is selected.
- Added V5 asset cache-busting to prevent stale CSS/JS after deployment.

## Database note

Products are stored in the `products` database table. The application already runs `php artisan migrate --force` during the production container startup. Product images are stored through Laravel's public disk; database product information remains the source of truth for catalogue access.

## Verification

PHP syntax checks passed for the changed controllers, model and product feature test. Full PHPUnit execution was not available in this package because `vendor/` is intentionally excluded from the deployment source package.

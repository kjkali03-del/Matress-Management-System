# Wonder Godoro Point — Upgrade Notes

This version upgrades the existing Laravel application without replacing the existing CRM/WhatsApp foundation.

## Main changes

- Real Delivery module with assignment and status workflow.
- Real Reports module covering orders, revenue, estimated COGS, gross profit, customers, messages and delivery.
- Real Settings health page for WhatsApp, Reverb, queue and database configuration.
- Product management now supports SKU, cost price, stock quantity and reorder level.
- Orders can reference catalogue products while preserving the existing product-name snapshot.
- Customer records gain lead source, district, area and next follow-up date fields.
- Incoming WhatsApp processing now accepts text, images, video, audio, documents, locations, interactive replies and buttons.
- WhatsApp status webhooks update messages through sent/delivered/read/failed states.
- Automation runs are logged.
- No-reply automations now have a scheduled command: `php artisan automation:process-followups`.
- Laravel Scheduler runs the follow-up processor every minute.
- Navigation now exposes Pipeline, Automations and Tags.
- Missing Product CRUD and Automation edit pages have been added.
- Editable system flowchart is available at `docs/system-flowchart.mmd`.

## Migration

Run:

```bash
php artisan migrate
php artisan optimize:clear
```

For production, run the scheduler/queue processes required by your deployment.

## Security

The distributable project intentionally excludes the local `.env` file. Copy `.env.example` to `.env` and provide your own credentials.

## Important compatibility note

Existing order fields (`product_name`, `product_size`, `unit_price`) remain in place. The new `product_id` relationship is optional so existing historical orders remain valid.

## Admin UI CSS pass

Added `public/css/admin-ui.css` and loaded it from `resources/views/layouts/admin.blade.php`.
The shared visual layer standardizes the admin sidebar, content width, page headings, cards, tables, forms, buttons, customer tables, product empty states, reports and settings while preserving the existing Blade markup and functionality.

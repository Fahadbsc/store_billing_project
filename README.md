# Store Order & Inventory Mini-System

Laravel API (+ a small billing screen) for recording counter orders against a product catalogue and keeping stock in sync.

## Setup

```bash
composer create-project laravel/laravel store-orders   # Laravel 11/12, PHP 8.2+
cd store-orders
php artisan install:api                                # creates routes/api.php + registers it
# copy this repo's files over the project (overwrites routes/api.php, routes/web.php, DatabaseSeeder)

cp .env.example .env && php artisan key:generate
# .env: QUEUE_CONNECTION=database (default in L11+), optional LOW_STOCK_THRESHOLD=10
php artisan migrate --seed
php artisan serve                  # http://127.0.0.1:8000  -> billing screen
php artisan queue:work             # processes the confirmation-email job (watch storage/logs/laravel.log)
php artisan test                   # PHPUnit
```

## API

| Method | Endpoint | Notes |
|---|---|---|
| POST | `/api/orders` | `{customer:{email,name?}, items:[{product_id,quantity}], amount_paid?}` → 201 order, 422 on validation / insufficient stock |
| GET | `/api/orders?email=` | Paginated order history; 404 if the email is unknown |
| GET | `/api/products/low-stock?threshold=` | Defaults to `config('inventory.low_stock_threshold')` (`LOW_STOCK_THRESHOLD`) |
| GET | `/api/products` | Catalogue (used by the UI dropdown) |
| GET | `/api/customers/lookup?email=` | Name auto-fill for the UI |

Insufficient stock returns:
```json
{"message":"Insufficient stock for one or more products.",
 "errors":{"items":[{"product_id":3,"name":"Bread","requested":2,"available":1}]}}
```

## Design

- **Schema**: `customers`, `products`, `orders`, `order_items`. `order_items` snapshots `unit_price` / `tax_percent` / line amounts, so editing a product later never changes past orders. Money columns are `decimal`, never float.
- **Layers**: thin controllers → `StoreOrderRequest` (validation) → `OrderService` (transaction, stock, totals) → `PriceCalculator` (pure money maths) → `OrderResource`.
- **Concurrency (requirement 7)**: inside one DB transaction the service does `SELECT … FOR UPDATE` on the ordered products, checks stock, then deducts. A second request for the last unit blocks until the first commits, then sees `stock = 0` and fails cleanly with 422. Rows are locked in `id` order so multi-product orders can't deadlock each other. Order, items, stock and the new customer commit or roll back together.
- **Queue**: `SendOrderConfirmation` (`ShouldQueue`, 3 tries) is dispatched after commit and only logs the "email". A failed/rolled-back order never triggers a confirmation.
- **Money**: integer paise internally; tax rounded half-up per line, so lines always sum to the bill.

## Assumptions

1. **Tax is per product** (`tax_percent`) and **line total includes tax** (wireframe shows ₹100 → ₹108 at 8%). The wireframe's summary figures (₹210 / ₹17.20 / ₹227.20) don't add up with its own rows, so I treated them as placeholders.
2. **Existing email**: the existing customer is used and a different submitted name is ignored. **New email**: `customer.name` is required. Emails are trimmed and lowercased.
3. **"Low stock" = strictly below the threshold**; `?threshold=` overrides the config per request.
4. **Same product on several lines** is merged before the stock check.
5. **Amount given / balance** (wireframe) is optional. If sent, it must cover the grand total, otherwise 422 with a full rollback. Change breakdown uses ₹500/200/100/50/20/10/5/2/1 notes and coins; leftover paise are reported separately.
6. **"Emails PDF to customer"** from the wireframe is out of scope: the brief asks only for a simulated confirmation, so the job logs it.
7. **No auth** on the API (not requested). Stock is never restored on cancellation because there is no cancel flow.
8. Quantity limited to 1–10000 per line, max 50 lines per order.

## Testing

`php artisan test` covers: happy path (totals, stock deducted, job pushed), insufficient stock (no partial deduction, nothing persisted, no job), last-unit-two-orders, merged duplicate lines, existing/new customer rules, underpaid rollback, validation, history, low-stock, the job itself and `PriceCalculator`.

SQLite (the test default) serialises writes, so the PHPUnit test proves the *logic* (second order fails once stock is 0). To prove the *lock* with real parallel requests use MySQL and run:
```bash
# set one product's stock to 1, then:
scripts/race-test.sh <product_id>   # expect one 201, nine 422
```
# Bagisto Architecture

## Purpose

Bagisto is a Laravel-based open-source e-commerce platform. It provides a modular package
structure where each domain (Cart, Checkout, Products, Orders, Customers, etc.) lives in a
separate Composer package under `packages/Webkul/`.

## Directory Structure

```
packages/Webkul/
  Shop/                   # Customer-facing storefront (controllers, views, routes)
    src/
      Http/Controllers/   # Cart, Checkout, Product, Account controllers
      Resources/          # Blade views, lang files, assets
      Routes/             # Storefront route definitions
  Admin/                  # Admin panel (order management, catalog, config)
    src/
      Http/Controllers/
        Sales/            # Order, Invoice, Shipment, Refund management
        Catalog/          # Product and category management
        Settings/         # Channel, currency, locale, tax settings
      DataGrids/          # Server-side paginated data grid definitions
  Checkout/               # Cart domain logic, Cart facade, repositories
  Sales/                  # Order, invoice, shipment domain and repositories
  Product/                # Product, variant, attribute domain
  Customer/               # Customer and address domain
  Core/                   # Shared helpers, channel resolution, currency formatting
  Inventory/              # Stock management
  Taxation/               # Tax category and rate calculation
```

## Key Design Decisions

- **Laravel service container**: All domain services are bound via `ServiceProvider` classes
  in each package. The `Cart` facade (Webkul\Checkout\Facades\Cart) wraps the cart service.
- **Repository pattern**: Each aggregate uses a repository class extending
  `Webkul\Core\Eloquent\Repository` (based on Prettus L5-Repository) for data access.
- **Event-driven extensibility**: Blade/feature events follow the pattern
  `{domain}.{action}.before` / `.after` (e.g. `sales.order.comment.create.after`).
  Third-party packages hook in via `Event::listen()` in their service providers.
- **DataGrids**: Admin listing pages use the `DataGrid` abstraction which handles
  server-side filtering, sorting, and pagination via AJAX.
- **MagicAI integration**: LLM-powered features (product descriptions, checkout messages)
  are opt-in via configuration and gracefully degrade when disabled or when the AI service
  throws an exception.
- **Channel architecture**: Multi-store is handled via Channel models. Prices, currencies,
  locales, themes, and inventory sources are channel-scoped.

## Extension Points

- Create a new Composer package under `packages/YourVendor/` with a `ServiceProvider`.
- Extend any repository by binding a custom class in the service container.
- Add admin menu items via the `admin.layouts.nav.left` Blade component.
- Hook into order lifecycle with event listeners on `sales.order.*` events.
- Override any Blade view by publishing and modifying vendor views.
- Add custom DataGrids by extending `Webkul\DataGrid\DataGrid`.

## Dependency Flow

```
Shop / Admin HTTP Request
  └─> Controller (e.g. OnepageController, OrderController)
        └─> Cart Facade (Webkul\Checkout\Facades\Cart)
              └─> CartRepository (Eloquent + cache)
              └─> Cart validation / total collection
        └─> OrderRepository::create()
              └─> Event: sales.order.created.before / .after
              └─> InventorySource deduction
              └─> Invoice / Shipment creation (if auto)
```

## PHP Version Requirements

PHP 8.2+. Laravel 11.x. All new files must declare `strict_types=1`.

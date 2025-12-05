````markdown
# Laravel E-Commerce Wishlist API

## Overview

A scalable, RESTful API backend for managing user wishlists. Built with **Laravel 12**, focusing on strict typing, separation of concerns, and data integrity.

Architecture prioritizes maintainability and reliability: business logic is isolated in a service layer, controllers stay thin, and strict database constraints ensure consistency and resilience against race conditions.

---

## Architecture Decisions

- **Service Layer Pattern:** All business logic is encapsulated in `WishlistService`. This keeps code testable, maintainable, and reusable (for scheduled tasks, CLI, etc).
- **Database Integrity:** `wishlists` table uses a composite unique index (`user_id` + `product_id`) to prevent duplicates, even under heavy concurrency.
- **Idempotency:** Adding a product uses `firstOrCreate`. Double submissions and retries won't create duplicates or raise errors.
- **API Resources:** All responses are formatted using `JsonResource` classes, decoupling the database structure from the frontend API contract.
- **Scalability:**
  - `/products` endpoint uses pagination for large datasets.
  - Wishlist queries use eager loading (`with('product')`) to eliminate N+1 query problems.
- **Security:** RESTful authentication is powered by Laravel Sanctum for token-based API usage.

---

## Requirements

- PHP >= 8.2
- MySQL 8.0+ or MariaDB
- Composer

---

## Setup Instructions

### 1. Clone & Install

```bash
git clone https://github.com/yeganeh1243/frontier-dental-assessment.git
cd wishlist-api
composer install
```

### 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup

Edit `.env` with your database credentials:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wishlist_api
DB_USERNAME=root
DB_PASSWORD=
```

**Create the database manually:**

```sql
CREATE DATABASE wishlist_api;
```

### 4. Migrate & Seed

Run migrations and sample product seeds:

```bash
php artisan migrate --seed
```

### 5. Run Tests

Run feature and unit tests:

```bash
php artisan test
```

### 6. Start the Server

```bash
php artisan serve
```

---

## API Documentation

### Public Endpoints

#### Register

- **POST** `/api/register`
- **Body:**
  ```json
  {
    "name": "Test",
    "email": "test@test.com",
    "password": "password",
    "password_confirmation": "password"
  }
  ```

#### Login

- **POST** `/api/login`
- **Body:**
  ```json
  {
    "email": "test@test.com",
    "password": "password"
  }
  ```
- **Response:**
  ```json
  { "token": "..." }
  ```

#### List Products

- **GET** `/api/products`
- **Response:** Paginated list of products.

---

### Protected Endpoints

*Requires Header:* `Authorization: Bearer <token>`

| Method   | Endpoint               | Body                | Description                                   |
|----------|------------------------|---------------------|-----------------------------------------------|
| **GET**  | `/api/wishlist`        | None                | Retrieves wishlist items for the user         |
| **POST** | `/api/wishlist`        | `{ "product_id": 1 }` | Adds a product to the wishlist (idempotent)   |
| **DELETE** | `/api/wishlist/{id}` | None                | Removes a product from the wishlist           |

---

## Testing Notes

The suite in `tests/Feature/WishlistTest.php` covers:

- **Authentication:** Only authenticated users can access wishlist endpoints.
- **CRUD Operations:** Add, view, and remove wishlist items.
- **Validation:** Prevents adding non-existent products.
- **Idempotency:** Ensures repeated additions do not create duplicates.



````
# Laravel Flash-Sale Checkout API

This project implements a **Flash-Sale Checkout API** that safely sells a limited-stock product under high concurrency. It ensures correctness of stock, supports short-lived holds, pre-payment orders, and idempotent payment webhooks.

**Target:** Laravel 12, MySQL (InnoDB), Redis (or any Laravel cache driver).

---

## Key Features

- Product endpoint with real-time stock availability  
- Temporary holds that auto-expire (~2 minutes)  
- Order creation from valid holds  
- Idempotent and out-of-order safe payment webhook  
- High concurrency handling to avoid overselling  

---

## Assumptions & Invariants

- **Single Product:** Seeded for simplicity; can extend to multiple products  
- **Stock Integrity:**
- total stock = available stock + held stock + confirmed orders
- - **Holds:**  
- Immediately reduce available stock  
- Expire automatically after ~2 minutes  
- Can only be used once  
- **Orders:**  
- Only valid, unexpired holds can create an order  
- Status: `pending`, `paid`, `cancelled`  
- **Payment Webhook:**  
- Idempotent using `idempotency_key`  
- Can arrive before order creation  
- Ensures final stock/order state is consistent  

---

## API Endpoints

### 1. Product

**GET** `/api/products/{id}`  

Returns basic product info and accurate available stock.

**Response Example:**
```json
{
"id": 1,
"name": "Limited Edition Item",
"price": 100.0,
"available_stock": 5
}
2. Create Hold

POST /api/holds
Payload:

{ "product_id": 1, "quantity": 2 }


Creates a temporary reservation (~2 minutes).

Success Response:

{
  "hold_id": 7,
  "expires_at": "2025-12-02T18:30:00Z"
}


Notes:

Holds immediately reduce available stock for others

Expired holds automatically release stock
### 3. Create Order

POST /api/orders

Payload Example:

{
  "hold_id": 7
}


Success Response Example:

{
  "order_id": 1,
  "hold_id": 7,
  "status": "pending"
}


Notes:

Only valid, unexpired holds are accepted

Each hold can only be used once

4. Payment Webhook

POST /api/payments/webhook

Payload Example:

{
  "idempotency_key": "abc123",
  "order_id": 1,
  "status": "success"
}


Behavior:

Updates order to paid on success

Cancels order and releases hold on failure

Safe for repeated or out-of-order webhook deliveries

Running the Application

Clone the repository:

git clone <repo-url> flash-sale-api


Install dependencies:

composer install


Set up .env:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=flashsale
DB_USERNAME=root
DB_PASSWORD=
CACHE_DRIVER=redis


Run migrations & seeders:

php artisan migrate --seed


Run the application:

php artisan serve

Cache Setup & Usage (Docker)

This project uses Redis via Docker to cache product availability and improve read performance.

Running Redis with Docker:
docker run -d --name flashsale_redis -p 6379:6379 redis

Laravel Redis Configuration (.env):
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379


Cache Behavior:

Product stock availability is cached for fast reads

Holds immediately reduce cached stock

Expired holds automatically release stock and update cache

Prevents stale or incorrect stock under heavy load

Optional Commands:

# Check Redis cache keys
docker exec -it flashsale_redis redis-cli
keys *

# Clear cache manually
php artisan cache:clear

Testing

Automated tests verify:

Concurrency & Oversell Prevention: Multiple parallel hold attempts at stock boundaries

Hold Expiry: Expired holds automatically return stock availability

Webhook Idempotency: Same idempotency_key multiple times has no duplicate effect

Out-of-Order Webhook Handling: Webhook arrives before order creation

Run Tests:

php artisan test


Ensure you have a .env.testing file configured for your test database.

Logs & Metrics

Logs are stored in storage/logs/laravel.log.
Structured logs capture:

Hold creation & expiry

Stock contention & retries

Webhook deduplication events

Notes

Redis caching improves read performance while keeping stock accuracy

Background jobs ensure holds expire reliably without duplication

Avoids N+1 queries on list endpoints

API only; no UI included


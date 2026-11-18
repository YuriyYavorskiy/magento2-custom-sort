# Perspective LoyaltyEngine for Magento 2

## Overview
**Perspective_LoyaltyEngine** is a high-performance loyalty and cashback engine designed for enterprise-grade, high-load Magento 2 stores and headless applications. It separates write-intensive operations (ledger logging, order accruals) from read-intensive operations (instant customer balance queries) following CQRS and Event-Driven principles.

---

## Architectural Highlights

### 1. Data Layer & Asynchronous Queue (The Background)
- **Immutable Ledger (`loyalty_transactions`)**: Append-only ledger recording all points movements (`accrual`, `redemption`, `refund`, `adjustment`) with a unique `reference_key` constraint. Entries cannot be updated or deleted.
- **RabbitMQ Queue (`loyalty.points.accrual`)**: Order completion does not compute or write points in the synchronous HTTP process. An observer on `sales_order_save_after` publishes lightweight messages `{"order_id": 123, "customer_id": 456}` to RabbitMQ.
- **Idempotent Consumer (`Perspective\LoyaltyEngine\Model\Queue\OrderAccrualConsumer`)**: Processes messages from `loyalty.points.accrual.queue`, calculates store-configured cashback on the adjusted order subtotal, and appends an immutable transaction record.

### 2. High Performance & Real-time Balance (The Speed)
- **Flat Table (`loyalty_balance_index`)**: Contains only `customer_id`, `total_active_points`, and `updated_at`. Balance retrieval is an $O(1)$ single primary key lookup.
- **Mview & Custom Indexer (`loyalty_balance`)**: Subscribes to changes on `loyalty_transactions.customer_id` via MySQL triggers (`loyalty_balance_cl`). In runtime, balance updates are applied atomically via `SELECT ... FOR UPDATE` and `applyDelta()`, while Mview ensures eventual consistency.

### 3. Headless GraphQL API & Batch Loading (The Connectivity)
- **Customer Query**: Extends `Customer` with `loyalty_points` (balance & currency) and `loyalty_transactions` (paginated history).
- **Batch & Deferred Loading (N+1 Prevention)**: Implements both `BatchResolverInterface` (`BatchResponse`) and `GraphQL\Deferred` (`ValueFactory`) backed by `LoyaltyBatchLoader`, plus `@cache` identity tagging (`LoyaltyBalanceIdentity`). When querying lists of users (e.g. friends, referrals), all customer balances are loaded in **one single SQL query**.
- **Cart Mutations**:
  - `applyLoyaltyPointsToCart(input: ApplyLoyaltyPointsInput!)`: Validates customer ownership, active balance, and max 50% cart limit.
  - `removeLoyaltyPointsFromCart(input: RemoveLoyaltyPointsInput!)`: Clears applied points and recalculates cart totals.

### 4. Checkout, Invoice & Creditmemo Totals (The Money)
- **Quote, Invoice & Creditmemo Total Models (`loyalty_points`)**: Run in the totals collection lifecycle (`sort_order="450"`), deducting points discount from `grand_total` and `base_grand_total` across quotes, invoices, and credit memos.
- **Order Placement, Cancel & Refund**: Transfers loyalty attributes and Extension Attributes to `sales_order` via `etc/fieldset.xml` and `QuoteToOrderLoyaltyTransfer`, logs an atomic `redemption` transaction in `sales_order_save_after`, and handles point returns/reversals on order cancel and credit memo creation.

---

## Database Architecture

### `loyalty_transactions`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `transaction_id` | INT UNSIGNED | PK, Auto Increment | Unique transaction identifier |
| `customer_id` | INT UNSIGNED | Foreign Key (CASCADE) | Customer entity ID |
| `order_id` | INT UNSIGNED | Nullable, Indexed | Related order ID |
| `points` | DECIMAL(12,4) | Signed (+/-) | Points delta (+ for accrual/refund, - for spend) |
| `type` | VARCHAR(32) | Not Null | `accrual`, `redemption`, `refund`, `adjustment` |
| `comment` | VARCHAR(255) | Nullable | Transaction description / order increment ID |
| `reference_key` | VARCHAR(64) | Nullable, UNIQUE | Idempotency key (e.g. `accrual:101`, `redemption:101`) |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Created timestamp (Immutable, no updated_at) |

### `loyalty_balance_index`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `customer_id` | INT UNSIGNED | PK, Foreign Key (CASCADE) | Customer entity ID |
| `total_active_points` | DECIMAL(12,4) | DEFAULT 0.0000 | Fast indexed balance |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Last index timestamp |

---

## GraphQL Examples

### 1. Fetch Current Customer Balance & History
```graphql
query {
  customer {
    email
    loyalty_points {
      balance
      currency_code
    }
    loyalty_transactions(pageSize: 5, currentPage: 1) {
      total_count
      items {
        transaction_id
        points
        type
        comment
        created_at
      }
    }
  }
}
```

### 2. Batch Query (e.g., Friends List with BatchResponse Loading)
```graphql
query {
  loyalty_customers_balance(customerIds: [1, 2, 3, 4, 5]) {
    customer_id
    loyalty_points {
      balance
      currency_code
    }
  }
}
```
*(All 5 customer balances are resolved with a single `SELECT ... WHERE customer_id IN (1, 2, 3, 4, 5)`).*

### 3. Apply Loyalty Points to Cart
```graphql
mutation {
  applyLoyaltyPointsToCart(input: {
    cart_id: "your_masked_cart_id",
    points: 25.0
  }) {
    applied_points
    cart {
      id
      prices {
        grand_total {
          value
          currency
        }
      }
    }
  }
}
```

### 4. Remove Loyalty Points from Cart
```graphql
mutation {
  removeLoyaltyPointsFromCart(input: {
    cart_id: "your_masked_cart_id"
  }) {
    cart {
      id
      prices {
        grand_total {
          value
          currency
        }
      }
    }
  }
}
```

---

## CLI & Testing Commands (Warden)

### 1. Run Unit Tests
```bash
warden env exec php-fpm vendor/bin/phpunit app/code/Perspective/LoyaltyEngine/Test/Unit
```

### 2. Run Grumphp Quality Check
```bash
vendor/bin/grumphp run
```

### 3. Run Adobe Magento Coding Standards (PHPCS)
```bash
warden env exec php-fpm vendor/bin/phpcs --standard=Magento2 app/code/Perspective/LoyaltyEngine/Model app/code/Perspective/LoyaltyEngine/Api app/code/Perspective/LoyaltyEngine/Observer
```

### 4. Run Message Queue Consumer
```bash
warden env exec php-fpm bin/magento queue:consumers:start loyaltyPointsAccrualConsumer
```

### 5. Reindex Loyalty Balance
```bash
warden env exec php-fpm bin/magento indexer:reindex loyalty_balance
```

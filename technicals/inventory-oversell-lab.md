# Inventory Oversell Lab

## 1. Mục tiêu

Inventory Oversell Lab là scenario đầu tiên trong bộ Laravel Production Pattern Comparison Lab.

Mục tiêu của lab này là giúp người học hiểu:

```text
Vì sao code checkout nhìn đơn giản vẫn có thể sai trong production?
Vì sao nhiều request cùng mua 1 sản phẩm có thể tạo nhiều order?
Atomic update giải quyết vấn đề như thế nào?
Vì sao cần invariant, movement ledger và reset deterministic trong lab?
```

---

## 2. Tình huống thực tế

Giả sử hệ thống có:

```text
Product A
Stock = 1
```

Có nhiều tình huống thực tế khiến nhiều request checkout xảy ra gần như cùng lúc:

```text
- User double click nút "Buy"
- Browser retry request
- Mobile network timeout rồi retry
- User mở nhiều tab
- Bot spam checkout
- Flash sale
- Queue/job retry
- Payment callback đến nhiều lần
```

Nếu code checkout không kiểm soát concurrency, hệ thống có thể tạo nhiều order dù chỉ còn 1 sản phẩm.

Ví dụ:

```text
Stock ban đầu = 1

Request A đọc stock = 1
Request B đọc stock = 1
Request C đọc stock = 1

A tạo order
B tạo order
C tạo order

Kết quả:
Stock chỉ có 1 nhưng tạo 3 orders
```

Đây gọi là:

```text
Oversell
```

---

## 3. Vì sao oversell nguy hiểm?

Oversell không chỉ là bug kỹ thuật.

Nó có thể gây hậu quả business:

```text
- Bán quá số lượng hàng thật
- Phải hủy order
- Mất uy tín với khách hàng
- Refund thủ công
- Sai báo cáo doanh thu
- Sai tồn kho
- CSKH phải xử lý khiếu nại
- Dữ liệu kế toán/kho bị lệch
```

Trong hệ thống lớn, oversell có thể trở thành incident production.

---

## 4. Mô hình lab hiện tại

Lab tách thành 2 bên:

```text
Naive
vs
Production
```

Naive dùng table riêng:

```text
naive_inventory_products
naive_inventory_orders
```

Production dùng table riêng:

```text
production_inventory_products
production_inventory_orders
production_inventory_movements
```

Lý do tách table:

```text
- Dễ so sánh hai bên
- Dữ liệu không ảnh hưởng nhau
- Reset từng bên độc lập
- Nhìn được production schema phức tạp hơn naive schema
- Phù hợp mục tiêu học tập
```

---

## 5. Naive schema

```php
Schema::create('naive_inventory_products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->unsignedInteger('stock')->default(0);
    $table->timestamps();
});
```

```php
Schema::create('naive_inventory_orders', function (Blueprint $table) {
    $table->id();

    $table->foreignId('product_id')
        ->constrained('naive_inventory_products')
        ->cascadeOnDelete();

    $table->unsignedInteger('quantity')->default(1);
    $table->string('status')->default('created');

    $table->timestamps();

    $table->index(['product_id', 'created_at']);
});
```

Naive chỉ có:

```text
stock
orders
```

Không có:

```text
reserved_stock
sold_stock
movement ledger
atomic update
idempotency/request key
```

---

## 6. Production schema

```php
Schema::create('production_inventory_products', function (Blueprint $table) {
    $table->id();
    $table->string('name');

    $table->unsignedInteger('stock_on_hand')->default(0);
    $table->unsignedInteger('reserved_stock')->default(0);
    $table->unsignedInteger('sold_stock')->default(0);

    $table->timestamps();
});
```

```php
Schema::create('production_inventory_orders', function (Blueprint $table) {
    $table->id();

    $table->foreignId('product_id')
        ->constrained('production_inventory_products')
        ->cascadeOnDelete();

    $table->unsignedInteger('quantity')->default(1);
    $table->string('status')->default('created');

    $table->string('request_key')->nullable();

    $table->timestamps();

    $table->unique('request_key');
    $table->index(['product_id', 'created_at']);
});
```

```php
Schema::create('production_inventory_movements', function (Blueprint $table) {
    $table->id();

    $table->foreignId('product_id')
        ->constrained('production_inventory_products')
        ->cascadeOnDelete();

    $table->string('type');

    $table->integer('stock_delta')->default(0);
    $table->integer('reserved_delta')->default(0);
    $table->integer('sold_delta')->default(0);

    $table->unsignedInteger('stock_on_hand_after');
    $table->unsignedInteger('reserved_stock_after');
    $table->unsignedInteger('sold_stock_after');

    $table->string('reference_type')->nullable();
    $table->unsignedBigInteger('reference_id')->nullable();

    $table->timestamps();

    $table->index(['product_id', 'created_at']);
});
```

Production có thêm:

```text
stock_on_hand
reserved_stock
sold_stock
request_key
movement ledger
```

---

## 7. Naive implementation

Naive service:

```php
public function order(array $payload = []): LabActionResult
{
    $runMode = $payload['run_mode'] ?? 'single';

    if ($runMode === 'batch_race') {
        return $this->simulateRaceBatch($payload);
    }

    return $this->singleOrder($payload);
}
```

### Single order

```php
private function singleOrder(array $payload = []): LabActionResult
{
    try {
        $product = NaiveInventoryProduct::query()->firstOrFail();

        if ($product->stock < 1) {
            return LabActionResult::failed('Naive: Sold out.');
        }

        usleep((int) ($payload['delay_microseconds'] ?? 300_000));

        $product->stock -= 1;
        $product->save();

        $order = NaiveInventoryOrder::create([
            'product_id' => $product->id,
            'quantity' => 1,
            'status' => 'created',
        ]);

        return LabActionResult::success(
            message: "Naive: Order #{$order->id} created.",
            data: [
                'order_id' => $order->id,
                'stock_after' => $product->fresh()->stock,
            ],
        );
    } catch (Throwable $e) {
        return LabActionResult::failed(
            message: 'Naive: '.$e->getMessage(),
            statusCode: 500,
        );
    }
}
```

Vấn đề nằm ở flow:

```text
Read stock
→ Check stock
→ Delay
→ Save stock
→ Create order
```

Đây là pattern dễ bị race condition.

---

## 8. Vì sao Naive bị lỗi?

Giả sử 3 request cùng chạy:

```text
Request A đọc stock = 1
Request B đọc stock = 1
Request C đọc stock = 1
```

Cả 3 đều pass:

```php
if ($product->stock < 1) {
    return failed;
}
```

Vì tại thời điểm đọc, cả 3 đều thấy:

```text
stock = 1
```

Sau đó cả 3 cùng tạo order.

Đó là lỗi của pattern:

```text
Read → Check → Save
```

Nó không atomic.

---

## 9. Vì sao local có thể không tái hiện bug?

Nếu chạy bằng:

```bash
php artisan serve
```

hoặc môi trường local xử lý request tuần tự, thì browser gửi 20 request nhưng backend có thể xử lý lần lượt.

Khi đó:

```text
Request 1: stock = 1 → success
Request 2: stock = 0 → sold out
Request 3: stock = 0 → sold out
```

Người học sẽ không thấy oversell.

Vì vậy lab có thêm:

```text
Real Requests
Race Simulation
```

---

## 10. Real Requests vs Race Simulation

### Real Requests

```text
Gửi nhiều Ajax request thật từ browser.
```

Ưu điểm:

```text
- Gần thực tế
- Cho thấy môi trường server xử lý request như thế nào
```

Nhược điểm:

```text
- Phụ thuộc local server
- Có thể không tái hiện bug nếu request bị xử lý tuần tự
```

### Race Simulation

```text
Mô phỏng nhiều request cùng đọc stock tại cùng một thời điểm.
```

Ưu điểm:

```text
- Ổn định
- Dễ demo
- Không phụ thuộc PHP worker
- Người học luôn thấy bug
```

Nhược điểm:

```text
- Không phải concurrency thật
- Chỉ dùng để học, không dùng làm bằng chứng benchmark production
```

---

## 11. Naive race simulation

```php
private function simulateRaceBatch(array $payload = []): LabActionResult
{
    try {
        $count = min(
            max((int) ($payload['count'] ?? 5), 1),
            500
        );

        $product = NaiveInventoryProduct::query()->firstOrFail();

        $snapshotStock = $product->stock;

        if ($snapshotStock < 1) {
            return LabActionResult::failed('Naive Batch: Sold out.');
        }

        $createdOrders = [];

        for ($i = 1; $i <= $count; $i++) {
            if ($snapshotStock >= 1) {
                $order = NaiveInventoryOrder::create([
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'status' => 'created',
                ]);

                $createdOrders[] = $order->id;
            }
        }

        $product->update([
            'stock' => 0,
        ]);

        return LabActionResult::success(
            message: "Naive Batch Race: {$count} requests saw stock=1 and created ".count($createdOrders).' orders.',
            data: [
                'created_order_ids' => $createdOrders,
                'orders_created' => count($createdOrders),
                'initial_stock' => $snapshotStock,
                'stock_after' => 0,
            ],
        );
    } catch (Throwable $e) {
        return LabActionResult::failed(
            message: 'Naive Batch Race: '.$e->getMessage(),
            statusCode: 500,
        );
    }
}
```

Điểm quan trọng:

```php
$snapshotStock = $product->stock;
```

Đây là cách mô phỏng nhiều request cùng đọc cùng một snapshot cũ.

---

## 12. Production implementation

Production service dùng:

```text
Atomic update
Request key
Movement ledger
Invariant check
```

Flow chính:

```php
$affected = ProductionInventoryProduct::query()
    ->whereKey($product->id)
    ->whereRaw('(stock_on_hand - reserved_stock) >= 1')
    ->update([
        'reserved_stock' => DB::raw('reserved_stock + 1'),
    ]);

if ($affected === 0) {
    return null;
}
```

Đây là điểm quan trọng nhất.

Thay vì:

```text
Read → Check → Save
```

Production dùng:

```text
Check + Update trong cùng một SQL statement
```

---

## 13. Vì sao atomic update an toàn hơn?

Câu SQL:

```sql
UPDATE production_inventory_products
SET reserved_stock = reserved_stock + 1
WHERE id = ?
AND (stock_on_hand - reserved_stock) >= 1
```

Ý nghĩa:

```text
Chỉ reserve nếu available stock >= 1.
```

Nếu request đầu tiên thành công:

```text
stock_on_hand = 1
reserved_stock = 0

available = 1
```

Sau update:

```text
stock_on_hand = 1
reserved_stock = 1

available = 0
```

Request sau chạy cùng câu SQL:

```text
available = stock_on_hand - reserved_stock = 0
```

Nên:

```text
affected rows = 0
```

Không tạo order.

---

## 14. Production single order flow

```php
private function singleOrder(array $payload = []): LabActionResult
{
    $requestKey = $payload['request_key'] ?? (string) Str::uuid();

    try {
        $order = DB::transaction(function () use ($requestKey) {
            $existing = ProductionInventoryOrder::query()
                ->where('request_key', $requestKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $product = ProductionInventoryProduct::query()->firstOrFail();

            $affected = ProductionInventoryProduct::query()
                ->whereKey($product->id)
                ->whereRaw('(stock_on_hand - reserved_stock) >= 1')
                ->update([
                    'reserved_stock' => DB::raw('reserved_stock + 1'),
                ]);

            if ($affected === 0) {
                return null;
            }

            $product->refresh();

            $order = ProductionInventoryOrder::create([
                'product_id' => $product->id,
                'quantity' => 1,
                'status' => 'created',
                'request_key' => $requestKey,
            ]);

            ProductionInventoryMovement::create([
                'product_id' => $product->id,
                'type' => 'reserve',
                'stock_delta' => 0,
                'reserved_delta' => 1,
                'sold_delta' => 0,
                'stock_on_hand_after' => $product->stock_on_hand,
                'reserved_stock_after' => $product->reserved_stock,
                'sold_stock_after' => $product->sold_stock,
                'reference_type' => ProductionInventoryOrder::class,
                'reference_id' => $order->id,
            ]);

            return $order;
        }, attempts: 3);

        if (! $order) {
            return LabActionResult::failed('Production: Sold out. No order created.');
        }

        return LabActionResult::success(
            message: "Production: Order #{$order->id} created safely.",
            data: [
                'order_id' => $order->id,
                'request_key' => $requestKey,
            ],
        );
    } catch (Throwable $e) {
        return LabActionResult::failed(
            message: 'Production: '.$e->getMessage(),
            statusCode: 500,
        );
    }
}
```

---

## 15. Request key dùng để làm gì?

Production order có:

```php
$table->string('request_key')->nullable();
$table->unique('request_key');
```

Mục tiêu:

```text
Giảm duplicate action khi cùng request key được gửi lại.
```

Trong lab này, request key có vai trò giống idempotency-lite.

Lưu ý:

```text
Đây chưa phải full Idempotency Pattern.
```

Full Idempotency sẽ cần thêm:

```text
idempotency_keys table
request_hash
response cache
processing state
expired_at
same key different payload detection
```

Nhưng với Inventory Oversell Lab, request key đủ để giới thiệu ý tưởng duplicate protection cơ bản.

---

## 16. Movement ledger dùng để làm gì?

Production có bảng:

```text
production_inventory_movements
```

Mỗi lần reserve stock sẽ ghi:

```text
type = reserve
reserved_delta = 1
stock_on_hand_after = 1
reserved_stock_after = 1
sold_stock_after = 0
reference_type = ProductionInventoryOrder
reference_id = order id
```

Mục tiêu:

```text
- Audit lịch sử stock
- Debug tại sao reserved_stock tăng
- Làm reconciliation
- Phục vụ báo cáo kho
```

Nếu chỉ update counter:

```text
reserved_stock = reserved_stock + 1
```

thì sau này rất khó trả lời:

```text
Ai làm tăng?
Tăng khi nào?
Tăng vì order nào?
Có bị lệch không?
```

---

## 17. Invariants đang kiểm tra

Naive:

```text
Stock must not be negative
Orders must not exceed initial stock
```

Production:

```text
Reserved stock must not exceed stock on hand
Orders must not exceed initial stock
```

Invariant là luật luôn phải đúng.

Ví dụ:

```text
orders_count <= initial_stock
reserved_stock <= stock_on_hand
available_stock >= 0
```

Nếu invariant bị phá vỡ, UI hiển thị:

```text
BROKEN
```

Mục tiêu:

```text
Người học không chỉ nhìn response success/fail.
Người học nhìn được state cuối cùng có đúng không.
```

---

## 18. Metrics đang hiển thị

Naive:

```text
stock
orders_count
valid_stock_limit
```

Production:

```text
stock_on_hand
reserved_stock
available_stock
orders_count
valid_stock_limit
```

Chart dùng:

```text
Naive Orders
Production Orders
Valid Stock Limit
```

Khi bấm simulate 20:

```text
Naive Orders = 20
Production Orders = 1
Valid Stock Limit = 1
```

Người học nhìn chart sẽ thấy rõ:

```text
Naive vượt giới hạn
Production giữ đúng giới hạn
```

---

## 19. Reset bằng truncate

Lab reset dùng:

```php
Schema::disableForeignKeyConstraints();

NaiveInventoryOrder::truncate();
NaiveInventoryProduct::truncate();

ProductionInventoryMovement::truncate();
ProductionInventoryOrder::truncate();
ProductionInventoryProduct::truncate();

Schema::enableForeignKeyConstraints();

NaiveInventoryProduct::factory()
    ->oneStock()
    ->create();

ProductionInventoryProduct::factory()
    ->oneStock()
    ->create();
```

Lý do dùng truncate:

```text
- ID reset về #1
- demo dễ nhìn
- data sạch hoàn toàn
- mỗi lần học lại từ trạng thái ban đầu
```

Không nên áp dụng truncate kiểu này trong business production.

Nó chỉ phù hợp cho:

```text
lab
test
demo
local development
```

---

## 20. UI action design

UI chia action thành 2 nhóm:

```text
Real Requests
Race Simulation
```

### Real Requests

```text
1 request
2 requests
5 requests
custom count
```

Dùng để gửi Ajax thật từ browser.

### Race Simulation

```text
Simulate 5
Simulate 20
custom count
```

Dùng để ép bug xuất hiện ổn định.

Điểm này rất quan trọng vì nếu không giải thích, người dùng sẽ nghĩ:

```text
2 request và simulate 20 chỉ khác số lượng
```

Trong thực tế chúng khác bản chất.

---

## 21. Learning Center

Inventory Oversell Lab có Learning Center gồm:

```text
Overview
Code
Sequence
Database
Trade-off
```

### Overview

Giải thích:

```text
Problem
Failure
Solution
Cost
```

### Code

Hiển thị code Naive và Production.

### Sequence

Hiển thị flow request.

### Database

Hiển thị schema liên quan.

### Trade-off

So sánh:

```text
Read → Check → Save
Atomic Update
Stock Movement Ledger
```

---

## 22. Trade-off chính

| Kỹ thuật            | Lợi ích                          | Chi phí                    |
| ------------------- | -------------------------------- | -------------------------- |
| Read → Check → Save | Dễ hiểu, ít code                 | Race condition, oversell   |
| Atomic Update       | Nhanh, an toàn cho rule đơn giản | Khó áp dụng rule phức tạp  |
| DB Transaction      | Gom nhiều thao tác thành unit    | Nếu dài sẽ giữ lock lâu    |
| Request Key         | Giảm duplicate action            | Chưa phải full idempotency |
| Movement Ledger     | Audit/reconcile tốt              | Thêm bảng, thêm code       |
| Invariant Check     | Dễ detect bug                    | Phải định nghĩa luật đúng  |

---

## 23. Khi nào dùng atomic update?

Nên dùng khi:

```text
- Rule đơn giản
- Check và update cùng một row/table
- Cần performance tốt
- Cần tránh race condition
```

Ví dụ:

```text
stock >= quantity
balance >= amount
coupon_remaining > 0
view_count increment
like_count increment
```

Không nên dùng atomic update đơn giản khi:

```text
- Business rule cần check nhiều bảng phức tạp
- Cần xử lý booking time overlap
- Cần hold nhiều resource cùng lúc
- Cần compensation workflow
```

Khi đó có thể cần:

```text
lockForUpdate
unique constraint
exclusion logic
reservation table
saga
queue
distributed lock
```

---

## 24. Điểm cần cải thiện sau này

Inventory Oversell Lab hiện tại là bản học tập.

Có thể nâng cấp thêm:

```text
- Confirm reservation
- Release reservation
- Expiry reservation job
- Sold stock movement
- Reconciliation command
- PHPUnit concurrency test
- Pest test for invariants
- Redis token bucket for flash sale
- Queue per SKU
- Dashboard metrics
```

---

## 25. Test cases nên có

### Reset test

```text
Reset xong:
Naive product id = 1
Naive stock = 1
Naive orders = 0

Production product id = 1
Production stock_on_hand = 1
Production reserved_stock = 0
Production orders = 0
Production movements = 0
```

### Naive simulation test

```text
Given stock = 1
When simulate 20 readers
Then orders_count = 20
And invariant is broken
```

### Production simulation test

```text
Given stock_on_hand = 1
When simulate 20 readers
Then orders_count = 1
And reserved_stock = 1
And available_stock = 0
And invariant is OK
```

### Production duplicate request key test

```text
Given request_key = abc
When call order twice with same request_key
Then only one order is created
```

---

## 26. Kết luận

Inventory Oversell Lab giúp người học hiểu một bài học rất quan trọng:

```text
Code đúng trong request đơn lẻ chưa chắc đúng trong production concurrency.
```

Naive code fail vì:

```text
Read → Check → Save không atomic
```

Production code tốt hơn vì:

```text
Atomic update để DB enforce available stock
Movement ledger để audit
Invariant để detect bug
Request key để giảm duplicate action
```

Nhưng production code cũng có cost:

```text
Nhiều bảng hơn
Nhiều code hơn
Cần hiểu transaction/atomic update/invariant
Cần test kỹ hơn
```

Đó chính là tư duy system design:

```text
Không chỉ code cho chạy.
Phải code để đúng khi có concurrency, retry, failure và production traffic.
```

# How to Add New Lab Scenario

## 1. Mục tiêu của Lab System

Dự án này được thiết kế để học System Design trong Laravel bằng cách so sánh trực quan giữa:

```text
Naive Implementation
vs
Production Implementation
```

Mỗi tình huống sẽ có:

```text
- UI so sánh 2 bên
- API action/reset/state
- Metrics
- Invariants
- Chart visualization
- Learning Center
- Code examples
- Sequence flow
- Database schema
- Trade-off
```

Mục tiêu không phải chỉ demo code chạy được, mà là giúp người học nhìn thấy:

```text
Problem → Failure → Production Solution → Cost → Trade-off
```

---

## 2. Cấu trúc tổng thể

```text
app/
├── DTOs/
│   └── Labs/
│       ├── LabActionResult.php
│       └── LabStateResult.php
│
├── Enums/
│   └── Labs/
│       └── LabMode.php
│
├── Http/
│   ├── Controllers/
│   │   └── Labs/
│   │       ├── LabDashboardController.php
│   │       ├── LabActionController.php
│   │       ├── LabStateController.php
│   │       └── LabResetController.php
│   │
│   └── Requests/
│       └── Labs/
│           └── LabActionRequest.php
│
├── Models/
│   └── Labs/
│       ├── Naive/
│       └── Production/
│
├── Services/
│   └── Labs/
│       ├── Contracts/
│       │   └── LabScenarioContract.php
│       │
│       ├── Core/
│       │   ├── LabScenarioRegistry.php
│       │   ├── LabDatabaseResetService.php
│       │   └── Scenarios/
│       │
│       ├── Naive/
│       └── Production/
│
resources/
├── views/
│   └── labs/
│       └── dashboard.blade.php
│
└── js/
    └── labs/
        ├── api.js
        ├── chart.js
        ├── dashboard.js
        ├── learning-center.js
        ├── scenario-meta.js
        ├── ui.js
        ├── utils.js
```

---

## 3. Mỗi scenario cần có gì?

Một scenario hoàn chỉnh nên có:

```text
1. Naive table
2. Production table
3. Naive model
4. Production model
5. Naive service
6. Production service
7. Scenario coordinator
8. Reset logic
9. Factory
10. Translation metadata
11. Learning Center metadata
12. Registry registration
13. Test cases
```

---

## 4. Contract bắt buộc

Mỗi scenario phải implement:

```php
interface LabScenarioContract
{
    public function key(): string;

    public function title(): string;

    public function subtitle(): string;

    public function description(): string;

    public function actionHint(): string;

    public function howToUse(): array;

    public function learningGoals(): array;

    public function naiveTechniques(): array;

    public function productionTechniques(): array;

    public function actionPresets(): array;

    public function limits(): array;

    public function learningCenter(): array;

    public function action(LabMode $mode, array $payload = []): LabActionResult;

    public function state(LabMode $mode): LabStateResult;

    public function reset(LabMode $mode): LabActionResult;

    public function resetAll(): LabActionResult;
}
```

---

## 5. Quy tắc đặt tên

### Scenario key

Dùng kebab-case:

```text
inventory-oversell
payment-idempotency
booking-concurrency
queue-retry-safe-job
outbox-lost-event
multi-tenant-data-leak
```

### Scenario class

```text
InventoryOversellScenario
PaymentIdempotencyScenario
BookingConcurrencyScenario
QueueRetrySafeJobScenario
OutboxLostEventScenario
```

### Naive service

```text
NaiveInventoryOversellService
NaivePaymentIdempotencyService
NaiveBookingConcurrencyService
```

### Production service

```text
ProductionInventoryOversellService
ProductionPaymentIdempotencyService
ProductionBookingConcurrencyService
```

### Tables

```text
naive_{domain}_{resources}
production_{domain}_{resources}
```

Ví dụ:

```text
naive_inventory_products
naive_inventory_orders

production_inventory_products
production_inventory_orders
production_inventory_movements
```

---

## 6. Quy trình thêm scenario mới

Ví dụ thêm scenario:

```text
Payment Idempotency
```

---

# Step 1: Tạo migration

Naive:

```php
Schema::create('naive_payment_orders', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('amount');
    $table->string('status')->default('pending');
    $table->timestamps();
});

Schema::create('naive_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')
        ->constrained('naive_payment_orders')
        ->cascadeOnDelete();

    $table->unsignedInteger('amount');
    $table->string('status')->default('succeeded');
    $table->timestamps();
});
```

Production:

```php
Schema::create('production_payment_orders', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('amount');
    $table->string('status')->default('pending');
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
});

Schema::create('production_payments', function (Blueprint $table) {
    $table->id();

    $table->foreignId('order_id')
        ->constrained('production_payment_orders')
        ->cascadeOnDelete();

    $table->unsignedInteger('amount');
    $table->string('status')->default('succeeded');
    $table->string('idempotency_key');

    $table->timestamps();

    $table->unique('idempotency_key');
    $table->index(['order_id', 'status']);
});
```

---

# Step 2: Tạo models

```text
app/Models/Labs/Naive/NaivePaymentOrder.php
app/Models/Labs/Naive/NaivePayment.php

app/Models/Labs/Production/ProductionPaymentOrder.php
app/Models/Labs/Production/ProductionPayment.php
```

Model nên dùng:

```php
use HasFactory;

protected $table = '...';

protected $fillable = [...];
```

---

# Step 3: Tạo factories

```text
database/factories/Labs/Naive/NaivePaymentOrderFactory.php
database/factories/Labs/Production/ProductionPaymentOrderFactory.php
```

Factory cần có state deterministic:

```php
public function defaultPending(): static
{
    return $this->state([
        'amount' => 100000,
        'status' => 'pending',
    ]);
}
```

Không dùng random data cho demo reset chính.

---

# Step 4: Tạo Naive Service

```php
final class NaivePaymentIdempotencyService
{
    public function pay(array $payload = []): LabActionResult
    {
        $order = NaivePaymentOrder::query()->firstOrFail();

        $payment = NaivePayment::create([
            'order_id' => $order->id,
            'amount' => $order->amount,
            'status' => 'succeeded',
        ]);

        $order->update([
            'status' => 'paid',
        ]);

        return LabActionResult::success(
            "Naive: Payment #{$payment->id} created.",
            [
                'payment_id' => $payment->id,
            ],
        );
    }
}
```

Naive version nên là:

```text
Code nhìn hợp lý
Dễ hiểu
Nhưng thiếu production protection
```

Không nên cố tình viết code vô lý.

---

# Step 5: Tạo Production Service

```php
final class ProductionPaymentIdempotencyService
{
    public function pay(array $payload = []): LabActionResult
    {
        $idempotencyKey = $payload['request_key'] ?? (string) Str::uuid();

        $payment = DB::transaction(function () use ($idempotencyKey) {
            $existing = ProductionPayment::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $order = ProductionPaymentOrder::query()
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status === 'paid') {
                return null;
            }

            $payment = ProductionPayment::create([
                'order_id' => $order->id,
                'amount' => $order->amount,
                'status' => 'succeeded',
                'idempotency_key' => $idempotencyKey,
            ]);

            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            return $payment;
        }, attempts: 3);

        if (! $payment) {
            return LabActionResult::failed('Production: Order already paid.');
        }

        return LabActionResult::success(
            "Production: Payment #{$payment->id} created safely.",
            [
                'payment_id' => $payment->id,
                'idempotency_key' => $idempotencyKey,
            ],
        );
    }
}
```

Production version phải thể hiện rõ:

```text
Pattern nào được thêm
Bug nào được fix
Cost là gì
```

---

# Step 6: Tạo Scenario Coordinator

```php
final class PaymentIdempotencyScenario implements LabScenarioContract
{
    public function __construct(
        private readonly NaivePaymentIdempotencyService $naive,
        private readonly ProductionPaymentIdempotencyService $production,
        private readonly LabDatabaseResetService $resetService,
    ) {}

    public function key(): string
    {
        return 'payment-idempotency';
    }

    public function title(): string
    {
        return __('lab_scenarios.payment_idempotency.title');
    }

    public function subtitle(): string
    {
        return __('lab_scenarios.payment_idempotency.subtitle');
    }

    public function description(): string
    {
        return __('lab_scenarios.payment_idempotency.description');
    }

    public function actionHint(): string
    {
        return __('lab_scenarios.payment_idempotency.action_hint');
    }

    public function howToUse(): array
    {
        return __('lab_scenarios.payment_idempotency.how_to_use');
    }

    public function learningGoals(): array
    {
        return __('lab_scenarios.payment_idempotency.learning_goals');
    }

    public function naiveTechniques(): array
    {
        return __('lab_scenarios.payment_idempotency.naive_techniques');
    }

    public function productionTechniques(): array
    {
        return __('lab_scenarios.payment_idempotency.production_techniques');
    }

    public function actionPresets(): array
    {
        return [
            'real_requests' => [1, 2, 5],
            'race_simulation' => [5, 20, 100],
        ];
    }

    public function limits(): array
    {
        return [
            'real_requests_max' => 20,
            'race_simulation_max' => 500,
        ];
    }

    public function action(LabMode $mode, array $payload = []): LabActionResult
    {
        return match ($mode) {
            LabMode::Naive => $this->naive->pay($payload),
            LabMode::Production => $this->production->pay($payload),
        };
    }

    public function state(LabMode $mode): LabStateResult
    {
        return match ($mode) {
            LabMode::Naive => $this->naive->state(),
            LabMode::Production => $this->production->state(),
        };
    }

    public function reset(LabMode $mode): LabActionResult
    {
        return match ($mode) {
            LabMode::Naive => $this->naive->reset(),
            LabMode::Production => $this->production->reset(),
        };
    }

    public function resetAll(): LabActionResult
    {
        $this->resetService->resetPaymentIdempotency();

        return LabActionResult::success(
            'Reset both Naive and Production databases successfully.'
        );
    }

    public function learningCenter(): array
    {
        return [
            'overview' => [
                'problem' => __('lab_scenarios.payment_idempotency.learning_center.overview.problem'),
                'failure' => __('lab_scenarios.payment_idempotency.learning_center.overview.failure'),
                'solution' => __('lab_scenarios.payment_idempotency.learning_center.overview.solution'),
                'cost' => __('lab_scenarios.payment_idempotency.learning_center.overview.cost'),
            ],

            'code_examples' => [
                // ...
            ],

            'sequence_diagrams' => [
                // ...
            ],

            'database_schemas' => [
                // ...
            ],

            'trade_offs' => [
                // ...
            ],
        ];
    }
}
```

---

# Step 7: Đăng ký scenario vào Registry

```php
private function scenarios(): array
{
    return [
        'inventory-oversell' => InventoryOversellScenario::class,
        'payment-idempotency' => PaymentIdempotencyScenario::class,
    ];
}
```

Sau đó UI sẽ tự có thêm scenario mới trong dropdown.

---

# Step 8: Thêm Reset Service

Trong:

```text
app/Services/Labs/Core/LabDatabaseResetService.php
```

Thêm:

```php
public function resetPaymentIdempotency(): void
{
    Schema::disableForeignKeyConstraints();

    NaivePayment::truncate();
    NaivePaymentOrder::truncate();

    ProductionPayment::truncate();
    ProductionPaymentOrder::truncate();

    Schema::enableForeignKeyConstraints();

    NaivePaymentOrder::factory()
        ->defaultPending()
        ->create();

    ProductionPaymentOrder::factory()
        ->defaultPending()
        ->create();
}
```

Dùng `truncate()` trong lab để:

```text
- data sạch
- ID reset về #1
- dễ demo
```

Không nên dùng truncate bừa trong production business.

---

# Step 9: Thêm translations

```text
lang/vi/lab_scenarios.php
lang/en/lab_scenarios.php
```

Cấu trúc:

```php
'payment_idempotency' => [
    'title' => 'Payment Idempotency Lab',
    'subtitle' => 'Laravel System Design Lab',
    'description' => 'So sánh payment endpoint không idempotency với endpoint có idempotency key.',
    'action_hint' => 'Bấm nhiều request để thấy Naive tạo nhiều payment, còn Production chỉ xử lý một payment duy nhất.',
    'learning_goals' => [
        'Hiểu idempotency là gì.',
        'Hiểu vì sao retry có thể tạo duplicate payment.',
        'Hiểu cách dùng idempotency key trong Laravel.',
    ],
    'how_to_use' => [
        'Bấm Real Requests để gửi request thật.',
        'Bấm Race Simulation để mô phỏng nhiều request trùng hành động.',
        'So sánh payments_count và invariants.',
    ],
    'naive_techniques' => [
        'Basic POST action',
        'No idempotency key',
        'No duplicate protection',
    ],
    'production_techniques' => [
        'Idempotency key',
        'Unique constraint',
        'DB transaction',
        'lockForUpdate',
    ],
];
```

---

## 7. Checklist khi thêm scenario mới

```text
[ ] Có migration naive
[ ] Có migration production
[ ] Có model naive
[ ] Có model production
[ ] Có factory deterministic
[ ] Có naive service
[ ] Có production service
[ ] Có state() cho naive
[ ] Có state() cho production
[ ] Có reset() cho naive
[ ] Có reset() cho production
[ ] Có resetAll()
[ ] Có learningCenter()
[ ] Có translation vi/en
[ ] Có registry registration
[ ] Có action presets
[ ] Có limits
[ ] UI dropdown thấy scenario mới
[ ] Reset All chạy đúng
[ ] Metrics cập nhật đúng
[ ] Invariants hiển thị đúng
[ ] Chart cập nhật đúng
```

---

## 8. Nguyên tắc thiết kế Naive vs Production

### Naive không phải code rác

Naive nên là:

```text
code đơn giản
dễ hiểu
thường gặp trong thực tế
nhìn có vẻ đúng
nhưng fail trong production
```

Ví dụ tốt:

```php
$product = Product::find($id);

if ($product->stock > 0) {
    $product->stock--;
    $product->save();
}
```

Ví dụ không tốt:

```php
DB::statement('DROP TABLE products');
```

Naive phải giúp người học thấy:

```text
"À, code kiểu này mình từng viết."
```

---

### Production phải chỉ rõ cost

Production không chỉ là “code tốt hơn”.

Production phải chỉ rõ:

```text
- thêm table gì
- thêm index gì
- thêm transaction không
- thêm lock không
- thêm unique constraint không
- thêm queue/job không
- thêm monitoring không
- thêm complexity gì
```

---

## 9. Khi nào nên thêm scenario mới?

Nên thêm scenario khi chủ đề có thể nhìn thấy bằng một trong các dạng:

```text
- duplicate record
- oversell
- stale read
- lost event
- race condition
- queue retry duplicate
- tenant data leak
- cache stale
- slow query
- failed invariant
```

Nếu chủ đề chỉ là lý thuyết, nên đưa vào Learning Center trước, chưa cần làm interactive scenario.

---

## 10. Kết luận

Mỗi scenario là một mini-course:

```text
UI để thấy bug
Metrics để đo bug
Invariants để xác nhận đúng/sai
Chart để visualize
Learning Center để hiểu sâu
Code để học cách implement
Trade-off để hiểu cost
```

Cách thêm scenario chuẩn là:

```text
Migration → Model → Factory → Service → Scenario → Registry → Translation → Test
```

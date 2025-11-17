# 004. subscribe Catalog Management - TDD Implementation

## 개요
구독 카탈로그 CRUD 관리 시스템 구현: 구독 생성, 수정, 삭제, 조회 및 공개 카탈로그

## 의존관계
- **선행 태스크**: [003. 인증 시스템](003_authentication_system.md)
- **후속 태스크**: [005. 구독 관리 시스템](005_subscription_management.md)

## TDD 테스트 시나리오 (모두 HTTP 200 반환)

### Admin 구독 관리 테스트

#### 1. 구독 카탈로그 목록 조회
**테스트**: `AdminsubscribeCatalogListTest`

```php
public function test_admin_subscribe_catalog_list_returns_200()
{
    // Given: 관리자와 구독 데이터
    $admin = User::factory()->admin()->create();
    subscribe::factory()->count(5)->create();

    // When: 구독 목록 조회
    $response = $this->actingAs($admin)->get('/admin/subscribe/catalog');

    // Then: HTTP 200과 구독 목록
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'status',
        'data' => [
            'subscribes' => [
                '*' => ['id', 'name', 'category', 'status', 'base_price', 'created_at']
            ],
            'pagination' => ['current_page', 'total_pages', 'total_count']
        ]
    ]);
}

public function test_admin_subscribe_search_returns_200()
{
    // Given: 관리자와 검색 가능한 구독
    $admin = User::factory()->admin()->create();
    subscribe::factory()->create(['name' => 'Premium Air Conditioning subscribe']);
    subscribe::factory()->create(['name' => 'Basic Cleaning subscribe']);

    // When: 구독 검색
    $response = $this->actingAs($admin)->get('/admin/subscribe/catalog?search=Air');

    // Then: HTTP 200과 검색 결과
    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data.subscribes');
    $response->assertJsonPath('data.subscribes.0.name', 'Premium Air Conditioning subscribe');
}
```

#### 2. 구독 생성
**테스트**: `AdminsubscribeCreateTest`

```php
public function test_admin_can_create_subscribe_returns_200()
{
    // Given: 관리자
    $admin = User::factory()->admin()->create();

    // When: 새 구독 생성
    $subscribeData = [
        'name' => 'Premium Air Conditioning subscribe',
        'description' => 'Complete AC maintenance and cleaning subscribe',
        'category' => 'maintenance',
        'pricing_model' => 'fixed',
        'base_price' => 150000,
        'features' => ['Deep cleaning', 'Filter replacement', 'Performance check'],
        'trial_enabled' => true,
        'trial_config' => [
            'type' => 'time_based',
            'duration' => 7,
            'discount' => 100
        ],
        'status' => 'active'
    ];

    $response = $this->actingAs($admin)->post('/admin/subscribe/catalog', $subscribeData);

    // Then: HTTP 200과 생성된 구독
    $response->assertStatus(200);
    $response->assertJson(['status' => 'success']);
    $this->assertDatabaseHas('subscribes', [
        'name' => 'Premium Air Conditioning subscribe',
        'category' => 'maintenance'
    ]);
}

public function test_subscribe_creation_validation_returns_422()
{
    // Given: 관리자
    $admin = User::factory()->admin()->create();

    // When: 잘못된 데이터로 구독 생성
    $response = $this->actingAs($admin)->post('/admin/subscribe/catalog', [
        'name' => '', // 필수 필드 누락
        'category' => 'invalid_category'
    ]);

    // Then: 422 Validation Error
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'category']);
}
```

#### 3. 구독 상세 조회
**테스트**: `AdminsubscribeDetailTest`

```php
public function test_admin_subscribe_detail_returns_200()
{
    // Given: 관리자와 구독
    $admin = User::factory()->admin()->create();
    $subscribe = subscribe::factory()->create();
    Subscription::factory()->count(3)->create(['subscribe_id' => $subscribe->id]);

    // When: 구독 상세 조회
    $response = $this->actingAs($admin)->get("/admin/subscribe/catalog/{$subscribe->id}");

    // Then: HTTP 200과 상세 정보
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'status',
        'data' => [
            'subscribe' => [
                'id', 'name', 'description', 'category', 'pricing_model',
                'base_price', 'features', 'trial_config', 'status'
            ],
            'statistics' => [
                'subscriptions_count', 'revenue_total', 'average_rating'
            ]
        ]
    ]);
}

public function test_nonexistent_subscribe_returns_404()
{
    // Given: 관리자
    $admin = User::factory()->admin()->create();

    // When: 존재하지 않는 구독 조회
    $response = $this->actingAs($admin)->get('/admin/subscribe/catalog/999');

    // Then: 404 Not Found
    $response->assertStatus(404);
}
```

### Customer 공개 카탈로그 테스트

#### 4. 공개 구독 카탈로그
**테스트**: `CustomersubscribeCatalogTest`

```php
public function test_public_subscribe_catalog_returns_200()
{
    // Given: 공개된 구독들
    subscribe::factory()->count(3)->create(['status' => 'active']);
    subscribe::factory()->create(['status' => 'draft']); // 비공개

    // When: 공개 카탈로그 조회 (인증 불필요)
    $response = $this->get('/home/subscribe/catalog');

    // Then: HTTP 200과 활성 구독만 반환
    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data.subscribes');
    $response->assertJsonStructure([
        'data' => [
            'subscribes' => [
                '*' => ['id', 'name', 'category', 'base_price', 'features', 'trial_available']
            ],
            'filters' => ['categories', 'price_range']
        ]
    ]);
}

public function test_customer_subscribe_filtering_returns_200()
{
    // Given: 다양한 카테고리의 구독
    subscribe::factory()->create(['category' => 'maintenance', 'base_price' => 100000, 'status' => 'active']);
    subscribe::factory()->create(['category' => 'cleaning', 'base_price' => 200000, 'status' => 'active']);

    // When: 카테고리 필터 적용
    $response = $this->get('/home/subscribe/catalog?category=maintenance&price_max=150000');

    // Then: HTTP 200과 필터된 결과
    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data.subscribes');
}
```

#### 5. 고객용 구독 상세
**테스트**: `CustomersubscribeDetailTest`

```php
public function test_customer_subscribe_detail_returns_200()
{
    // Given: 활성 구독
    $subscribe = subscribe::factory()->create([
        'status' => 'active',
        'trial_config' => json_encode(['type' => 'time_based', 'duration' => 7])
    ]);

    // When: 구독 상세 조회
    $response = $this->get("/home/subscribe/catalog/{$subscribe->id}");

    // Then: HTTP 200과 고객용 정보
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'subscribe' => [
                'id', 'name', 'description', 'category', 'base_price',
                'features', 'trial_available', 'trial_config'
            ],
            'reviews' => ['average_rating', 'total_reviews'],
            'pricing_options' => ['monthly', 'quarterly', 'yearly']
        ]
    ]);
}

public function test_draft_subscribe_not_accessible_to_customers()
{
    // Given: 비공개 구독
    $subscribe = subscribe::factory()->create(['status' => 'draft']);

    // When: 고객이 비공개 구독 접근
    $response = $this->get("/home/subscribe/catalog/{$subscribe->id}");

    // Then: 404 Not Found
    $response->assertStatus(404);
}
```

## 컨트롤러 구현

### 1. Admin subscribe Catalog Controller

```php
<?php

namespace Jiny\Subscribe\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Jiny\Subscribe\Models\subscribe;
use Jiny\Subscribe\Models\subscribeCategory;
use Illuminate\Support\Str;

class subscribeCatalogController extends Controller
{
    public function index(Request $request)
    {
        $query = subscribe::with(['category']);

        // 검색 필터
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 카테고리 필터
        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        // 상태 필터
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // 정렬
        $query->orderBy($request->get('sort', 'created_at'),
                       $request->get('direction', 'desc'));

        // 페이지네이션
        $subscribes = $query->paginate($request->get('per_page', 20));

        // 통계 계산
        $statistics = $this->calculateStatistics();

        return response()->json([
            'status' => 'success',
            'data' => [
                'subscribes' => $subscribes->items(),
                'pagination' => [
                    'current_page' => $subscribes->currentPage(),
                    'total_pages' => $subscribes->lastPage(),
                    'total_count' => $subscribes->total(),
                    'per_page' => $subscribes->perPage()
                ],
                'statistics' => $statistics
            ]
        ], 200);
    }

    public function create()
    {
        $categories = subscribeCategory::where('is_active', true)
                                   ->orderBy('sort_order')
                                   ->get();

        $pricingModels = ['fixed', 'hourly', 'subscription'];
        $trialTypes = ['time_based', 'usage_based', 'feature_based', 'hybrid'];

        return response()->json([
            'status' => 'success',
            'data' => [
                'categories' => $categories,
                'pricing_models' => $pricingModels,
                'trial_types' => $trialTypes
            ]
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:subscribes,name',
            'description' => 'required|string',
            'category' => 'required|string|exists:subscribe_categories,name',
            'pricing_model' => 'required|in:fixed,hourly,subscription',
            'base_price' => 'required|numeric|min:0',
            'features' => 'array',
            'features.*' => 'string',
            'trial_enabled' => 'boolean',
            'trial_config' => 'array|required_if:trial_enabled,true',
            'status' => 'required|in:active,inactive,draft',
            'image_url' => 'nullable|url',
            'sort_order' => 'integer|min:0'
        ]);

        // 슬러그 생성
        $validated['slug'] = Str::slug($validated['name']);

        // 중복 슬러그 처리
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (subscribe::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter++;
        }

        // JSON 필드 처리
        $validated['features'] = json_encode($validated['features'] ?? []);
        $validated['trial_config'] = $validated['trial_enabled']
            ? json_encode($validated['trial_config'])
            : null;

        $subscribe = subscribe::create($validated);

        // 활동 로그
        $this->logAdminActivity('subscribe_created', [
            'subscribe_id' => $subscribe->id,
            'subscribe_name' => $subscribe->name
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'subscribe created successfully',
            'data' => [
                'subscribe' => [
                    'id' => $subscribe->id,
                    'name' => $subscribe->name,
                    'slug' => $subscribe->slug,
                    'status' => $subscribe->status,
                    'created_at' => $subscribe->created_at
                ]
            ]
        ], 200);
    }

    public function show(subscribe $subscribe)
    {
        // 구독 통계
        $subscriptionStats = [
            'total_subscriptions' => $subscribe->subscriptions()->count(),
            'active_subscriptions' => $subscribe->subscriptions()->where('status', 'active')->count(),
            'trial_subscriptions' => $subscribe->subscriptions()->where('status', 'trial')->count(),
        ];

        // 수익 통계
        $revenueStats = [
            'total_revenue' => $subscribe->subscriptions()
                ->join('subscription_billings', 'subscriptions.id', '=', 'subscription_billings.subscription_id')
                ->where('subscription_billings.status', 'paid')
                ->sum('subscription_billings.total_amount'),
            'monthly_recurring_revenue' => $subscribe->subscriptions()
                ->where('status', 'active')
                ->where('billing_cycle', 'monthly')
                ->sum('amount')
        ];

        // 평점 통계
        $ratingStats = [
            'average_rating' => $subscribe->subscribeExecutions()
                ->whereNotNull('customer_rating')
                ->avg('customer_rating'),
            'total_reviews' => $subscribe->subscribeExecutions()
                ->whereNotNull('customer_feedback')
                ->count()
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'subscribe' => $subscribe->load('category'),
                'statistics' => [
                    'subscriptions' => $subscriptionStats,
                    'revenue' => $revenueStats,
                    'ratings' => $ratingStats
                ]
            ]
        ], 200);
    }

    public function edit(subscribe $subscribe)
    {
        $categories = subscribeCategory::where('is_active', true)
                                   ->orderBy('sort_order')
                                   ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'subscribe' => $subscribe,
                'categories' => $categories,
                'pricing_models' => ['fixed', 'hourly', 'subscription'],
                'trial_types' => ['time_based', 'usage_based', 'feature_based', 'hybrid']
            ]
        ], 200);
    }

    public function update(Request $request, subscribe $subscribe)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:subscribes,name,' . $subscribe->id,
            'description' => 'required|string',
            'category' => 'required|string|exists:subscribe_categories,name',
            'pricing_model' => 'required|in:fixed,hourly,subscription',
            'base_price' => 'required|numeric|min:0',
            'features' => 'array',
            'features.*' => 'string',
            'trial_enabled' => 'boolean',
            'trial_config' => 'array|required_if:trial_enabled,true',
            'status' => 'required|in:active,inactive,draft',
            'image_url' => 'nullable|url',
            'sort_order' => 'integer|min:0'
        ]);

        // 이름이 변경되면 슬러그 재생성
        if ($subscribe->name !== $validated['name']) {
            $validated['slug'] = Str::slug($validated['name']);

            $originalSlug = $validated['slug'];
            $counter = 1;
            while (subscribe::where('slug', $validated['slug'])->where('id', '!=', $subscribe->id)->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter++;
            }
        }

        // JSON 필드 처리
        $validated['features'] = json_encode($validated['features'] ?? []);
        $validated['trial_config'] = $validated['trial_enabled']
            ? json_encode($validated['trial_config'])
            : null;

        $subscribe->update($validated);

        // 활동 로그
        $this->logAdminActivity('subscribe_updated', [
            'subscribe_id' => $subscribe->id,
            'subscribe_name' => $subscribe->name
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'subscribe updated successfully',
            'data' => ['subscribe' => $subscribe->fresh()]
        ], 200);
    }

    public function destroy(subscribe $subscribe)
    {
        // 활성 구독 확인
        $activeSubscriptions = $subscribe->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->count();

        if ($activeSubscriptions > 0) {
            return response()->json([
                'error' => 'Cannot delete subscribe with active subscriptions',
                'active_subscriptions' => $activeSubscriptions
            ], 422);
        }

        // 소프트 삭제 또는 하드 삭제
        $hasHistoricalData = $subscribe->subscriptions()->count() > 0;

        if ($hasHistoricalData) {
            // 소프트 삭제 (상태를 inactive로 변경)
            $subscribe->update(['status' => 'inactive']);
            $message = 'subscribe deactivated due to historical data';
        } else {
            // 하드 삭제
            $subscribe->delete();
            $message = 'subscribe deleted successfully';
        }

        // 활동 로그
        $this->logAdminActivity('subscribe_deleted', [
            'subscribe_id' => $subscribe->id,
            'subscribe_name' => $subscribe->name,
            'deletion_type' => $hasHistoricalData ? 'soft' : 'hard'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $message
        ], 200);
    }

    public function updateStatus(Request $request, subscribe $subscribe)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,draft',
            'reason' => 'nullable|string|max:500'
        ]);

        $oldStatus = $subscribe->status;
        $subscribe->update(['status' => $validated['status']]);

        // 활동 로그
        $this->logAdminActivity('subscribe_status_changed', [
            'subscribe_id' => $subscribe->id,
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
            'reason' => $validated['reason'] ?? null
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'subscribe status updated successfully',
            'data' => [
                'subscribe' => $subscribe->fresh()
            ]
        ], 200);
    }

    public function duplicate(subscribe $subscribe)
    {
        $newsubscribe = $subscribe->replicate();
        $newsubscribe->name = $subscribe->name . ' (Copy)';
        $newsubscribe->slug = Str::slug($newsubscribe->name);
        $newsubscribe->status = 'draft';

        // 슬러그 중복 처리
        $originalSlug = $newsubscribe->slug;
        $counter = 1;
        while (subscribe::where('slug', $newsubscribe->slug)->exists()) {
            $newsubscribe->slug = $originalSlug . '-' . $counter++;
        }

        $newsubscribe->save();

        return response()->json([
            'status' => 'success',
            'message' => 'subscribe duplicated successfully',
            'data' => ['subscribe' => $newsubscribe]
        ], 200);
    }

    private function calculateStatistics(): array
    {
        return [
            'total_subscribes' => subscribe::count(),
            'active_subscribes' => subscribe::where('status', 'active')->count(),
            'draft_subscribes' => subscribe::where('status', 'draft')->count(),
            'subscribes_with_trials' => subscribe::whereNotNull('trial_config')->count(),
        ];
    }

    private function logAdminActivity(string $action, array $data): void
    {
        \DB::table('admin_activity_logs')->insert([
            'admin_id' => auth()->id(),
            'action' => $action,
            'data' => json_encode($data),
            'ip_address' => request()->ip(),
            'created_at' => now()
        ]);
    }
}
```

### 2. Customer subscribe Catalog Controller

```php
<?php

namespace Jiny\Subscribe\Http\Controllers\Customer;

use Illuminate\Http\Request;
use Jiny\Subscribe\Models\subscribe;
use Jiny\Subscribe\Models\subscribeCategory;

class subscribeCatalogController extends Controller
{
    public function index(Request $request)
    {
        $query = subscribe::where('status', 'active')
                       ->with(['category']);

        // 카테고리 필터
        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        // 가격 범위 필터
        if ($priceMin = $request->get('price_min')) {
            $query->where('base_price', '>=', $priceMin);
        }
        if ($priceMax = $request->get('price_max')) {
        }

        // 무료 체험 가능 구독
        if ($request->boolean('trial_available')) {
            $query->whereNotNull('trial_config');
        }

        // 위치 기반 필터 (향후 구현)
        if ($location = $request->get('location')) {
            // 지역 기반 필터링 로직
        }

        // 정렬
        $sortBy = $request->get('sort', 'sort_order');
        $direction = $request->get('direction', 'asc');

        if ($sortBy === 'popularity') {
            // 구독 수 기준 정렬
            $query->withCount('subscriptions')
                  ->orderBy('subscriptions_count', 'desc');
        } elseif ($sortBy === 'rating') {
            // 평점 기준 정렬
            $query->leftJoin('subscribe_executions', 'subscribes.id', '=', 'subscribe_executions.subscription_id')
                  ->select('subscribes.*')
                  ->selectRaw('AVG(subscribe_executions.customer_rating) as avg_rating')
                  ->groupBy('subscribes.id')
                  ->orderBy('avg_rating', 'desc');
        } else {
            $query->orderBy($sortBy, $direction);
        }

        $subscribes = $query->paginate($request->get('per_page', 12));

        // 구독 데이터 변환 (고객용)
        $transformedsubscribes = $subscribes->getCollection()->map(function ($subscribe) {
            return [
                'id' => $subscribe->id,
                'name' => $subscribe->name,
                'description' => $subscribe->description,
                'category' => $subscribe->category,
                'base_price' => $subscribe->base_price,
                'features' => json_decode($subscribe->features, true),
                'trial_available' => !is_null($subscribe->trial_config),
                'trial_config' => $subscribe->trial_config ? json_decode($subscribe->trial_config, true) : null,
                'image_url' => $subscribe->image_url,
                'pricing_options' => $this->generatePricingOptions($subscribe),
                'rating' => $this->getsubscribeRating($subscribe->id),
                'reviews_count' => $this->getReviewsCount($subscribe->id)
            ];
        });

        // 필터 옵션
        $filters = [
            'categories' => subscribeCategory::where('is_active', true)
                                          ->orderBy('sort_order')
                                          ->pluck('name'),
            'price_range' => [
                'min' => subscribe::where('status', 'active')->min('base_price'),
                'max' => subscribe::where('status', 'active')->max('base_price')
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'subscribes' => $transformedsubscribes,
                'pagination' => [
                    'current_page' => $subscribes->currentPage(),
                    'total_pages' => $subscribes->lastPage(),
                    'total_count' => $subscribes->total()
                ],
                'filters' => $filters
            ]
        ], 200);
    }

    public function show(subscribe $subscribe)
    {
        // 활성 구독만 조회 가능
        if ($subscribe->status !== 'active') {
            return response()->json(['error' => 'subscribe not found'], 404);
        }

        // 구독 상세 정보
        $subscribeData = [
            'id' => $subscribe->id,
            'name' => $subscribe->name,
            'description' => $subscribe->description,
            'category' => $subscribe->category,
            'pricing_model' => $subscribe->pricing_model,
            'base_price' => $subscribe->base_price,
            'features' => json_decode($subscribe->features, true),
            'trial_available' => !is_null($subscribe->trial_config),
            'trial_config' => $subscribe->trial_config ? json_decode($subscribe->trial_config, true) : null,
            'image_url' => $subscribe->image_url
        ];

        // 리뷰 및 평점
        $reviews = [
            'average_rating' => $this->getsubscribeRating($subscribe->id),
            'total_reviews' => $this->getReviewsCount($subscribe->id),
            'rating_distribution' => $this->getRatingDistribution($subscribe->id),
            'recent_reviews' => $this->getRecentReviews($subscribe->id, 5)
        ];

        // 가격 옵션
        $pricingOptions = $this->generatePricingOptions($subscribe);

        // 비슷한 구독
        $similarsubscribes = subscribe::where('category', $subscribe->category)
                                 ->where('id', '!=', $subscribe->id)
                                 ->where('status', 'active')
                                 ->limit(4)
                                 ->get(['id', 'name', 'base_price', 'image_url']);

        // 조회수 증가 (비동기로 처리)
        $this->incrementViewCount($subscribe->id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'subscribe' => $subscribeData,
                'reviews' => $reviews,
                'pricing_options' => $pricingOptions,
                'similar_subscribes' => $similarsubscribes
            ]
        ], 200);
    }

    public function trialInfo(subscribe $subscribe)
    {
        if (!$subscribe->trial_config) {
            return response()->json(['error' => 'Trial not available for this subscribe'], 404);
        }

        $trialConfig = json_decode($subscribe->trial_config, true);

        // 고객별 개인화 체험 설정 (JWT 토큰에서 고객 정보 추출)
        $customer = $request->input('authenticated_customer');
        if ($customer) {
            $trialConfig = $this->personalizeTrialConfig($trialConfig, $customer);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'trial_config' => $trialConfig,
                'terms_and_conditions' => $this->getTrialTerms(),
                'estimated_value' => $this->calculateTrialValue($subscribe, $trialConfig)
            ]
        ], 200);
    }

    private function generatePricingOptions(subscribe $subscribe): array
    {
        $basePrice = $subscribe->base_price;

        return [
            'monthly' => [
                'price' => $basePrice,
                'savings' => 0,
                'total' => $basePrice
            ],
            'quarterly' => [
                'price' => $basePrice * 3 * 0.95, // 5% 할인
                'savings' => $basePrice * 3 * 0.05,
                'total' => $basePrice * 3 * 0.95
            ],
            'yearly' => [
                'price' => $basePrice * 12 * 0.85, // 15% 할인
                'savings' => $basePrice * 12 * 0.15,
                'total' => $basePrice * 12 * 0.85
            ]
        ];
    }

    private function getsubscribeRating(int $subscribeId): float
    {
        return \DB::table('subscribe_executions')
                  ->join('subscriptions', 'subscribe_executions.subscription_id', '=', 'subscriptions.id')
                  ->where('subscriptions.subscribe_id', $subscribeId)
                  ->whereNotNull('subscribe_executions.customer_rating')
                  ->avg('subscribe_executions.customer_rating') ?? 0;
    }

    private function getReviewsCount(int $subscribeId): int
    {
        return \DB::table('subscribe_executions')
                  ->join('subscriptions', 'subscribe_executions.subscription_id', '=', 'subscriptions.id')
                  ->where('subscriptions.subscribe_id', $subscribeId)
                  ->whereNotNull('subscribe_executions.customer_feedback')
                  ->count();
    }

    private function getRatingDistribution(int $subscribeId): array
    {
        $ratings = \DB::table('subscribe_executions')
                     ->join('subscriptions', 'subscribe_executions.subscription_id', '=', 'subscriptions.id')
                     ->where('subscriptions.subscribe_id', $subscribeId)
                     ->whereNotNull('subscribe_executions.customer_rating')
                     ->select('customer_rating', \DB::raw('count(*) as count'))
                     ->groupBy('customer_rating')
                     ->pluck('count', 'customer_rating')
                     ->toArray();

        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $ratings[$i] ?? 0;
        }

        return $distribution;
    }

    private function getRecentReviews(int $subscribeId, int $limit = 5): array
    {
        return \DB::table('subscribe_executions')
                  ->join('subscriptions', 'subscribe_executions.subscription_id', '=', 'subscriptions.id')
                  ->where('subscriptions.subscribe_id', $subscribeId)
                  ->whereNotNull('subscribe_executions.customer_feedback')
                  ->whereNotNull('subscribe_executions.customer_rating')
                  ->select([
                      'subscribe_executions.customer_rating',
                      'subscribe_executions.customer_feedback',
                      'subscribe_executions.completed_at'
                  ])
                  ->orderBy('subscribe_executions.completed_at', 'desc')
                  ->limit($limit)
                  ->get()
                  ->toArray();
    }

    private function incrementViewCount(int $subscribeId): void
    {
        // 비동기 작업으로 조회수 증가
        \DB::table('subscribe_view_logs')->insert([
            'subscribe_id' => $subscribeId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'viewed_at' => now()
        ]);
    }

    private function personalizeTrialConfig(array $config, object $customer): array
    {
        // 고객의 이전 구독 이용 이력에 따른 개인화
        // 이는 향후 AI/ML 기반 개인화로 확장 가능
        return $config;
    }

    private function getTrialTerms(): array
    {
        return [
            'duration_limit' => 'Trial is limited to the specified duration only',
            'auto_conversion' => 'Trial will automatically convert to paid subscription unless cancelled',
            'cancellation_policy' => 'You can cancel anytime during the trial period',
            'refund_policy' => 'Full refund available if cancelled within trial period'
        ];
    }

    private function calculateTrialValue(subscribe $subscribe, array $trialConfig): array
    {
        $basePrice = $subscribe->base_price;
        $trialDuration = $trialConfig['duration'] ?? 7;

        return [
            'trial_value' => $basePrice * ($trialDuration / 30), // 일할 계산
            'full_subscribe_value' => $basePrice,
            'savings' => $basePrice * ($trialDuration / 30)
        ];
    }
}
```

## 모델 구현

### subscribe Model
```php
<?php

namespace Jiny\Subscribe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class subscribe extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'category', 'pricing_model',
        'base_price', 'features', 'trial_config', 'status',
        'image_url', 'sort_order'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'features' => 'array',
        'trial_config' => 'array',
        'sort_order' => 'integer'
    ];

    protected $dates = ['deleted_at'];

    // Relationships
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function category()
    {
        return $this->belongsTo(subscribeCategory::class, 'category', 'name');
    }

    public function subscribeExecutions()
    {
        return $this->hasManyThrough(
            subscribeExecution::class,
            Subscription::class,
            'subscribe_id',
            'subscription_id'
        );
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWithTrials($query)
    {
        return $query->whereNotNull('trial_config');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Accessors
    public function getFeaturesListAttribute()
    {
        return $this->features ?? [];
    }

    public function getTrialConfigDataAttribute()
    {
        return $this->trial_config ?? [];
    }

    public function getHasTrialAttribute()
    {
        return !is_null($this->trial_config);
    }

    // Helper Methods
    public function getActiveSubscriptionsCount(): int
    {
        return $this->subscriptions()->where('status', 'active')->count();
    }

    public function getTotalRevenue(): float
    {
        return $this->subscriptions()
                   ->join('subscription_billings', 'subscriptions.id', '=', 'subscription_billings.subscription_id')
                   ->where('subscription_billings.status', 'paid')
                   ->sum('subscription_billings.total_amount');
    }

    public function getAverageRating(): float
    {
        return $this->subscribeExecutions()
                   ->whereNotNull('customer_rating')
                   ->avg('customer_rating') ?? 0;
    }
}
```

## 구현 체크리스트

### Admin 구독 관리
- [ ] **구독 목록 조회** (`GET /admin/subscribe/catalog`)
  - [ ] HTTP 200 응답 검증
  - [ ] 검색/필터 기능
  - [ ] 페이지네이션
  - [ ] 정렬 기능
  - [ ] 통계 데이터 포함

- [ ] **구독 생성** (`POST /admin/subscribe/catalog`)
  - [ ] HTTP 200 응답 검증
  - [ ] 입력 검증 (422 오류)
  - [ ] 슬러그 자동 생성
  - [ ] 중복 이름 방지
  - [ ] JSON 필드 처리

- [ ] **구독 상세 조회** (`GET /admin/subscribe/catalog/{id}`)
  - [ ] HTTP 200 응답 검증
  - [ ] 404 오류 처리
  - [ ] 구독 통계 계산
  - [ ] 수익 통계 계산
  - [ ] 평점 통계 계산

- [ ] **구독 수정** (`PUT /admin/subscribe/catalog/{id}`)
  - [ ] HTTP 200 응답 검증
  - [ ] 검증 로직
  - [ ] 슬러그 업데이트
  - [ ] 변경 이력 로그

- [ ] **구독 삭제** (`DELETE /admin/subscribe/catalog/{id}`)
  - [ ] HTTP 200 응답 검증
  - [ ] 활성 구독 확인
  - [ ] 소프트/하드 삭제 로직
  - [ ] 삭제 이력 로그

### Customer 공개 카탈로그
- [ ] **공개 카탈로그 조회** (`GET /home/subscribe/catalog`)
  - [ ] HTTP 200 응답 검증
  - [ ] 활성 구독만 표시
  - [ ] 카테고리/가격 필터
  - [ ] 정렬 옵션
  - [ ] 필터 메타데이터

- [ ] **구독 상세 조회** (`GET /home/subscribe/catalog/{id}`)
  - [ ] HTTP 200 응답 검증
  - [ ] 비활성 구독 404 처리
  - [ ] 리뷰/평점 표시
  - [ ] 가격 옵션 계산
  - [ ] 비슷한 구독 추천

- [ ] **체험 정보 조회** (`GET /home/subscribe/catalog/{id}/trial-info`)
  - [ ] HTTP 200 응답 검증
  - [ ] 체험 불가능한 구독 404 처리
  - [ ] 개인화된 체험 설정
  - [ ] 체험 가치 계산

### 데이터 모델
- [ ] **subscribe 모델**
  - [ ] 관계 설정 (Subscription, subscribeCategory)
  - [ ] 스코프 메서드
  - [ ] 접근자 메서드
  - [ ] 헬퍼 메서드

- [ ] **subscribeCategory 모델**
  - [ ] 기본 카테고리 시딩
  - [ ] 정렬 순서 관리

## 완료 기준

### 기능적 검증
- [ ] 모든 엔드포인트 HTTP 200 반환
- [ ] 관리자 CRUD 작업 정상 동작
- [ ] 고객 카탈로그 조회 정상 동작
- [ ] 검색/필터/정렬 기능 작동
- [ ] 입력 검증 및 오류 처리 완료

### 성능 검증
- [ ] 카탈로그 조회 < 200ms
- [ ] 검색 기능 < 300ms
- [ ] 대량 데이터 페이지네이션 최적화
- [ ] 데이터베이스 쿼리 최적화

---

**이전 태스크**: [003. 인증 시스템](003_authentication_system.md)
**다음 태스크**: [005. 구독 관리 시스템](005_subscription_management.md)

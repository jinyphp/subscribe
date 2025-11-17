<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\subscribeUsers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Jiny\Subscribe\Models\subscribeCategory;
use Jiny\Subscribe\Models\subscribe;
use Jiny\Subscribe\Models\subscribePlan;

class HierarchyController extends Controller
{
    /**
     * 구독 카테고리 목록 조회
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = subscribeCategory::where('enable', true)
                ->whereNull('deleted_at')
                ->orderBy('pos')
                ->orderBy('title')
                ->get(['id', 'title as name', 'description', 'icon', 'color']);

            return response()->json([
                'success' => true,
                'categories' => $categories
            ]);

        } catch (\Exception $e) {
            Log::error('Categories fetch error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '카테고리 목록을 불러오는 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 특정 카테고리의 구독 목록 조회
     */
    public function getsubscribesByCategory(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => 'required|integer|exists:subscribe_categories,id'
        ]);

        try {
            $categoryId = $request->input('category_id');

            $subscribes = subscribe::where('category_id', $categoryId)
                ->where('enable', true)
                ->orderBy('title')
                ->get(['id', 'title', 'description', 'price', 'sale_price', 'image']);

            return response()->json([
                'success' => true,
                'subscribes' => $subscribes
            ]);

        } catch (\Exception $e) {
            Log::error('subscribes fetch error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '구독 목록을 불러오는 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 특정 구독의 플랜 목록 조회
     */
    public function getPlansBysubscribe(Request $request): JsonResponse
    {
        $request->validate([
            'subscribe_id' => 'required|integer|exists:subscribes,id'
        ]);

        try {
            $subscribeId = $request->input('subscribe_id');

            $plans = subscribePlan::where('subscribe_id', $subscribeId)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('monthly_price')
                ->get([
                    'id', 'plan_name', 'plan_code', 'description',
                    'monthly_price', 'quarterly_price', 'yearly_price', 'lifetime_price',
                    'monthly_available', 'quarterly_available', 'yearly_available', 'lifetime_available',
                    'trial_period_days', 'setup_fee', 'features', 'is_popular', 'is_featured'
                ]);

            return response()->json([
                'success' => true,
                'plans' => $plans
            ]);

        } catch (\Exception $e) {
            Log::error('Plans fetch error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '플랜 목록을 불러오는 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 특정 플랜의 가격 정보 조회 (청구 주기별)
     */
    public function getPricesByPlan(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|integer|exists:subscribe_plans,id',
            'billing_cycle' => 'sometimes|string|in:monthly,quarterly,yearly,lifetime'
        ]);

        try {
            $planId = $request->input('plan_id');
            $billingCycle = $request->input('billing_cycle');

            $plan = subscribePlan::findOrFail($planId);

            // 기본 가격 정보
            $prices = [
                'monthly' => [
                    'cycle' => 'monthly',
                    'cycle_display' => '월간',
                    'price' => $plan->monthly_price,
                    'available' => $plan->monthly_available,
                    'period_multiplier' => 1
                ],
                'quarterly' => [
                    'cycle' => 'quarterly',
                    'cycle_display' => '분기 (3개월)',
                    'price' => $plan->quarterly_price,
                    'available' => $plan->quarterly_available,
                    'period_multiplier' => 3
                ],
                'yearly' => [
                    'cycle' => 'yearly',
                    'cycle_display' => '연간 (12개월)',
                    'price' => $plan->yearly_price,
                    'available' => $plan->yearly_available,
                    'period_multiplier' => 12
                ],
                'lifetime' => [
                    'cycle' => 'lifetime',
                    'cycle_display' => '평생',
                    'price' => $plan->lifetime_price,
                    'available' => $plan->lifetime_available,
                    'period_multiplier' => null
                ]
            ];

            // 할인율 계산
            foreach ($prices as $cycle => &$priceInfo) {
                if ($cycle !== 'monthly' && $plan->monthly_price > 0 && $priceInfo['price'] > 0 && $priceInfo['period_multiplier']) {
                    $monthlyEquivalent = $plan->monthly_price * $priceInfo['period_multiplier'];
                    $savings = $monthlyEquivalent - $priceInfo['price'];
                    $discountPercentage = round(($savings / $monthlyEquivalent) * 100, 1);

                    $priceInfo['monthly_equivalent'] = $monthlyEquivalent;
                    $priceInfo['savings'] = $savings;
                    $priceInfo['discount_percentage'] = $discountPercentage;
                    $priceInfo['monthly_cost'] = round($priceInfo['price'] / $priceInfo['period_multiplier'], 2);
                } else {
                    $priceInfo['monthly_equivalent'] = $priceInfo['price'];
                    $priceInfo['savings'] = 0;
                    $priceInfo['discount_percentage'] = 0;
                    $priceInfo['monthly_cost'] = $priceInfo['price'];
                }
            }

            // 사용 가능한 가격 옵션만 필터링
            $availablePrices = array_filter($prices, function($price) {
                return $price['available'] && $price['price'] > 0;
            });

            // 특정 청구 주기가 요청된 경우
            if ($billingCycle && isset($availablePrices[$billingCycle])) {
                $selectedPrice = $availablePrices[$billingCycle];
                $selectedPrice['setup_fee'] = $plan->setup_fee;
                $selectedPrice['trial_period_days'] = $plan->trial_period_days;

                return response()->json([
                    'success' => true,
                    'plan' => [
                        'id' => $plan->id,
                        'name' => $plan->plan_name,
                        'code' => $plan->plan_code,
                        'description' => $plan->description,
                        'features' => $plan->features
                    ],
                    'price' => $selectedPrice
                ]);
            }

            // 모든 사용 가능한 가격 옵션 반환
            return response()->json([
                'success' => true,
                'plan' => [
                    'id' => $plan->id,
                    'name' => $plan->plan_name,
                    'code' => $plan->plan_code,
                    'description' => $plan->description,
                    'features' => $plan->features,
                    'setup_fee' => $plan->setup_fee,
                    'trial_period_days' => $plan->trial_period_days
                ],
                'prices' => array_values($availablePrices)
            ]);

        } catch (\Exception $e) {
            Log::error('Prices fetch error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '가격 정보를 불러오는 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 완전한 계층구조 정보 조회 (초기 로드용)
     */
    public function getFullHierarchy(): JsonResponse
    {
        try {
            $categories = subscribeCategory::with([
                'subscribes' => function($query) {
                    $query->where('enable', true)
                          ->select('id', 'category_id', 'title', 'description', 'price', 'sale_price')
                          ->orderBy('title');
                }
            ])
            ->where('enable', true)
            ->whereNull('deleted_at')
            ->orderBy('pos')
            ->orderBy('title')
            ->get(['id', 'title as name', 'description', 'icon', 'color']);

            return response()->json([
                'success' => true,
                'hierarchy' => $categories
            ]);

        } catch (\Exception $e) {
            Log::error('Full hierarchy fetch error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '구독 계층구조를 불러오는 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 빠른 가격 계산 (AJAX용)
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|integer|exists:subscribe_plans,id',
            'billing_cycle' => 'required|string|in:monthly,quarterly,yearly,lifetime'
        ]);

        try {
            $planId = $request->input('plan_id');
            $billingCycle = $request->input('billing_cycle');

            $plan = subscribePlan::findOrFail($planId);

            $price = match($billingCycle) {
                'monthly' => $plan->monthly_price,
                'quarterly' => $plan->quarterly_price,
                'yearly' => $plan->yearly_price,
                'lifetime' => $plan->lifetime_price,
                default => 0
            };

            $available = match($billingCycle) {
                'monthly' => $plan->monthly_available,
                'quarterly' => $plan->quarterly_available,
                'yearly' => $plan->yearly_available,
                'lifetime' => $plan->lifetime_available,
                default => false
            };

            if (!$available || $price <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => '선택한 청구 주기는 이용할 수 없습니다.'
                ], 400);
            }

            // 할인 계산
            $monthlyPrice = $plan->monthly_price;
            $periodMultiplier = match($billingCycle) {
                'monthly' => 1,
                'quarterly' => 3,
                'yearly' => 12,
                'lifetime' => null,
                default => 1
            };

            $discount = 0;
            $savings = 0;
            if ($billingCycle !== 'monthly' && $monthlyPrice > 0 && $periodMultiplier) {
                $monthlyEquivalent = $monthlyPrice * $periodMultiplier;
                $savings = $monthlyEquivalent - $price;
                $discount = round(($savings / $monthlyEquivalent) * 100, 1);
            }

            return response()->json([
                'success' => true,
                'calculation' => [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->plan_name,
                    'billing_cycle' => $billingCycle,
                    'price' => $price,
                    'setup_fee' => $plan->setup_fee,
                    'total_price' => $price + $plan->setup_fee,
                    'monthly_equivalent' => $periodMultiplier ? round($price / $periodMultiplier, 2) : $price,
                    'discount_percentage' => $discount,
                    'savings' => $savings,
                    'trial_period_days' => $plan->trial_period_days,
                    'currency' => 'KRW'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Price calculation error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '가격 계산 중 오류가 발생했습니다.'
            ], 500);
        }
    }
}

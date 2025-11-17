<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\Plan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\Sitesubscribe;

class CreateController extends Controller
{
    public function __invoke(Request $request)
    {
        // 구독 목록 조회
        $subscribes = Sitesubscribe::select('id', 'title')
                              ->where('enable', true)
                              ->orderBy('title')
                              ->get();

        // 플랜 타입 옵션
        $planTypes = [
            'basic' => 'Basic',
            'standard' => 'Standard',
            'premium' => 'Premium',
            'enterprise' => 'Enterprise',
            'custom' => 'Custom'
        ];

        // 결제 타입 옵션
        $billingTypes = [
            'subscription' => 'Subscription',
            'one_time' => 'One Time',
            'usage_based' => 'Usage Based',
            'hybrid' => 'Hybrid'
        ];

        // 기본 피처 템플릿
        $defaultFeatures = [
            'api_access' => 'API Access',
            'customer_support' => 'Customer Support',
            'data_export' => 'Data Export',
            'custom_branding' => 'Custom Branding',
            'advanced_analytics' => 'Advanced Analytics',
            'priority_support' => 'Priority Support',
            'white_label' => 'White Label',
            'custom_integrations' => 'Custom Integrations'
        ];

        return view('jiny-subscribe::admin.plan.create', compact(
            'subscribes',
            'planTypes',
            'billingTypes',
            'defaultFeatures'
        ));
    }
}

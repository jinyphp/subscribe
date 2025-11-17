@extends('jiny-subscribe::layouts.admin.sidebar')

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $subscribe->title }}</h2>
                    <p class="text-muted mb-0">구독 상세 정보와 통계를 확인합니다.</p>
                </div>
                <div>
                    <a href="{{ route('admin.site.subscribes.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="fe fe-arrow-left me-2"></i>목록으로
                    </a>
                    <a href="{{ route('admin.site.subscribes.edit', $subscribe->id) }}" class="btn btn-primary">
                        <i class="fe fe-edit me-2"></i>수정하기
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 통계 카드 -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-eye text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">조회수</h6>
                            <h4 class="mb-0">{{ number_format($subscribe->view_count ?? 0) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-dollar-sign text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">현재 가격</h6>
                            <h4 class="mb-0">
                                @if($subscribe->sale_price && $subscribe->sale_price < $subscribe->price)
                                    ₩{{ number_format($subscribe->sale_price) }}
                                @elseif($subscribe->price)
                                    ₩{{ number_format($subscribe->price) }}
                                @else
                                    문의
                                @endif
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-clock text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">소요 기간</h6>
                            <h4 class="mb-0">{{ $subscribe->duration ?? '-' }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-star text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">추천 구독</h6>
                            <h4 class="mb-0">{{ $subscribe->featured ? '예' : '아니오' }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- 기본 정보 -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">기본 정보</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">구독명</label>
                                <p class="text-gray-800">{{ $subscribe->title }}</p>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold">슬러그</label>
                                <p class="text-gray-800">{{ $subscribe->slug }}</p>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold">카테고리</label>
                                <p class="text-gray-800">{{ $subscribe->category ?? '-' }}</p>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold">담당자</label>
                                <p class="text-gray-800">{{ $subscribe->manager ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">상태</label>
                                <p>
                                    @if($subscribe->enable)
                                        <span class="badge bg-success">활성</span>
                                    @else
                                        <span class="badge bg-secondary">비활성</span>
                                    @endif
                                    @if($subscribe->featured)
                                        <span class="badge bg-warning text-dark ms-1">추천</span>
                                    @endif
                                </p>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold">조회수</label>
                                <p class="text-gray-800">{{ number_format($subscribe->view_count ?? 0) }}회</p>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold">등록일</label>
                                <p class="text-gray-800">{{ date('Y-m-d H:i', strtotime($subscribe->created_at)) }}</p>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold">수정일</label>
                                <p class="text-gray-800">{{ date('Y-m-d H:i', strtotime($subscribe->updated_at)) }}</p>
                            </div>
                        </div>
                    </div>

                    @if($subscribe->description)
                    <div class="form-group">
                        <label class="font-weight-bold">설명</label>
                        <div class="text-gray-800">{{ $subscribe->description }}</div>
                    </div>
                    @endif

                    @if($subscribe->tags)
                    <div class="form-group">
                        <label class="font-weight-bold">태그</label>
                        <div>
                            @foreach(explode(',', $subscribe->tags) as $tag)
                                <span class="badge bg-secondary me-1">{{ trim($tag) }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($subscribe->duration)
                    <div class="form-group">
                        <label class="font-weight-bold">소요 기간</label>
                        <p class="text-gray-800">{{ $subscribe->duration }}</p>
                    </div>
                    @endif
                </div>
            </div>


            <!-- 구독 특징 -->
            @if($subscribe->features)
            @php
                $features = json_decode($subscribe->features, true);
            @endphp
            @if($features && count($features) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">구독 특징</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($features as $feature)
                        <div class="col-md-6 mb-2">
                            <i class="fe fe-check text-success me-2"></i>{{ $feature }}
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
            @endif

            <!-- 구독 프로세스 -->
            @if($subscribe->process)
            @php
                $process = json_decode($subscribe->process, true);
            @endphp
            @if($process && count($process) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">구독 프로세스</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($process as $index => $step)
                        <div class="col-12 mb-2">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <span class="badge bg-primary rounded-circle" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                                        {{ $index + 1 }}
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    {{ $step }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
            @endif

            <!-- 요구사항 및 결과물 -->
            <div class="row">
                <!-- 요구사항 -->
                @if($subscribe->requirements)
                @php
                    $requirements = json_decode($subscribe->requirements, true);
                @endphp
                @if($requirements && count($requirements) > 0)
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">요구사항</h5>
                        </div>
                        <div class="card-body">
                            @foreach($requirements as $requirement)
                            <div class="mb-2">
                                <i class="fe fe-arrow-right text-primary me-2"></i>{{ $requirement }}
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
                @endif

                <!-- 결과물 -->
                @if($subscribe->deliverables)
                @php
                    $deliverables = json_decode($subscribe->deliverables, true);
                @endphp
                @if($deliverables && count($deliverables) > 0)
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">결과물</h5>
                        </div>
                        <div class="card-body">
                            @foreach($deliverables as $deliverable)
                            <div class="mb-2">
                                <i class="fe fe-package text-success me-2"></i>{{ $deliverable }}
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
                @endif
            </div>

            <!-- 상세 내용 -->
            @if($subscribe->content)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">상세 내용</h5>
                </div>
                <div class="card-body">
                    <div class="content-html">
                        {!! $subscribe->content !!}
                    </div>
                </div>
            </div>
            @endif

            <!-- 구독 가격 -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">구독 가격</h5>
                    <div>
                        <a href="{{ route('admin.site.subscribes.price.create', $subscribe->id) }}" class="btn btn-primary btn-sm me-2">
                            <i class="fe fe-plus me-1"></i>가격 추가
                        </a>
                        <a href="{{ route('admin.site.subscribes.price.index', $subscribe->id) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fe fe-dollar-sign me-1"></i>가격 관리
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @php
                        $subscribePrices = \Jiny\Subscribe\Models\subscribePrice::where('subscribe_id', $subscribe->id)
                            ->where('enable', true)
                            ->orderBy('pos')
                            ->get();
                    @endphp

                    @if($subscribePrices->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>옵션명</th>
                                        <th>가격</th>
                                        <th>할인</th>
                                        <th>상태</th>
                                        <th width="80">관리</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subscribePrices as $price)
                                    <tr>
                                        <td>
                                            <strong>{{ $price->name }}</strong>
                                            @if($price->is_popular)
                                                <span class="badge bg-warning text-dark ms-1">인기</span>
                                            @endif
                                            @if($price->is_recommended)
                                                <span class="badge bg-success ms-1">추천</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($price->has_discount)
                                                <span class="text-decoration-line-through text-muted small">{{ $price->formatted_price }}</span>
                                                <br>
                                                <strong class="text-success">{{ $price->formatted_sale_price }}</strong>
                                            @else
                                                <strong>{{ $price->formatted_price }}</strong>
                                            @endif
                                        </td>
                                        <td>
                                            @if($price->has_discount)
                                                <span class="badge bg-success">{{ $price->actual_discount_percentage }}%</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $price->enable ? 'success' : 'secondary' }}">
                                                {{ $price->enable ? '활성' : '비활성' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.site.subscribes.price.edit', [$subscribe->id, $price->id]) }}"
                                                   class="btn btn-outline-primary btn-sm"
                                                   title="수정">
                                                    <i class="fe fe-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fe fe-dollar-sign fe-2x text-muted mb-2"></i>
                            <p class="text-muted mb-3">등록된 가격 옵션이 없습니다</p>
                            <a href="{{ route('admin.site.subscribes.price.create', $subscribe->id) }}" class="btn btn-primary btn-sm">
                                <i class="fe fe-plus me-1"></i>첫 번째 가격 옵션 추가
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 구독 상세 정보 -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">구독 상세 정보</h5>
                    <div>
                        <a href="{{ route('admin.site.subscribes.detail.create', $subscribe->id) }}" class="btn btn-primary btn-sm me-2">
                            <i class="fe fe-plus me-1"></i>상세 정보 추가
                        </a>
                        <a href="{{ route('admin.site.subscribes.detail.index', $subscribe->id) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fe fe-list me-1"></i>상세 정보 관리
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @php
                        $subscribeDetails = \Jiny\Subscribe\Models\subscribePlanDetail::where('subscribe_id', $subscribe->id)
                            ->where('enable', true)
                            ->orderBy('category')
                            ->orderBy('group_name')
                            ->orderBy('group_order')
                            ->orderBy('pos')
                            ->get();
                    @endphp

                    @if($subscribeDetails->count() > 0)
                        @php
                            $groupedDetails = $subscribeDetails->groupBy(function($detail) {
                                return $detail->category . '|' . ($detail->group_name ?: '기타');
                            });
                        @endphp

                        @foreach($groupedDetails as $groupKey => $details)
                            @php
                                [$category, $groupName] = explode('|', $groupKey);
                                $firstDetail = $details->first();
                            @endphp
                            <div class="mb-4">
                                <h6 class="text-muted mb-2">
                                    <span class="badge bg-secondary me-2">{{ $firstDetail->category_display }}</span>
                                    {{ $groupName }}
                                </h6>
                                <div class="row">
                                    @foreach($details as $detail)
                                    <div class="col-md-6 mb-2">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0 me-2">
                                                <i class="{{ $detail->icon_class }} {{ $detail->color_class }}"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center">
                                                    <strong class="me-2">{{ $detail->title }}</strong>
                                                    @if($detail->is_highlighted)
                                                        <span class="{{ $detail->badge_class }}">{{ $detail->detail_type_display }}</span>
                                                    @endif
                                                </div>
                                                @if($detail->description)
                                                    <small class="text-muted d-block">{{ $detail->description }}</small>
                                                @endif
                                                <div class="mt-1">
                                                    <span class="fw-medium">{{ $detail->formatted_value }}</span>
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <a href="{{ route('admin.site.subscribes.detail.edit', [$subscribe->id, $detail->id]) }}"
                                                   class="btn btn-outline-secondary btn-sm"
                                                   title="수정">
                                                    <i class="fe fe-edit"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fe fe-info fe-2x text-muted mb-2"></i>
                            <p class="text-muted mb-3">등록된 상세 정보가 없습니다</p>
                            <a href="{{ route('admin.site.subscribes.detail.create', $subscribe->id) }}" class="btn btn-primary btn-sm">
                                <i class="fe fe-plus me-1"></i>첫 번째 상세 정보 추가
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 사이드바 -->
        <div class="col-lg-4">
            <!-- 구독 이미지 -->
            @if($subscribe->image)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">대표 이미지</h5>
                </div>
                <div class="card-body text-center">
                    <img src="{{ $subscribe->image }}" alt="{{ $subscribe->title }}" class="img-fluid rounded">
                </div>
            </div>
            @endif

            <!-- 추가 이미지들 -->
            @if($subscribe->images)
            @php
                $images = json_decode($subscribe->images, true);
            @endphp
            @if($images && count($images) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">추가 이미지</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($images as $image)
                        <div class="col-6 mb-2">
                            <img src="{{ $image }}" alt="{{ $subscribe->title }}" class="img-fluid rounded">
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
            @endif

            <!-- SEO 정보 -->
            @if($subscribe->meta_title || $subscribe->meta_description)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">SEO 정보</h5>
                </div>
                <div class="card-body">
                    @if($subscribe->meta_title)
                    <div class="mb-3">
                        <label class="font-weight-bold small">META 제목</label>
                        <p class="text-muted small mb-0">{{ $subscribe->meta_title }}</p>
                    </div>
                    @endif
                    @if($subscribe->meta_description)
                    <div>
                        <label class="font-weight-bold small">META 설명</label>
                        <p class="text-muted small mb-0">{{ $subscribe->meta_description }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- 관리 정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">관리 정보</h5>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="mb-2">
                            <strong>ID:</strong> {{ $subscribe->id }}
                        </div>
                        <div class="mb-2">
                            <strong>카테고리 ID:</strong> {{ $subscribe->category_id ?? '-' }}
                        </div>
                        <div class="mb-2">
                            <strong>등록일:</strong><br>
                            {{ date('Y-m-d H:i:s', strtotime($subscribe->created_at)) }}
                        </div>
                        <div class="mb-2">
                            <strong>수정일:</strong><br>
                            {{ date('Y-m-d H:i:s', strtotime($subscribe->updated_at)) }}
                        </div>
                        @if($subscribe->deleted_at)
                        <div class="mb-2">
                            <strong class="text-danger">삭제일:</strong><br>
                            <span class="text-danger">{{ date('Y-m-d H:i:s', strtotime($subscribe->deleted_at)) }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* 통계 카드 원형 아이콘 스타일 */
.stat-circle {
    width: 48px !important;
    height: 48px !important;
    min-width: 48px;
    min-height: 48px;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
}

.stat-circle i {
    font-size: 20px;
}

/* 콘텐츠 HTML 스타일링 */
.content-html {
    line-height: 1.6;
}

.content-html h1,
.content-html h2,
.content-html h3,
.content-html h4,
.content-html h5,
.content-html h6 {
    margin-top: 1.5rem;
    margin-bottom: 1rem;
    font-weight: 600;
}

.content-html p {
    margin-bottom: 1rem;
}

.content-html ul,
.content-html ol {
    margin-bottom: 1rem;
    padding-left: 1.5rem;
}

.content-html li {
    margin-bottom: 0.25rem;
}
</style>
@endpush

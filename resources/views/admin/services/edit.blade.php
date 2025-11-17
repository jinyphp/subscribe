@extends($layout ?? 'jiny-subscribe::layouts.admin.sidebar')

@section('title', $config['title'])

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $config['title'] }}</h2>
                    <p class="text-muted mb-0">{{ $config['subtitle'] }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.site.subscribes.index') }}" class="btn btn-outline-secondary">
                        <i class="fe fe-arrow-left me-2"></i>목록으로
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 구독 수정 폼 -->
    <form method="POST" action="{{ route('admin.site.subscribes.update', $subscribe->id) }}">
        @csrf
        @method('PUT')

        <div class="row">
            <!-- 기본 정보 -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">기본 정보</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="title" class="form-label">구독명 <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('title') is-invalid @enderror"
                                       id="title"
                                       name="title"
                                       value="{{ old('title', $subscribe->title) }}"
                                       required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">간단 설명</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          id="description"
                                          name="description"
                                          rows="3">{{ old('description', $subscribe->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="content" class="form-label">상세 설명</label>
                                <textarea class="form-control @error('content') is-invalid @enderror"
                                          id="content"
                                          name="content"
                                          rows="10">{{ old('content', $subscribe->content) }}</textarea>
                                @error('content')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 구독 상세 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">구독 상세 정보</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="image" class="form-label">대표 이미지 URL</label>
                                <input type="url"
                                       class="form-control @error('image') is-invalid @enderror"
                                       id="image"
                                       name="image"
                                       value="{{ old('image', $subscribe->image) }}">
                                @error('image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="features" class="form-label">구독 특징</label>
                                <textarea class="form-control @error('features') is-invalid @enderror"
                                          id="features"
                                          name="features"
                                          rows="4"
                                          placeholder="JSON 형식으로 입력하세요. 예: [&quot;특징1&quot;, &quot;특징2&quot;, &quot;특징3&quot;]">{{ old('features', $subscribe->features) }}</textarea>
                                @error('features')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="process" class="form-label">구독 프로세스</label>
                                <textarea class="form-control @error('process') is-invalid @enderror"
                                          id="process"
                                          name="process"
                                          rows="4"
                                          placeholder="JSON 형식으로 입력하세요. 예: [&quot;1단계: 상담&quot;, &quot;2단계: 분석&quot;, &quot;3단계: 제안&quot;]">{{ old('process', $subscribe->process) }}</textarea>
                                @error('process')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="requirements" class="form-label">요구사항</label>
                                <textarea class="form-control @error('requirements') is-invalid @enderror"
                                          id="requirements"
                                          name="requirements"
                                          rows="4"
                                          placeholder="JSON 형식으로 입력하세요. 예: [&quot;요구사항1&quot;, &quot;요구사항2&quot;]">{{ old('requirements', $subscribe->requirements) }}</textarea>
                                @error('requirements')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="deliverables" class="form-label">결과물</label>
                                <textarea class="form-control @error('deliverables') is-invalid @enderror"
                                          id="deliverables"
                                          name="deliverables"
                                          rows="4"
                                          placeholder="JSON 형식으로 입력하세요. 예: [&quot;결과물1&quot;, &quot;결과물2&quot;]">{{ old('deliverables', $subscribe->deliverables) }}</textarea>
                                @error('deliverables')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="tags" class="form-label">태그</label>
                                <input type="text"
                                       class="form-control @error('tags') is-invalid @enderror"
                                       id="tags"
                                       name="tags"
                                       value="{{ old('tags', $subscribe->tags) }}"
                                       placeholder="쉼표로 구분하여 입력하세요. 예: 컨설팅, 개발, 디자인">
                                @error('tags')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 사이드바 정보 -->
            <div class="col-lg-4">
                <!-- 구독 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">구독 정보</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="category_id" class="form-label">카테고리</label>
                            <div class="d-flex">
                                <select class="form-control @error('category_id') is-invalid @enderror"
                                        id="category_id"
                                        name="category_id">
                                    <option value="">카테고리 선택</option>
                                    @if(isset($categories) && count($categories) > 0)
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}"
                                                    {{ old('category_id', $subscribe->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->display_title }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <a href="{{ route('admin.subscribe.categories.index') }}" class="btn btn-outline-info ml-2" title="카테고리 관리">
                                    ⚙️
                                </a>
                            </div>
                            @if(count($categories) === 0)
                                <small class="text-muted">
                                    ℹ️
                                    사용 가능한 카테고리가 없습니다. <a href="{{ route('admin.subscribe.categories.index') }}">카테고리를 먼저 생성하세요</a>
                                </small>
                            @endif
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">구독 가격 (원)</label>
                            <input type="number"
                                   class="form-control @error('price') is-invalid @enderror"
                                   id="price"
                                   name="price"
                                   value="{{ old('price', $subscribe->price) }}"
                                   step="0.01"
                                   min="0">
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">구독 기본 가격을 입력하세요.</div>
                        </div>

                        <div class="mb-3">
                            <label for="duration" class="form-label">소요 기간</label>
                            <input type="text"
                                   class="form-control @error('duration') is-invalid @enderror"
                                   id="duration"
                                   name="duration"
                                   value="{{ old('duration', $subscribe->duration) }}"
                                   placeholder="예: 1-2주, 30일, 3개월">
                            @error('duration')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">구독 완료까지 예상 기간을 입력하세요.</div>
                        </div>
                    </div>
                </div>

                <!-- 상태 설정 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">상태 설정</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="enable"
                                       name="enable"
                                       value="1"
                                       {{ old('enable', $subscribe->enable) ? 'checked' : '' }}>
                                <label class="form-check-label" for="enable">
                                    구독 활성화
                                </label>
                            </div>
                            <div class="form-text">체크하면 고객에게 노출됩니다.</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="featured"
                                       name="featured"
                                       value="1"
                                       {{ old('featured', $subscribe->featured) ? 'checked' : '' }}>
                                <label class="form-check-label" for="featured">
                                    추천 구독
                                </label>
                            </div>
                            <div class="form-text">추천 구독로 표시됩니다.</div>
                        </div>
                    </div>
                </div>

                <!-- SEO 설정 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">SEO 설정</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="meta_title" class="form-label">메타 제목</label>
                            <input type="text"
                                   class="form-control @error('meta_title') is-invalid @enderror"
                                   id="meta_title"
                                   name="meta_title"
                                   value="{{ old('meta_title', $subscribe->meta_title) }}">
                            @error('meta_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="meta_description" class="form-label">메타 설명</label>
                            <textarea class="form-control @error('meta_description') is-invalid @enderror"
                                      id="meta_description"
                                      name="meta_description"
                                      rows="3">{{ old('meta_description', $subscribe->meta_description) }}</textarea>
                            @error('meta_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- 액션 버튼 -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save me-2"></i>구독 등록
                            </button>
                            <a href="{{ route('admin.site.subscribes.index') }}" class="btn btn-outline-secondary">
                                <i class="fe fe-x me-2"></i>취소
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// 폼 제출 전 JSON 유효성 검사
document.querySelector('form').addEventListener('submit', function(e) {
    const features = document.getElementById('features').value;
    const process = document.getElementById('process').value;
    const requirements = document.getElementById('requirements').value;
    const deliverables = document.getElementById('deliverables').value;

    // JSON 필드들 검증
    const jsonFields = [
        { value: features, name: '구독 특징' },
        { value: process, name: '구독 프로세스' },
        { value: requirements, name: '요구사항' },
        { value: deliverables, name: '결과물' }
    ];

    for (let field of jsonFields) {
        if (field.value && field.value.trim()) {
            try {
                JSON.parse(field.value);
            } catch (error) {
                alert(`${field.name} 필드의 JSON 형식이 올바르지 않습니다.`);
                e.preventDefault();
                return;
            }
        }
    }
});
</script>
@endpush

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscribe_plan_detail', function (Blueprint $table) {
            // subscribe_id 컬럼 추가 (구독에 직접 연결)
            $table->unsignedBigInteger('subscribe_id')->nullable()->after('id');
            $table->foreign('subscribe_id')->references('id')->on('subscribes')->onDelete('cascade');

            // subscribe_plan_id를 nullable로 변경 (플랜과 구독 둘 다 지원)
            $table->unsignedBigInteger('subscribe_plan_id')->nullable()->change();

            // 인덱스 추가
            $table->index(['subscribe_id', 'enable']);
            $table->index(['subscribe_id', 'detail_type', 'enable']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscribe_plan_detail', function (Blueprint $table) {
            $table->dropForeign(['subscribe_id']);
            $table->dropColumn('subscribe_id');
            $table->unsignedBigInteger('subscribe_plan_id')->nullable(false)->change();
        });
    }
};

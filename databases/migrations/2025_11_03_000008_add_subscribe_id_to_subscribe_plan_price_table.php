<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscribe_plan_price', function (Blueprint $table) {
            // subscribe_id 컬럼 추가 (구독에 직접 연결용)
            $table->unsignedBigInteger('subscribe_id')->nullable()->after('subscribe_plan_id');
            $table->foreign('subscribe_id')->references('id')->on('subscribes')->onDelete('cascade');

            // subscribe_plan_id를 nullable로 변경 (기존 데이터 유지)
            $table->unsignedBigInteger('subscribe_plan_id')->nullable()->change();

            // 인덱스 추가
            $table->index(['subscribe_id', 'enable']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscribe_plan_price', function (Blueprint $table) {
            $table->dropForeign(['subscribe_id']);
            $table->dropColumn('subscribe_id');
            $table->unsignedBigInteger('subscribe_plan_id')->nullable(false)->change();
        });
    }
};

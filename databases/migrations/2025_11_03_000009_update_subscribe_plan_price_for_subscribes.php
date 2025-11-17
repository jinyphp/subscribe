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
            // 가격을 nullable로 변경 (무료 가격 지원)
            $table->decimal('price', 10, 2)->nullable()->change();

            // min_quantity, max_quantity가 없다면 추가 (이미 있을 수 있음)
            if (!Schema::hasColumn('subscribe_plan_price', 'min_quantity')) {
                $table->integer('min_quantity')->default(1);
            }
            if (!Schema::hasColumn('subscribe_plan_price', 'max_quantity')) {
                $table->integer('max_quantity')->nullable();
            }

            // 인덱스 추가
            if (!Schema::hasIndex('subscribe_plan_price', ['subscribe_id', 'enable'])) {
                $table->index(['subscribe_id', 'enable'], 'subscribe_price_subscribe_enable_index');
            }
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
            // 가격을 다시 required로 변경
            $table->decimal('price', 10, 2)->nullable(false)->change();
        });
    }
};
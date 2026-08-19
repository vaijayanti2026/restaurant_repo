<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSquareFieldsToBranchesAndOrders extends Migration
{
    public function up()
    {
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'square_location_id')) {
                $table->string('square_location_id')->nullable()->after('fax')->index();
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'square_order_id')) {
                $table->string('square_order_id')->nullable()->after('transaction_reference')->index();
            }

            if (!Schema::hasColumn('orders', 'square_payment_id')) {
                $table->string('square_payment_id')->nullable()->after('square_order_id')->index();
            }

            if (!Schema::hasColumn('orders', 'square_location_id')) {
                $table->string('square_location_id')->nullable()->after('square_payment_id')->index();
            }

            if (!Schema::hasColumn('orders', 'square_source')) {
                $table->string('square_source')->nullable()->after('square_location_id');
            }
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['square_source', 'square_location_id', 'square_payment_id', 'square_order_id'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'square_location_id')) {
                $table->dropColumn('square_location_id');
            }
        });
    }
}

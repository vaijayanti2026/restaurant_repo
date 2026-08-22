<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSquarePaymentIdempotencyConstraints extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Nullable so historical orders remain untouched. Every new Square
            // order stores its local payment reference here; the unique index is
            // the final concurrency guard against repeated callbacks.
            $table->string('payment_idempotency_key', 100)
                ->nullable()
                ->after('transaction_reference')
                ->unique('orders_payment_idempotency_key_unique');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_payment_idempotency_key_unique');
            $table->dropColumn('payment_idempotency_key');
        });
    }
}

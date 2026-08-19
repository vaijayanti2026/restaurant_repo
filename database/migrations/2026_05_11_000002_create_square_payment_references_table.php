<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSquarePaymentReferencesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('square_payment_references')) {
            Schema::create('square_payment_references', function (Blueprint $table) {
                $table->id();
                $table->string('reference')->unique();
                $table->string('square_order_id')->nullable()->index();
                $table->string('square_payment_id')->nullable()->index();
                $table->string('square_location_id')->nullable()->index();
                $table->bigInteger('local_order_id')->nullable()->index();
                $table->bigInteger('customer_id')->nullable()->index();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('currency', 10)->nullable();
                $table->string('status')->default('created');
                $table->text('callback')->nullable();
                $table->longText('payload')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('square_payment_references');
    }
}

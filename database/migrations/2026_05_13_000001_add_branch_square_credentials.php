<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBranchSquareCredentials extends Migration
{
    public function up()
    {
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'square_status')) {
                $table->tinyInteger('square_status')->default(0)->after('square_location_id');
            }

            if (!Schema::hasColumn('branches', 'square_application_id')) {
                $table->string('square_application_id')->nullable()->after('square_status');
            }

            if (!Schema::hasColumn('branches', 'square_access_token')) {
                $table->text('square_access_token')->nullable()->after('square_application_id');
            }

            if (!Schema::hasColumn('branches', 'square_environment')) {
                $table->string('square_environment', 20)->default('sandbox')->after('square_access_token');
            }

            if (!Schema::hasColumn('branches', 'square_webhook_signature_key')) {
                $table->text('square_webhook_signature_key')->nullable()->after('square_environment');
            }
        });

        Schema::table('square_payment_references', function (Blueprint $table) {
            if (!Schema::hasColumn('square_payment_references', 'branch_id')) {
                $table->bigInteger('branch_id')->nullable()->index()->after('customer_id');
            }
        });
    }

    public function down()
    {
        Schema::table('square_payment_references', function (Blueprint $table) {
            if (Schema::hasColumn('square_payment_references', 'branch_id')) {
                $table->dropColumn('branch_id');
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            foreach (['square_webhook_signature_key', 'square_environment', 'square_access_token', 'square_application_id', 'square_status'] as $column) {
                if (Schema::hasColumn('branches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}

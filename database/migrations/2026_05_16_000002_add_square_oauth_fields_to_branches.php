<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSquareOauthFieldsToBranches extends Migration
{
    public function up()
    {
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'square_merchant_id')) {
                $table->string('square_merchant_id')->nullable()->after('square_application_id');
            }

            if (!Schema::hasColumn('branches', 'square_oauth_refresh_token')) {
                $table->text('square_oauth_refresh_token')->nullable()->after('square_access_token');
            }

            if (!Schema::hasColumn('branches', 'square_oauth_token_expires_at')) {
                $table->timestamp('square_oauth_token_expires_at')->nullable()->after('square_oauth_refresh_token');
            }

            if (!Schema::hasColumn('branches', 'square_oauth_refresh_token_expires_at')) {
                $table->timestamp('square_oauth_refresh_token_expires_at')->nullable()->after('square_oauth_token_expires_at');
            }

            if (!Schema::hasColumn('branches', 'square_oauth_connected_at')) {
                $table->timestamp('square_oauth_connected_at')->nullable()->after('square_oauth_refresh_token_expires_at');
            }
        });
    }

    public function down()
    {
        Schema::table('branches', function (Blueprint $table) {
            foreach ([
                'square_oauth_connected_at',
                'square_oauth_refresh_token_expires_at',
                'square_oauth_token_expires_at',
                'square_oauth_refresh_token',
                'square_merchant_id',
            ] as $column) {
                if (Schema::hasColumn('branches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}

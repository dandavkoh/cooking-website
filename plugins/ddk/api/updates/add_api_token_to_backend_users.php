<?php

namespace Ddk\Api\Updates;

use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Add API token column to backend_users table
 *
 * This migration adds an api_token column that stores a hashed
 * token for API authentication.
 */
class AddApiTokenToBackendUsers extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('backend_users', 'api_token')) {
            Schema::table('backend_users', function (Blueprint $table) {
                $table->string('api_token', 64)->nullable()->unique()->after('persist_code');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('backend_users', 'api_token')) {
            Schema::table('backend_users', function (Blueprint $table) {
                $table->dropColumn('api_token');
            });
        }
    }
}

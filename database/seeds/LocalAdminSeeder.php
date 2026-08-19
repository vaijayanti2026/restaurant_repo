<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LocalAdminSeeder extends Seeder
{
    public function run()
    {
        $now = now();

        if (Schema::hasTable('admin_roles')) {
            DB::table('admin_roles')->updateOrInsert(
                ['id' => 1],
                [
                    'name' => 'Master Admin',
                    'module_access' => null,
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $admin = [
            'f_name' => 'Admin',
            'l_name' => '',
            'phone' => '',
            'email' => 'admin@gmail.com',
            'image' => 'def.png',
            'password' => Hash::make('12345678'),
            'remember_token' => Str::random(10),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('admins', 'admin_role_id')) {
            $admin['admin_role_id'] = 1;
        }

        if (Schema::hasColumn('admins', 'status')) {
            $admin['status'] = 1;
        }

        foreach (['identity_number', 'identity_type', 'identity_image'] as $column) {
            if (Schema::hasColumn('admins', $column)) {
                $admin[$column] = '';
            }
        }

        $existing = DB::table('admins')->where('email', 'admin@gmail.com')->first();

        if ($existing) {
            DB::table('admins')->where('email', 'admin@gmail.com')->update($admin);
            return;
        }

        $admin['id'] = ((int) DB::table('admins')->max('id')) + 1;
        DB::table('admins')->insert($admin);
    }
}

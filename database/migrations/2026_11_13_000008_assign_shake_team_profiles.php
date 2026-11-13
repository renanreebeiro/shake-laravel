<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('email', 'renanreebeiro@gmail.com')
            ->update(['is_admin' => true, 'team_role' => 'programmer']);

        DB::table('users')->whereIn('email', ['apmonteiro2012@gmail.com', 'arthurxerfan@gmail.com'])
            ->update(['is_admin' => true, 'team_role' => 'member']);
    }
};

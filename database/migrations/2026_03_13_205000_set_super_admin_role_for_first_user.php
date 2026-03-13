<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ✅ এই Migration দুটো কাজ করবে:
     * ১. যদি 'role' কলাম না থাকে, তাহলে যোগ করবে (নতুন সার্ভার সেফটি)
     * ২. সবচেয়ে পুরনো User (প্রথম তৈরি হওয়া) কে 'super_admin' হিসেবে মার্ক করবে
     */
    public function up(): void
    {
        // ১. 'role' কলাম না থাকলে যোগ করা
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('client')->after('email');
            });
        }

        // ২. সবচেয়ে পুরনো user-কে (যার id সবচেয়ে ছোট) super_admin বানানো
        $firstUser = DB::table('users')->orderBy('id', 'asc')->first();

        if ($firstUser) {
            DB::table('users')
                ->where('id', $firstUser->id)
                ->update(['role' => 'super_admin']);
        }
    }

    /**
     * Rollback: super_admin কে আবার client করে দেবে।
     */
    public function down(): void
    {
        $firstUser = DB::table('users')->orderBy('id', 'asc')->first();

        if ($firstUser) {
            DB::table('users')
                ->where('id', $firstUser->id)
                ->update(['role' => 'client']);
        }
    }
};

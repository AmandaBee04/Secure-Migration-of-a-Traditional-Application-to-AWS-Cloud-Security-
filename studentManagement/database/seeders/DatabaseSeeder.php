<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * Uses DB::table() directly — bypasses Eloquent model booting entirely.
     * Eloquent's HasApiTokens / $appends traits crash PHP on ECS Fargate
     * (SIGSEGV in the Zend runtime during model boot).
     *
     * Passwords are precomputed bcrypt hashes (cost=8).
     *   UC001 / MU001 / AD001 → "001"
     *   UC002 / MU002         → "002"
     *   UC003                 → "003"
     */
    public function run(): void
    {
        $now   = now()->toDateTimeString();
        $h001  = '$2b$08$wN0bpIj.I9YCqxivR2D8buqey55UNzlG5TwNBxDeGBthRR2r4dDsa';
        $h002  = '$2b$08$A5KSNFJzZV8CRPIVl4a.j.zYKYWd6IZ9h7o.NhTWhWcbjUDvTqNWC';
        $h003  = '$2b$08$8YJX.fJ99QDihXFQzxLBa.wD/ralWBVS/BR55142fPA4zxYn7sm6q';

        DB::table('students')->insertOrIgnore([
            ['id' => 'UC001', 'name' => 'Ali Ahmad',  'email' => 'ali@student.com',  'password' => $h001, 'icNumber' => '010101010101', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'UC002', 'name' => 'Siti Nur',   'email' => 'siti@student.com', 'password' => $h002, 'icNumber' => '020202020202', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'UC003', 'name' => 'John Tan',   'email' => 'john@student.com', 'password' => $h003, 'icNumber' => '030303030303', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('lecturers')->insertOrIgnore([
            ['id' => 'MU001', 'name' => 'Dr Lim',  'email' => 'lim@mmu.edu.my',  'password' => $h001, 'icNumber' => '900101010101', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'MU002', 'name' => 'Dr Wong', 'email' => 'wong@mmu.edu.my', 'password' => $h002, 'icNumber' => '910202020202', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('subjects')->insertOrIgnore([
            ['id' => 'SJ001', 'name' => 'Database Security', 'lecturerID' => 'MU001', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'SJ002', 'name' => 'Cloud Security',    'lecturerID' => 'MU002', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Remove any test/extra subjects and lecturers added during testing
        DB::table('subjects')->whereNotIn('id', ['SJ001', 'SJ002'])->delete();
        DB::table('lecturers')->whereNotIn('id', ['MU001', 'MU002'])->delete();

        // Results use UUID PKs so insertOrIgnore can't detect duplicates across runs.
        // Delete seed students' results first, then insert exactly what we want.
        DB::table('results')->whereIn('studentID', ['UC001', 'UC002', 'UC003'])->delete();
        DB::table('results')->insert([
            ['id' => (string) Str::uuid(), 'studentID' => 'UC001', 'subjectID' => 'SJ001', 'grade' => 'A', 'semester' => '2026', 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::uuid(), 'studentID' => 'UC001', 'subjectID' => 'SJ002', 'grade' => 'B', 'semester' => '2026', 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::uuid(), 'studentID' => 'UC002', 'subjectID' => 'SJ001', 'grade' => 'B', 'semester' => '2026', 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::uuid(), 'studentID' => 'UC003', 'subjectID' => 'SJ002', 'grade' => 'A', 'semester' => '2026', 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::uuid(), 'studentID' => 'UC003', 'subjectID' => 'SJ001', 'grade' => 'C', 'semester' => '2026', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('admins')->insertOrIgnore([
            ['id' => 'AD001', 'name' => 'Admin One', 'email' => 'admin001@mmu.edu.my', 'password' => $h001, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}

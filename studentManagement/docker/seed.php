<?php
// Standalone seed script — uses DB::table() only, no Eloquent model boot
require '/var/www/html/vendor/autoload.php';

$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$now  = now()->toDateTimeString();
$h001 = '$2b$08$wN0bpIj.I9YCqxivR2D8buqey55UNzlG5TwNBxDeGBthRR2r4dDsa';
$h002 = '$2b$08$A5KSNFJzZV8CRPIVl4a.j.zYKYWd6IZ9h7o.NhTWhWcbjUDvTqNWC';
$h003 = '$2b$08$8YJX.fJ99QDihXFQzxLBa.wD/ralWBVS/BR55142fPA4zxYn7sm6q';

try {
    DB::table('students')->insertOrIgnore([
        ['id'=>'UC001','name'=>'Ali Ahmad','email'=>'ali@student.com','password'=>$h001,'icNumber'=>'010101010101','created_at'=>$now,'updated_at'=>$now],
        ['id'=>'UC002','name'=>'Siti Nur','email'=>'siti@student.com','password'=>$h002,'icNumber'=>'020202020202','created_at'=>$now,'updated_at'=>$now],
        ['id'=>'UC003','name'=>'John Tan','email'=>'john@student.com','password'=>$h003,'icNumber'=>'030303030303','created_at'=>$now,'updated_at'=>$now],
    ]);
    DB::table('lecturers')->insertOrIgnore([
        ['id'=>'MU001','name'=>'Dr Lim','email'=>'lim@mmu.edu.my','password'=>$h001,'icNumber'=>'900101010101','created_at'=>$now,'updated_at'=>$now],
        ['id'=>'MU002','name'=>'Dr Wong','email'=>'wong@mmu.edu.my','password'=>$h002,'icNumber'=>'910202020202','created_at'=>$now,'updated_at'=>$now],
    ]);
    DB::table('subjects')->insertOrIgnore([
        ['id'=>'SJ001','name'=>'Database Security','lecturerID'=>'MU001','created_at'=>$now,'updated_at'=>$now],
        ['id'=>'SJ002','name'=>'Cloud Security','lecturerID'=>'MU002','created_at'=>$now,'updated_at'=>$now],
    ]);
    DB::table('admins')->insertOrIgnore([
        ['id'=>'AD001','name'=>'Admin One','email'=>'admin001@mmu.edu.my','password'=>$h001,'created_at'=>$now,'updated_at'=>$now],
    ]);
    echo "Seed complete.\n";
} catch (Exception $e) {
    echo "Seed error: " . $e->getMessage() . "\n";
}

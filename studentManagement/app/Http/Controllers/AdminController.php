<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin controller — uses DB::table() exclusively to avoid Eloquent model-boot
 * SIGSEGV on ECS Fargate (PHP 8.2 + Debian Bookworm).
 */
class AdminController extends Controller
{
    // ── helpers ──────────────────────────────────────────────────────────────

    private function maskIC(?string $ic): ?string
    {
        if (!$ic) return null;
        $len = strlen($ic);
        if ($len <= 8) {
            $v = (int) floor($len / 2);
            return substr($ic, 0, $v) . str_repeat('*', $len - $v);
        }
        return substr($ic, 0, 8) . str_repeat('*', $len - 8);
    }

    private function fmtStudent(object $r): array
    {
        return ['id' => $r->id, 'name' => $r->name, 'email' => $r->email, 'maskedIC' => $this->maskIC($r->icNumber ?? null)];
    }

    private function fmtLecturer(object $r): array
    {
        return ['id' => $r->id, 'name' => $r->name, 'email' => $r->email, 'maskedIC' => $this->maskIC($r->icNumber ?? null)];
    }

    // ── students ─────────────────────────────────────────────────────────────

    public function getStudent(Request $request)
    {
        $q = DB::table('students');
        if ($request->search) {
            $s = $request->search;
            $q->where(fn($x) => $x->where('name', 'like', "%$s%")->orWhere('id', 'like', "%$s%"));
        }
        $rows = $q->get();
        if ($rows->isEmpty()) return response()->json(['message' => 'No students found'], 200);
        return response()->json($rows->map(fn($r) => $this->fmtStudent($r)), 200);
    }

    public function addStudent(Request $request)
    {
        $pepper = env('PASSWORD_PEPPER', '');
        $now    = now()->toDateTimeString();
        DB::table('students')->insert([
            'id'         => $request->id,
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => password_hash($request->password . $pepper, PASSWORD_BCRYPT, ['cost' => 8]),
            'icNumber'   => $request->icNumber,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return response()->json(['message' => 'student added successfully'], 201);
    }

    public function updateStudent(Request $request, $id)
    {
        $student = DB::table('students')->where('id', $id)->first();
        if (!$student) return response()->json(['message' => 'student not found'], 404);

        $upd = ['updated_at' => now()->toDateTimeString()];
        if ($request->password) {
            $pepper = env('PASSWORD_PEPPER', '');
            if (password_verify($request->password . $pepper, $student->password))
                return response()->json(['message' => 'Password cannot be same as old password'], 400);
            $upd['password'] = password_hash($request->password . $pepper, PASSWORD_BCRYPT, ['cost' => 8]);
        }
        if ($request->name)     $upd['name']     = $request->name;
        if ($request->email)    $upd['email']    = $request->email;
        if ($request->icNumber) $upd['icNumber'] = $request->icNumber;

        DB::table('students')->where('id', $id)->update($upd);
        return response()->json(['message' => 'student updated successfully'], 200);
    }

    public function deleteStudent($id)
    {
        if (!DB::table('students')->where('id', $id)->delete())
            return response()->json(['message' => 'student not found'], 404);
        return response()->json(['message' => 'student deleted successfully'], 200);
    }

    // ── lecturers ────────────────────────────────────────────────────────────

    public function getLecturer(Request $request)
    {
        $q = DB::table('lecturers');
        if ($request->search) {
            $s = $request->search;
            $q->where(fn($x) => $x->where('name', 'like', "%$s%")->orWhere('id', 'like', "%$s%"));
        }
        $rows = $q->get();
        if ($rows->isEmpty()) return response()->json(['message' => 'No lecturers found'], 200);
        return response()->json($rows->map(fn($r) => $this->fmtLecturer($r)), 200);
    }

    public function addLecturer(Request $request)
    {
        $pepper = env('PASSWORD_PEPPER', '');
        $now    = now()->toDateTimeString();
        DB::table('lecturers')->insert([
            'id'         => $request->id,
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => password_hash($request->password . $pepper, PASSWORD_BCRYPT, ['cost' => 8]),
            'icNumber'   => $request->icNumber,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return response()->json(['message' => 'lecturer added successfully'], 201);
    }

    public function updateLecturer(Request $request, $id)
    {
        $lecturer = DB::table('lecturers')->where('id', $id)->first();
        if (!$lecturer) return response()->json(['message' => 'lecturer not found'], 404);

        $upd = ['updated_at' => now()->toDateTimeString()];
        if ($request->password) {
            $pepper = env('PASSWORD_PEPPER', '');
            if (password_verify($request->password . $pepper, $lecturer->password))
                return response()->json(['message' => 'Password cannot be same as old password'], 400);
            $upd['password'] = password_hash($request->password . $pepper, PASSWORD_BCRYPT, ['cost' => 8]);
        }
        if ($request->name)     $upd['name']     = $request->name;
        if ($request->email)    $upd['email']    = $request->email;
        if ($request->icNumber) $upd['icNumber'] = $request->icNumber;

        DB::table('lecturers')->where('id', $id)->update($upd);
        return response()->json(['message' => 'lecturer updated successfully'], 200);
    }

    public function deleteLecturer($id)
    {
        if (!DB::table('lecturers')->where('id', $id)->delete())
            return response()->json(['message' => 'lecturer not found'], 404);
        return response()->json(['message' => 'lecturer deleted successfully'], 200);
    }

    // ── subjects ─────────────────────────────────────────────────────────────

    public function getSubject(Request $request)
    {
        $q = DB::table('subjects')
            ->join('lecturers', 'subjects.lecturerID', '=', 'lecturers.id')
            ->select('subjects.id', 'subjects.name', 'subjects.lecturerID',
                     'subjects.created_at', 'subjects.updated_at',
                     'lecturers.id as lec_id', 'lecturers.name as lec_name');

        if ($request->search) {
            $s = $request->search;
            $q->where(fn($x) => $x->where('subjects.name', 'like', "%$s%")->orWhere('subjects.id', 'like', "%$s%"));
        }

        $rows = $q->get();
        if ($rows->isEmpty()) return response()->json(['message' => 'No subjects found'], 200);

        return response()->json($rows->map(fn($r) => [
            'id'         => $r->id,
            'name'       => $r->name,
            'lecturerID' => $r->lecturerID,
            'created_at' => $r->created_at,
            'updated_at' => $r->updated_at,
            'lecturer'   => ['id' => $r->lec_id, 'name' => $r->lec_name],
        ]), 200);
    }

    public function addSubject(Request $request)
    {
        if (!DB::table('lecturers')->where('id', $request->lecturerID)->exists())
            return response()->json(['message' => 'Lecturer not found'], 404);

        // Enforce 1-to-1: each lecturer can only have one subject
        if (DB::table('subjects')->where('lecturerID', $request->lecturerID)->exists())
            return response()->json(['message' => 'Lecturer already has a subject assigned'], 400);

        $now = now()->toDateTimeString();
        DB::table('subjects')->insert([
            'id'         => $request->id,
            'name'       => $request->name,
            'lecturerID' => $request->lecturerID,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return response()->json(['message' => 'subject added successfully'], 201);
    }

    public function updateSubject(Request $request, $id)
    {
        if (!DB::table('subjects')->where('id', $id)->exists())
            return response()->json(['message' => 'subject not found'], 404);

        if ($request->lecturerID) {
            if (!DB::table('lecturers')->where('id', $request->lecturerID)->exists())
                return response()->json(['message' => 'Lecturer not found'], 404);

            // Enforce 1-to-1: reject if another subject already uses this lecturerID
            if (DB::table('subjects')->where('lecturerID', $request->lecturerID)->where('id', '!=', $id)->exists())
                return response()->json(['message' => 'Lecturer already has a subject assigned'], 400);
        }

        $upd = ['updated_at' => now()->toDateTimeString()];
        if ($request->name)       $upd['name']       = $request->name;
        if ($request->lecturerID) $upd['lecturerID'] = $request->lecturerID;

        DB::table('subjects')->where('id', $id)->update($upd);
        return response()->json(['message' => 'subject updated successfully'], 200);
    }

    public function deleteSubject($id)
    {
        if (!DB::table('subjects')->where('id', $id)->delete())
            return response()->json(['message' => 'subject not found'], 404);
        return response()->json(['message' => 'subject deleted successfully'], 200);
    }
}

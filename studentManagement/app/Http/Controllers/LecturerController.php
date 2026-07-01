<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Lecturer controller — DB::table() only, no Eloquent model boot.
 */
class LecturerController extends Controller
{
    public function addResults(Request $request)
    {
        $lecturerID = $request->user()->id;

        $subject = DB::table('subjects')->where('lecturerID', $lecturerID)->first();
        if (!$subject) return response()->json(['message' => 'No subject found for lecturer'], 404);

        if (!DB::table('students')->where('id', $request->studentID)->exists())
            return response()->json(['message' => 'Student not found'], 404);

        if (DB::table('results')->where('studentID', $request->studentID)->where('subjectID', $subject->id)->exists())
            return response()->json(['message' => 'Result already exists'], 400);

        $now = now()->toDateTimeString();
        DB::table('results')->insert([
            'id'         => (string) Str::uuid(),
            'studentID'  => $request->studentID,
            'subjectID'  => $subject->id,
            'grade'      => $request->grade,
            'semester'   => $request->semester,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json(['message' => 'Result added successfully'], 201);
    }

    public function updateResults(Request $request, $resultID)
    {
        $lecturerID = $request->user()->id;

        $subject = DB::table('subjects')->where('lecturerID', $lecturerID)->first();
        if (!$subject) return response()->json(['message' => 'No subject found'], 404);

        $result = DB::table('results')->where('id', $resultID)->where('subjectID', $subject->id)->first();
        if (!$result) return response()->json(['message' => 'Not found or unauthorized'], 404);

        $upd = ['updated_at' => now()->toDateTimeString()];
        if ($request->grade)    $upd['grade']    = $request->grade;
        if ($request->semester) $upd['semester'] = $request->semester;

        DB::table('results')->where('id', $resultID)->update($upd);
        return response()->json(['message' => 'Updated successfully']);
    }

    public function deleteResults(Request $request, $id)
    {
        $lecturerID = $request->user()->id;

        $subject = DB::table('subjects')->where('lecturerID', $lecturerID)->first();
        if (!$subject) return response()->json(['message' => 'No subject found for lecturer'], 404);

        $result = DB::table('results')->where('id', $id)->where('subjectID', $subject->id)->first();
        if (!$result) return response()->json(['message' => 'Result not found or unauthorized'], 404);

        DB::table('results')->where('id', $id)->delete();
        return response()->json(['message' => 'Result deleted successfully'], 200);
    }

    public function viewResultsBySubject(Request $request)
    {
        $lecturerID = $request->user()->id;

        $subject = DB::table('subjects')->where('lecturerID', $lecturerID)->first();
        if (!$subject) return response()->json(['message' => 'No subject found'], 404);

        $q = DB::table('results')
            ->join('students', 'results.studentID', '=', 'students.id')
            ->where('results.subjectID', $subject->id)
            ->select('results.id', 'results.grade', 'results.semester',
                     'students.id as studentId', 'students.name as studentName');

        $semester = $request->semester;
        if ($semester && $semester !== 'all') $q->where('results.semester', $semester);

        $search = $request->search;
        if ($search && $search !== '') {
            $q->where(fn($x) => $x->where('students.id', 'LIKE', "%$search%")->orWhere('students.name', 'LIKE', "%$search%"));
        }

        $results = $q->orderBy('results.studentID')->get();

        return response()->json([
            'subject'  => ['id' => $subject->id, 'name' => $subject->name],
            'students' => $results->map(fn($r) => [
                'id'       => $r->studentId,
                'name'     => $r->studentName,
                'grade'    => $r->grade,
                'semester' => $r->semester,
                'resultID' => $r->id,
            ]),
        ]);
    }

    public function getAvailableSemesters(Request $request)
    {
        $lecturerID = $request->user()->id;

        $subject = DB::table('subjects')->where('lecturerID', $lecturerID)->first();
        if (!$subject) return response()->json(['message' => 'No subject found for lecturer'], 404);

        $semesters = DB::table('results')
            ->where('subjectID', $subject->id)
            ->distinct()
            ->orderBy('semester', 'desc')
            ->pluck('semester');

        return response()->json(['semesters' => $semesters]);
    }

    public function getStudentsBySubject(Request $request)
    {
        $lecturerID = $request->user()->id;

        $subject = DB::table('subjects')->where('lecturerID', $lecturerID)->first();
        if (!$subject) return response()->json(['message' => 'No subject found for lecturer'], 404);

        $students = DB::table('students')
            ->join('results', 'students.id', '=', 'results.studentID')
            ->where('results.subjectID', $subject->id)
            ->distinct()
            ->select('students.id', 'students.name', 'students.email')
            ->get();

        return response()->json($students);
    }

    public function getStudentsWithoutGrades(Request $request)
    {
        $lecturerID = $request->user()->id;

        $subject = DB::table('subjects')->where('lecturerID', $lecturerID)->first();
        if (!$subject) return response()->json(['message' => 'No subject found for lecturer'], 404);

        $students = DB::table('students')
            ->whereNotExists(function ($q) use ($subject) {
                $q->select(DB::raw(1))
                  ->from('results')
                  ->whereColumn('results.studentID', 'students.id')
                  ->where('results.subjectID', $subject->id);
            })
            ->select('id', 'name', 'email')
            ->get();

        return response()->json($students);
    }
}

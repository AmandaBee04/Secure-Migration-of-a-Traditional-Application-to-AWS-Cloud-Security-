<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Student controller — DB::table() only, no Eloquent model boot.
 */
class StudentController extends Controller
{
    public function viewResults(Request $request)
    {
        $id = $request->user()->id;

        $student = DB::table('students')->where('id', $id)->first();
        if (!$student) return response()->json(['message' => 'Student not found'], 404);

        $semester = $request->semester;
        if (!$semester) return response()->json(['message' => 'Please select a semester'], 400);

        $results = DB::table('results')
            ->join('subjects', 'results.subjectID', '=', 'subjects.id')
            ->where('results.studentID', $id)
            ->where('results.semester', $semester)
            ->select('results.id', 'results.grade', 'subjects.id as subjectId', 'subjects.name as subjectName')
            ->get()
            ->map(fn($r) => [
                'id'      => $r->id,
                'grade'   => $r->grade,
                'subject' => ['id' => $r->subjectId, 'name' => $r->subjectName],
            ]);

        if ($results->isEmpty())
            return response()->json(['message' => 'No results found for this semester'], 200);

        return response()->json([
            'student'  => $student->id,
            'name'     => $student->name,
            'semester' => $semester,
            'results'  => $results,
        ], 200);
    }

    public function getStudentAvailableSemesters($id)
    {
        $student = DB::table('students')->where('id', $id)->first();
        if (!$student) return response()->json(['message' => 'Student not found'], 404);

        $semesters = DB::table('results')
            ->where('studentID', $id)
            ->distinct()
            ->orderBy('semester', 'desc')
            ->pluck('semester');

        return response()->json(['semesters' => $semesters]);
    }
}

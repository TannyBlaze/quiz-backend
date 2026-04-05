<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Attempt;
use App\Models\User;
use MongoDB\BSON\ObjectId;

class QuestionController extends Controller
{
    private function formatCourse($course)
    {
        return [
            '_id' => (string) $course->_id,
            'title' => $course->title,
            'questions' => $course->questions,
            'created_by' => $course->created_by,
            'is_public' => $course->is_public,
            'timer' => $course->timer ?? 0,
            'question_count' => $course->question_count ?? count($course->questions ?? []),
            'created_at' => $course->created_at ?? null,
            'updated_at' => $course->updated_at ?? null,
        ];
    }

    public function index(Request $request)
    {
        $user = $request->attributes->get('auth_user');

        if ($user->role === 'admin') {
            $courses = Course::all();
        } elseif ($user->role === 'setter') {
            $courses = Course::where('created_by', $user->id)->get();
        } else {
            $courses = Course::where('is_public', true)->get();
        }

        return response()->json(
            $courses->map(fn($c) => $this->formatCourse($c))
        );
    }

    public function store(Request $request)
    {
        $user = $request->attributes->get('auth_user');

        $course = Course::create([
            'title' => $request->title,
            'questions' => $request->questions ?? [],
            'created_by' => $user->id,
            'is_public' => false,
            'timer' => $request->timer ?? 0,
            'question_count' => $request->question_count ?? 0,
            'max_attempts' => $request->max_attempts ?? null,
        ]);

        return response()->json([
            'message' => 'Course created',
            'course' => $this->formatCourse($course)
        ], 201);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->attributes->get('auth_user');

        $course = Course::where('_id', new \MongoDB\BSON\ObjectId($id))->first();

        if (!$course) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (
            $course->created_by != $user->id &&
            $user->role !== 'admin'
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $course->delete();

        return response()->json([
            'message' => 'Course deleted'
        ]);
    }

    public function show($id)
    {
        $course = Course::where('_id', new ObjectId($id))->first();

        if (!$course) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($this->formatCourse($course));
    }

    public function update(Request $request, $id)
    {
        $user = $request->attributes->get('auth_user');

        $course = Course::where('_id', new ObjectId($id))->first();

        if (!$course) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (
            $course->created_by != $user->id &&
            $user->role !== 'admin'
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $course->update([
            'title' => $request->title ?? $course->title,
            'questions' => $request->questions ?? $course->questions,
            'timer' => $request->timer ?? $course->timer,
            'question_count' => $request->question_count ?? $course->question_count,
            'max_attempts' => $request->max_attempts ?? $course->max_attempts,
        ]);

        return response()->json([
            'message' => 'Updated',
            'course' => $this->formatCourse($course)
        ]);
    }

    public function togglePublic(Request $request, $id)
    {
        $user = $request->attributes->get('auth_user');

        $course = Course::where('_id', new ObjectId($id))->first();

        if (
            $course->created_by != $user->id &&
            $user->role !== 'admin'
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $course->is_public = !$course->is_public;
        $course->save();

        return response()->json($this->formatCourse($course));
    }

    public function quiz(Request $request, $id)
    {
        $user = $request->attributes->get('auth_user');

        $course = Course::where('_id', new ObjectId($id))->first();

        if (!$course || !$course->is_public) {
            return response()->json(['message' => 'Not available'], 403);
        }

        if (!empty($course->max_attempts)) {
            $attemptCount = Attempt::where('course_id', (string) $course->_id)
                ->where('user_id', $user->id)
                ->count();

            if ($attemptCount >= $course->max_attempts) {
                return response()->json([
                    'message' => 'Max attempts reached'
                ], 403);
            }
        }

        $questions = collect($course->questions ?? [])
            ->map(function ($q, $index) {
                $q['original_index'] = $index;
                return $q;
            })
            ->shuffle();

        if (!empty($course->question_count)) {
            $questions = $questions->take($course->question_count);
        }

        $questions = $questions->map(function ($q) {
            if (isset($q['options'])) {
                $q['options'] = collect($q['options'])->shuffle()->values();
            }

            return $q;
        })->values();

        return response()->json([
            'course_id' => (string) $course->_id,
            'title' => $course->title,
            'timer' => $course->timer ?? 0,
            'questions' => $questions,
        ]);
    }

    public function submitQuiz(Request $request, $id)
    {
        $user = $request->attributes->get('auth_user');

        $course = Course::where('_id', new ObjectId($id))->first();

        if (!$course) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (!empty($course->max_attempts)) {
            $attemptCount = Attempt::where('course_id', (string) $course->_id)
                ->where('user_id', $user->id)
                ->count();

            if ($attemptCount >= $course->max_attempts) {
                return response()->json([
                    'message' => 'Max attempts reached'
                ], 403);
            }
        }

        $answers = $request->answers ?? [];
        $timeSpent = $request->time_spent ?? null;

        $score = 0;

        $questions = collect($course->questions ?? []);

        foreach ($answers as $answer) {
            $index = $answer['original_index'] ?? null;
            $value = $answer['value'] ?? null;

            if ($index === null) continue;

            $question = $questions[$index] ?? null;

            if ($question && $value == $question['correct']) {
                $score++;
            }
        }

        $total = count($answers);

        Attempt::create([
            'user_id' => $user->id,
            'course_id' => (string) $course->_id,
            'score' => $score,
            'total' => $total,
            'answers' => $answers,
            'time_spent' => $timeSpent,
        ]);

        return response()->json([
            'score' => $score,
            'total' => $total,
        ]);
    }

    public function getAttempts(Request $request, $id)
    {
        $user = $request->attributes->get('auth_user');

        $course = Course::where('_id', new ObjectId($id))->first();

        if (!$course) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (
            $course->created_by != $user->id &&
            $user->role !== 'admin'
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $attempts = Attempt::where('course_id', (string) $course->_id)->get();

        $data = $attempts->map(function ($a, $index) {
            $user = User::find($a->user_id);

            return [
                'user' => $user ? $user->name : 'Unknown',
                'email' => $user ? $user->email : '',
                'score' => $a->score,
                'total' => $a->total,
                'percentage' => $a->total ? round(($a->score / $a->total) * 100) : 0,
                'time_spent' => $a->time_spent ?? null,
                'attempt_number' => $index + 1,
                'date' => $a->created_at ?? null,
            ];
        });

        return response()->json($data);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function rules(Request $request)
    {
        return response()->json([
            'rules' => $request->user()->availabilityRules()->orderBy('day_of_week')->get(),
        ]);
    }

    public function storeRule(Request $request)
    {
        $data = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_minutes' => ['nullable', 'integer', 'min:10', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rule = $request->user()->availabilityRules()->create([
            ...$data,
            'slot_minutes' => $data['slot_minutes'] ?? 30,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json(['rule' => $rule], 201);
    }

    public function updateRule(Request $request, AvailabilityRule $rule)
    {
        abort_unless($rule->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'day_of_week' => ['sometimes', 'integer', 'between:0,6'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
            'slot_minutes' => ['sometimes', 'integer', 'min:10', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $rule->update($data);

        return response()->json(['rule' => $rule->fresh()]);
    }

    public function destroyRule(Request $request, AvailabilityRule $rule)
    {
        abort_unless($rule->user_id === $request->user()->id, 403);
        $rule->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function exceptions(Request $request)
    {
        return response()->json([
            'exceptions' => $request->user()->availabilityExceptions()->orderBy('date')->get(),
        ]);
    }

    public function storeException(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'is_closed' => ['nullable', 'boolean'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:190'],
        ]);

        $exception = $request->user()->availabilityExceptions()->updateOrCreate(
            ['date' => $data['date']],
            [
                'is_closed' => $data['is_closed'] ?? true,
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'reason' => $data['reason'] ?? null,
            ]
        );

        return response()->json(['exception' => $exception], 201);
    }

    public function destroyException(Request $request, AvailabilityException $exception)
    {
        abort_unless($exception->user_id === $request->user()->id, 403);
        $exception->delete();

        return response()->json(['message' => 'Deleted']);
    }
}

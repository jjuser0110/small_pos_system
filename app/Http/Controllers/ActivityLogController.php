<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Superadmin only: who did what, when and from where. */
class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date',
            'user_id'   => 'nullable|integer',
            'action'    => 'nullable|string|max:30',
            'type'      => 'nullable|string|max:100',
            'q'         => 'nullable|string|max:100',
        ]);

        $from = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : Carbon::today()->subDays(6);
        $to = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : Carbon::today()->endOfDay();

        $query = ActivityLog::with(['branch', 'company'])
            ->whereBetween('created_at', [$from, $to])
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))
            ->when($request->filled('type'), fn ($q) => $q->where('subject_type', $request->type))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . str_replace(['%', '_'], ['\%', '\_'], $request->q) . '%';
                $q->where(fn ($q) => $q->where('subject_label', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('user_name', 'like', $term)
                    ->orWhere('ip', 'like', $term)
                    ->orWhere('subject_id', $request->q));
            });

        // the top bar company narrows the log too
        if ($picked = $this->pickedCompanyId()) {
            $query->where('company_id', $picked);
        }

        return view('activity_log.index', [
            'logs'     => $query->orderByDesc('id')->paginate(50)->withQueryString(),
            'from'     => $from,
            'to'       => $to,
            'users'    => User::withTrashed()->orderBy('name')->get(['id', 'name', 'username']),
            'actions'  => ActivityLog::distinct()->orderBy('action')->pluck('action'),
            'types'    => ActivityLog::whereNotNull('subject_type')->distinct()->orderBy('subject_type')->pluck('subject_type'),
        ]);
    }
}

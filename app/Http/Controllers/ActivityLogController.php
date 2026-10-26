<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Inertia\Inertia;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
    $search = $request->input('search');
    $module = $request->input('module');
    $date = $request->input('date');

    $logs = ActivityLog::with('user')
        ->when($search, function ($query) use ($search) {
            $query->where('action', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
        })
        ->when($module, function ($query) use ($module) {
            $query->where('module', $module);
        })
        ->when($date, function ($query) use ($date) {
            $query->whereDate('created_at', $date);
        })
        ->latest()
        ->limit(300)
        ->get()
        ->map(function ($log) {
            $log->created_at_formatted = $log->created_at
                ? $log->created_at->format('d/m/Y H:i:s')
                : null;

            return $log;
        });

    return Inertia::render('Auditoria/Index', [
        'logs' => $logs,
        'filters' => [
            'search' => $search,
            'module' => $module,
            'date' => $date,
        ],
        'modules' => ActivityLog::select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module'),
    ]);
    }
}

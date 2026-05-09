<?php

namespace App\Http\Controllers;

use App\Models\Title;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the user dashboard.
     */
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'linksCount' => $request->user()->links()->count(),
            'titleOptions' => Title::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name']),
            'userName' => $request->user()->name,
        ]);
    }
}

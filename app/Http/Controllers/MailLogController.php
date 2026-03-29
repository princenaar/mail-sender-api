<?php

namespace App\Http\Controllers;

use App\Models\MailLog;
use Illuminate\View\View;

class MailLogController extends Controller
{
    public function index(): View
    {
        $logs = MailLog::orderByDesc('created_at')->paginate(25);

        return view('logs.index', compact('logs'));
    }
}

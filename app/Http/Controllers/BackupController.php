<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BackupController extends Controller
{
    public function download(Request $request)
    {
        Gate::authorize('access-dashboard');

        $file = basename((string) $request->query('file', ''));

        if ($file === '') {
            abort(404);
        }

        $root = env('BACKUP_PATH', storage_path('app/backups'));
        $fullPath = $root . DIRECTORY_SEPARATOR . $file;

        if (!is_file($fullPath)) {
            abort(404);
        }

        return response()->download($fullPath, $file);
    }
}

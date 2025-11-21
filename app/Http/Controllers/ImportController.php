<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportRequest; // Import the Form Request
use Illuminate\Http\RedirectResponse;

class ImportController extends Controller
{
    /**
     * Handle the import handoff. At this point:
     * - API key middleware already authorized the caller.
     * - ImportRequest already validated input payload.
     *
     * For API clients (e.g. Nexo) that send Accept: application/json,
     * we return a JSON response with the prefill URL so they can
     * redirect the user manually.
     *
     * For normal browser-style requests, we keep the original
     * redirect behavior to the event creation form.
     */
    public function handlePrefillRedirect(ImportRequest $request)
    {
        // 1. Validated data only (prevents junk from being forwarded)
        $validatedData = $request->validated();

        // 2. Build the prefill URL for the event creation form
        $prefillUrl = route('event.create', $validatedData);

        // 3. If the client expects JSON (e.g. Nexo backend), return JSON with the URL
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'url'     => $prefillUrl,
            ]);
        }

        // 4. Otherwise, keep redirect behavior for browser-style clients
        return redirect()->to($prefillUrl);
    }
}

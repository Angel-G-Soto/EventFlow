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
     * We then redirect to the standard event creation route, attaching
     * the validated data as query parameters (so the form can prefill).
     */
    public function handlePrefillRedirect(ImportRequest $request): RedirectResponse
    {
        // 1. If we reach this point, the data is already validated.
        // Get only the validated data to prevent malicious inputs.
        // Only the validated fields are forwarded (prevents mass-assignment of junk)
        $validatedData = $request->validated();

        // 2. Redirect the user to the standard "create event" form.
        // The validated data is passed directly to the route(),
        // which will append it as query parameters.
        return redirect()->route('events.create', $validatedData);
    }
}

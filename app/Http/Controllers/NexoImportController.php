<?php

namespace App\Http\Controllers;

use App\Http\Requests\NexoImportRequest; // Import the Form Request
use Illuminate\Http\RedirectResponse;

class NexoImportController extends Controller
{
    /**
     * Handles an incoming request to pre-fill the event form.
     *
     * @param NexoImportRequest $request Laravel automatically validates using this request.
     * @return RedirectResponse
     */
    public function handlePrefillRedirect(NexoImportRequest $request): RedirectResponse
    {
        // 1. If we reach this point, the data is already validated.
        // Get only the validated data to prevent malicious inputs.
        $validatedData = $request->validated();

        // 2. Redirect the user to the standard "create event" form.
        // The validated data is passed directly to the route(),
        // which will append it as query parameters.
        return redirect()->route('events.create', $validatedData);
    }
}

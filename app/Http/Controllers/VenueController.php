<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use App\Services\VenueService;
use App\Policies\VenuePolicy;

class VenueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Policy

        $venues = new VenueService()->getAllVenues(['department_id'=>Auth::user()->department_id]);

        return view('venues.index', ['venues' => $venues]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Policy

        return view('venues.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(int $venue_id)
    {
        // Policy

        $venue = VenueService::getVenueById($venue_id);

        return view('venues.show', ['venue' => $venue]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $venue_id)
    {
        // Policy

        $venue = VenueService::getVenueById($venue_id);

        return view('venues.show', ['venue' => $venue]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $venue_id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $venue_id)
    {
        //
    }

    public function show_requirements(int $venue_id)
    {
        // Policy

        $venue = VenueService::getVenueById($venue_id);

        $requirements = $venue->requirements; //Create service

        return view('venues.showRequirements', ['venue' => $venue, 'requirements' => $requirements]);
    }

    public function edit_requirements(int $venue_id)
    {
        // Policy

        $venue = VenueService::getVenueById($venue_id);

        $requirements = $venue->requirements; //Create service

        return view('venues.editRequirements', ['venue' => $venue, 'requirements' => $requirements]);
    }

    public function update_requirements(int $venue_id)
    {

    }
}

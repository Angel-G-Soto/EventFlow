<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //
    public function index()
    {
        $users = User::query()
            ->latest('created_at') // newest requests first
            ->paginate(8);          // 10 per page

        return view('requests.index', compact('users'));

    }
}

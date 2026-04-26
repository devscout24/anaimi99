<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Help;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class HelpController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $help = Help::all()->makeHidden(['created_at', 'updated_at']);

            return $this->success($help, 'Helps fetched successfully');
        } catch (\Throwable $e) {
            return $this->error([], 'Something went wrong while fetching helps', 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
        ]);

        try {
            $help = Help::create($validated);

            // hide timestamps only here
            $help->makeHidden(['created_at', 'updated_at']);

            return $this->created($help, 'Help created successfully', 201);
        } catch (\Throwable $e) {
            return $this->error([], 'Something went wrong while creating help', 500);
        }
    }
}
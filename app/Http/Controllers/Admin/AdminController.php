<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class AdminController extends Controller
{
    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . session('jwt_token')];
    }

    private function apiUrl(string $path): string
    {
        return config('services.auth_service.url') . '/api/admin/' . $path;
    }

    // ── Dashboard ──────────────────────────────────
    public function dashboard(): Response
    {
        return Inertia::render('Admin/Dashboard');
    }

    // ── ManageData page ────────────────────────────
    public function ManageData(): Response
    {
        // Load all data for the page
        $faculties   = Http::withHeaders($this->authHeaders())
            ->get($this->apiUrl('faculties'))->json('faculties', []);
        $departments = Http::withHeaders($this->authHeaders())
            ->get($this->apiUrl('departments'))->json('departments', []);
        $programs    = Http::withHeaders($this->authHeaders())
            ->get($this->apiUrl('programs'))->json('programs', []);

        return Inertia::render('Admin/ManageData', [
            'faculties'   => $faculties,
            'departments' => $departments,
            'programs'    => $programs,
        ]);
    }

    public function storeFaculty(Request $request): RedirectResponse
    {
        $response = Http::withHeaders($this->authHeaders())
            ->post($this->apiUrl('faculties'), $request->all());

        if (!$response->successful()) {
            return back()->withErrors($response->json('errors', []))
                ->withInput();
        }

        return redirect()->route('admin.ManageData')
            ->with('success', 'Faculty created successfully.');
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $response = Http::withHeaders($this->authHeaders())
            ->post($this->apiUrl('departments'), $request->all());

        if (!$response->successful()) {
            return back()->withErrors($response->json('errors', []))
                ->withInput();
        }

        return redirect()->route('admin.ManageData')
            ->with('success', 'Department created successfully.');
    }

    public function storeProgram(Request $request): RedirectResponse
    {
        $response = Http::withHeaders($this->authHeaders())
            ->post($this->apiUrl('programs'), $request->all());

        if (!$response->successful()) {
            return back()->withErrors($response->json('errors', []))
                ->withInput();
        }

        return redirect()->route('admin.ManageData')
            ->with('success', 'Program created successfully.');
    }
}

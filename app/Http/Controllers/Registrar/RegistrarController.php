<?php
namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class RegistrarController extends Controller
{
    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . session('jwt_token')];
    }

    private function apiUrl(string $path): string
    {
        return config('services.auth_service.url') . '/api/registrar/' . $path;
    }

    public function dashboard(): Response
    {
        return Inertia::render('Register/Dashboard');
    }

    public function ManageUser(): Response
    {
        $response = Http::withHeaders($this->authHeaders())
            ->get($this->apiUrl('imports'));

        $imports = $response->successful()
            ? $response->json('imports', [])
            : [];

        return Inertia::render('Register/ManageUser', [
            'imports' => $imports,
        ]);
    }

    public function importStudents(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $file = $request->file('csv_file');

        try {
            $response = Http::withHeaders($this->authHeaders())
                ->timeout(60)
                ->attach(
                    'csv_file',                          // field name
                    file_get_contents($file->getRealPath()), // file contents
                    $file->getClientOriginalName(),      // original filename
                    ['Content-Type' => 'text/csv']       // ✅ content type
                )
                ->post($this->apiUrl('import/students'));

        } catch (\Exception $e) {
            return back()->withErrors([
                'csv_file' => 'Could not connect to auth service: ' . $e->getMessage(),
            ]);
        }

        $data = $response->json();

        if (!$response->successful()) {
            return back()->withErrors([
                'csv_file' => $data['message'] ?? 'Import failed. Status: ' . $response->status(),
            ]);
        }

        return back()->with([
            'success'         => $data['message'],
            'successful_rows' => $data['successful_rows'],
            'failed_rows'     => $data['failed_rows'],
            'import_errors'   => $data['errors'] ?? [],
        ]);
    }

    public function importStaff(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $file = $request->file('csv_file');

        try {
            $response = Http::withHeaders($this->authHeaders())
                ->timeout(60)
                ->attach(
                    'csv_file',
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName(),
                    ['Content-Type' => 'text/csv']
                )
                ->post($this->apiUrl('import/staff'));

        } catch (\Exception $e) {
            return back()->withErrors([
                'csv_file' => 'Could not connect to auth service: ' . $e->getMessage(),
            ]);
        }

        $data = $response->json();

        if (!$response->successful()) {
            return back()->withErrors([
                'csv_file' => $data['message'] ?? 'Import failed. Status: ' . $response->status(),
            ]);
        }

        return back()->with([
            'success'         => $data['message'],
            'successful_rows' => $data['successful_rows'],
            'failed_rows'     => $data['failed_rows'],
            'import_errors'   => $data['errors'] ?? [],
        ]);
    }
}
<?php

namespace App\Http\Controllers\Patients;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patients\UploadPatientPhotoRequest;
use App\Models\Patients;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PatientPhotoController extends Controller
{
    public function store(UploadPatientPhotoRequest $request, Patients $item): RedirectResponse
    {
        $this->deletePhotoFile($item);

        $extension = $request->file('photo')->getClientOriginalExtension();
        $filename = sprintf('%s-%s.%s', $item->id, (string) Str::ulid(), strtolower($extension));
        $path = $request->file('photo')->storeAs(
            "patients/photos/{$item->id}",
            $filename,
            'public',
        );

        $item->update(['photo_path' => $path]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Patient photo updated.')]);
        Inertia::flash('patientPhotoUrl', $item->fresh()->photo_url);

        return back();
    }

    public function destroy(Patients $item): RedirectResponse
    {
        $this->deletePhotoFile($item);
        $item->update(['photo_path' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Patient photo removed.')]);
        Inertia::flash('patientPhotoUrl', null);

        return back();
    }

    protected function deletePhotoFile(Patients $item): void
    {
        if ($item->photo_path && Storage::disk('public')->exists($item->photo_path)) {
            Storage::disk('public')->delete($item->photo_path);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $isTraining = $user->isTraining();

        $profile = CompanyProfile::where('is_training', $isTraining)->first();
        
        if (!$profile) {
            $profile = CompanyProfile::create([
                'company_name' => $isTraining ? 'PT LATIHAN JAYA' : 'PT MAJU MAKMUR',
                'company_address' => $isTraining ? 'Jln. Contoh No. 1, Kota Latihan' : 'Jln. Mawar No. 10, Madiun, Jawa Timur 130001',
                'company_phone' => $isTraining ? '021-0000000' : '021-123456',
                'company_logo_initials' => $isTraining ? 'LJ' : 'MM',
                'is_training' => $isTraining,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $profile,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $isTraining = $user->isTraining();

        // Check if the user is a Manajer (Manajer or Manajer Latihan)
        if ($user->role !== 'Manajer' && $user->role !== 'Manajer Latihan') {
            return response()->json([
                'status' => 'failed',
                'message' => 'Hanya Manajer yang bisa mengubah profil perusahaan',
            ], 403);
        }

        $val = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'company_address' => 'required|string|max:500',
            'company_phone' => 'nullable|string|max:50',
            'company_logo_initials' => 'required|string|max:5',
        ]);

        if ($val->fails()) {
            return response()->json([
                'error' => $val->errors(),
            ], 422);
        }

        $profile = CompanyProfile::where('is_training', $isTraining)->first();

        if (!$profile) {
            $profile = CompanyProfile::create(array_merge(
                $request->only(['company_name', 'company_address', 'company_phone', 'company_logo_initials']),
                ['is_training' => $isTraining]
            ));
        } else {
            $profile->update($request->only(['company_name', 'company_address', 'company_phone', 'company_logo_initials']));
        }

        return response()->json([
            'status' => 'success',
            'data' => $profile,
        ]);
    }
}

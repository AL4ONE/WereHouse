<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyProfileController extends Controller
{
    /**
     * Get the company profile.
     * For training users: return editable training profile.
     * For non-training users: return default hardcoded profile.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->isTraining()) {
            $profile = CompanyProfile::where('is_training', true)->first();
            
            if (!$profile) {
                $profile = CompanyProfile::create([
                    'company_name' => 'PT LATIHAN JAYA',
                    'company_address' => 'Jln. Contoh No. 1, Kota Latihan',
                    'company_phone' => '021-0000000',
                    'company_logo_initials' => 'LJ',
                    'is_training' => true,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => $profile,
            ]);
        }

        // Non-training: return hardcoded default
        return response()->json([
            'status' => 'success',
            'data' => [
                'company_name' => 'PT MAJU MAKMUR',
                'company_address' => 'Jln. Mawar No. 10, Madiun, Jawa Timur 130001',
                'company_phone' => null,
                'company_logo_initials' => 'MM',
                'is_training' => false,
            ],
        ]);
    }

    /**
     * Update the company profile (training mode only, Manajer Latihan)
     */
    public function update(Request $request)
    {
        $user = $request->user();

        if (!$user->isTraining()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Hanya mode latihan yang bisa mengubah profil perusahaan',
            ], 403);
        }

        if ($user->role !== 'Manajer Latihan') {
            return response()->json([
                'status' => 'failed',
                'message' => 'Hanya Manajer Latihan yang bisa mengubah profil perusahaan',
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

        $profile = CompanyProfile::where('is_training', true)->first();

        if (!$profile) {
            $profile = CompanyProfile::create(array_merge(
                $request->only(['company_name', 'company_address', 'company_phone', 'company_logo_initials']),
                ['is_training' => true]
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

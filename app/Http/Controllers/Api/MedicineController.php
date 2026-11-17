<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (strlen($q) < 1) {
            return response()->json(['medicines' => []]);
        }

        $medicines = Medicine::query()
            ->where(function ($query) use ($q) {
                $query->where('brand_name', 'like', "%{$q}%")
                    ->orWhere('generic_name', 'like', "%{$q}%")
                    ->orWhere('company', 'like', "%{$q}%");
            })
            ->orderBy('brand_name')
            ->limit(25)
            ->get();

        return response()->json(['medicines' => $medicines]);
    }
}

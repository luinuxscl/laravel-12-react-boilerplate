<?php

namespace App\Http\Controllers\Admin;

use App\Facades\Settings;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandingController extends Controller
{
    /**
     * Sube el logo y actualiza el setting site.brand.logo_url.
     */
    public function uploadLogo(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:png,svg,webp', 'max:2048'], // 2MB
        ]);

        $file = $data['file'];
        $path = $file->storeAs('branding', 'logo.' . $file->getClientOriginalExtension(), 'public');

        // Guardar URL pública relativa (/storage/...) para evitar problemas de host/puerto
        $url = Storage::disk('public')->url($path);

        $brand = Settings::get('site.brand', [
            'logo_url' => null,
            'favicon_url' => null,
        ]);
        $brand['logo_url'] = $url;
        Settings::set('site.brand', $brand);

        return response()->json([
            'data' => ['url' => $url],
        ]);
    }

    /**
     * Sube el favicon y actualiza el setting site.brand.favicon_url.
     */
    public function uploadFavicon(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:ico,png,svg', 'max:1024'], // 1MB
        ]);

        $file = $data['file'];
        $path = $file->storeAs('branding', 'favicon.' . $file->getClientOriginalExtension(), 'public');

        // Guardar URL pública relativa (/storage/...) para evitar problemas de host/puerto
        $url = Storage::disk('public')->url($path);

        $brand = Settings::get('site.brand', [
            'logo_url' => null,
            'favicon_url' => null,
        ]);
        $brand['favicon_url'] = $url;
        Settings::set('site.brand', $brand);

        return response()->json([
            'data' => ['url' => $url],
        ]);
    }
}

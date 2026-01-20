<?php

namespace App\Http\Controllers\AdminConsole;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('admin-console.settings.index');
    }

    public function update_site_info(Request $request)
    {
        $data = $request->validate([
            'site_title' => 'required|string|max:255',
            'site_logo' => 'nullable|mimes:jpeg,png,jpg,svg,webp|max:2048',
        ]);

        // Here you would typically save the data to the database or configuration file.
        // For example:
        // config(['app.name' => $data['site_name']]);
        // config(['app.description' => $data['site_description']]);
        // config(['app.keywords' => $data['site_keywords']]);

        // save these settings to Settings model. check if available and update or create. save these values as json in the payload field.
        Setting::setSetting('site_info', 'site_title', $data['site_title']);

        if ($request->hasFile('site_logo')) {
            $logoPath = $request->file('site_logo')->store('settings/logos');
            Setting::setSetting('site_info', 'site_logo', $logoPath);
        }

        return back()->with('success', 'Site information updated successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        // Redirect to website settings by default
        return redirect()->route('settings.website');
    }

    /**
     * Display website settings page.
     */
    public function website()
    {
        // Get settings from database, fallback to config if not set
        $appName = Setting::get('app_name', config('app.name'));
        $timezone = Setting::get('timezone', config('app.timezone'));
        $locale = Setting::get('locale', config('app.locale'));

        return Inertia::render('Settings/Website', [
            'settings' => [
                'app_name' => $appName,
                'app_url' => config('app.url'),
                'app_env' => config('app.env'),
                'app_debug' => config('app.debug'),
                'timezone' => $timezone,
                'locale' => $locale,
            ],
        ]);
    }

    /**
     * Update application settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
            'timezone' => 'required|string',
            'locale' => 'required|string|max:10',
        ]);

        // Save settings to database
        Setting::set('app_name', $request->app_name, 'string', 'website', 'Application name');
        Setting::set('timezone', $request->timezone, 'string', 'website', 'Application timezone');
        Setting::set('locale', $request->locale, 'string', 'website', 'Application locale');
        
        return back()->with('message', 'Settings updated successfully!');
    }
}


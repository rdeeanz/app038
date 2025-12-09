<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Website Settings
        Setting::set('app_name', config('app.name', 'ERP System'), 'string', 'website', 'Application name');
        Setting::set('timezone', config('app.timezone', 'UTC'), 'string', 'website', 'Application timezone');
        Setting::set('locale', config('app.locale', 'en'), 'string', 'website', 'Application locale');
        
        $this->command->info('Default settings created successfully!');
    }
}

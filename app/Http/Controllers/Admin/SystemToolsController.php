<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Silber\Bouncer\BouncerFacade as Bouncer;

class SystemToolsController extends Controller
{
    public function clearCache(): RedirectResponse
    {
        $this->ensurePrivilegedAdmin();

        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');

        return back()
            ->with('status', __('Application cache has been cleared.'))
            ->with('notify', [
                'type' => 'success',
                'message' => __('Application cache has been cleared.'),
            ]);
    }

    public function maintenanceOn(): RedirectResponse
    {
        $this->ensurePrivilegedAdmin();

        $secret = (string) config('app.maintenance_bypass_secret', 'agshop-admin-bypass');

        Artisan::call('down', ['--secret' => $secret]);

        return redirect('/'.$secret);
    }

    public function maintenanceOff(): RedirectResponse
    {
        $this->ensurePrivilegedAdmin();

        Artisan::call('up');

        return back()
            ->with('status', __('Maintenance mode is now OFF.'))
            ->with('notify', [
                'type' => 'success',
                'message' => __('Maintenance mode is now OFF.'),
            ]);
    }

    private function ensurePrivilegedAdmin(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && (Bouncer::is($user)->an('superadmin') || $user->can('settings.system.runtime.manage')),
            403,
            'Missing settings.system.runtime.manage ability.'
        );
    }
}

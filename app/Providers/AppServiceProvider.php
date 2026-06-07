<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->shareAdminViewData();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function shareAdminViewData(): void
    {
        View::composer('components.layouts.admin', function ($view) {
            $unreadCount = 0;

            // Hanya query kalau tabel sudah ada (aman saat migration belum jalan)
            try {
                $unreadCount = \App\Models\ContactMessage::unread()->count();
            } catch (\Throwable) {
                $unreadCount = 0;
            }

            $view->with('unreadCount', $unreadCount);
        });
    }
}
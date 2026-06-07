<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\ContactSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminContactController extends Controller
{
    /* ───────────────────────────────────────────────
     | MESSAGES (Daftar pesan masuk)
    ─────────────────────────────────────────────── */

    public function index(Request $request): View
    {
        $status = $request->get('status', 'all');

        $query = ContactMessage::query()->latest();

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name',    'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $messages = $query->paginate(20)->withQueryString();

        $counts = [
            'all'      => ContactMessage::count(),
            'unread'   => ContactMessage::unread()->count(),
            'read'     => ContactMessage::status('read')->count(),
            'replied'  => ContactMessage::status('replied')->count(),
            'archived' => ContactMessage::status('archived')->count(),
        ];

        return view('pages.admin.contact.index', compact('messages', 'counts', 'status'));
    }

    public function show(ContactMessage $message): View
    {
        // Tandai sebagai dibaca kalau masih unread
        if ($message->isUnread()) {
            $message->update(['status' => ContactMessage::STATUS_READ]);
        }

        return view('pages.admin.contact.show', compact('message'));
    }

    public function updateStatus(Request $request, ContactMessage $message): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:' . implode(',', ContactMessage::STATUSES)],
        ]);

        $message->update(['status' => $request->status]);

        return back()->with('success', 'Status pesan diperbarui.');
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return redirect()->route('admin.contact.index')
            ->with('success', 'Pesan berhasil dihapus.');
    }

    /* ───────────────────────────────────────────────
     | SETTINGS (Pengaturan halaman kontak)
    ─────────────────────────────────────────────── */

    public function settings(): View
    {
        $setting = ContactSetting::current();

        return view('pages.admin.contact.settings', compact('setting'));
    }

    public function settingsUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'heading'         => ['required', 'string', 'max:100'],
            'subheading'      => ['nullable', 'string', 'max:150'],
            'description'     => ['nullable', 'string', 'max:500'],
            'phone'           => ['nullable', 'string', 'max:50'],
            'email'           => ['nullable', 'email', 'max:150'],
            'address'         => ['nullable', 'string', 'max:200'],
            'address_city'    => ['nullable', 'string', 'max:100'],
            'address_country' => ['nullable', 'string', 'max:100'],
            'open_hours'      => ['nullable', 'string', 'max:200'],
            'notify_email'    => ['nullable', 'email', 'max:150'],
            'notify_phone'    => ['nullable', 'string', 'max:50'],
        ]);

        $setting = ContactSetting::current();
        $setting->update($validated);

        return back()->with('success', 'Pengaturan Contact berhasil disimpan.');
    }
}

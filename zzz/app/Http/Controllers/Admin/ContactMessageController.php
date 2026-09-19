<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function index(): View
    {
        $messages = ContactMessage::latest()->paginate(25);

        return view('admin.contact-messages.index', compact('messages'));
    }

    public function show(ContactMessage $contactMessage): View
    {
        if (!$contactMessage->is_read) {
            $contactMessage->update([
                'is_read' => true,
            ]);
        }

        return view(
            'admin.contact-messages.show',
            compact('contactMessage')
        );
    }

    public function destroy(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->delete();

        return redirect()
            ->route('admin.contact-messages.index')
            ->with('success', 'پیام با موفقیت حذف شد.');
    }
}
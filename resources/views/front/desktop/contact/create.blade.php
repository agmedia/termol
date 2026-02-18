@extends('front.desktop.layouts.store')

@section('title', 'Contact')

@section('content')
    <section class="mb-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Contact us</h1>
        <p class="mt-2 max-w-2xl text-slate-600">Use this form for product, order, wholesale, or support questions. Messages are stored in `contact_messages` for team follow-up.</p>
    </section>

    <div class="grid gap-8 lg:grid-cols-[1fr_320px]">
        <form method="POST" action="{{ route('contact.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Name</label>
                    <input type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" class="w-full rounded-lg border-slate-300 text-sm" required>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Email</label>
                    <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" class="w-full rounded-lg border-slate-300 text-sm" required>
                </div>
            </div>

            <div class="mt-4">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Phone (optional)</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="w-full rounded-lg border-slate-300 text-sm">
            </div>

            <div class="mt-4">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</label>
                <input type="text" name="subject" value="{{ old('subject') }}" class="w-full rounded-lg border-slate-300 text-sm" required>
            </div>

            <div class="mt-4">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Message</label>
                <textarea name="message" rows="8" class="w-full rounded-lg border-slate-300 text-sm" required>{{ old('message') }}</textarea>
            </div>

            <button type="submit" class="mt-5 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Send message</button>
        </form>

        <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Direct contact</h2>
            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                <li>Email: support@agshop.local</li>
                <li>Phone: +385 1 0000 000</li>
                <li>Response time: same business day</li>
            </ul>
        </aside>
    </div>
@endsection

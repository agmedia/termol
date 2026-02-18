@extends('front.mobile.layouts.store')

@section('title', 'Contact')
@section('header_title', 'Contact')
@section('page_title', 'Get in Touch')

@section('content')
    <div class="card card-style">
        <div class="content">
            <p class="font-600 mb-n1 color-highlight">Support</p>
            <h2>Contact Us</h2>
            <p>Use this form and your message will be stored for follow-up.</p>

            <form method="POST" action="{{ route('contact.store') }}">
                @csrf
                <div class="input-style has-borders no-icon input-style-always-active mb-3">
                    <label for="contact-name" class="color-highlight">Name</label>
                    <input id="contact-name" type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" required>
                </div>

                <div class="input-style has-borders no-icon input-style-always-active mb-3">
                    <label for="contact-email" class="color-highlight">Email</label>
                    <input id="contact-email" type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required>
                </div>

                <div class="input-style has-borders no-icon input-style-always-active mb-3">
                    <label for="contact-phone" class="color-highlight">Phone</label>
                    <input id="contact-phone" type="text" name="phone" value="{{ old('phone') }}">
                </div>

                <div class="input-style has-borders no-icon input-style-always-active mb-3">
                    <label for="contact-subject" class="color-highlight">Subject</label>
                    <input id="contact-subject" type="text" name="subject" value="{{ old('subject') }}" required>
                </div>

                <div class="input-style has-borders input-style-always-active no-icon mb-3">
                    <textarea id="contact-message" name="message" style="height:140px;" required>{{ old('message') }}</textarea>
                    <label for="contact-message" class="color-highlight">Message</label>
                </div>

                <button type="submit" class="btn btn-full gradient-blue font-600 rounded-s">Send Message</button>
            </form>
        </div>
    </div>
@endsection

<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\Front\NewsletterSignupService;
use App\Services\Front\StoreNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Throwable;

class NewsletterController extends Controller
{
    public function __construct(
        private readonly NewsletterSignupService $newsletterSignups,
        private readonly StoreNotificationService $notifications,
    ) {
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'newsletter_email' => ['required', 'email', 'max:191'],
                'newsletter_accept_terms' => ['accepted'],
            ],
            [
                'newsletter_email.required' => __('ui.front.desktop.newsletter.validation.email_required'),
                'newsletter_email.email' => __('ui.front.desktop.newsletter.validation.email_invalid'),
                'newsletter_accept_terms.accepted' => __('ui.front.desktop.newsletter.validation.accept_terms'),
            ]
        );

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->ajax()) {
                return new JsonResponse([
                    'message' => __('ui.front.desktop.newsletter.status.failed'),
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors($validator, 'newsletter')
                ->withInput();
        }

        try {
            $result = $this->newsletterSignups->subscribe(
                email: (string) $validator->validated()['newsletter_email'],
                consentAccepted: true,
                user: $request->user(),
                source: \App\Models\User\NewsletterSignup::SOURCE_FOOTER,
                locale: (string) app()->getLocale(),
                ipAddress: (string) $request->ip(),
                userAgent: (string) $request->userAgent(),
                referrer: (string) $request->headers->get('referer', '')
            );
        } catch (Throwable) {
            if ($request->expectsJson() || $request->ajax()) {
                return new JsonResponse([
                    'message' => __('ui.front.desktop.newsletter.status.failed'),
                    'errors' => [
                        'newsletter_email' => [__('ui.front.desktop.newsletter.status.failed')],
                    ],
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors([
                    'newsletter_email' => __('ui.front.desktop.newsletter.status.failed'),
                ], 'newsletter')
                ->withInput();
        }

        $message = $result['synced']
            ? __('ui.front.desktop.newsletter.status.subscribed')
            : __('ui.front.desktop.newsletter.status.saved_with_sync_issue');

        if ($result['synced']) {
            $this->notifications->sendNewsletterCoupon(
                (string) $validator->validated()['newsletter_email'],
                (string) app()->getLocale(),
                'BALI10'
            );
        }

        if ($request->expectsJson() || $request->ajax()) {
            return new JsonResponse([
                'message' => $message,
                'type' => $result['synced'] ? 'success' : 'warning',
            ]);
        }

        return redirect()
            ->back()
            ->with('status', $message);
    }
}

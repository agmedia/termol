<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Front\Concerns\ResolvesFrontendView;
use App\Models\User;
use App\Services\Front\StoreSettingsService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Silber\Bouncer\BouncerFacade as Bouncer;

class AuthController extends Controller
{
    use ResolvesFrontendView;

    private const LOGIN_RECAPTCHA_ACTION = 'login_form';

    private const REGISTER_RECAPTCHA_ACTION = 'register_form';

    public function __construct(
        private readonly StoreSettingsService $storeSettings
    ) {}

    public function showLogin(Request $request): View
    {
        return view($this->frontendView($request, 'auth.login'));
    }

    public function login(Request $request): RedirectResponse
    {
        $captchaSettings = $this->storeSettings->captcha();
        $captchaEnabled = $this->recaptchaIsEnabled($captchaSettings);

        $credentials = $request->validate(
            [
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
                'remember' => ['nullable', 'boolean'],
                'recaptcha_token' => [$captchaEnabled ? 'required' : 'nullable', 'string', 'max:4096'],
            ],
            [
                'email.required' => __('ui.auth.validation.email_required'),
                'email.email' => __('ui.auth.validation.email_invalid'),
                'password.required' => __('ui.auth.validation.password_required'),
                'recaptcha_token.required' => __('ui.auth.captcha_failed'),
            ],
            [
                'recaptcha_token' => __('ui.auth.validation.security_check'),
            ]
        );

        if ($captchaEnabled) {
            $this->assertRecaptchaIsValid(
                token: (string) ($credentials['recaptcha_token'] ?? ''),
                secret: (string) $captchaSettings['recaptcha_v3_secret_key'],
                minScore: (float) ($captchaSettings['recaptcha_v3_min_score'] ?? 0.5),
                expectedAction: self::LOGIN_RECAPTCHA_ACTION,
                ip: (string) $request->ip()
            );
        }

        if (! Auth::attempt([
            'email' => (string) $credentials['email'],
            'password' => (string) $credentials['password'],
        ], (bool) ($credentials['remember'] ?? false))) {
            return back()
                ->withErrors(['email' => __('auth.failed')])
                ->withInput($request->except('password', 'recaptcha_token'));
        }

        $request->session()->regenerate();

        return redirect()->to($this->resolveIntendedPath($request));
    }

    public function showRegister(Request $request): View
    {
        return view($this->frontendView($request, 'auth.register'));
    }

    public function register(Request $request): RedirectResponse
    {
        $captchaSettings = $this->storeSettings->captcha();
        $captchaEnabled = $this->recaptchaIsEnabled($captchaSettings);

        $validated = $request->validate(
            [
                'first_name' => ['required', 'string', 'max:120'],
                'last_name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:191', 'unique:users,email'],
                'password' => ['required', 'string', 'confirmed', Password::defaults()],
                'recaptcha_token' => [$captchaEnabled ? 'required' : 'nullable', 'string', 'max:4096'],
            ],
            [
                'first_name.required' => __('ui.auth.validation.first_name_required'),
                'last_name.required' => __('ui.auth.validation.last_name_required'),
                'email.required' => __('ui.auth.validation.email_required'),
                'email.email' => __('ui.auth.validation.email_invalid'),
                'email.unique' => __('ui.auth.validation.email_unique'),
                'password.required' => __('ui.auth.validation.password_required'),
                'password.confirmed' => __('ui.auth.validation.password_confirmed'),
                'recaptcha_token.required' => __('ui.auth.captcha_failed'),
            ],
            [
                'recaptcha_token' => __('ui.auth.validation.security_check'),
            ]
        );

        if ($captchaEnabled) {
            $this->assertRecaptchaIsValid(
                token: (string) ($validated['recaptcha_token'] ?? ''),
                secret: (string) $captchaSettings['recaptcha_v3_secret_key'],
                minScore: (float) ($captchaSettings['recaptcha_v3_min_score'] ?? 0.5),
                expectedAction: self::REGISTER_RECAPTCHA_ACTION,
                ip: (string) $request->ip()
            );
        }

        $user = User::query()->create([
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        Bouncer::assign('customer')->to($user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->to($this->resolveIntendedPath($request));
    }

    private function resolveIntendedPath(Request $request): string
    {
        $intended = (string) $request->input('intended', '');

        if ($intended !== '') {
            $parts = parse_url($intended);
            $host = (string) ($parts['host'] ?? '');
            $path = (string) ($parts['path'] ?? '');
            $query = (string) ($parts['query'] ?? '');

            if ($host === '' && $path !== '' && str_starts_with($path, '/')) {
                return $path.($query !== '' ? '?'.$query : '');
            }
        }

        return route('account.dashboard');
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function recaptchaIsEnabled(array $settings): bool
    {
        return (bool) ($settings['recaptcha_v3_enabled'] ?? false)
            && trim((string) ($settings['recaptcha_v3_site_key'] ?? '')) !== ''
            && trim((string) ($settings['recaptcha_v3_secret_key'] ?? '')) !== '';
    }

    private function assertRecaptchaIsValid(
        string $token,
        string $secret,
        float $minScore,
        string $expectedAction,
        string $ip
    ): void {
        $minScore = max(0.0, min(1.0, $minScore));

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ]);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'recaptcha_token' => __('ui.auth.captcha_failed'),
            ]);
        }

        if (! $response->ok()) {
            throw ValidationException::withMessages([
                'recaptcha_token' => __('ui.auth.captcha_failed'),
            ]);
        }

        $json = $response->json();
        $success = (bool) ($json['success'] ?? false);
        $score = (float) ($json['score'] ?? 0.0);
        $action = (string) ($json['action'] ?? '');

        if (! $success || $score < $minScore || ($action !== '' && $action !== $expectedAction)) {
            throw ValidationException::withMessages([
                'recaptcha_token' => __('ui.auth.captcha_failed'),
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Front\Concerns\ResolvesFrontendView;
use App\Models\User;
use App\Models\User\B2BAccount;
use App\Models\User\UserAddress;
use App\Models\User\UserProfile;
use App\Services\Front\StoreSettingsService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public function showB2BRegister(Request $request): View
    {
        return view($this->frontendView($request, 'auth.b2b-register'));
    }

    public function registerB2B(Request $request): RedirectResponse
    {
        $captchaSettings = $this->storeSettings->captcha();
        $captchaEnabled = $this->recaptchaIsEnabled($captchaSettings);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:80'],
            'company_name' => ['required', 'string', 'max:191'],
            'oib' => ['required', 'regex:/^\d{11}$/', 'unique:b2b_accounts,oib'],
            'vat_id' => ['nullable', 'string', 'max:60'],
            'address_line_1' => ['required', 'string', 'max:191'],
            'address_line_2' => ['nullable', 'string', 'max:191'],
            'postal_code' => ['required', 'string', 'max:32'],
            'city' => ['required', 'string', 'max:120'],
            'country_code' => ['required', 'string', 'size:2'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'terms_accepted' => ['accepted'],
            'recaptcha_token' => [$captchaEnabled ? 'required' : 'nullable', 'string', 'max:4096'],
        ], [
            'oib.regex' => __('OIB mora sadržavati točno 11 znamenki.'),
            'oib.unique' => __('Za ovaj OIB već postoji B2B zahtjev.'),
            'terms_accepted.accepted' => __('Za nastavak morate prihvatiti uvjete B2B registracije.'),
            'recaptcha_token.required' => __('ui.auth.captcha_failed'),
        ]);

        if ($captchaEnabled) {
            $this->assertRecaptchaIsValid(
                token: (string) ($validated['recaptcha_token'] ?? ''),
                secret: (string) $captchaSettings['recaptcha_v3_secret_key'],
                minScore: (float) ($captchaSettings['recaptcha_v3_min_score'] ?? 0.5),
                expectedAction: self::REGISTER_RECAPTCHA_ACTION,
                ip: (string) $request->ip()
            );
        }

        $user = DB::transaction(function () use ($validated): User {
            $user = User::query()->create([
                'name' => trim($validated['first_name'].' '.$validated['last_name']),
                'email' => strtolower(trim((string) $validated['email'])),
                'password' => Hash::make($validated['password']),
            ]);

            UserProfile::query()->create([
                'user_id' => $user->getKey(),
                'first_name' => trim((string) $validated['first_name']),
                'last_name' => trim((string) $validated['last_name']),
                'phone' => trim((string) $validated['phone']),
                'company' => trim((string) $validated['company_name']),
                'oib' => trim((string) $validated['oib']),
            ]);

            UserAddress::query()->create([
                'user_id' => $user->getKey(),
                'type' => UserAddress::TYPE_BILLING,
                'first_name' => trim((string) $validated['first_name']),
                'last_name' => trim((string) $validated['last_name']),
                'company' => trim((string) $validated['company_name']),
                'oib' => trim((string) $validated['oib']),
                'vat_id' => trim((string) ($validated['vat_id'] ?? '')) ?: null,
                'phone' => trim((string) $validated['phone']),
                'address_line_1' => trim((string) $validated['address_line_1']),
                'address_line_2' => trim((string) ($validated['address_line_2'] ?? '')) ?: null,
                'postal_code' => trim((string) $validated['postal_code']),
                'city' => trim((string) $validated['city']),
                'country_code' => strtoupper((string) $validated['country_code']),
                'is_default' => true,
            ]);

            B2BAccount::query()->create([
                'user_id' => $user->getKey(),
                'status' => B2BAccount::STATUS_PENDING,
                'company_name' => trim((string) $validated['company_name']),
                'oib' => trim((string) $validated['oib']),
                'vat_id' => trim((string) ($validated['vat_id'] ?? '')) ?: null,
                'phone' => trim((string) $validated['phone']),
                'address_line_1' => trim((string) $validated['address_line_1']),
                'address_line_2' => trim((string) ($validated['address_line_2'] ?? '')) ?: null,
                'postal_code' => trim((string) $validated['postal_code']),
                'city' => trim((string) $validated['city']),
                'country_code' => strtoupper((string) $validated['country_code']),
                'requested_at' => now(),
            ]);

            Bouncer::assign('customer')->to($user);

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('account.dashboard')
            ->with('status', __('B2B zahtjev je zaprimljen. Obavijestit ćemo vas nakon odobrenja.'));
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

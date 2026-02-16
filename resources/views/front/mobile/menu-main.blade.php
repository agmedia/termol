<div class="menu-header">
    <a href="/" class="menu-logo text-center">
        <span class="font-800 font-16">{{ config('app.name', 'AG Shop') }}</span>
    </a>
    <p class="text-center mt-2 mb-0 opacity-70 font-13">
        Mobile starter navigation
    </p>
</div>

<div class="divider divider-margins mt-3 mb-3"></div>

<div class="list-group list-custom-small list-menu">
    <a href="/" class="close-menu">
        <i class="fa fa-home color-highlight"></i>
        <span>Home</span>
        <i class="fa fa-angle-right"></i>
    </a>

    <a href="/" class="close-menu">
        <i class="fa fa-globe color-blue-dark"></i>
        <span>Desktop storefront</span>
        <i class="fa fa-angle-right"></i>
    </a>

    @auth
        <a href="{{ route('admin.dashboard') }}" class="close-menu">
            <i class="fa fa-shield-halved color-gray-dark"></i>
            <span>Admin dashboard</span>
            <i class="fa fa-angle-right"></i>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
            @csrf
            <button type="submit" class="list-group-item close-menu border-0 bg-transparent w-100 text-start">
                <i class="fa fa-arrow-right-from-bracket color-red-light"></i>
                <span>Odjava</span>
                <i class="fa fa-angle-right"></i>
            </button>
        </form>
    @else
        <a href="{{ route('login') }}" class="close-menu">
            <i class="fa fa-right-to-bracket color-highlight"></i>
            <span>Prijava</span>
            <i class="fa fa-angle-right"></i>
        </a>

        <a href="{{ route('register') }}" class="close-menu">
            <i class="fa fa-user-plus color-highlight"></i>
            <span>Registracija</span>
            <i class="fa fa-angle-right"></i>
        </a>
    @endauth
</div>

<div class="divider divider-margins mt-3 mb-3"></div>

<div class="content px-3">
    <a href="#" data-menu="menu-colors" class="btn btn-full rounded-s font-13 font-700 bg-highlight">
        Theme colors
    </a>
</div>

<link rel="stylesheet" href= "{{asset('css/login.css')}}">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>{{ __('Login Failed!') }}</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    <form name="loginForm" method="POST" action="{{ route('login') }}" novalidate>
                        @csrf
                        
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">{{ __('Login') }}</h4>
                        </div>
                        <div class="header-logo">
                            <img src="{{ asset('images/sample.jpg') }}" alt="Logo" class="logo">
                        </div>

                        <!-- Username -->
                        <div class="mb-3">
                            <label for="username" class="form-label">{{ __('Username') }} <span class="text-danger">*</span></label>
                            <input id="username" type="text" class="form-control @error('username') is-invalid @enderror"
                                   name="username" value="{{ old('username') }}" required autofocus placeholder="Enter your username">
                            @error('username')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('Password') }} <span class="text-danger">*</span></label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                                   name="password" required placeholder="Enter your password">
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Login Button -->
                        <div class="d-grid mb-3 text-white">
                            <button type="submit" class="btn btn-primary btn-lg">
                                {{ __('Login') }}
                            </button>
                        </div>

                        <!-- Register Link -->
                        <div class="text-center">
                            <p class="text-muted">
                                {{ __("Don't have an account?") }}
                                <a href="{{ route('register') }}" class="text-decoration-none fw-bold">
                                    {{ __('Register here') }}
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="{{ asset('css/register.css') }}">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">{{ __('Employee Registration') }}</h4>
                </div>

                <div class="card-body p-4">
                    <img src="{{ asset('images/sample.jpg') }}" alt="Logo" class="logo">
                </div>

                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>{{ __('Registration Failed!') }}</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}" novalidate>
                        @csrf
                        
                        <select id="project_id" name="project_id" class="form-select @error('project_id') is-invalid @enderror" required>
                        <option value="" selected disabled>-- Select a Project --</option>

                        @foreach($projects as $project)
                            <option value="{{ $project->id }}"
                                {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>

                        <div class="row">
                            <!-- First Name -->
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">{{ __('First Name') }} <span class="text-danger">*</span></label>
                                <input 
                                    id="first_name" 
                                    type="text" 
                                    class="form-control @error('first_name') is-invalid @enderror" 
                                    name="first_name" 
                                    value="{{ old('first_name') }}" 
                                    required 
                                    autofocus
                                    placeholder="Juan"
                                >
                                @error('first_name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Middle Name -->
                            <div class="col-md-6 mb-3">
                                <label for="middle_name" class="form-label">{{ __('Middle Name') }}</label>
                                <input 
                                    id="middle_name" 
                                    type="text" 
                                    class="form-control @error('middle_name') is-invalid @enderror" 
                                    name="middle_name" 
                                    value="{{ old('middle_name') }}"
                                    placeholder="Garcia"
                                >
                                @error('middle_name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Last Name -->
                        <div class="mb-3">
                            <label for="last_name" class="form-label">{{ __('Last Name') }} <span class="text-danger">*</span></label>
                            <input 
                                id="last_name" 
                                type="text" 
                                class="form-control @error('last_name') is-invalid @enderror" 
                                name="last_name" 
                                value="{{ old('last_name') }}" 
                                required
                                placeholder="Dela Cruz"
                            >
                            @error('last_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Username -->
                        <div class="mb-3">
                            <label for="username" class="form-label">{{ __('Username') }} <span class="text-danger">*</span></label>
                            <input 
                                id="username" 
                                type="text" 
                                class="form-control @error('username') is-invalid @enderror" 
                                name="username" 
                                value="{{ old('username') }}" 
                                required
                                placeholder="juandelacruz"
                            >
                            @error('username')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Must be unique and alphanumeric</small>
                        </div>

                        <div class="row">
                            <!-- Password -->
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">{{ __('Password') }} <span class="text-danger">*</span></label>
                                <input 
                                    id="password" 
                                    type="password" 
                                    class="form-control @error('password') is-invalid @enderror" 
                                    name="password" 
                                    required
                                    placeholder="Minimum 8 characters"
                                    pattern="[a-zA-Z0-9]+"
                                >
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label">{{ __('Confirm Password') }} <span class="text-danger">*</span></label>
                                <input 
                                    id="password_confirmation" 
                                    type="password" 
                                    class="form-control" 
                                    name="password_confirmation" 
                                    required
                                    placeholder="Repeat password"
                                >
                            </div>
                        </div>

                        <!-- Role Selection -->
                        <div class="mb-3">
                            <label for="user_id" class="form-label">{{ __('Assign Role') }} <span class="text-danger">*</span></label>
                            <select 
                                id="user_id" 
                                class="form-select @error('user_id') is-invalid @enderror" 
                                name="user_id" 
                                required
                            >
                                <option value="" selected disabled>{{ __('Select a Role') }}</option>
                                @forelse($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('user_id') == $role->id ? 'selected' : '' }}>
                                        {{ $role->role_name }} (₱{{ number_format($role->hourly_rate, 2) }}/hr)
                                    </option>
                                @empty
                                    <option value="" disabled>{{ __('No roles available') }}</option>
                                @endforelse
                            </select>
                            @error('user_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
    <label for="position" class="form-label">
        {{ __('Assign Role') }} <span class="text-danger">*</span>
    </label>

    <select 
        id="position" 
        class="form-select @error('position') is-invalid @enderror" 
        name="position" 
        required
    >
        <option value="" selected disabled>{{ __('Select a Role') }}</option>

        @forelse($roles as $role)
            <option value="{{ $role->role_name }}" 
                {{ old('position') == $role->role_name ? 'selected' : '' }}>
                
                {{ $role->role_name }} (₱{{ number_format($role->hourly_rate, 2) }}/hr)
            
            </option>
        @empty
            <option value="" disabled>{{ __('No roles available') }}</option>
        @endforelse
    </select>

    @error('position')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>


                        <div class="row">
                            <!-- Contact Number -->
                            <div class="col-md-6 mb-3">
                                <label for="contact_number" class="form-label">{{ __('Contact Number') }}</label>
                                <input 
                                    id="contact_number" 
                                    type="text" 
                                    class="form-control @error('contact_number') is-invalid @enderror" 
                                    name="contact_number" 
                                    value="{{ old('contact_number') }}"
                                    placeholder="09XX-XXXX-XXXX"
                                >
                                @error('contact_number')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Gender -->
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label">{{ __('Gender') }}</label>
                                <select 
                                    id="gender" 
                                    class="form-select @error('gender') is-invalid @enderror" 
                                    name="gender"
                                >
                                    <option value="">{{ __('-- Select Gender --') }}</option>
                                    <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>{{ __('Male') }}</option>
                                    <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>{{ __('Female') }}</option>
                                    <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Birthdate -->
                            <div class="col-md-6 mb-3">
                                <label for="birthdate" class="form-label">{{ __('Birthdate') }}</label>
                                <input 
                                    id="birthdate" 
                                    type="date" 
                                    class="form-control @error('birthdate') is-invalid @enderror" 
                                    name="birthdate" 
                                    value="{{ old('birthdate') }}"
                                >
                                @error('birthdate')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            

                        <div class="row">
                            <!-- City -->
                             <div class="col-md-6 mb-3">
                                <label for="barangay" class="form-label">{{ __('Barangay') }}</label>
                                <input id="barangay" type="text" class="form-control @error('barangay') is-invalid @enderror" name="barangay" value="{{ old('barangay') }}"placeholder="Lourdes">
                                @error('barangay')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">{{ __('City') }}</label>
                                <input id="city" type="text" class="form-control @error('city') is-invalid @enderror" name="city" value="{{ old('city') }}"placeholder="Cabanatuan">
                                @error('city')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Province -->
                            <div class="col-md-6 mb-3">
                                <label for="province" class="form-label">{{ __('Province') }}</label>
                                <input id="province" type="text" class="form-control @error('province') is-invalid @enderror" name="province" value="{{ old('province') }}"placeholder="Nueva Ecija">
                                @error('province')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Register Button -->
                        <div class="d-grid mb-3 mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                {{ __('Register') }}
                            </button>
                        </div>

                        <!-- Login Link -->
                        <div class="text-center">
                            <p class="text-muted">
                                {{ __('Already have an account?') }}
                                <a href="{{ route('login') }}" class="text-decoration-none fw-bold">
                                    {{ __('Login here') }}
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
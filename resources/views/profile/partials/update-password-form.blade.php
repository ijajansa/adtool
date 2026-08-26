<section class="card content-card"><div class="card-body p-4">
    <h2 class="h5">Update password</h2><p class="text-secondary small">Use a long, unique password to keep your account secure.</p>
    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf @method('put')
        <div class="mb-3"><label for="current_password" class="form-label">Current password</label><input id="current_password" name="current_password" type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password">@error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label for="new_password" class="form-label">New password</label><input id="new_password" name="password" type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password">@error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label for="password_confirmation" class="form-label">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password"></div>
        <button class="btn btn-primary" type="submit">Update password</button>
    </form>
</div></section>

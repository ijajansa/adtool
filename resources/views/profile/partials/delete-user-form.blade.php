<section class="card content-card border-danger-subtle"><div class="card-body p-4">
    <h2 class="h5 text-danger">Delete account</h2><p class="text-secondary small">This permanently deletes your account and cannot be undone.</p>
    <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Are you sure you want to delete your account?')" novalidate>
        @csrf @method('delete')
        <div class="mb-3"><label for="delete_password" class="form-label">Password</label><input id="delete_password" name="password" type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" autocomplete="current-password">@error('password', 'userDeletion')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <button class="btn btn-outline-danger" type="submit">Delete account</button>
    </form>
</div></section>

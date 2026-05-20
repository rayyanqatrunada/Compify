<div class="w-full max-w-xl">
    <form wire:submit="subscribe" class="flex flex-col gap-3 sm:flex-row">
        <input
            type="email"
            wire:model.blur="email"
            placeholder="email@domain.com"
            class="form-input min-h-12 flex-1"
        >
        <button type="submit" class="neon-button justify-center">
            <span wire:loading.remove wire:target="subscribe">Subscribe</span>
            <span wire:loading wire:target="subscribe">Sending...</span>
        </button>
    </form>

    @error('email')
        <p class="mt-3 text-sm text-rose-300">{{ $message }}</p>
    @enderror

    @if ($message)
        <div class="mt-4 rounded-lg border border-emerald-300/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">
            {{ $message }}
        </div>
    @endif
</div>

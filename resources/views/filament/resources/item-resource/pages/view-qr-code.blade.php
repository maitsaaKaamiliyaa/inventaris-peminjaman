{{-- <x-filament::page> kalau pakai ini jadinya nanti listitem malah ada di modal--}}
    <div class="text-center space-y-4">
        <h2 class="text-xl font-bold">QR Code for {{ $name }}</h2>

        @if ($qr_path)
            <img src="{{ Storage::url($qr_path) }}"class="mx-auto w-48 h-48" alt="Gambar {{ $name }}">

            <a href="{{ Storage::url($qr_path) }}" download="" target="_blank" class="mt-4 inline-block px-4 py-2 bg-primary-500 text-white rounded hover:bg-primary-600">
                Download QR Code
            </a>
        @else
            <p class="text-red-500">QR Code not available for this item.</p>
        @endif
{{-- </x-filament::page> --}}
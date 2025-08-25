@php
    // ambil URL ke file SVG yg sudah disimpan di storage
    $qrUrl = asset('storage/'.$record->qr_path);
@endphp

<div class="flex flex-col items-center gap-4 p-4">
    {{-- tampilkan gambar QR dari storage --}}
    <img src="{{ $qrUrl }}" alt="QR Code" class="mx-auto w-48 h-48"/>

    {{-- tombol download, nama file sesuai model --}}
    <a href="{{ $qrUrl }}"
       download="{{ basename($record->qr_path) }}"
       class="mt-4 inline-block px-4 py-2 bg-primary-500 text-white rounded hover:bg-primary-600">
        Download QR
    </a>

    <div class="text-sm break-all">
        <a href="{{ $url }}" target="_blank" class="text-blue-500 underline">
            {{ $url }}
        </a>
    </div>
</div>
@php($photoVersion=max(is_file(public_path('assets/js/profile-photos.js'))?filemtime(public_path('assets/js/profile-photos.js')):1,is_file(public_path('assets/css/profile-photos.css'))?filemtime(public_path('assets/css/profile-photos.css')):1))
<link rel="stylesheet" href="{{ asset('assets/css/profile-photos.css') }}?v={{ $photoVersion }}">
<script src="{{ asset('assets/js/profile-photos.js') }}?v={{ $photoVersion }}" data-ife-photos data-photo-base="{{ url('foto') }}/" data-photo-placeholder="{{ asset('assets/img/profile-placeholder.svg') }}"></script>

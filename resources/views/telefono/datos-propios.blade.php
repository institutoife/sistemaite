@php
    $birth = $contacto ? $contacto->getRawOriginal('fechanacimiento') : null;
    $birth = $birth && $birth !== '0000-00-00' ? substr((string) $birth, 0, 10) : '';
@endphp
<div class="row mt-3">
    <div class="col-12 col-md-6 mb-3">
        <label for="guardian-birthday">Fecha de nacimiento del apoderado (opcional)</label>
        <input class="form-control @error('fechanacimiento') is-invalid @enderror" type="date" name="fechanacimiento" id="guardian-birthday" value="{{ old('fechanacimiento', $birth) }}" max="{{ now()->format('Y-m-d') }}">
        @error('fechanacimiento')<span class="text-danger">{{ $message }}</span>@enderror
    </div>
    <div class="col-12 col-md-6 mb-3">
        <label for="guardian-photo">Fotografía propia del apoderado (opcional)</label>
        <input class="form-control-file @error('foto') is-invalid @enderror" type="file" name="foto" id="guardian-photo" accept="image/jpeg,image/png,image/webp">
        <small class="form-text text-muted">JPG, PNG o WebP, hasta 5 MB y 4000 × 4000 píxeles. Si no eliges otra, se conserva su fotografía actual.</small>
        @error('foto')<span class="text-danger">{{ $message }}</span>@enderror
    </div>
</div>

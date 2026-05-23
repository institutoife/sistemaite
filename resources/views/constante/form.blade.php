<input type="hidden" name="constante" id="constante" value="{{ old('constante', $constante->constante ?? '') }}">
<input type="hidden" name="valor" id="valor" value="{{ old('valor', $constante->valor ?? '') }}">

<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
        <div class="form-floating mb-3 text-gray">
            <input type="text" name="cuenta" id="cuenta" class="form-control @error('cuenta') is-invalid @enderror" value="{{ old('cuenta', $constante->cuenta ?? '') }}" autocomplete="off">
            <label for="cuenta">Correo o Usuario</label>
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
        <div class="form-floating mb-3 text-gray">
            <input type="text" name="plataforma" id="plataforma" class="form-control @error('plataforma') is-invalid @enderror" value="{{ old('plataforma', $constante->plataforma ?? '') }}" autocomplete="off">
            <label for="plataforma">Plataforma</label>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
        <div class="form-floating mb-3 text-gray">
            <input type="text" name="clave" id="clave" class="form-control @error('clave') is-invalid @enderror" value="{{ old('clave', $constante->clave ?? '') }}" autocomplete="off">
            <label for="clave">Contraseña</label>
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
        <div class="form-floating mb-3 text-gray">
            <textarea name="descripcion" id="descripcion" class="form-control @error('descripcion') is-invalid @enderror" rows="3">{{ old('descripcion', $constante->descripcion ?? '') }}</textarea>
            <label for="descripcion">Descripción</label>
        </div>
    </div>
</div>

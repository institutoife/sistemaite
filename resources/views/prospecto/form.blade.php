<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" >
        @if($errors->has('telefono'))
        <span class="text-danger"> {{ $errors->first('telefono')}}</span>
        @endif
    </div>
</div>
<div class="row"> 
    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" >
        <div class="form-floating mb-3 text-gray">
            <input  type="text" name="telefono" class="form-control @error('telefono') is-invalid @enderror" value="{{old('telefono',$prospecto->telefono ?? '')}}">
            <label for="telefono">Prospecto</label>
        </div>    
    </div>
</div>

<div class="form-floating mb-3 text-gray">
    <select class="form-control @error('estado_id') is-invalid @enderror" data-old="{{ old('estado_id') }}" name="estado_id" id="estado_id">
        <option value="" selected>Seleccion un estado</option>
        @foreach ($estados as $estado)
            <option value="{{ $estado->id }}" {{ (string) old('estado_id', $prospecto->estado_id ?? '') === (string) $estado->id ? 'selected' : '' }}>{{ $estado->estado }}</option>
        @endforeach
    </select>
    <label for="como">Estado</label>
</div>

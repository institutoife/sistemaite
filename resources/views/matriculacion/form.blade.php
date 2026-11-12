<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $("#country").change(function() {
        var asignaturaId = $(this).val();
        if(asignaturaId) {
            $.ajax({
                url: '/asignatura/datos/' + asignaturaId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if(data.costo !== undefined) {
                        $("#costo").val(data.costo);
                    }
                    if(data.totalhoras !== undefined) {
                        $("#totalhoras").val(data.totalhoras);
                    }
                },
                error: function() {
                    $("#costo").val('');
                    $("#totalhoras").val('');
                }
            });
        } else {
            $("#costo").val('');
            $("#totalhoras").val('');
        }
    });
});
</script>
{{-- %%%%%%%%%%%%%%%%%%%%%%% CAMPO  FECHA NACIMIENTO %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}}

    <input  type="text" hidden name="computacion_id" value="{{old('computacion_id',$computacion->id ?? '')}}">

    @include('inscripcione.sucursal')
    <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" >
                @if($errors->has('fechaini'))
                    <span class="text-danger"> {{ $errors->first('fechaini')}}</span>
                @endif
            </div>
            <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" >
                @if($errors->has('costo'))
                    <span class="text-danger"> {{ $errors->first('costo')}}</span>
                @endif
            </div>
            <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" >
                @if($errors->has('totalhoras'))
                    <span class="text-danger"> {{ $errors->first('totalhoras')}}</span>
                @endif
            </div>
    </div>

    <div class="row"> 
        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" >
            @isset($persona)
                <div class="form-floating mb-3 text-gray">
                    <input  type="date" name="fechaini" class="form-control @error('fechaini') is-invalid @enderror" value="{{old('fechaini',$matriculacion->fechaini->format('Y-m-d') ?? '')}}">
                    <label for="fechaini">fechainicio</label>
                </div>
            @else
                <div class="form-floating mb-3 text-gray">
                    <input  type="date" name="fechaini" class="form-control @error('fechaini') is-invalid @enderror" value="{{old('fechaini' ?? '16-11-2021')}}">
                    <label for="fechaini">fechaini</label>
                </div>
            @endisset
        </div>
        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" >
            <div class="form-floating mb-3 text-gray">
                <input type="numeric" step="any" class="form-control @error('costo') is-invalid @enderror" name="costo" id="costo" value="{{old('costo',$matriculacion->costo ?? '175')}}">
                <label for="costo">Ingrese Costo</label>    
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" >
            <div class="form-floating mb-3 text-gray">
                <input id="totalhoras" type="number" step="any" name="totalhoras" class="form-control @error('totalhoras') is-invalid @enderror" value="{{old('totalhoras',$matriculacion->totalhoras ?? '15')}}">
                <label for="totalhoras">Ingrese Total Horas</label>  
            </div> 
        </div>

    </div>
    

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6" >
            @if($errors->has('asignatura_id'))
                <span class="text-danger"> {{ $errors->first('asignatura_id')}}</span>
            @endif
        </div>
        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6" >
            @if($errors->has('motivo_id'))
                <span class="text-danger"> {{ $errors->first('motivo_id')}}</span>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6" >
            <div class="form-floating text-gray">
                <select class="form-control @error('asignatura_id') is-invalid @enderror" data-old="{{ old('asignatura_id') }}" name="asignatura_id" id="country">
                    <option value="">Elija una Asignatura</option>
                    @foreach ($asignaturasFaltantes as $asignatura)
                        @isset($matriculacion)     
                            <option  value="{{$asignatura->id}}" {{$asignatura->id==$matriculacion->asignatura_id ? 'selected':''}}>{{$asignatura->asignatura}}</option>     
                        @else
                            <option value="{{ $asignatura->id }}" {{ old('carrera_id') == $asignatura->id ? 'selected':'' }} >{{ $asignatura->asignatura }}</option>
                        @endisset 
                    @endforeach
                </select>
                <label for="pais">Elija asignatura*</label>
            </div>
        </div>
        
        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6 mb-3" >
            <div class="form-floating text-gray">
                <select class="form-control @error('motivo_id') is-invalid @enderror" data-old="{{ old('motivo_id') }}" name="motivo_id" id="motivo_id">
                    <option value="">Elija un motivo</option>
                    @foreach ($motivos as $motivo)
                        @isset($matriculacion)     
                            <option  value="{{$motivo->id}}" {{$motivo->id==$matriculacion->motivo_id ? 'selected':''}}>{{$motivo->motivo}}</option>     
                        @else
                            <option value="{{ $motivo->id }}" {{ old('motivo_id') == $motivo->id ? 'selected':'' }} >{{ $motivo->motivo }}</option>
                        @endisset 
                    @endforeach
                </select>
                <label for="motivo_id">Elija motivo*</label>
            </div>
        </div>
    </div>

    @isset($matriculacion)
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                @if($errors->has('estado_id'))
                    <span class="text-danger"> {{ $errors->first('estado_id')}}</span>
                @endif
            </div>
        </div>
        <div class="row"> 
            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" >
                <div class="form-floating mb-3 text-gray">
                    <select class="form-control @error('estado_id') is-invalid @enderror" data-old="{{ old('estado_id') }}" name="estado_id" id="estado_id">
                        <option value="" > Seleccione Estado </option>
                        @foreach ($estados as $estado)
                            @isset($matriculacion)     
                                <option  value="{{$estado->id}}" {{$estado->id==$matriculacion->estado_id ? 'selected':''}}>{{$estado->estado}}</option>     
                            @else
                                <option value="{{ $estado->id }}" {{ old('estado_id') == $estado->id ? 'selected':'' }} >{{ $estado->estado }}</option>
                            @endisset 
                        @endforeach
                    </select>
                    <label for="motivo">Elija un estado</label>  
                </div>    
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" >
                @if($errors->has('condonado'))
                    <span class="text-danger"> {{ $errors->first('condonado')}}</span>
                @endif
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" >
                <div class="form-floating mb-3 text-gray">
                    <select class="form-control @error('condonado') is-invalid @enderror" name="condonado" id="condonado">
                        <option value=""> Elija tipo condonacion</option>
                            @if(isset($matriculacion->condonado))
                                @if($matriculacion->condonado==0)
                                    <option value="{{ $matriculacion->condonado }}" {{ 0==$matriculacion->condonado ? 'selected':''}} >No condonar</option>
                                    <option value="1">Condonar</option>
                                @else
                                    <option value="{{$matriculacion->condonado }}" {{ 1==$matriculacion->condonado ? 'selected':''}} > Condonar </option>
                                    <option value="0">No condonar</option>
                                @endif
                            @else  
                                <option value="1" @if(old('vigente') == '1') {{'selected'}} @endif>Vigente</option>
                                <option value="0" @if(old('vigente') == '0') {{'selected'}} @endif>Desvigente</option>
                            @endif 
                    </select>
                    <label for="condonado">Elija condonado*</label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" >
                @if($errors->has('vigente'))
                    <span class="text-danger"> {{ $errors->first('vigente')}}</span>
                @endif
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" >
                <div class="form-floating mb-3 text-gray">
                    <select class="form-control @error('vigente') is-invalid @enderror" name="vigente" id="vigente">
                        <option value=""> Elija tipo de vigencia</option>
                            @if(isset($matriculacion->vigente))
                                @if($matriculacion->vigente==0)
                                    <option value="{{ $matriculacion->vigente }}" {{ 0==$matriculacion->vigente ? 'selected':''}} >Desvigente</option>
                                    <option value="1">Vigente</option>
                                @else
                                    <option value="{{$matriculacion->vigente }}" {{ 1==$matriculacion->vigente ? 'selected':''}} > Vigente </option>
                                    <option value="0">Desvigente</option>
                                @endif
                            @else  
                                <option value="1" @if(old('vigente') == '1') {{'selected'}} @endif>Vigente</option>
                                <option value="0" @if(old('vigente') == '0') {{'selected'}} @endif>Desvigente</option>
                            @endif 
                    </select>
                    <label for="vigente">Elija Vigencia*</label>
                </div>
            </div>
        </div>
    @endisset


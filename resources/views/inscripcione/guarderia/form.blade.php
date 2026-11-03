@include('inscripcione.sucursal')
<input  type="date" hidden readonly name="fechanacimiento" id="fechanacimiento" class="form-control @error('fechanacimiento') is-invalid @enderror" value="{{$persona->fechanacimiento->isoFormat('YYYY-MM-DD')}}">    


        <div class="col-12">
            <div class="card card-primary collapsed-card">
                <div class="card-header">
                    <h3 class="card-title">Aqui puedes ver las constantes</h3>

                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-3" >
                            <div class="form-floating mb-3 text-gray">
                                <input readonly  class="form-control"  type="number" id="factorguarderia" value="{{$FACTORGUARDERIA->valor}}">    
                                <label for="factorguarderia">factor </label>    
                            </div>   
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-3" >
                            <div class="form-floating mb-3 text-gray">
                                <input readonly  class="form-control"  type="number" id="factorcostohoraguarderia" value="{{$FACTORCOSTOHORAGUARDERIA->valor}}">    
                                <label for="factorguarderia">Costo hora </label>    
                            </div>   
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-3" >
                            <div class="form-floating mb-3 text-gray">
                                <input readonly  class="form-control"  type="number" id="factorguarderiamenor111" value="{{$FACTORGUARDERIAMENOR111->valor}}">    
                                <label for="factorguarderia">Multiplicador menor 111 </label>    
                            </div>   
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-3" >
                            <div class="form-floating mb-3 text-gray">
                                <input readonly  class="form-control"  type="number" id="factorguarderiamayor111" value="{{$FACTORGUARDERIAMAYOR111->valor}}">    
                                <label for="factorguarderia">Multiplicador mayo 111 </label>    
                            </div>   
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" >
                            <div class="form-floating mb-3 text-gray">
                                <a class="btn btn-primary" href="{{route('constante.index')}}">Cambiar Valores</a>
                            </div>   
                        </div>
                    </div>
                </div>
            </div>
        </div>


    


    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6" >
            <div class="form-floating mb-3 text-gray">
                @if($errors->has('modalidad_id'))
                    <span class="text-danger"> {{ $errors->first('modalidad_id')}}</span>
                @endif
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6" >
            <div class="form-floating mb-3 text-gray">
                @if($errors->has('motivo_id'))
                    <span class="text-danger"> {{ $errors->first('motivo_id')}}</span>
                @endif
            </div>    
        </div>

    </div>
   {{-- {{$ultima_inscripcion}}6  --}}
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6" >
            <div class="form-floating mb-3 text-gray">
                <select class="form-control @error('modalidad_id') is-invalid @enderror" data-old="{{ old('modalidad_id') }}" name="modalidad_id" id="modalidad_id">
                    <option value="" > Seleccione Modalidad </option>
                    @foreach ($modalidades as $modalidad)
                        @isset($ultima_inscripcion)     
                            <option  value="{{$modalidad->id}}" {{$modalidad->id==$ultima_inscripcion->modalidad_id ? 'selected':''}}>{{$modalidad->modalidad}}</option>     
                        @else
                            <option value="{{ $modalidad->id }}" {{ old('modalidad_id') == $modalidad->id ? 'selected':'' }} >{{ $modalidad->modalidad }}</option>
                        @endisset 
                    @endforeach
                </select>
                <label for="modalidad_id">Elija modalidad</label>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6" >
            <div class="form-floating mb-3 text-gray">
                <select class="form-control @error('motivo_id') is-invalid @enderror" data-old="{{ old('motivo_id') }}" name="motivo_id" id="motivo">
                    <option value="" > Seleccione Motivo </option>
                    @foreach ($motivos as $motivo)
                        @isset($ultima_inscripcion)     
                            <option  value="{{$motivo->id}}" {{$motivo->id==$ultima_inscripcion->motivo_id ? 'selected':''}}>{{$motivo->motivo}}</option>     
                        @else
                            <option value="{{ $motivo->id }}" {{ old('motivo_id') == $motivo->id ? 'selected':'' }} >{{ $motivo->motivo }}</option>
                        @endisset 
                    @endforeach
                </select>
                 <label for="motivo">Elija el motivo de las clases</label>  
            </div>    
        </div>

    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4" >
            <div class="form-floating mb-3 text-gray">
                @if($errors->has('fechaini'))
                    <span class="text-danger"> {{ $errors->first('fechaini')}}</span>
                @endif
            </div>    
        </div>
        <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4" >
            <div class="form-floating mb-3 text-gray">
                @if($errors->has('horainicio'))
                    <span class="text-danger"> {{ $errors->first('hora')}}</span>
                @endif
            </div> 
        </div>
        <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4" >
            <div class="form-floating mb-3 text-gray">
                @if($errors->has('horafin'))
                    <span class="text-danger"> {{ $errors->first('horafin')}}</span>
                @endif
            </div>   
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4" >
            <div class="form-floating mb-3 text-gray">
                @isset($ultima_inscripcion)
                    <input  type="date" name="fechaini" class="form-control @error('fechaini') is-invalid @enderror" value="{{ old('fechaini', $ultima_inscripcion->fechaini->format('Y-m-d') ?? '')}}">    
                @else
                    <input  type="date" name="fechaini" class="form-control @error('fechaini') is-invalid @enderror" value="{{ Carbon\Carbon::now()->format('Y-m-d') }}">
                @endif
                <label for="fechaini">Fecha Inicio</label>  

            </div>    
        </div>
        <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4" >
            <div class="form-floating mb-3 text-gray">
                <input type="time" class="form-control hora" value="{{ old('horainicio', '07:30' ?? '')}}" name="horainicio" id="horainicio">
                <label for="costo">Hora Inicio</label>    
        </div> 
        </div>
        <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4" >
            <div class="form-floating mb-3 text-gray">
                <input type="time" class="form-control hora" value="{{ old('horafin', '12:30' ?? '')}}" name="horafin" id="horafin">
                <label for="costo">Hora Fin</label>    
            </div>   
        </div>
    </div>
    
    
    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3" >
            <div class="form-floating mb-3 text-gray">
                @if($errors->has('diasxsemana'))
                    <span class="text-danger"> {{ $errors->first('diasxsemana')}}</span>
                @endif
            </div>   
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3" >
            <div class="form-floating mb-3 text-gray">
                @if($errors->has('totalhoras'))
                    <span class="text-danger"> {{ $errors->first('totalhoras')}}</span>
                @endif
            </div>    
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3" >
            <div class="form-floating mb-3 text-gray">
                @if($errors->has('horas_total'))
                    <span class="text-danger"> {{ $errors->first('horas_total')}}</span>
                @endif
            </div> 
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3" >
            <div class="form-floating mb-3 text-gray">
                @if($errors->has('costo'))
                    <span class="text-danger"> {{ $errors->first('costo')}}</span>
                @endif
            </div>   
        </div>
        
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3" >
            <div class="form-floating mb-3 text-gray">
                {{-- <input type="number" step="1" class="form-control hora @error('diasxsemana') is-invalid @enderror" name="diasxsemana" id="diasxsemana" value="{{old('diasxsemana','4' ?? '5')}}"> --}}
                <select class="form-control hora @error('diasxsemana') is-invalid @enderror" name="diasxsemana" id="diasxsemana">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option selected value="5">5</option>
                    <option value="6">6</option>
                    
                </select>
                <label for="costo">Dias Por Semana</label>  
            </div>   
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3" >
            <div class="form-floating mb-3 text-gray">
                <input id="totalhoras" type="number" step="0.01" name="totalhoras" class="form-control @error('totalhoras') is-invalid @enderror" value="{{old('totalhoras',$ultima_inscripcion->totalhoras ?? '')}}" placeholder="Total Horas">
                <label for="totalhoras">Horas por día</label>
            </div>    
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3" >
            <div class="form-floating mb-3 text-gray">
                <input id="horas_total" type="number" step="0.01" name="horas_total" class="form-control @error('horas_total') is-invalid @enderror" value="{{old('totalhoras',$ultima_inscripcion->totalhoras ?? '')}}" placeholder="Total Horas">
                <label for="horas_total">Total_horas</label>
            </div> 
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3" >
            <div class="form-floating mb-3 text-gray">
                <input type="number" step="0.01" class="form-control @error('costo') is-invalid @enderror" name="costo" id="costo" value="{{old('costo',$ultima_inscripcion->costo ?? '')}}">
                <label for="costo">Ingrese Costo</label>  
            </div>   
        </div>
        
    </div>
<hr>

    <input  type="text" name="estudiante_id"  value="{{ $persona->estudiante->id }}" hidden>    
    
    @isset($editando)
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
                            @isset($ultima_inscripcion)     
                                <option  value="{{$estado->id}}" {{$estado->id==$ultima_inscripcion->estado_id ? 'selected':''}}>{{$estado->estado}}</option>     
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
                            @if(isset($ultima_inscripcion->condonado))
                                @if($ultima_inscripcion->condonado==0)
                                    <option value="{{ $ultima_inscripcion->condonado }}" {{ 0==$ultima_inscripcion->condonado ? 'selected':''}} >No condonar</option>
                                    <option value="1">Condonar</option>
                                @else
                                    <option value="{{$ultima_inscripcion->condonado }}" {{ 1==$ultima_inscripcion->condonado ? 'selected':''}} > Condonar </option>
                                    <option value="0">No condonar</option>
                                @endif
                            @else  
                                <option value="MUJER" @if(old('condonado') == 'MUJER') {{'selected'}} @endif>MUJER</option>
                                <option value="HOMBRE" @if(old('condonado') == 'HOMBRE') {{'selected'}} @endif>HOMBRE</option>
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
                            @if(isset($ultima_inscripcion->vigente))
                                @if($ultima_inscripcion->vigente==0)
                                    <option value="{{ $ultima_inscripcion->vigente }}" {{ 0==$ultima_inscripcion->vigente ? 'selected':''}} >Desvigente</option>
                                    <option value="1">Vigente</option>
                                @else
                                    <option value="{{$ultima_inscripcion->vigente }}" {{ 1==$ultima_inscripcion->vigente ? 'selected':''}} > Vigente </option>
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


    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4">
            @if($errors->has('objetivo'))
                <span class="text-danger"> {{ $errors->first('objetivo')}}</span>
            @endif
        </div>
    </div>


                                                                                                                                                                                                                           
                <textarea name="objetivo" class="form-control @error('objetivo') is-invalid @enderror" id="objetivo" cols="30" rows="3" row="6" placeholder="Introduce el objetivo de la inscripcion">{{ old('objetivo') ?? $ultima_inscripcion->objetivo ?? '' }}</textarea>
    {{-- <div id="toolbar-container"></div>
    <div id="editor" name="objetivo">

    </div> --}}


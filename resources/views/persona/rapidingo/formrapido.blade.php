                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                        @if($errors->has('nombre'))
                            <span class="text-danger"> {{ $errors->first('nombre')}}</span>
                        @endif
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                        @if($errors->has('apellidop'))
                            <span class="text-danger"> {{ $errors->first('apellidop')}}</span>
                        @endif
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" > 
                        <div class="form-floating mb-3 text-gray">
                            <input  type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{old('nombre',$persona->nombre ?? '')}}">
                            <label for="nombre">nombre</label>
                        </div>
                    </div>
                    {{-- %%%%%%%%%%%%%%% CAMPO APELLIDO PATERNO --}}
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" >
                        <div class="form-floating mb-3 text-gray">
                            <input  type="text" name="apellidop" class="form-control @error('apellidop') is-invalid @enderror" value="{{old('apellidop',$persona->apellidop ?? '')}}">
                            <label for="apellidop">apellidop</label>
                        </div>    
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" >
                        <div class="form-floating mb-3 text-gray">
                            <input class="form-control @error('telefono') is-invalid @enderror" type="tel" id="phone" name="telefono" value="{{ old('telefono', app(\App\Services\TelefonoService::class)->isMissing($persona->telefono ?? null) ? '' : $persona->telefono) }}">
                            <label for="phone">Teléfono (opcional)</label>
                        </div>
                    </div>
                    {{-- %%%%%%%%%%%%%%%%%%%%%%% CAMPO APELLIDO MATERNO %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}}
                </div> {{-- FIN DEL ROW DE LOS INPUT DE ESTUDIANTE  --}}

                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                        @if($errors->has('telefono'))
                            <span class="text-danger"> {{ $errors->first('telefono')}}</span>
                        @endif
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                        @if($errors->has('como_id'))
                            <span class="text-danger"> {{ $errors->first('como_id')}}</span>
                        @endif
                    </div>    
                </div>
                <div class="row">
                    
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" >
                        <div class="form-floating mb-3 text-gray">
                            <input  type="date" name="vuelvefecha" class="form-control @error('vuelvefecha') is-invalid @enderror" value="{{old('vuelvefecha',$persona->vuelvefecha ?? '')}}">
                            <label for="vuelvefecha">Cuando vuelve?</label>
                        </div>
                    </div>
                    
                    <div class="col-xs-9 col-sm-9 col-md-6 col-lg-4" >
                        <div class="form-floating mb-3 text-gray">
                            <select class="form-control @error('estado_id') is-invalid @enderror" data-old="{{ old('estado_id') }}" name="estado_id" id="estado_id">
                                <option value="" selected>Seleccion un estado</option>
                                @foreach ($estados as $estado)
                                    @isset($persona)     
                                        <option  value="{{$estado->id}}" {{$estado->id==$persona->habilitado ? 'selected':''}}>{{$estado->estado}}</option>     
                                    @else
                                        <option value="{{ $estado->id}}" {{ old('estado_id') == $estado->id ? 'selected':'' }} >{{ $estado->estado }}</option>
                                    @endisset 
                                @endforeach
                            </select>
                            <label for="como">Estado</label>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" >
                        <div class="row">
                            <div class="col-xs-9 col-sm-9 col-md-9 col-lg-9" >
                                <div class="form-floating mb-3 text-gray">
                                    <select onchange="mostrarModal();" onfocus="this.selectedIndex = -1;"  class="form-control @error('como_id') is-invalid @enderror" data-old="{{ old('como_id') }}" name="como_id" id="como_id">
                                        <option value="" selected>Seleccionen como se enteró?</option>
                                        @foreach ($comos as $como)
                                            @isset($persona)     
                                                <option  value="{{$como->id}}" {{$como->id==$persona->como_id ? 'selected':''}}>{{$como->como}}</option>     
                                            @else
                                                <option value="{{ $como->id}}" {{ old('como_id') == $como->id ? 'selected':'' }} >{{ $como->como }}</option>
                                            @endisset 
                                        @endforeach
                                    </select>
                                    <label for="como">como</label>
                                </div>
                            </div>
                            {{-- %%%%%%%%%%%%%%%%%%%%%%%%%%  CAMPO REFERENCIA %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}}
                            <div class="col-xs-3 col-sm-3 col-md-3 col-lg-3" >
                                <div class="form-floating mb-3 text-gray">
                                    <input  type="text" readonly id="persona_id" name="persona_id" class="form-control @error('carnet') is-invalid @enderror" value="{{old('persona_id',$persona->persona_id ?? '')}}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" >
                            @if($errors->has('interests'))
                                <span class="text-danger"> {{ $errors->first('interests')}}</span>
                            @endif
                    </div>
                </div>
               <div class="row">
                    <div class="card bg-warning">
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 @error('interests') is-invalid @enderror">
                            @isset($persona)
                                @foreach ($interests_currents as $current)
                                    <div class="form-check form-switch form-check-inline mb-2 mt-2 ml-2 mr-2">
                                        <input class="form-check-input" type="checkbox" name="interests[{{$current->id}}]" checked value="{{$current->interest}}" id="{{$current->interest}}">
                                        <label class="form-check-label" for="{{$current->id}}">{{$current->interest}}</label>
                                    </div>
                                @endforeach
                                @foreach ($interests_faltantes as $faltante)
                                    <div class="form-check form-switch form-check-inline mb-2 mt-2 ml-2 mr-2">
                                        <input class="form-check-input" type="checkbox" name="interests[{{$faltante->id}}]"  value="{{$faltante->interest}}" id="{{$faltante->interest}}">
                                        <label class="form-check-label" for="{{$faltante->interest}}">{{$faltante->interest}}</label>
                                    </div>
                                @endforeach
                            @else
                                @foreach ($interests as $interest)
                                    <div class="form-check form-switch form-check-inline mb-2 mt-2 ml-2 mr-2">
                                        <input class="form-check-input" type="checkbox" name="interests[{{$interest->id}}]" value="{{$interest->interest}}" id="{{$interest->interest}}">
                                        <label class="form-check-label" for="{{$interest->interest}}">{{$interest->interest}}</label>
                                    </div>
                                @endforeach
                            @endisset
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" >
                            @if($errors->has('observacion'))
                                <span class="text-danger"> {{ $errors->first('observacion')}}</span>
                            @endif
                    </div>
                </div>
                <textarea placeholder="Ingrese un requerimiento inicial por que esta registrando el cliente el motivo escuchar bien al cliente"  name="observacion" id="observacion" class="form-control @error('observacion') is-invalid @enderror" >{{old('observacion',$observacion ?? '')}}</textarea>

{{-- %%%%%%%%%%%%%%%%%%%%%%%%%%% CAMPO NOMBRE %%%%%%%%%%%%%%%%%%%%%% --}}
<div class="modal" tabindex="-1" id="modal-ife">
    <div class="modal-dialog modal-lg modalito">
        <div class="modal-content">
            <div class="modal-header">
                Seleccione la persona referenciadora
                <button class="btn btn-danger close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <table id="personas" class="table table-bordered table-hover table-striped">
                    <thead class="bg-primary">
                        <tr>
                            <th>ID</th>
                            <th>OLD</th>
                            <th>NOMBRE</th>
                            <th>APATERNO</th>
                            <th>AMATERNO</th>
                            <th>FOTO</th>
                            <th></th>
                        </tr>
                    </thead>
                </table>

            </div>
            <div class="modal-footer">
                pie del modal 
            </div>
        </div>
    </div>
</div>






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
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6" > 
                        <div class="form-floating mb-3 text-gray">
                            <input  type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{old('nombre',$persona->nombre ?? '')}}">
                            <label for="nombre">nombre</label>
                        </div>
                    </div>
                    {{-- %%%%%%%%%%%%%%% CAMPO APELLIDO PATERNO --}}
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6" >
                        <div class="form-floating mb-3 text-gray">
                            <input  type="text" name="apellidop" class="form-control @error('apellidop') is-invalid @enderror" value="{{old('apellidop',$persona->apellidop ?? '')}}">
                            <label for="apellidop">apellidop</label>
                        </div>    
                    </div>
                </div>

                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6" >
                            @if($errors->has('como_id'))
                                <span class="text-danger"> {{ $errors->first('como_id')}}</span>
                            @endif
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                        @if($errors->has('telefono'))
                            <span class="text-danger"> {{ $errors->first('telefono')}}</span>
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6" >
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
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6" >
                        <div class="form-floating mb-3 text-gray">
                            <input class="form-control @error('telefono') is-invalid @enderror" type="tel" id="phone" name="telefono" value="{{ old('telefono', app(\App\Services\TelefonoService::class)->isMissing($persona->telefono ?? null) ? '' : $persona->telefono) }}">
                            <label for="phone">Teléfono (opcional)</label>
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
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 @error('observacion') is-invalid @enderror">
                        <textarea placeholder="Ingrese un requerimiento inicial por que esta registrando el cliente el motivo escuchar bien al cliente" maxlength="5" name="observacion" id="observacion" class="form-control @error('observacion') is-invalid @enderror" >{{old('observacion',$observacion ?? '')}}</textarea>
                    </div>
                </div>

{{-- %%%%%%%%%%%%%%%%%%%%%%%%%%% CAMPO NOMBRE %%%%%%%%%%%%%%%%%%%%%% --}}
<div class="modal" tabindex="-1" id="modal-ife">
    <div class="modal-dialog modal-lg modalito">
        <div class="modal-content">
            <div class="modal-header">
                Seleccione la persona referenciadorax
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






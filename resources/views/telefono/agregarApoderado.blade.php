
@extends('adminlte::page')

@section('css')
    <link rel="stylesheet" href="{{asset('bootstrap/css/bootstrap.css')}}">
@stop

@section('title', 'Agregar Apoderado')

@section('content_header')
    {{-- <h1 class="text-center text-primary">Telefonos <span class="text-secondary">{{$persona->nombre.' '.$persona->apellidop.' '.$persona->apellidom}} </span> </h1> --}}
@stop

@section('content')
    <div class="card">
        <div class="card-header bg-secondary">
            FORMULARIO 
        </div>
        <div class="card-body">
                <form action="{{route('guardar.apoderado.existente')}}" method="POST">
                    @csrf
                    <input hidden class="form-control" type="text" name="persona_id"  value="{{$estudiante->id}}">
                    <input hidden class="form-control" type="text" name="apoderado_id" value="{{$apoderado->id}}">
                    
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 input-group text-sm" > 
                        <div class="input-group mb-2" >
                            <p class="col-3 form-control bg-secondary p-1" for="">Teléfono</p> 
                            <input class="form-control" type="text" name="telefono" placeholder="Opcional" value="{{ old('telefono', app(\App\Services\TelefonoService::class)->isMissing($apoderado->telefono) ? '' : $apoderado->telefono) }}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" >
                                @if($errors->has('telefono'))
                                    <span class="text-danger"> {{ $errors->first('telefono')}}</span>
                                @endif
                        </div>
                    </div>
                    
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 input-group text-sm" > 
                        <div class="input-group mb-2" >
                            <p class="col-3 form-control bg-secondary p-1" for="">Parentesco</p> 
                            <select class="form-control @error('parentesco') is-invalid @enderror"  name="parentesco" id="parentesco">
                                <option value="">Grado Parentesco</option>
                                    @isset($registro_pivot)
                                        <option value="PAPA" @if($registro_pivot[0]->parentesco == 'PAPA') {{'selected'}} @endif>PAPA</option>
                                        <option value="MAMA" @if($registro_pivot[0]->parentesco == 'MAMA') {{'selected'}} @endif>MAMA</option>    
                                        <option value="ABUELO" @if($registro_pivot[0]->parentesco == 'ABUELO') {{'selected'}} @endif>ABUELO</option>
                                        <option value="ABUELA" @if($registro_pivot[0]->parentesco == 'ABUELA') {{'selected'}} @endif>ABUELA</option>
                                        <option value="HERMANO" @if($registro_pivot[0]->parentesco == 'HERMANO') {{'selected'}} @endif>HERMANO</option>
                                        <option value="HERMANA" @if($registro_pivot[0]->parentesco == 'HERMANA') {{'selected'}} @endif>HERMANA</option>
                                        <option value="TIO" @if($registro_pivot[0]->parentesco == 'TIO') {{'selected'}} @endif>TIO</option>
                                        <option value="TIA" @if($registro_pivot[0]->parentesco == 'TIA') {{'selected'}} @endif>TIA</option>
                                        <option value="ESPOSO" @if($registro_pivot[0]->parentesco == 'ESPOSO') {{'selected'}} @endif>ESPOSO</option>
                                        <option value="ESPOSA" @if($registro_pivot[0]->parentesco == 'ESPOSA') {{'selected'}} @endif>ESPOSA</option>
                                        <option value="OTRO" @if($registro_pivot[0]->parentesco == 'OTRO') {{'selected'}} @endif>OTRO</option>
                                    @else 
                                        <option value="PAPA" @if(old('parentesco') == 'PAPA') {{'selected'}} @endif>PAPA</option>
                                        <option value="MAMA" @if(old('parentesco') == 'MAMA') {{'selected'}} @endif>MAMA</option>    
                                        <option value="ABUELO" @if(old('parentesco') == 'ABUELO') {{'selected'}} @endif>ABUELO</option>
                                        <option value="ABUELA" @if(old('parentesco') == 'ABUELA') {{'selected'}} @endif>ABUELA</option>
                                        <option value="HERMANO" @if(old('parentesco') == 'HERMANO') {{'selected'}} @endif>HERMANO</option>
                                        <option value="HERMANA" @if(old('parentesco') == 'HERMANA') {{'selected'}} @endif>HERMANA</option>
                                        <option value="TIO" @if(old('parentesco') == 'TIO') {{'selected'}} @endif>TIO</option>
                                        <option value="TIA" @if(old('parentesco') == 'TIA') {{'selected'}} @endif>TIA</option>
                                        <option value="ESPOSO" @if(old('parentesco') == 'ESPOSO') {{'selected'}} @endif>ESPOSO</option>
                                        <option value="ESPOSA" @if(old('parentesco') == 'ESPOSA') {{'selected'}} @endif>ESPOSA</option>
                                        <option value="OTRO" @if(old('parentesco') == 'OTRO') {{'selected'}} @endif>OTRO</option>
                                    @endisset
                            </select>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" >
                                @if($errors->has('parentesco'))
                                    <span class="text-danger"> {{ $errors->first('parentesco')}}</span>
                                @endif
                        </div>
                    </div>
                    @include('include.botones')
                </form>
        </div>
    </div>
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    
    <script>
        $(document).ready(function() {
            
        } );
    </script>
@stop

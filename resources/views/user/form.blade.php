<input class="form-control" hidden type="text" name="persona_id" value="{{old('persona_id','' ?? $persona->id)}}">
<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4 input-group text-sm">    
        @if($errors->has('name'))
        <span class="text-danger"> {{ $errors->first('name')}}</span>
        @endif
    </div>
</div> 
<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 input-group" >
        <div class="input-group mb-3">
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            @isset ($persona)
                value="{{old('name',$user->name ?? strtolower(str_replace(' ','',$persona->nombre).($persona->id)))}}"
            @else
                value="{{old('name',$user->name ?? '')}}" placeholder="introduzca nombre de usuario" autofocus
            @endisset
            >
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-user "></span>
                </div>
            </div>
           
        </div>
    </div>
</div>


<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4 input-group text-sm">    
        @if($errors->has('email'))
        <span class="text-danger"> {{ $errors->first('email')}}</span>
        @endif
    </div>
</div> 
<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 input-group" >
        <div class="input-group mb-3">
            <input type="text" name="email" class="form-control @error('email') is-invalid @enderror"
                value="{{old('email',$user->email ?? strtolower(str_replace(' ','',$persona->nombre).($persona->id).'@ife.bo'))}}" placeholder="Escriba correo eletronico">
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-envelope"></span>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4 input-group text-sm">    
        @if($errors->has('password'))
        <span class="text-danger"> {{ $errors->first('password')}}</span>
        @endif
    </div>
</div> 
<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" >
        <div class="input-group mb-3">
            <input type="password" name="password" id="password"
            class="form-control @error('password') is-invalid @enderror"
            placeholder="Introduzca la contraseña"
            @isset($persona)
                value="*{{ucfirst(strtolower(str_replace(' ','',$persona->nombre).($persona->id).'*'))}}"
            @endisset
            >
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-lock"></span>
                </div>
            </div>
            <div class="input-group-append">
                <button class="btn btn-outline-secondary js-toggle-password" type="button" data-target="#password" title="Mostrar u ocultar contrasena">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4 input-group text-sm">    
            @if($errors->has('password_confirmation'))
            <span class="text-danger"> {{ $errors->first('password_confirmation')}}</span>
            @endif
        </div>
    </div> 
</div>
<div class="row">
     <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 input-group" >
        <div class="input-group mb-3">
            <input type="password" name="password_confirmation" id="password_confirmation"
                class="form-control @error('password_confirmation') is-invalid @enderror"
                placeholder="Vuelva a escribir la contraseña para confirmar"
                @isset($persona)
                    value="*{{ucfirst(strtolower(str_replace(' ','',$persona->nombre).($persona->id).'*'))}}"
                @endisset
                >
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-lock"></span>
                </div>
            </div>
            <div class="input-group-append">
                <button class="btn btn-outline-secondary js-toggle-password" type="button" data-target="#password_confirmation" title="Mostrar u ocultar contrasena">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            @if($errors->has('password_confirmation'))
                <span class="text-danger"> {{ $errors->first('password_confirmation')}}</span>
            @endif
        </div>
    </div>
</div>
<div class="row">
    
</div>
<div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 input-group" >
            <div class="input-group mb-3">
                <input type="file" class="form-control"  data-initial-preview="{{isset($user) ? app(\App\Services\ProfilePhotoService::class)->forUser($user) : app(\App\Services\ProfilePhotoService::class)->placeholder() }}" accept=".png, .jpg, .jpeg, .gif, .webp" name="foto" id="foto" data-classButton="btn btn-success" data-input="false" data-classIcon="icon-plus">
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-camera"></span>
                    </div>
                </div>
                @if($errors->has('password_confirmation'))
                    <span class="text-danger"> {{ $errors->first('password_confirmation')}}</span>
                @endif
            </div>
        </div>
</div>

<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4 input-group text-sm" >
        <div class="border-danger input-group text-center">
            @isset ($user)
                <div class="text-center">
                    <img data-profile-photo class="img-thumbnail" src="{{app(\App\Services\ProfilePhotoService::class)->forUser($user)}}" alt="">
                </div>
            @else
                <div class="text-center">
                    <img data-profile-photo class="img-thumbnail" src="{{app(\App\Services\ProfilePhotoService::class)->placeholder()}}" alt="">
                </div>
            @endisset
        </div>
    </div>
</div>



         <div class="row p-3">
            {{-- %%%%%%%%%%%%%%%%%%%%%%% CAMPO OBSERVACION  %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}} 
            <div class="col-xs-12 col-sm-12 col-md-8 col-lg-8 input-group" style="position: relative" >
              
                
              
               
           
            </div>
            {{-- $$$$$$$$$$$ CAMPO REPETIR FOTOGRAFIA --}}
            
        </div>

            {{-- Register button --}}
        <div class="text-center">
            <button type="submit" class="btn btn-primary">
                <span class="fas fa-user-plus text-white"></span> Guardar
            </button>
        </div>
@once
<script>
document.addEventListener('DOMContentLoaded', function () {
    var botones = document.querySelectorAll('.js-toggle-password');
    botones.forEach(function (boton) {
        boton.addEventListener('click', function () {
            var selector = this.getAttribute('data-target');
            var input = document.querySelector(selector);
            if (!input) {
                return;
            }
            var icono = this.querySelector('i');
            var esTexto = input.type === 'text';
            input.type = esTexto ? 'password' : 'text';
            if (icono) {
                icono.classList.toggle('fa-eye', esTexto);
                icono.classList.toggle('fa-eye-slash', !esTexto);
            }
        });
    });
});
</script>
@endonce
        


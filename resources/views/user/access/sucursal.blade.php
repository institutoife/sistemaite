@if(auth()->check() && app(\App\Services\AccountSecurity::class)->ready() && !app(\App\Services\AccountSecurity::class)->family(auth()->user()))
    @php($branchIds=app(\App\Services\SedeAccess::class)->allowedIds(auth()->user()))
    @php($branches=\App\Models\CrmSede::whereIn('id',$branchIds)->orderBy('nombre')->get())
    <li class="nav-item d-flex align-items-center px-1">
        @if($branches->isNotEmpty())
            <form method="POST" action="{{ route('users.sede.switch') }}" class="m-0">@csrf
                <label for="sede-activa" class="sr-only">Sucursal activa</label>
                <select id="sede-activa" name="sede_id" class="form-control form-control-sm" style="max-width:145px" aria-label="Sucursal activa" onchange="this.form.submit()">
                    @if(auth()->user()->hasRole('Admin'))<option value="0" @if(!session('sede_activa')) selected @endif>Todas · Admin</option>@endif
                    @foreach($branches as $branch)<option value="{{ $branch->id }}" @if((int)session('sede_activa')===$branch->id) selected @endif>{{ $branch->nombre }}</option>@endforeach
                </select>
            </form>
        @else<span class="small">Sin Sucursal<br>Acceso histórico</span>@endif
    </li>
@endif

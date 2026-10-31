@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Ciudad: {{$ciudad->ciudad}}</h1>
@stop

@section('content')
    {{-- @isset($Mensaje)
        <p>{{$Mensaje}}</p>    
    @endisset
    
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped"> 
                <tr class="bg-primary">
                    
                        <th>ATRIBUTO</th>
                        <th>VALOR</th>
                    
                </tr>
                <tbody>
                    <tr>
                        <td>Id</td>
                        <td>{{$ciudad->id}}</td>
                    </tr>
                    <tr>
                        <td>Ciudad</td>
                        <td>{{$ciudad->ciudad}}</td>
                    </tr>
                    <tr>
                        <td>Pais</td>
                        <td>{{$pais->nombrepais}}</td>
                    </tr>
                    <tr>
                        <td>Creado</td>
                        <td>{{$ciudad->created_at}}</td>
                    </tr>
                    <tr>
                        <td>Actualizado</td>
                        <td>{{$ciudad->updated_at}}</td>
                    </tr>

                    <tr>
                        <td>Usuario</td>
                        <td>
                            {{$user->name}}
                            <img data-profile-photo  src="{{app(\App\Services\ProfilePhotoService::class)->forUser($user)}}" alt="{{$user->name}}" class="rounded img-thumbnail img-fluid border-primary border-5">
                        </td>
                    </tr>

                </tbody>
            </table>

        </div>
    </div> --}}
            <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <nav class="course-single-tabs">
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home"
                                role="tab" aria-controls="nav-home" aria-selected="true">Aprendizaje</a>
                                <a class="nav-item nav-link" id="nav-feedback-tab" data-toggle="tab" href="#nav-feedback"
                                role="tab" aria-controls="nav-contact" aria-selected="false">Requisitos</a>
                            <a class="nav-item nav-link" id="nav-topics-tab" data-toggle="tab" href="#nav-topics"
                                role="tab" aria-controls="nav-profile" aria-selected="false">Temario</a>
                            
                        </div>
                    </nav>
                    <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-home" role="tabpanel"
                            aria-labelledby="nav-home-tab">
                            <div class="single-course-details ">
                                <div class="course-widget course-info">
                                    <h4 class="course-title">Lo que aprenderás</h4>
                                    <ul>
                                        {{-- @foreach ($metas as $meta)
                                            <li>
                                                <i class="fa fa-check"></i>
                                                {{$meta->nombre.'fdsfd'}}
                                            </li>
                                        @endforeach --}}
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="nav-feedback" role="tabpanel" aria-labelledby="nav-feedback-tab">
                            <div class="single-course-details ">
                                <div class="course-widget course-info">
                                    <h4 class="course-title">Requisitos</h4>
                                    <ul>
                                        {{-- @foreach ($requisitos as $requisito)
                                            <li>
                                                <i class="fa fa-check"></i>
                                                {{$requisito->nombre}}
                                            </li>
                                        @endforeach --}}
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="nav-topics" role="tabpanel" aria-labelledby="nav-topics-tab">
                            <!--  Course Topics -->
                            <div class="edutim-single-course-segment  edutim-course-topics-wrap">
                                <div class="edutim-course-topics-header d-lg-flex justify-content-between">
                                    <div class="edutim-course-topics-header-left">
                                        <h4 class="course-title">Temario</h4>
                                    </div>
                                    {{-- <div class="edutim-course-topics-header-right">
                                        <span> Total learning: <strong>14 Lessons</strong></span>
                                        <span> Time: <strong>13h 20m 20s</strong> </span>
                                    </div> --}}
                                </div>

                                <div class="edutim-course-topics-contents">
                                    <div class="edutim-course-topic ">
                                        <div class="accordion" id="accordionExample">
                                            <div class="card">
                                                <div class="card-header" id="headingTwo">
                                                    <h2 class="mb-0">
                                                        <button
                                                            class="btn-block text-left collapsed curriculmn-title-btn"
                                                            type="button" data-toggle="collapse"
                                                            data-target="#collapseTwo" aria-expanded="false"
                                                            aria-controls="collapseTwo">
                                                            <h4 class="curriculmn-title"> Introducción</h4>
                                                        </button>
                                                    </h2>
                                                </div>
                                                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo"
                                                    data-parent="#accordionExample">
                                                    <div class="course-lessons">
                                                        <div class="single-course-lesson">
                                                            <div class="course-topic-lesson">
                                                                <i class="fab fa-file"></i>
                                                                <span>Introducción al curso</span>
                                                            </div>
                                                        </div>
                                                        <div class="single-course-lesson">
                                                            <div class="course-topic-lesson">
                                                                <i class="fab fa-file"></i>
                                                                <span>funcionamiento de la computadora</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--  COurse Topics End -->

                        </div>
                        
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="course-sidebar">
                        <div class="course-single-thumb">
                            <img src="{{asset('assets/images/course/course14.jpg')}}" alt="" class="img-fluid w-100">

                            <div class="course-price-wrapper">
                                <div class="course-price">
                                   5000 {{-- <h4>Precio: <span>{{$carrera->precio}}</span> </h4> --}}
                                </div>
                                <div class="buy-btn"><a href="#" class="btn btn-main btn-block">Contactanos</a></div>
                            </div>
                        </div>

                        <div class="course-widget course-details-info">
                            <h4 class="course-title">Este curso incluye:</h4>
                            <ul>
                                <li>
                                    <div class="">
                                        <span><i class="bi bi-flag"></i>Carga Horaria :</span>

                                    </div>
                                </li>
                                <li>
                                    <div class="">
                                        <span><i class="bi bi-paper"></i>Lecciones :</span>

                                    </div>
                                </li>

                                <li>
                                    <div class="">
                                        <span><i class="bi bi-madel"></i>Certificado :</span>
                                        Si
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="course-widget course-share d-flex justify-content-between align-items-center">
                            <span>Compartir Curso</span>
                            <ul class="social-share list-inline">
                                <li class="list-inline-item"><a href="#"><i class="fab fa-facebook"></i></a></li>
                                <li class="list-inline-item"><a href="#"><i class="fab fa-twitter"></i></a></li>
                                <li class="list-inline-item"><a href="#"><i class="fab fa-linkedin"></i></a></li>
                                <li class="list-inline-item"><a href="#"><i class="fab fa-pinterest"></i></a></li>
                            </ul>
                        </div>

                        {{-- <div class="course-widget">
                            <h4 class="course-title">Tags</h4>
                            <div class="single-course-tags">
                                <a href="#">Web Deisgn</a>
                                <a href="#">Development</a>
                                <a href="#">Html</a>
                                <a href="#">css</a>
                            </div>
                        </div> --}}
                    </div>
                </div>
            </div>
        </div>
@stop

<?php

namespace App\Http\Controllers;

use App\Models\Asignatura;
use App\Models\Colegio;
use App\Models\Convenio;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Feriado;
use App\Models\Homequestion;
use App\Models\Homeschedule;
use App\Models\Hometext;
use App\Models\Modalidad;
use App\Models\Carrera;
use App\Models\Persona;
use App\Models\Nivel;
use App\Models\Plan;
use App\Models\Interest;
use App\Models\Ventaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use Carbon\Carbon;

use Dompdf\Dompdf;
use Dompdf\Options;

use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class HomeController extends Controller
{
    public function __construct()
    {
       
        
    }

    public function index()
    {
        return view('home.public.index');
    }

    public function guarderia()
    {
        $modalidadesguarderia=Modalidad::where('vigente',1)->where('nivel_id',1)->get();
        $horalibre=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%hora libre%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",1) // el 1 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $semana=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%semana%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",1) // el 1 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $quincena=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%quincena%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",1) // el 1 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $mes=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%MES%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",1) // el 1 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $bimestre=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%bimestre%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",1) // el 1 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $trimestre=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%trimestre%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",1) // el 1 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        return view('home.public.service', ['pageKey' => 'guarderia', 'modalidades' => $modalidadesguarderia]);

        /* $modalidadesguarderia=Modalidad::where('vigente',1)->where('nivel_id',1)->get();
        return view('home.fronted.guarderia', compact(['modalidadesguarderia'])); */
    }

    public function inicial()
    {
        $modalidadesinicial=Modalidad::where('vigente',1)->where('nivel_id',2)->get();
        $horalibre=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%hora libre%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",2) // el 2 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $semana=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%semana%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",2) // el 2 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $quincena=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%quincena%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",2) // el 2 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $mes=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%MES%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",2) // el 2 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $bimestre=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%bimestre%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",2) // el 2 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $trimestre=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%trimestre%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",2) // el 2 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        return view('home.public.service', ['pageKey' => 'inicial', 'modalidades' => $modalidadesinicial]);
    }

    public function primaria()
    {
        $modalidadesprimaria=Modalidad::where('vigente',1)->where('nivel_id',3)->get();
        
        $horalibre=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%hora libre%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",3) // el 3 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $semana=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%semana%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",3) // el 3 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $quincena=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%quincena%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",3) // el 3 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $mes=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%MES%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",3) // el 3 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $bimestre=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%bimestre%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",3) // el 3 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $trimestre=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%trimestre%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",3) // el 3 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        return view('home.public.service', ['pageKey' => 'primaria', 'modalidades' => $modalidadesprimaria]);
    }

    public function secundaria()
    {
        $modalidadessecundaria=Modalidad::where('vigente',1)->where('nivel_id',4)->get();
        $horalibre=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%hora libre%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",4) // el 4 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $semana=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%semana%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",4) // el 4 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $quincena=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%quincena%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",4) // el 4 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $mes=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%MES%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",4) // el 4 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $bimestre=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%bimestre%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",4) // el 4 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $trimestre=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%trimestre%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",4) // el 4 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        return view('home.public.service', ['pageKey' => 'secundaria', 'modalidades' => $modalidadessecundaria]);
    }

    public function preuniversitario()
    {
        $modalidadespreuniversitario=Modalidad::where('vigente',1)->where('nivel_id',5)->get();
        $horalibre=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%hora libre%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",5) // el 5 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $semana=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%semana%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",5) // el 5 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $quincena=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%quincena%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",5) // el 5 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $mes=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%MES%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",5) // el 5 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $bimestre=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%bimestre%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",5) // el 5 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $trimestre=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%trimestre%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",5) // el 5 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        return view('home.public.service', ['pageKey' => 'preuniversitario', 'modalidades' => $modalidadespreuniversitario]);

    }


    public function universitario()
    {
        
        $modalidadesuniversitario=Modalidad::where('vigente',1)->where('nivel_id',7)->get();
        $horalibre=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%hora libre%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",7) // el 7 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $semana=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%semana%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",7) // el 7 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $quincena=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%quincena%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",7) // el 7 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $mes=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%MES%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",7) // el 7 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $bimestre=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%bimestre%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",7) // el 7 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        $trimestre=Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
        ->where("modalidad","like",'%trimestre%')// semana es la primer palabra del nombre de la modadalidad
        ->where("modalidads.nivel_id",7) // el 7 es un id del nivel buscado
        ->where('vigente',1)
        ->get();
        return view('home.public.service', ['pageKey' => 'universitario', 'modalidades' => $modalidadesuniversitario]);

    }

    public function robotica()
    {
        return view('home.public.service', ['pageKey' => 'robotica']);
    } 

    public function programacion()
    {
        return view('home.public.service', ['pageKey' => 'programacion']);
    } 

    public function creacionapp()
    {
        return view('home.public.service', ['pageKey' => 'creacionapp']);
    } 

    public function disenoweb()
    {
        return view('home.public.service', ['pageKey' => 'disenoweb']);
    } 

    public function computacion()
    {
        $computacion = Asignatura::where('carrera_id',1)->get();
        $intereses = Ventaja::where('interest_id',10)->get();

        return view('home.public.service', ['pageKey' => 'computacion', 'catalogItems' => $computacion, 'benefits' => $intereses]);
    } 

    public function disenografico()
    {
        $disenografico=Asignatura::where('carrera_id',2)->get();
        return view('home.public.service', ['pageKey' => 'disenografico', 'catalogItems' => $disenografico]);
    } 

    public function mantenimientocomputadoras()
    {
        $mantenimientocomputadoras=Asignatura::where('carrera_id',3)->get();
        return view('home.public.service', ['pageKey' => 'mantenimientocomputadoras', 'catalogItems' => $mantenimientocomputadoras]);
    } 

    public function ajedrez()
    {
        return view('home.public.service', ['pageKey' => 'ajedrez']);
    } 

    public function cuborubik()
    {
        return view('home.public.service', ['pageKey' => 'cuborubik']);
    } 

    public function plan($id)
    {
        $convenio = Convenio::find($id);
        $planes = $convenio ? $convenio->planes : collect();
        return view('home.public.plans', compact(['planes']));
    }

    public function about()
    {
        return view('home.public.content', ['pageKey' => 'about']);
    } 
    
    public function fotocopia()
    {
        return view('home.public.service', ['pageKey' => 'fotocopia']);
    } 

    public function resolucionpracticos()
    {
        return view('home.public.service', ['pageKey' => 'resolucionpracticos']);
    } 

    public function asistenteife()
    {
        return view('home.public.service', ['pageKey' => 'asistenteife']);
    }

    public function matematica()
    {
        return view('home.public.service', ['pageKey' => 'matematica']);
    } 

    public function fisica()
    {
        return view('home.public.service', ['pageKey' => 'fisica']);
    } 

    public function quimica()
    {
        return view('home.public.service', ['pageKey' => 'quimica']);
    } 

    public function algebra()
    {
        return view('home.public.service', ['pageKey' => 'algebra']);
    } 

    public function lenguaje()
    {
        return view('home.public.service', ['pageKey' => 'lenguaje']);
    }
    
    public function estadistica()
    {
        return view('home.public.service', ['pageKey' => 'estadistica']);
    }

    public function ingles()
    {
        return view('home.public.service', ['pageKey' => 'ingles']);
    } 

    public function contact()
    {
        return view('home.public.content', ['pageKey' => 'contact']);
    }

    public function privacy()
    {
        return view('home.public.content', ['pageKey' => 'privacy']);
    }

    public function termscondition()
    {
        return view('home.public.content', ['pageKey' => 'termscondition']);
    }
    
    public function preguntasfrecuentes()
    {
        $preguntasfrecuentes = Homequestion::all();
        return view('home.public.content', ['pageKey' => 'preguntasfrecuentes', 'preguntasfrecuentes' => $preguntasfrecuentes]);
    }

    public function planescorporativos()
    {
        return view('home.public.service', ['pageKey' => 'planescorporativos']);
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        $text = Hometext::get()->last();
        return view('home.text.edit', compact('text'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Hometext $text)
    {
        $text->header=$request->header;
        $text->heading=$request->heading;
        $text->subheading=$request->subheading;

         if ($request->hasFile('banner')) {
            // verificando si exites la foto actual
            
            
            if (Storage::disk('public')->exists($text->banner)) {
                // aquí la borro
                Storage::disk('public')->delete($text->banner);
            }

            $banner = $request->file('banner');
            $nombreImagen = 'banners/' . Str::random(20) . '.jpg';
            /** las convertimos en jpg y la redimensionamos */
            $imagen = Image::make($banner)->encode('jpg', 75);
            $imagen->resize(1400, 300, function ($constraint) {
                $constraint->upsize();
            });
            /* las guarda en en la carpeta estudiantes  */
            $bannercillo = Storage::disk('public')->put($nombreImagen, $imagen->stream());

            $text->banner = $nombreImagen;
        }
        $text->save();
        return redirect()->route('home.edit');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    // public function imprimirPrimaria(){
    //     $modalidades = Modalidad::join('nivels','modalidads.nivel_id','nivels.id')
    //     ->where("nivels.id",3)
    //     ->where("vigente",1)
    //     ->get();
    //     $dompdf = PDF::loadView('home.fronted.primariareporte', compact('modalidades'));
    //     /**entrae a la persona al cual corresponde esta inscripcion */
    //     $fecha_actual = Carbon::now();
    //     $fecha_actual->isoFormat('DD-MM-YYYY-HH:mm:ss');
    //     $dompdf->setPaper('letter','portrait');
    //     // $dompdf->setPaper('A4', 'portrait');
    //     return $dompdf->download("IFE_PRIMARIA_INFO".'.pdf');
   
    // }


    public function imprimirPrimaria() {
        // Obtener las modalidades de primaria vigentes
        $modalidades = Modalidad::join('nivels', 'modalidads.nivel_id', 'nivels.id')
                        ->where('nivels.id', 3) // Filtrar por el nivel de primaria (ejemplo: ID 3)
                        ->where('vigente', 1) // Filtrar solo las modalidades vigentes
                        ->get();
    
        // Configurar las opciones para Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);
    
        // Crear una instancia de Dompdf con las opciones configuradas
        $dompdf = new Dompdf($options);
    
        // Cargar la vista que contiene el contenido del PDF
        $view = view('home.fronted.primariareporte', compact('modalidades'))->render();
    
        // Dividir el contenido en trozos de 1 MB
        $chunks = str_split($view, 1024 * 1024);
    
        // Configurar el tamaño y orientación del papel
        $dompdf->setPaper('letter', 'portrait'); // Papel carta en orientación vertical
    
        // Crear el PDF para cada trozo de contenido
        foreach ($chunks as $index => $chunk) {
            // Cargar HTML en Dompdf con la codificación UTF-8
            $dompdf->loadHtml(mb_convert_encoding($chunk, 'HTML-ENTITIES', 'UTF-8'));
    
            // Renderizar el PDF (capturando cualquier excepción si ocurre)
            try {
                $dompdf->render();
            } catch (\Exception $e) {
                // Manejar cualquier excepción que ocurra durante el renderizado
                return response()->json(['error' => 'Error al generar el PDF: ' . $e->getMessage()], 500);
            }
    
            // Guardar el PDF generado en el servidor
            //$pdfOutput = $dompdf->output();
            //file_put_contents(storage_path('app/public/IFE_PRIMARIA_INFO_'.$index.'.pdf'), $pdfOutput); // Guardar el PDF en almacenamiento público
        }
    
        $nombreArchivo = "IFE_PRIMARIA_INFO" . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        return $dompdf->stream($nombreArchivo);
    }
    public function imprimirSecundaria() {
        // Obtener las modalidades de primaria vigentes
        $modalidades = Modalidad::join('nivels', 'modalidads.nivel_id', 'nivels.id')
                        ->where('nivels.id', 4) // Filtrar por el nivel de primaria (ejemplo: ID 3)
                        ->where('vigente', 1) // Filtrar solo las modalidades vigentes
                        ->get();
    
        // Configurar las opciones para Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);
    
        // Crear una instancia de Dompdf con las opciones configuradas
        $dompdf = new Dompdf($options);
    
        // Cargar la vista que contiene el contenido del PDF
        $view = view('home.fronted.secundariareporte', compact('modalidades'))->render();
    
        // Dividir el contenido en trozos de 1 MB
        $chunks = str_split($view, 1024 * 1024);
    
        // Configurar el tamaño y orientación del papel
        $dompdf->setPaper('letter', 'portrait'); // Papel carta en orientación vertical
    
        // Crear el PDF para cada trozo de contenido
        foreach ($chunks as $index => $chunk) {
            // Cargar HTML en Dompdf con la codificación UTF-8
            $dompdf->loadHtml(mb_convert_encoding($chunk, 'HTML-ENTITIES', 'UTF-8'));
    
            // Renderizar el PDF (capturando cualquier excepción si ocurre)
            try {
                $dompdf->render();
            } catch (\Exception $e) {
                // Manejar cualquier excepción que ocurra durante el renderizado
                return response()->json(['error' => 'Error al generar el PDF: ' . $e->getMessage()], 500);
            }
    
            // Guardar el PDF generado en el servidor
            //$pdfOutput = $dompdf->output();
            //file_put_contents(storage_path('app/public/IFE_SECUNDARIA_INFO_'.$index.'.pdf'), $pdfOutput); // Guardar el PDF en almacenamiento público
        }
    
        $nombreArchivo = "IFE_SECUNDARIA_INFO" . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        return $dompdf->stream($nombreArchivo);
    }

}

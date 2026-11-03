<?php
// CLI-only synthetic browser fixture. No real database, accounts or HTTP requests.
if (PHP_SAPI !== 'cli') exit(1);
$root=dirname(__DIR__,2);
require $root.'/vendor/autoload.php';
foreach (simplexml_load_file($root.'/phpunit.attendance.xml')->php->env as $env) {
    $key=(string)$env['name'];$value=(string)$env['value'];putenv($key.'='.$value);$_ENV[$key]=$value;$_SERVER[$key]=$value;
}
$preview=new class('preview') extends \Tests\Feature\PresentAttendanceTest {
    public function build(): void
    {
        $this->setUp();
        try {
            $this->present();$this->present('computacion');
            $this->present('apoyo',['horafin'=>'10:30']);$this->present('apoyo',['horafin'=>'10:10']);
            $this->present('apoyo',['fecha'=>'2026-11-01']);
            $response=$this->get(route('clase.presentes'))->assertOk();
            $data=$this->getJson(route('presentes.estado'))->assertOk()->json();
            $doc=new DOMDocument;@$doc->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
            $xpath=new DOMXPath($doc);
            $main=$doc->saveHTML($xpath->query('//*[@id="ife-presentes-list"]')->item(0));
            $alerts=$doc->saveHTML($xpath->query('//*[@id="ife-presence"]')->item(0));
            $navbar=view('clase.asistencia.exit-navbar')->render();
            $pub='file:///'.str_replace('\\','/',public_path()).'/';
            $head='<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
            foreach(['vendor/adminlte/dist/css/adminlte.min.css','vendor/fontawesome-free/css/all.min.css','assets/css/presentes.css'] as $css) $head.='<link rel="stylesheet" href="'.$pub.$css.'">';
            $scripts='<script>window.presenceFixture='.json_encode($data,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS).';</script>';
            foreach(['vendor/jquery/jquery.min.js','vendor/bootstrap/js/bootstrap.bundle.min.js','../tests/Browser/presentes-ui.js','assets/js/presentes.js'] as $script) $scripts.='<script src="'.$pub.$script.'"></script>';
            $folder=storage_path('framework/testing/presentes-ui');if(!is_dir($folder))mkdir($folder,0777,true);
            $html='<!doctype html><html lang="es"><head>'.$head.'</head><body><nav class="navbar navbar-light bg-light"><ul class="navbar-nav">'.$navbar.'</ul></nav><main class="container-fluid pt-3">'.$main.'</main>'.$alerts.$scripts.'</body></html>';
            file_put_contents($folder.'/presentes.html',$html);
            file_put_contents($folder.'/otro-modulo.html',str_replace($main,'<h1>Otro módulo del sistema</h1><p>Secretaría está trabajando en otra pantalla.</p>',$html));
            // Mobile layout exercised in a real narrow iframe, independent of Chrome's minimum window width.
            foreach([320,390] as $width) {
                file_put_contents($folder.'/mobile-'.$width.'.html','<!doctype html><html><body><iframe id="fixture" width="'.$width.'" height="1000" src="presentes.html"></iframe><script>addEventListener("message",function(e){if(e.source!==document.getElementById("fixture").contentWindow)return;var p=document.createElement("pre");p.id="ui-test-result";p.textContent=JSON.stringify(e.data);document.body.append(p);});</script></body></html>');
            }
            echo "Fixture local creada con SQLite en memoria; solicitudes del navegador interceptadas.\n";
        } finally { $this->tearDown(); }
    }
};
$preview->build();

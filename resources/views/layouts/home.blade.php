<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IFE</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Carga de fuentes locales -->
    <style>
        @font-face {
            font-family: 'GlyphaLTStd-Bold';
            src: url('homeprincipal/fonts/GlyphaLTStd-Bold.otf') format('opentype');
            font-weight: bold;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Montserrat-Medium';
            src: url('homeprincipal/fonts/Montserrat-Medium.otf') format('opentype');
            font-weight: 500;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Montserrat-Light';
            src: url('homeprincipal/fonts/Montserrat-Light.otf') format('opentype');
            font-weight: 300;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Montserrat-SemiBold';
            src: url('homeprincipal/fonts/Montserrat-SemiBold.otf') format('opentype');
            font-weight: 600;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Montserrat-Bold';
            src: url('homeprincipal/fonts/Montserrat-Bold.otf') format('opentype');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('homeprincipal/css/hero.css') }}">
    <link rel="stylesheet" href="{{ asset('homeprincipal/css/services.css') }}">
    <link rel="stylesheet" href="{{ asset('homeprincipal/css/sobresaliente.css') }}">
    <link rel="stylesheet" href="{{ asset('homeprincipal/css/eventos.css') }}">
    <link rel="stylesheet" href="{{ asset('homeprincipal/css/contactos.css') }}">
    <link rel="stylesheet" href="{{ asset('homeprincipal/css/footer.css') }}">
    {{-- <link rel="stylesheet" href="{{ asset('homeprincipal/css/header.css') }}"> --}}
    {{-- <link rel="stylesheet" href="{{ asset('homeprincipal/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('homeprincipal/css/styles.css') }}"> --}}
    <link rel="stylesheet" href="{{ asset('homeprincipal/css/redes.css') }}">
    <link rel="stylesheet" href="{{ asset('homeprincipal/css/recursos.css') }}">
    <link rel="stylesheet" href="{{ asset('homeprincipal/css/cortesia.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <!-- Navbar -->
    <header class="navbar">
        <div class="container">
          <div class="logo">
              <a href="/">
                  <span class="logo-text pulse-animation">IFE</span>
              </a>
          </div>
            
            <!-- Menú de navegación para escritorio -->
            <nav class="nav-menu">
                <a href="#" class="nav-link">Inicio</a>
                <a href="#" class="nav-link">Cursos</a>
                <a href="#" class="nav-link">Nosotros</a>
                <a href="#" class="nav-link">Contacto</a>
                @auth
                    <a href="{{ route('home') }}" class="btn btn-primary">Sistema</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
                @endauth
            </nav>
            
            <!-- Botón de menú para móvil -->
            <button class="menu-toggle" id="menuToggle">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </div>
        
        <!-- Menú móvil -->
        <div class="mobile-menu" id="mobileMenu">
            <a href="{{ route('home') }}" class="nav-link">Inicio</a>
            <a href="{{ route('cursos') }}" class="nav-link">Cursos</a>
            <a href="{{ route('nosotros') }}" class="nav-link">Nosotros</a>
            <a target="_blank" href="https://wa.me/59171324941?text=Quiero%20m%C3%A1s%20informaci%C3%B3n%20del%20curso%20de%20Contacto" class="nav-link">Contacto</a>
            <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
        </div>
    </header>
    
    <!-- Sección Hero -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text fade-in">
                    <h1>Educación innovadora con <span class="ite-text">IFE</span></h1>
                    <p>Ofrecemos clases de apoyo escolar, cursos de programación, robótica, ajedrez, 
                    cubo rubik, computación, oratoria, inglés, lectura, escritura y caligrafía.</p>
                    <div class="hero-buttons">
                        <a href="{{ route('cursos') }}" class="btn btn-primary">Explorar cursos</a>
                        <a href="{{ route('nosotros') }}" class="btn btn-outline">Conocer más</a>
                    </div>
                </div>
                <div class="hero-image fade-in delay">
                    <img src="{{ asset('homeprincipal/imagen/portada.gif') }}" alt="Estudiantes de IFE aprendiendo">
                </div>
            </div>
        </div>
    </section>

    {{-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  SERVICIOS %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}}
    <section class="services-section">
    <div class="container">
        <div class="section-header">
            <h2>Nuestros <span class="highlight">Servicios</span></h2>
            <p>Ofrecemos una amplia variedad de cursos y actividades educativas para desarrollar habilidades del siglo XXI</p>
        </div>
        <div class="services-grid">
            <!-- Servicio 1 -->
            <div class="service-card fade-in-scroll">
                <img class="service-image" src="{{ asset('homeprincipal/imagen/servicios/apoyo-escolar.png') }}" alt="Apoyo Escolar">
                <div class="service-content">
                    <h3>Apoyo Escolar</h3>
                    <p>Desde Inicial hasta Universidad – Refuerza, avanza y domina todas las materias con clases personalizadas.</p>
                    <a href="https://wa.me/59171324941?text=Quiero%20m%C3%A1s%20informaci%C3%B3n%20del%20curso%20de%20Apoyo%20Escolar" class="btn-consultar" target="_blank">Consultar</a>
                </div>
            </div>

            
            <!-- Servicio 2 -->
            <div class="service-card service-card-animated fade-in-scroll" data-delay="100">
                <img class="service-image" src="{{ asset('homeprincipal/imagen/servicios/programacion.png') }}" alt="Programación">
                <div class="service-content">
                    <h3>Programación</h3>
                    <p>Aprende a crear aplicaciones, sitios web y soluciones digitales con diferentes lenguajes.</p>
                    <a href="https://wa.me/59171324941?text=Quiero%20m%C3%A1s%20informaci%C3%B3n%20del%20curso%20de%20Programaci%C3%B3n" class="btn-consultar" target="_blank">Consultar</a>
                </div>
            </div>
            <!-- Servicio 3 -->
            <div class="service-card fade-in-scroll">
                <img class="service-image" src="{{ asset('homeprincipal/imagen/servicios/robotica.png') }}" alt="Robótica">
                <div class="service-content">
                    <h3>Robótica</h3>
                    <p>Diseña, construye y programa robots mientras desarrollas habilidades de resolución de problemas.</p>
                    <a href="https://wa.me/59171324941?text=Quiero%20m%C3%A1s%20informaci%C3%B3n%20del%20curso%20de%20Rob%C3%B3tica" class="btn-consultar" target="_blank">Consultar</a>
                </div>
            </div>
            <!-- Servicio 4 -->
            <div class="service-card fade-in-scroll">
                <img class="service-image" src="{{ asset('homeprincipal/imagen/servicios/ajedrez.png') }}" alt="Ajedrez">
                <div class="service-content">
                    <h3>Ajedrez</h3>
                    <p>Desarrolla pensamiento estratégico y concentración a través del juego ciencia.</p>
                    <a href="https://wa.me/59171324941?text=Quiero%20m%C3%A1s%20informaci%C3%B3n%20del%20curso%20de%20Ajedrez" class="btn-consultar" target="_blank">Consultar</a>
                </div>
            </div>
            <!-- Servicio 5 -->
            <div class="service-card fade-in-scroll">
                <img class="service-image" src="{{ asset('homeprincipal/imagen/servicios/cubo.png') }}" alt="Cubo Rubik">
                <div class="service-content">
                    <h3>Cubo Rubik</h3>
                    <p>Aprende técnicas para resolver el cubo de Rubik y mejora tu capacidad de resolución de problemas.</p>
                    <a href="https://wa.me/59171324941?text=Quiero%20m%C3%A1s%20informaci%C3%B3n%20del%20curso%20de%20Cubo%20Rubik" class="btn-consultar" target="_blank">Consultar</a>
                </div>
            </div>
            <!-- Servicio 6 -->
            <div class="service-card fade-in-scroll">
                <img class="service-image" src="{{ asset('homeprincipal/imagen/servicios/computacion.png') }}" alt="Computación">
                <div class="service-content">
                    <h3>Computación</h3>
                    <p>Domina las herramientas informáticas esenciales para el estudio y el trabajo.</p>
                    <a href="https://wa.me/59171324941?text=Quiero%20m%C3%A1s%20informaci%C3%B3n%20del%20curso%20de%20Computaci%C3%B3n" class="btn-consultar" target="_blank">Consultar</a>
                </div>
            </div>
            <!-- Servicio 7 -->
            <div class="service-card fade-in-scroll">
                <img class="service-image" src="{{ asset('homeprincipal/imagen/servicios/oratoria.png') }}" alt="Oratoria">
                <div class="service-content">
                    <h3>Oratoria</h3>
                    <p>Desarrolla habilidades de comunicación efectiva y habla en público con confianza.</p>
                    <a href="https://wa.me/59171324941?text=Quiero%20m%C3%A1s%20informaci%C3%B3n%20del%20curso%20de%20Oratoria" class="btn-consultar" target="_blank">Consultar</a>
                </div>
            </div>
            <!-- Servicio 8 -->
            <div class="service-card fade-in-scroll">
                <img class="service-image" src="{{ asset('homeprincipal/imagen/servicios/inicial.png') }}" alt="Inglés">
                <div class="service-content">
                    <h3>Inglés</h3>
                    <p>Aprende inglés con metodologías dinámicas y enfocadas en la comunicación real.</p>
                    <a href="https://wa.me/59171324941?text=Quiero%20m%C3%A1s%20informaci%C3%B3n%20del%20curso%20de%20Ingl%C3%A9s" class="btn-consultar" target="_blank">Consultar</a>
                </div>
            </div>
            <!-- Servicio 9 -->
            <div class="service-card fade-in-scroll">
                <img class="service-image" src="{{ asset('homeprincipal/imagen/servicios/lectura.png') }}" alt="Lectura y Escritura">
                <div class="service-content">
                    <h3>Lectura y Escritura</h3>
                    <p>Mejora tus habilidades de comprensión lectora y expresión escrita.</p>
                    <a href="https://wa.me/59171324941?text=Quiero%20m%C3%A1s%20informaci%C3%B3n%20del%20curso%20de%20Lectura%20y%20Escritura" class="btn-consultar" target="_blank">Consultar</a>
                </div>
            </div>
            <!-- Servicio 10 -->
            <div class="service-card fade-in-scroll">
                <img class="service-image" src="{{ asset('homeprincipal/imagen/servicios/grafico.png') }}" alt="Caligrafía">
                <div class="service-content">
                    <h3>Diseño Gráfico</h3>
                    <p>Comunica sin palabras, crea sin límites</p>
                    <a href="https://wa.me/59171324941?text=Quiero%20m%C3%A1s%20informaci%C3%B3n%20del%20curso%20de%20Caligraf%C3%ADa" class="btn-consultar" target="_blank">Consultar</a>
                </div>
            </div>
            <!-- Servicio 11 -->
            <div class="service-card fade-in-scroll">
                <img class="service-image" src="{{ asset('homeprincipal/imagen/servicios/supermemoria.png') }}" alt="Actividades Lúdicas">
                <div class="service-content">
                    <h3>Súper Memoria</h3>
                    <p> Técnicas PRO para memorizar al instante, retener más y aprender 10x más rápido. ¡Nunca más olvides NADA! ¡Garantizado! ✨ ¡Inscríbete AHORA! </p>
                    <a href="https://wa.me/59171324941?text=Quiero%20m%C3%A1s%20informaci%C3%B3n%20sobre%20las%20Actividades%20L%C3%BAdicas" class="btn-consultar" target="_blank">Consultar</a>
                </div>
            </div>
            <!-- Servicio 12 -->
            <div class="service-card fade-in-scroll">
                <img class="service-image" src="{{ asset('homeprincipal/imagen/servicios/creacioncontenido.png') }}" alt="Actividades Lúdicas">
                <div class="service-content">
                    <h3>Creacion de Contenido</h3>
                    <p> Domina las estrategias virales, atrapa audiencias y convierte likes en ventas. ¡Haz que tu contenido genere dinero YA! </p>
                    <a href="https://wa.me/59171324941?text=Quiero%20m%C3%A1s%20informaci%C3%B3n%20sobre%20las%20Actividades%20L%C3%BAdicas" class="btn-consultar" target="_blank">Consultar</a>
                </div>
            </div>
            <!-- Servicio 13 -->
            <div class="service-card fade-in-scroll">
                <img class="service-image" src="{{ asset('homeprincipal/imagen/servicios/ia.png') }}" alt="Actividades Lúdicas">
                <div class="service-content">
                    <h3>Inteligencia Artificial</h3>
                    <p> ¡Aprovecha la revolución IA y hazla trabajar para ti!  Automatiza negocios,  domina las herramientas que están cambiando el mundo.</p>
                    <a href="https://wa.me/59171324941?text=Quiero%20m%C3%A1s%20informaci%C3%B3n%20sobre%20las%20Actividades%20L%C3%BAdicas" class="btn-consultar" target="_blank">Consultar</a>
                </div>
            </div>

            <!-- Servicio 14 -->
            <div class="service-card fade-in-scroll">
                <img class="service-image" src="{{ asset('homeprincipal/imagen/servicios/impresion.png') }}" alt="Actividades Lúdicas">
                <div class="service-content">
                    <h3>Impresion 3D</h3>
                    <p>Imprime el futuro, capa a capa</p>
                    <a href="https://wa.me/59171324941?text=Quiero%20m%C3%A1s%20informaci%C3%B3n%20sobre%20las%20Actividades%20L%C3%BAdicas" class="btn-consultar" target="_blank">Consultar</a>
                </div>
            </div>
        </div>
    </div>
</section>

    {{-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  DESCARGA CALCULADORA %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}}
    <section class="services-section">
        <div class="container">
            <div class="section-header">
                <h2>Descargar <span class="highlight">emulador de Calculadora</span></h2>
                <p>Descarga directa del emulador de calculadora en formato .7z</p>
                <a href="{{ asset('Calculadora.zip') }}" class="btn-consultar" download="Calculadora.zip">Descargar emulador</a>
            </div>
        </div>
    </section>

    {{-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  Redes Sociales  %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}}
    <section id="redes" class="social-banner">
        <div class="container">
            <div class="social-content">
                <h2>¡Conéctate con nuestra comunidad educativa!</h2>
                <p class="subtitle">Contenido exclusivo, tips de estudio y novedades tecnológicas</p>
                <div class="social-links">
                    <a href="https://www.tiktok.com/@ife_educabol" target="_blank" class="social-link tiktok">
                        <div class="social-icon">
                            <i class="fab fa-tiktok"></i>
                        </div>
                        <span>TikTok</span>
                    </a>
                    <a href="https://www.facebook.com/ife.educabol" target="_blank" class="social-link facebook">
                        <div class="social-icon">
                            <i class="fab fa-facebook-f"></i>
                        </div>
                        <span>Facebook</span>
                    </a>
                    <a href="https://www.youtube.com/@ife_educabol" target="_blank" class="social-link youtube">
                        <div class="social-icon">
                            <i class="fab fa-youtube"></i>
                        </div>
                        <span>YouTube</span>
                    </a>
                    <a href="https://whatsapp.com/channel/0029VaAu3lwJJhzX5iSJBg44" target="_blank" class="social-link whatsapp">
                        <div class="social-icon">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <span>WhatsApp</span>
                    </a>
                    <a href="#" target="_blank" class="social-link instagram">
                        <div class="social-icon">
                            <i class="fab fa-instagram"></i>
                        </div>
                        <span>Instagram</span>
                    </a>
                    <a href="https://ife.bo" target="_blank" class="social-link website">
                        <div class="social-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <span>Web</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
    {{-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  recursos   %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}}
        <!-- ======================================================= -->
<!-- ============ SECCIÓN DE CLASE DE CORTESÍA ============ -->
<!-- ======================================================= -->
<section id="cortesia" class="cortesia-section">
    <div class="container">
        <div class="cortesia-box">
            <div class="cortesia-icon">
                <i class="fas fa-gift"></i>
            </div>
            <h2>¡Tu Clase de Cortesía te Espera!</h2>
            <p>
                Solo por visitar nuestra página, te regalamos una clase de prueba completamente <strong>gratuita</strong>.
                ¡Es válida para cualquiera de nuestros servicios!
            </p>

            <div class="cortesia-form">
                <label for="servicio-cortesia-select">1. Elige el servicio que te interesa:</label>
                <div class="select-wrapper">
                    <select id="servicio-cortesia-select" name="servicio">
                        <option value="Apoyo Escolar">Apoyo Escolar</option>
                        <option value="Preparación para Exámenes PSA CUB">Preparación para Exámenes PSA CUB</option>
                        <option value="Nivel Universitario">Apoyo Nivel Universitario</option>
                        <option value="Programación">Programación</option>
                        <option value="Robótica">Robótica</option>
                        <option value="Ajedrez">Ajedrez</option>
                        <option value="Cubo Rubik">Cubo Rubik</option>
                        <option value="Computación">Computación</option>
                        <option value="Oratoria">Oratoria</option>
                        <option value="Lectura y Escritura">Lectura y Escritura</option>
                        <option value="Caligrafía">Caligrafía</option>
                        <option value="Computación">Computación</option>
                        <option value="Cursos para Emprendedores">Cursos para Emprendedores</option>
                        <option value="Curso de Dactilografía">Curso de Dactilografía</option>
                        <option value="Creacion de aplicaciones">Creacion de aplicaciones</option>
                        <option value="Inteligencia Artificial">Inteligencia Artificial</option>
                        <option value="Curso de Geogebra">Curso de Geogebra</option>
                        <option value="Cubo Rubik">Cubo Rubik</option>
                        <option value="Contabilidad ">Contabilidad</option>
                        <option value="Curso Súper Memoria">Curso Súper Memoria</option>
                    </select>
                </div>

                <label>2. ¡Reserva tu clase ahora!</label>
                {{-- Este enlace será modificado por el JavaScript --}}
                <a id="btn-reservar-cortesia" href="#" target="_blank" class="btn-reservar">
                    <i class="fab fa-whatsapp"></i> Reservar por WhatsApp
                </a>
            </div>
        </div>
    </div>
</section>

    {{-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  recursos   %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}}
    <section id="recursos" class="recursos-section">
        <div class="container">
            <div class="section-header">
                <h2>Nuestros <span class="highlight">Recursos</span></h2>
                <p>Descarga guías de estudio, aplicaciones útiles y documentos para potenciar tu aprendizaje.</p>
            </div>
            
            <div class="recursos-grid">
                <!-- Ejemplo 1: Un archivo PDF -->
                <a href="https://formula.ife.bo" target="_blank" download class="recurso-card">
                    <div class="recurso-icon pdf">
                        <i class="fa-solid fa-superscript fa-beat"></i>
                    </div>
                    <div class="recurso-info">
                        <h3>Fórmulas</h3>
                        <p>Un completo manual con fórmulas </p>
                    </div>
                    <div class="recurso-descargar">
                        <span>Visitar</span>
                        <i class="fas fa-download"></i>
                    </div>
                </a>

                <!-- Ejemplo 2: Una Aplicación -->
                <a href="https://division.ife.bo" target="_blank" class="recurso-card">
                    <div class="recurso-icon app">
                        <i class="fas fa-divide fa-beat"></i>
                    </div>
                    <div class="recurso-info">
                        <h3>División 2.0</h3>
                        <p>Explica paso a paso la división</p>
                    </div>
                    <div class="recurso-descargar">
                        <span>Visitar</span>
                        <i class="fas fa-arrow-up-right-from-square"></i>
                    </div>
                </a>

                <!-- Ejemplo 3: Un Documento de Word -->
                <a href="https://play.google.com/store/apps/details?id=com.codeway.homework&hl=es_US0" target="_blank" download class="recurso-card">
                    <div class="recurso-icon doc">
                        <i class="fa-solid fa-mobile-screen-button fa-beat"></i>
                    </div>
                    <div class="recurso-info">
                        <h3>Nerd App</h3>
                        <p>Resuelve dudas al instante con IA</p>
                    </div>
                    <div class="recurso-descargar">
                        <span>Descargar</span>
                        <i class="fas fa-download"></i>
                    </div>
                </a>
                
                <!-- Ejemplo 4: Un Archivo ZIP -->
                <a href="https://calamar.ife.bo" target="_blank" download class="recurso-card">
                    <div class="recurso-icon zip">
                        <i class="fa-solid fa-trophy fa-beat"></i>
                    </div>
                    <div class="recurso-info">
                        <h3>Juego Calmar</h3>
                        <p>La Competencia Más Letal Acaba de Empezar</p>
                    </div>
                    <div class="recurso-descargar">
                        <span>Registrarse</span>
                        <i class="fas fa-download"></i>
                    </div>
                </a>
                <!-- Ejemplo 5: Un Archivo ZIP -->
                <a href="https://propuestos.ife.bo" target="_blank" download class="recurso-card">
                    <div class="recurso-icon zip">
                        <i class="fa-solid fa-plus-minus fa-beat"></i>
                    </div>
                    <div class="recurso-info">
                        <h3>Propuestos</h3>
                        <p>Entrena tu Mente: Ejercicios Aleatorios, Resultados Reales</p>
                    </div>
                    <div class="recurso-descargar">
                        <span>Generar</span>
                        <i class="fas fa-download"></i>
                    </div>
                </a>
                <!-- Ejemplo 6: Notas del primer trimestre -->
                <a href="https://notas.ife.bo" target="_blank" download class="recurso-card">
                    <div class="recurso-icon zip">
                        <i class="fa-solid fa-plus-minus fa-beat"></i>
                    </div>
                    <div class="recurso-info">
                        <h3>Notas 1</h3>
                        <p>No Adivines, Calcula: Tu Éxito Escolar en un Click</p>
                    </div>
                    <div class="recurso-descargar">
                        <span>Calcular</span>
                        <i class="fas fa-download"></i>
                    </div>
                </a>
                <!-- Ejemplo 6: Notas del primer trimestre -->
                <a href="https://division.ife.bo" target="_blank" download class="recurso-card">
                    <div class="recurso-icon zip">
                        <i class="fa-solid fa-divide fa-beat"></i>
                    </div>
                    <div class="recurso-info">
                        <h3>Division 1.0</h3>
                        <p>¡Divide sin Miedo! Guiado en Tiempo Real</p>
                    </div>
                    <div class="recurso-descargar">
                        <span>Dividir</span>
                        <i class="fas fa-download"></i>
                    </div>
                </a>
                
                <a href="https://es.educaplay.com" target="_blank" download class="recurso-card">
                    <div class="recurso-icon zip">
                        <i class="fa-solid fa-e fa-beat"></i>
                    </div>
                    <div class="recurso-info">
                        <h3>App Educaplay</h3>
                        <p>Transforma el Estudio en una Aventura Interactiva</p>
                    </div>
                    <div class="recurso-descargar">
                        <span>Visitar</span>
                        <i class="fas fa-download"></i>
                    </div>
                </a>
                {{-- wordwall --}}
                

                <a href="https://wordwall.net/" target="_blank" download class="recurso-card">
                    <div class="recurso-icon zip">
                        <i class="fa-solid fa-w fa-beat"></i>
                    </div>
                    <div class="recurso-info">
                        <h3>App Educaplay</h3>
                        <p>Transforma el Estudio en una Aventura Interactiva</p>
                    </div>
                    <div class="recurso-descargar">
                        <span>Visitar</span>
                        <i class="fas fa-download"></i>
                    </div>
                </a>

                <a href="https://yachay.ife.bo/" target="_blank" download class="recurso-card">
                    <div class="recurso-icon zip">
                        <i class="fa-solid fa-brain fa-beat"></i>
                    </div>
                    <div class="recurso-info">
                        <h3>Tabla con Anki</h3>
                        <p>La App Definitiva para Aprender Tablas (¡y no Olvidarlas!)</p>
                    </div>
                    <div class="recurso-descargar">
                        <span>Descargar</span>
                        <i class="fas fa-download"></i>
                    </div>
                </a>
            </div>
        </div>
    </section>


    {{-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  tik tok  %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}}

<!-- Nueva Sección de TikToks Virales -->
<section class="tiktok-section">
  <div class="container">
      <div class="section-header">
          <h2>Nuestros <span class="highlight">TikTok</span>con mas visualizaciones</h2>
          <p>Descubre nuestro contenido educativo más popular y divertido</p>
      </div>
      <div class="tiktok-grid">
          <!-- TikTok 1 -->
          <div class="tiktok-container fade-in-scroll">
              <blockquote class="tiktok-embed" cite="https://www.tiktok.com/@ife_educabol/video/7499610012852047159" data-video-id="7499610012852047159">
                  <section></section>
              </blockquote>
          </div>
      </div>
  </div>
</section>

    {{-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  cursos sobresalientes  %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}}

    {{-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  cursos sobresalientes  %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}}
    <section class="featured-courses-section">
      <div class="container">
          <div class="section-header">
              <h2>Cursos <span class="highlight">Destacados</span></h2>
              <p>Descubre nuestros cursos más populares y comienza tu aprendizaje hoy</p>
          </div>
          
          <div class="courses-slider-container">
              <button class="slider-arrow prev-arrow" id="prevCourse" aria-label="Curso anterior">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
              </button>
              
              <div class="courses-slider" id="coursesSlider">
                  <!-- Curso 1 -->
                  <div class="course-card">
                      <div class="course-image">
                          <img src="{{ asset('homeprincipal/imagen/tabla.png') }}" alt="Tabla de multiplicar">
                      </div>
                      <div class="course-content">
                          <h3>Tabla de multiplicar</h3>
                          <p>Aprende las tablas de multiplicar de forma divertida y efectiva con técnicas de memorización.</p>
                          <a href="#" class="btn btn-primary">Más información</a>
                      </div>
                  </div>
                  
                  <!-- Curso 2 -->
                  <div class="course-card">
                      <div class="course-image">
                          <img src="{{ asset('homeprincipal/imagen/programacion.png') }}" alt="Programación para niños">
                      </div>
                      <div class="course-content">
                          <h3>Programación para niños</h3>
                          <p>Introducción a la programación con bloques visuales para desarrollar pensamiento lógico.</p>
                          <a href="#" class="btn btn-primary">Más información</a>
                      </div>
                  </div>
                  
                  <!-- Curso 3 -->
                  <div class="course-card">
                      <div class="course-image">
                          <img src="{{ asset('homeprincipal/imagen/robotica.png') }}" alt="Robótica básica">
                      </div>
                      <div class="course-content">
                          <h3>Robótica básica</h3>
                          <p>Construye y programa robots sencillos mientras aprendes conceptos de electrónica y mecánica.</p>
                          <a href="#" class="btn btn-primary">Más información</a>
                      </div>
                  </div>
                  
                  <!-- Curso 6 -->
                  <div class="course-card">
                      <div class="course-image">
                          <img src="{{ asset('homeprincipal/imagen/cubo.png') }}" alt="Domina el cubo Rubik">
                      </div>
                      <div class="course-content">
                          <h3>Domina el cubo Rubik</h3>
                          <p>Aprende paso a paso cómo resolver el cubo de Rubik con métodos sencillos y efectivos.</p>
                          <a href="#" class="btn btn-primary">Más información</a>
                      </div>
                  </div>
              </div>
              
              <button class="slider-arrow next-arrow" id="nextCourse" aria-label="Curso siguiente">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
              </button>
          </div>
          
          <div class="slider-dots" id="coursesDots">
              <!-- Los dots se generarán con JavaScript -->
          </div>
      </div>
  </section>
    {{-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  EVENTOS %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}}
    <section class="events-section">
      <div class="container">
          <div class="section-header">
              <h2>Eventos y <span class="highlight">Actividades</span></h2>
              <p>Participa en nuestros eventos especiales y actividades recreativas</p>
          </div>
          
          <div class="events-filter">
              <button class="filter-btn active" data-filter="all">Todos</button>
              <button class="filter-btn" data-filter="torneos">Torneos</button>
              <button class="filter-btn" data-filter="talleres">Talleres</button>
              <button class="filter-btn" data-filter="competencias">Competencias</button>
          </div>
          
          <div class="events-grid">
              <!-- Evento 1 -->
              <div class="event-card fade-in-scroll" data-category="competencias">
                  <div class="event-image">
                      <img src="{{ asset('homeprincipal/imagen/calamar.jpg') }}" alt="Juegos del Calamar">
                      <div class="event-date">
                          <span class="day">15</span>
                          <span class="month">JUN</span>
                      </div>
                  </div>
                  <div class="event-content">
                      <div class="event-meta">
                          <span class="event-time">
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                              09:00 - 12:00
                          </span>
                          <span class="event-location">
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                              Por Definir
                          </span>
                      </div>
                      <h3>Juegos del Calamar: Cuarta Edición</h3>
                      <p>Participa en esta divertida competencia inspirada en juegos tradicionales. Premios para los ganadores.</p>
                      <a href="#" class="btn btn-secondary">Inscribirme</a>
                  </div>
              </div>
              
              <!-- Evento 6 -->
              <div class="event-card fade-in-scroll" data-category="competencias">
                  <div class="event-image">
                      <img src="{{ asset('homeprincipal/imagen/taller.png') }}" alt="Taller herramientas digitales">
                      <div class="event-date">
                          <span class="day">22</span>
                          <span class="month">MAR</span>
                      </div>
                  </div>
                  <div class="event-content">
                      <div class="event-meta">
                          <span class="event-time">
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                              09:00 - 11:30
                          </span>
                          <span class="event-location">
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                              Sede Central
                          </span>
                      </div>
                      <h3>Taller de Herramientas Digitales</h3>
                      <p>Taller desde cero de como aplicar herramientas digitales al aprendizaje</p>
                      <a href="#" class="btn btn-secondary">Inscribirme</a>
                  </div>
              </div>
          </div>
          
          <div class="events-cta">
              <a href="#" class="btn btn-primary">Ver todos los eventos</a>
          </div>
      </div>
  </section>
  
    {{-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  contactos %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}}
    <section class="contact-section" id="contacto">
      <div class="container">
          <div class="section-header">
              <h2>Ponte en <span class="highlight">Contacto</span></h2>
              <p>Estamos aquí para responder tus preguntas y ayudarte en lo que necesites</p>
          </div>
          
          <div class="contact-container">
              <div class="contact-info">
                  <h3>Información de Contacto</h3>
                  
                  <div class="info-item">
                      <div class="info-icon">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                      </div>
                      <div class="info-content">
                          <h4>Dirección</h4>
                          <p>Villa 1 de mayo calle 16 oeste #9</p>
                      </div>
                  </div>
                  
                  <div class="info-item">
                      <div class="info-icon">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                      </div>
                      <div class="info-content">
                          <h4>Teléfono</h4>
                          <p>71039910</p>
                      </div>
                  </div>
                  
                  <div class="info-item">
                      <div class="info-icon">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                      </div>
                      <div class="info-content">
                          <h4>Telefono</h4>
                          <p>71324941</p>
                      </div>
                  </div>
                  
                  <div class="info-item">
                      <div class="info-icon">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                      </div>
                      <div class="info-content">
                          <h4>Horario de Atención</h4>
                          <p>Lunes a Viernes: 07:30 - 18:30<br>Sábados: 07:30 - 18:30</p>
                      </div>
                  </div>
                  
                  <div class="social-links">
                      <a href="https://www.facebook.com/ife.educabol" class="social-link" aria-label="Facebook">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                      </a>
                      <a href="https://www.instagram.com/ife.educabol" class="social-link" aria-label="Instagram">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                      </a>
                      <a href="https://www.tiktok.com/@ife_educabol" class="social-link" aria-label="TikTok">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12a4 4 0 1 0 0 8 4 4 0 0 0 0-8z"/><path d="M15 8a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><path d="M15 2v20"/><path d="M9 16v6"/><path d="M9 10V4"/></svg>
                      </a>
                  </div>
              </div>
              
              <div class="contact-form-container">
                  <form id="contactForm" class="contact-form">
                      <div class="form-group">
                          <label for="name">Nombre completo</label>
                          <input type="text" id="name" name="name" required>
                      </div>
                      <div class="form-group">
                          <label for="phone">Teléfono</label>
                          <input type="tel" id="phone" name="phone">
                      </div>
                      <div class="form-group">
                          <label for="message">Mensaje</label>
                          <textarea id="message" name="message" rows="5" required></textarea>
                      </div>
                      
                      <button type="submit" class="btn btn-primary">Enviar mensaje</button>
                  </form>
              </div>
          </div>
          
          <div class="map-container fade-in-scroll">
              <iframe 
                  src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3823.978518546554!2d-63.1388413!3d-17.8020169!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x93f1e9948f88a431%3A0x448c1b5adb32a0f0!2site+(central)!5e0!3m2!1ses!2sbo!4v1696938145610!5m2!1ses!2sbo" 
                  width="100%" 
                  height="450" 
                  style="border:0;" 
                  allowfullscreen="" 
                  loading="lazy"
                  title="Ubicación de IFE">
              </iframe>
          </div>
      </div>
  </section>
    {{-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  footer %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}}
    <footer class="footer">
      <div class="container">
          <div class="footer-content">
              <div class="footer-column">
                  <div class="footer-logo">
                      <span class="ite-text">IFE</span>
                      <p>Instituto de Formación Educativa</p>
                  </div>
                  <p class="footer-description">
                      Ofrecemos clases de apoyo escolar, cursos de programación, robótica, ajedrez, 
                      cubo rubik, computación, oratoria, inglés, lectura, escritura y caligrafía.
                  </p>
              </div>
              
              <div class="footer-column">
                  <h3 class="footer-title">Enlaces Rápidos</h3>
                  <ul class="footer-links">
                      <li><a href="https://ife.bo/">Página web</a></li>
                      <li><a href="https://ife.bo/#cursos">Servicios</a></li>
                      <li><a href="https://ife.bo/#redes">Redes</a></li>
                      <li><a href="#cursos">Cursos</a></li>
                      <li><a href="#eventos">Eventos</a></li>
                      <li><a href="#contacto">Contacto</a></li>
                  </ul>
              </div>
              
              <div class="footer-column">
                  <h3 class="footer-title">Síguenos</h3>
                  <ul class="footer-social-links">
                      <li>
                          <a href="https://www.facebook.com/ife.educabol" target="_blank" rel="noopener noreferrer">
                              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                              Facebook
                          </a>
                      </li>
                      <li>
                          <a href="https://www.tiktok.com/@ife_educabol" target="_blank" rel="noopener noreferrer">
                              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12a4 4 0 1 0 0 8 4 4 0 0 0 0-8z"/><path d="M15 8a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><path d="M15 2v20"/><path d="M9 16v6"/><path d="M9 10V4"/></svg>
                              TikTok
                          </a>
                      </li>
                      <li>
                          <a href="https://www.instagram.com/ife.educabol/" target="_blank" rel="noopener noreferrer">
                              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                              Instagram
                          </a>
                      </li>
                      <li>
                          <a href="https://api.whatsapp.com/send/?phone=59171039910" target="_blank" rel="noopener noreferrer">
                              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                              WhatsApp
                          </a>
                      </li>
                  </ul>
              </div>
              
              <div class="footer-column">
                  <h3 class="footer-title">Contáctanos</h3>
                  <ul class="footer-contact-info">
                      <li>
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                          <span>Villa 1 de mayo calle 16 oeste #9, Santa Cruz de la Sierra</span>
                      </li>
                      <li>
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                          <span>75553338</span>
                      </li>
                      <li>
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                          <span>info@ife.bo</span>
                      </li>
                  </ul>
              </div>
          </div>
          
          <div class="footer-bottom">
              <div class="footer-legal">
                  <a href="#">Política de Privacidad</a>
                  <span class="separator">|</span>
                  <a href="#">Términos y Condiciones</a>
              </div>
              <div class="footer-copyright">
                  <p>&copy; 2024 IFE Todos los derechos reservados.|Desarrollado David Flores </p>
              </div>
          </div>
      </div>
  </footer>
    {{-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  SERVICIOS %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}}



    <script src="{{ asset('homeprincipal/js/hero.js') }}"></script>
    <script src="{{ asset('homeprincipal/js/redes.js') }}"></script>
    <script src="{{ asset('homeprincipal/js/services.js') }}"></script>
    <script src="{{ asset('homeprincipal/js/sobresaliente.js') }}"></script>
    <script src="{{ asset('homeprincipal/js/eventos.js') }}"></script>
    <script src="{{ asset('homeprincipal/js/contactos.js') }}"></script>
    <script src="{{ asset('homeprincipal/js/recursos.js') }}"></script>
    <script src="{{ asset('homeprincipal/js/cortesia.js') }}"></script>
    <script async src="https://www.tiktok.com/embed.js"></script>
   
</body>
</html>

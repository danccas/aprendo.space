    <div class="header-container">
      <a href="#" data-target="nav-mobile">
        <i class="material-icons">menu</i>
      </a>
    </div>
    <ul id="nav-mobile" class="sidenav hide">
        <li class="logo">
          <a id="logo-container" href="/" class="brand-logo">
            #Yo<br />Aprendo
          </a>
        </li>
        <li class="info">
          Bienvenid@, <b><?= Identify::g()->data['usuario']['nombres'] ?></b>
        </li>
        <li class="bold"><a href="/" class="waves-effect waves-teal">Inicio</a></li>
        <li class="bold"><a href="/cuestionarios" class="waves-effect waves-teal">Mis Cuestionarios</a></li>
        <li class="bold"><a href="/sesiones" class="waves-effect waves-teal">Mis Sesiones</a></li>
        <li class="bold"><a href="/identificacion?out" class="waves-effect waves-teal">Cerrar Sesión</a></li>
    </ul>

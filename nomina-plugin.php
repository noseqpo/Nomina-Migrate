<?php
/**
* Plugin Name: Nominas Escuelas/Circuitos
*
*
*/

// SEGURIDAD PHP
defined( 'ABSPATH' ) or die();

// Crea las tablas en la activacion

register_activation_hook( __FILE__, 'crear_tablas' );

function crear_tablas() {
    global $wpdb;

    $prefijo = $wpdb->prefix;

    $personas_t = $prefijo . "nom_personas";
    $escuelas_t = $prefijo . "nom_escuelas";
    $asistencias_t = $prefijo . "nom_asistencias";
    $posts_t = $prefijo . "nom_posts";

    $table_personas = "CREATE TABLE $personas_t (
        id_persona bigint(20) NOT NULL AUTO_INCREMENT,
        id_escuela int(3) NOT NULL,
        nombre varchar(60) NOT NULL,
        cuil varchar(10) NOT NULL UNIQUE,
        domicilio varchar(60),
        telefono varchar(60),
        mesa int(3),
        PRIMARY KEY  (id_persona)
    ) 
    ENGINE InnoDB DEFAULT CHARACTER SET utf8 COLLATE=utf8_general_ci
    ;";

    $table_asistencias = "CREATE TABLE $asistencias_t (
        id_asistencia bigint(20) NOT NULL AUTO_INCREMENT,
        id_persona int(3) NOT NULL,
        id_usuario int(3) NOT NULL,
        asistencia varchar(3) NOT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id_asistencia)      
    ) 
    ENGINE InnoDB DEFAULT CHARACTER SET utf8 COLLATE=utf8_general_ci
    ;";

    $tabla_escuelas = "CREATE TABLE $escuelas_t (
        id_escuela bigint(20) NOT NULL AUTO_INCREMENT,
        nombre varchar(60) NOT NULL,
        direccion varchar(60) NOT NULL,
        localidad varchar(60) NOT NULL,
        cto varchar(3),
        mesa_inicio int(4),
        mesa_final int(4),
        PRIMARY KEY (id_escuela)
    )
    ENGINE InnoDB DEFAULT CHARACTER SET utf8 COLLATE=utf8_general_ci
    ;";

    $tabla_posts = "CREATE TABLE $posts_t (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        id_escuela int(3) NOT NULL,
        id_post int(3) NOT NULL,
        PRIMARY KEY (id)
    )
    ENGINE InnoDB DEFAULT CHARACTER SET utf8 COLLATE=utf8_general_ci
    ;";

    $sql = $tabla_escuelas . $table_asistencias . $table_personas . $tabla_posts;

    // Include the upgrade functionality to create the table if it doesn't already exist
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}


/*
    Listar todas las personas de XX escuela en forma de tabla html
*/

function get_personas_table(){
    global $wpdb;
    $wpdb->show_errors();

    $prefijo = $wpdb->prefix;

    $personas_t = $prefijo . "nom_personas";
    $escuelas_t = $prefijo . "nom_escuelas";
    $asistencias_t = $prefijo . "nom_asistencias";
    $posts_t = $prefijo . "nom_posts";
    
    $id_post = get_the_ID();
    $escuela = $wpdb->get_var("SELECT id_escuela FROM $posts_t WHERE id_post = $id_post;");
    tabla_personas($escuela);
}
function tabla_personas($escuela){

    global $wpdb;
    $wpdb->show_errors();

    $prefijo = $wpdb->prefix;

    $personas_t = $prefijo . "nom_personas";
    $escuelas_t = $prefijo . "nom_escuelas";
    $asistencias_t = $prefijo . "nom_asistencias";
    $posts_t = $prefijo . "nom_posts";

    $post_id = get_the_ID( );

    if($escuela == "ALL"){
        $personas = $wpdb->get_results("SELECT * FROM $personas_t");
    }
    else{
        $personas = $wpdb->get_results("SELECT * FROM $personas_t WHERE id_escuela = $escuela");
    }

    $th = "";
    $td = "";

    if(is_user_logged_in(  )){
        $th = "<th>Asistencia</th>";
    }
    
	
	echo '<div class="contenedor-botones">';
    echo '<a href="/?page_id=306&valor=' . $post_id . '">
                Insertar persona
        </a>';
        echo '<br>';
    echo '<a class="tabla" href="/?page_id=5780&valor=' . $post_id . '">
        Insertar persona (TABLA)
        </a>';
	echo do_shortcode( "[ctp_print]" );
	echo '</div>';
	echo '<style>
				.contenedor-botones {
                display: flex; /* sets the container to use flexbox layout */
                align-items: center; /* centers the links and button vertically */
                justify-content: center; /* centers the links and button horizontally */
                }

                .contenedor-botones a, .ctp-button {
                flex: 1; /* sets the links and button to use equal widths */
                margin-right: 10px; /* adds space between the links and button */
                padding: 10px 20px; /* adds padding to the links and button */
                font-size: 16px; /* sets the font size of the links and button */
                color: white; /* sets the text color of the links and button to white */
                text-decoration: none; /* removes the underline from the links */
                background-color: lightblue; /* sets the background color of the links and button to light blue */
                border: none; /* removes the border from the links and button */
                border-radius: 5px; /* rounds the corners of the links and button */
                transition: all 0.2s ease-in-out; /* adds a smooth transition effect */
                }

                .ctp-button {
                background-color: #DCDCDC;
                color: black;/* changes the background color of the button to blue */
                }

                .contenedor-botones a:hover, .ctp-button:hover {
                background-color: white; /* sets the background color of the links and button to white on hover */
                color: lightblue; /* sets the text color of the links and button to light blue on hover */
                cursor: pointer; /* changes the cursor to a pointer on hover */
                }
                /* applies to screens smaller than 600px */

                @media (max-width: 600px) {
                .tabla {
                    display: none; /* hides the last child <a> element */
                }
                }


			</style>';
    
    if($personas != NULL){
        
        echo "<table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>CUIL</th>
                        <th>Domicilio</th>
                        <th>Telefono</th>
                        <th>Mesa</th>
                        ". $th . "
                    </tr>
                </thead>
            ";  // que asistencia sea un boton que cambie segun corresponda, que la fila entera cambie de color segun asitencia
        foreach ($personas as $persona) {
            //      envia id_persona, $asistencia, post_id para volver

            $asistencia = asistencia_id($persona->id_persona);

            $send = "Sin voto";
            if($asistencia == "aus"){
                $send = "Ya votó";
            }

            $asistencia_html = "<form action='" . esc_url( admin_url('admin-post.php')) . "' method='post'>
                <input type='hidden' name='persona' value='$persona->id_persona'>
                <input type='hidden' name='asistencia' value='" . $asistencia . "'>
                <input type='hidden' name='post_id' value='$post_id'>
                <input type='hidden' name='user_id' value=" . get_current_user_id() . ">
                <input type='hidden' name='action' value='insert_asistencia'>
                <input type='submit' value='" . $send ."' style='width: 75px; height: 25px;'>
            </form>";
            if(is_user_logged_in(  )){
                $td = "<td class='" . $asistencia . "'> ". $asistencia_html ." </td>";
            }
            echo "
                <tr>
                    <td> <a href='" . home_url() . "/?page_id=306&persona=$persona->id_persona&valor=$post_id'> $persona->nombre </a></td>
                    <td> $persona->cuil </td>
                    <td> $persona->domicilio </td>
                    <td> $persona->telefono </td>
                    <td> $persona->mesa </td>
                    ". $td ."
                </tr>";       
        }
        echo "</table>
            <style>
                table {
                    border-collapse: collapse;
                    width: 100%;
                }
                
                th, td {
                    padding: 8px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }

                tr:nth-child(even) {
                    background-color: #f2f2f2;
                }
                
                tr:nth-child(n) .aus {
                    background-color: #90ee90; /* Light green */
                }
                  
                tr:nth-child(n) .pre {
                    background-color: #f08080; /* Light red */
                }

                

                @media only screen and (max-width: 600px) {
                    table, thead, tbody, th, td, tr {
                        display: block;
                    }

                    thead tr {
                        display: none;
                    }

                    tr {
                        border: 1px solid #ddd;
                        margin-bottom: 5px;
                    }
                        
                    td {
                        border: none;
                        border-bottom: 1px solid #ddd;
                        position: relative;
                        padding-left: 50% !important;
                    }
                    td:before {
                        position: absolute;
                        top: 6px;
                        left: 6px;
                        width: 45%;
                        padding-right: 10px;
                        white-space: nowrap;
                    }

                    tr:nth-child(n) {
                        background-color: #ffffff;
                    }

                    td:nth-of-type(1):before { content: 'Nombre: '; }
                    td:nth-of-type(2):before { content: 'CUIL: '; }
                    td:nth-of-type(3):before { content: 'Domicilio: '; }
                    td:nth-of-type(4):before { content: 'Telefono:'; }
                    td:nth-of-type(5):before { content: 'Circuito y Mesa'; }
                    td:nth-of-type(6):before { content: 'Asistencia:'; }
                }
                
        </style>";  
    }
    //do_shortcode( "[formulario_personas]");
    if (!is_user_logged_in(  )) {
        acceso_denegado(get_the_ID(  ));
    }
}

add_shortcode("listado_personas", "get_personas_table");



/*
    FINAL
    Retorna la asistencia y procesa para la asistencia de cada persona
*/

    function asistencia_id($id_p){
        global $wpdb;
        $wpdb->show_errors();

        $prefijo = $wpdb->prefix;

        $personas_t = $prefijo . "nom_personas";
        $escuelas_t = $prefijo . "nom_escuelas";
        $asistencias_t = $prefijo . "nom_asistencias";
        $posts_t = $prefijo . "nom_posts";


        $asistencia = $wpdb->get_var("SELECT asistencia
            FROM $asistencias_t
            WHERE id_persona = $id_p
            ORDER BY timestamp DESC
            LIMIT 1;
        ");
        
        if($asistencia == "pre"){
            $asistencia = "aus";
        }
        else{
            $asistencia = "pre";
        }

        // enviamos a escribir $asistencia
        return $asistencia;
    }

    function insert_asistencia(){
        global $wpdb;
        $wpdb->show_errors();

        $prefijo = $wpdb->prefix;

        $personas_t = $prefijo . "nom_personas";
        $escuelas_t = $prefijo . "nom_escuelas";
        $asistencias_t = $prefijo . "nom_asistencias";
        $posts_t = $prefijo . "nom_posts";

        $wpdb->query(
            $wpdb->prepare("INSERT INTO $asistencias_t (id_persona, asistencia, id_usuario) 
                VALUES (%d, %s, %d)",
                $_POST['persona'], $_POST['asistencia'], $_POST['user_id']
            )
        );

        wp_redirect( get_permalink($_POST["post_id"]) );
    }
    add_action( "admin_post_insert_asistencia", "insert_asistencia");
    add_action( "admin_post_nopriv_insert_asistencia", "insert_asistencia");



/*


/*
    Agregar persona a la base de datos, table personas, form 

                                                                                 necesita una forma para ver que la query pasó
*/

function actions_persona(){

    global $wpdb;
    $wpdb->show_errors();

    $prefijo = $wpdb->prefix;

    $personas_t = $prefijo . "nom_personas";
    $escuelas_t = $prefijo . "nom_escuelas";
    $asistencias_t = $prefijo . "nom_asistencias";
    $posts_t = $prefijo . "nom_posts";

    switch ($_POST["submit"]) {
        case 'Enviar Datos':
            $wpdb->query(
                $wpdb->prepare("INSERT INTO $personas_t (nombre, cuil, domicilio, telefono, id_escuela, mesa) 
                    VALUES (%s, %s, %s, %d, %d, %d)",
                    $_POST['nombre'], $_POST['cuil'],$_POST['domicilio'], $_POST['telefono'], $_POST['id_escuela'], $_POST['mesa']
                )
            );
            break;
        case 'Actualizar':
            $wpdb->query(
                $wpdb->prepare("UPDATE $personas_t 
                                SET nombre = %s, cuil = %s, domicilio = %s, telefono = %d, id_escuela = %d, mesa = %d
                                WHERE id_persona = %d",
                    $_POST['nombre'], $_POST['cuil'], $_POST['domicilio'], $_POST['telefono'], $_POST['id_escuela'], $_POST['mesa'], $_POST['id']
                )
            );
            break;
        case 'Borrar':
            $wpdb->query(
                $wpdb->prepare("DELETE FROM $personas_t
                                WHERE id_persona = %d", $_POST['id'])
            );
            break;
        default:
            # code...
            break;
    }

    wp_redirect( get_permalink($_POST['caller']) );
    exit();

}


function form_personas(){

    $post_id = get_the_ID();// valor por defecto si no se recibe ningún parámetro
    
    if (isset($_GET['valor'])) {
        $post_id = sanitize_text_field($_GET['valor']); // actualiza el valor si se recibe un parámetro
    }
    echo "<h3>Ingresar persona:</h3>";
    
    if ( is_user_logged_in() ){

        global $wpdb;
        $wpdb->show_errors();

        $prefijo = $wpdb->prefix;

        $personas_t = $prefijo . "nom_personas";
        $escuelas_t = $prefijo . "nom_escuelas";
        $asistencias_t = $prefijo . "nom_asistencias";
        $posts_t = $prefijo . "nom_posts";

        $id_escuela = 0;
        $nom_escuela = "";
        $es_esc = "";
        $id = "";
        $nombre = "";
        $cuil = "";
        $dom = "";
        $tel = "";
        $mesa = "";
        $actualizar = 'Enviar Datos" style="background-color: #6699cc;"';
        $borrar = "";

        if($post_id != 306){
            $es_esc = "readonly";
            $id_escuela = $wpdb->get_var("SELECT id_escuela FROM $posts_t WHERE id_post = $post_id;");
            $nom_escuela = $wpdb->get_var("SELECT nombre FROM $escuelas_t WHERE id_escuela = $id_escuela;");
        }
        if (isset($_GET['persona'])) {
            $actualizar = 'Actualizar';
            $borrar ='<input type="submit" name="submit" value="Borrar" style="background-color: #ff6666;">';

            $id = $_GET['persona'];
            $persona = $wpdb->get_row("SELECT * FROM $personas_t WHERE id_persona = $id", ARRAY_A);
            $nombre = $persona['nombre'];
            $cuil = $persona['cuil'];
            $dom = $persona['domicilio'];
            $tel = $persona['telefono'];
            $mesa = $persona['mesa'];
        }
        echo '
        <div class="my-form">
            <form class="form-container" action="' . esc_url( admin_url('admin-post.php')) . '" method="post">
                <label for="nombre">Nombre completo:</label>
                <input type="text" id="nombre" name="nombre" value="' . $nombre . '" required>
                <label for="cuil">CUIL:</label>
                <input type="text" id="cuil" name="cuil" value="' . $cuil . '" required>
                
                <label for="domicilio">Domicilio (opcional):</label>
                <input type="text" id="domicilio" name="domicilio" value="' . $dom . '">
                
                <label for="telefono">Telefono (opcional):</label>
                <input type="number" id="telefono" name="telefono" value="' . $tel . '">
                
                <input type="hidden" id="id_escuela" name="id_escuela" value="' . $id_escuela . '" required ' . $es_esc . '>

                <label for="nom_escuela">Escuela:</label>
                <input type="text" id="nom_escuela" name="nom_escuela" value="' . $nom_escuela . '" required ' . $es_esc . '>
                
                <label for="mesa">Mesa:</label>
                <input type="number" id="mesa" name="mesa" value="' . $mesa . '">
                
                <input type="submit" name="submit" value="'. $actualizar . '"  style="background-color: #66cc66;">
                
                '. $borrar . '
                <input type="hidden" name="caller" value="' . $post_id . '">
                <input type="hidden" name="id" value="' . $id . '">
                <input type="hidden" name="action" value="actions_persona">
            </form>
            
        </div>
        <style>
            .my-form form {
            width: 400px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            border-radius: 10px;
            }
            .my-form label {
                display: block;
                margin-bottom: 10px;
                font-weight: bold;
            }
            
            .my-form input[type="text"],
            .my-form input[type="email"],
            .my-form input[type="number"],
            .my-form textarea {
                width: 100%;
                padding: 10px;
                border: 1px solid #ccc;
                border-radius: 5px;
                box-sizing: border-box;
            }
            
            .my-form input[type="submit"] {
                margin-top: 20px;
                padding: 10px 20px;
                background-color: #222;
                color: #fff;
                font-weight: bold;
                border: none;
                border-radius: 5px;
                cursor: pointer;
            }
            
            .my-form input[type="submit"]:hover {
                background-color: #333;
            }
              
        </style>
        <style>
            @media only screen and (max-width: 480px) {
                .my-form form {
                    width: 90%;
                }
            }
        </style>';
    }
    else{
        acceso_denegado($post_id);
    }
}

add_shortcode("formulario_personas", "form_personas");

add_action( "admin_post_actions_persona", "actions_persona");
add_action( "admin_post_nopriv_actions_persona", "actions_persona");



/*
    Funciones para mostar estadisticas del sistema


*/

function funciones_info(){

    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
      
        if (  $user_roles[0] != 'administrator' ) {
            echo '<p>Necesitas mas permisos para ver este contenido.</p>';
        }
        else{
            echo "<h2>Listado de Escuelas en la Base de Datos</h2>";
            echo do_shortcode( "[escuelas]" );

            echo '<h2>Insertar Personas en la Base de Datos</h2>
                    <p style="display: inline-block; background-color:red !important;">Solo Admin</p>';
            echo do_shortcode( "[masivo_personas]" );

            echo '<h2>Insertar Escuelas en la Base de Datos y crear Pagina correspondiente</h2>
                    <p style="display: inline-block; background-color:red !important;">Solo Admin</p>';
            echo do_shortcode( "[masivo_escuelas]" );

            echo "<h2>Estado de todas las personas y escuelas</h2>";
            echo do_shortcode( "[estados]" );

            echo "<h2>Crear Usuario: </h2>";
            echo do_shortcode( "[add_user]" );
        }
    }
    else{
        acceso_denegado(get_the_ID(  ));
    }
}

function generarListadoDesplegableConTablas($datos) {
    
    $html = '<ul class="desplegable mi-lista">';

    foreach ($datos as $key => $value) {
        $html .= '<li>';
        $html .= '<div class="item-titulo">Circuito: ' . $key . '</div>';
        $html .= '<div class="item-tabla" style="display:none">';
        $html .= '<table>';
        $html .= '<thead>
                            <tr>
                                <th>Escuela</th>
                                <th>Dirección</th>
                                <th>Mesas</th>
                                <th>Cantidad de Personas</th>
                            </tr>
                    </thead>';
        foreach ($value as $llave => $escuela) {

            $html .= '  <tbody>
                            <tr>
                                <td><a href="' . get_permalink( $escuela->id_post ) . '">' . $escuela->nombre . '</a></td>
                                <td>' . $escuela->direccion . '</td>
                                <td> De ' . $escuela->mesa_inicio . ' hasta ' . $escuela->mesa_final .'</td>
                                <td>' . $escuela->localidad . '</td>
                            </tr>
                        </tbody>';
        }
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</li>';
    }
    $html .= '</ul>';
  
    // Importar jQuery y crear el script para mostrar y ocultar las tablas
    $html .= '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
    $html .= '<script>';
    $html .= '$(document).ready(function() {';
    $html .= '$(".item-titulo").click(function() {';
    $html .= '$(this).next(".item-tabla").toggle();';
    $html .= '});';
    $html .= '});';
    $html .= '</script>';

    $html .= '<style>
                ul.mi-lista {
                    list-style-type: none;
                    padding: 0;
                    margin: 0;
                }
                
                ul.mi-lista li {
                    display: block;
                    margin: 10px 0;
                    border: 1px solid #ccc;
                    background-color: #f7f7f7;
                    padding: 10px;
                }
                  
                
                li .desplegable {
                    display: none;
                }
                
                li input:checked + .desplegable {
                    display: block;
                }
                
                li table {
                    border: 1px solid #ccc;
                    background-color: white;
                    margin-top: 10px;
                    padding: 10px;
                  }
                
            </style>';
  
    return $html;
  }

function get_escuela_table(){

    global $wpdb;
    $wpdb->show_errors();

    $prefijo = $wpdb->prefix;

    $personas_t = $prefijo . "nom_personas";
    $escuelas_t = $prefijo . "nom_escuelas";
    $asistencias_t = $prefijo . "nom_asistencias";
    $posts_t = $prefijo . "nom_posts";

    $query = "SELECT  $escuelas_t.cto, $escuelas_t.nombre, $escuelas_t.id_escuela, $posts_t.id_post, $escuelas_t.localidad, $escuelas_t.direccion,  $escuelas_t.mesa_inicio,  $escuelas_t.mesa_final 
    FROM $escuelas_t
    LEFT JOIN $personas_t ON $escuelas_t.id_escuela = $personas_t.id_escuela
    LEFT JOIN $posts_t ON $escuelas_t.id_escuela = $posts_t.id_escuela
    ORDER BY $escuelas_t.mesa_inicio";

    $results = $wpdb->get_results( $query );

    $grouped_results = array();

    foreach ( $results as $result ) {
        $cto = $result->cto;

        if ( ! array_key_exists( $cto, $grouped_results ) ) {
            $grouped_results[ $cto ] = array();
        }

        array_push( $grouped_results[ $cto ], $result );
    }

    //var_dump($grouped_results);
      
    echo generarListadoDesplegableConTablas( $grouped_results);

    if($grouped_results != NULL){
        
        echo "</table>
            <style>
            table {
                border-collapse: collapse;
                width: 100%;
            }
            
            th, td {
                padding: 8px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }

            tr:nth-child(even) {
                background-color: #f2f2f2;
            }
            
            tr:nth-child(n) .aus {
                background-color: #90ee90; /* Light green */
            }
              
            tr:nth-child(n) .pre {
                background-color: #f08080; /* Light red */
            }

            

            @media only screen and (max-width: 600px) {
                table, thead, tbody, th, td, tr {
                    display: block;
                }

                thead tr {
                    display: none;
                }

                tr {
                    border: 2px solid #ddd;
                    margin-bottom: 2px;
                }
                    
                td {
                    border: none;
                    border-bottom: 1px solid #ddd;
                    position: relative;
                    padding-left: 50% !important;
                }
                td:before {
                    position: absolute;
                    top: 6px;
                    left: 6px;
                    width: 45%;
                    padding-right: 10px;
                    white-space: nowrap;
                }

                tr:nth-child(n) {
                    background-color: #ffffff;
                }

                td:nth-of-type(1):before { content: 'Nombre: '; }
                td:nth-of-type(2):before { content: 'Direccion: '; }
                td:nth-of-type(3):before { content: 'Mesas: '; }
                td:nth-of-type(4):before { content: 'Personas:'; }        
            }
            </style>";
    }
}
add_shortcode( "escuela", "get_escuela_table" );

function get_escuelas_table(){

    global $wpdb;
    $wpdb->show_errors();

    $prefijo = $wpdb->prefix;

    $personas_t = $prefijo . "nom_personas";
    $escuelas_t = $prefijo . "nom_escuelas";
    $asistencias_t = $prefijo . "nom_asistencias";
    $posts_t = $prefijo . "nom_posts";

    $escuelas = $wpdb->get_results("SELECT $escuelas_t.nombre, $escuelas_t.id_escuela, $posts_t.id_post, count(id_persona) as total FROM $escuelas_t
        LEFT JOIN $personas_t ON $escuelas_t.id_escuela = $personas_t.id_escuela
        LEFT JOIN $posts_t ON $escuelas_t.id_escuela = $posts_t.id_escuela
        GROUP BY $escuelas_t.id_escuela, $posts_t.id_post");
    
    if($escuelas != NULL){
        echo "<div style='overflow:auto; max-height:200px;'>";
        echo "<table>
                <tr>
                    <th>ID</th>        
                    <th>Nombre</th> 
                    <th>ID en Post</th>
                    <th>LINK</th>
                    <th>Cantidad de Personas</th>
                </tr>
            ";  
        foreach ($escuelas as $escuela) {
            echo "
                <tr>
                    <td>" . $escuela->id_escuela . "</td>
                    <td>" . $escuela->nombre . "</td>
                    <td>" . $escuela->id_post . "</td>
                    <td> <a href='" . get_permalink( $escuela->id_post ) . "'>Listado</a></td>
                    <td>" . $escuela->total . "</td>
                </tr>
            ";       
        }
        echo "</table>
            <style>
                table {
                    border-collapse: collapse;
                    width: 100%;
                }
                
                th, td {
                    padding: 8px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }
                
                tr:nth-child(even) {
                    background-color: #f2f2f2;
                }
            </style>";
            echo "</div>";
    }
}
add_shortcode( "escuelas", "get_escuelas_table" );

function form_persona_masivo(){

    echo '
    <form class="form-container" action="' . esc_url( admin_url('admin-post.php')) . '" method="post">

        <label for="full">INSERT INTO PERSONAS (nombre, cuil, domicilio, telefono, escuela, mesa):</label>
        <input type="text" id="full" name="full" required>
        
        <input type="submit" value="Enviar datos">

        <input type="hidden" name="action" value="insert_persona_masivo">

    </form>';
}

function from_persona_table(){

    $post_id = get_the_ID();// valor por defecto si no se recibe ningún parámetro
    
    if (isset($_GET['valor'])) {
        $post_id = sanitize_text_field($_GET['valor']); // actualiza el valor si se recibe un parámetro
    }
    echo "<h3>Ingresar personas:</h3>";
    
    if ( is_user_logged_in() ){

        global $wpdb;
        $wpdb->show_errors();

        $prefijo = $wpdb->prefix;

        $personas_t = $prefijo . "nom_personas";
        $escuelas_t = $prefijo . "nom_escuelas";
        $asistencias_t = $prefijo . "nom_asistencias";
        $posts_t = $prefijo . "nom_posts";

        $id_escuela = $wpdb->get_var("SELECT id_escuela FROM $posts_t WHERE id_post = $post_id;");
        $nom_escuela = $wpdb->get_var("SELECT nombre FROM $escuelas_t WHERE id_escuela = $id_escuela;");
        
        echo '<div class="form-container">
                <form  action="' . esc_url( admin_url('admin-post.php')) . '" method="post">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre Completo</th>
                                <th>CUIL</th>
                                <th>Domicilio</th>
                                <th>Telefono</th>
                                <th>Escuela</th>
                                <th>Mesa</th>
                            </tr>
                        </thead>
                        <tbody id="entries">
                        <tr>
                            <td><input type="text" name="nombre[]"></td>
                            <td><input type="text" name="cuil[]" required></td>
                            <td><input type="text" name="domicilio[]"></td>
                            <td><input type="text" name="telefono[]"></td>
                            <td><input type="text" name="escuela[]" value="' . $nom_escuela . '" required readonly>
                                <input type="hidden" name="id_escuela[]" value="' . $id_escuela . '"></td>
                            <td><input type="text" name="mesa[]"></td>
                        </tr>
                        </tbody>
                    </table>
                    <input type="hidden" name="caller" value="' . $post_id . '">
                    <input type="hidden" name="action" value="insert_desde_tabla">
                   
                    
                    <div class="botones">
                        <button type="button" onclick="addEntry()">Añadir otra</button>
                        <button type="submit">Enviar</button>
                    </div>
                    
                </form>
            </div>
                <script>
                function addEntry() {
                    // Get the table body
                    const tableBody = document.getElementById("entries");

                    // Create a new row
                    const newRow = document.createElement("tr");


                    // Add two cells to the row
                    const nombreCell = document.createElement("td");
                    newRow.appendChild(nombreCell);

                    const cuilCell = document.createElement("td");
                    newRow.appendChild(cuilCell);

                    const domicilioCell = document.createElement("td");
                    newRow.appendChild(domicilioCell);

                    const telefonoCell = document.createElement("td");
                    newRow.appendChild(telefonoCell);

                    const escuelaCell = document.createElement("td");
                    newRow.appendChild(escuelaCell);

                    const mesaCell = document.createElement("td");
                    newRow.appendChild(mesaCell);


                    const nombreInput = document.createElement("input");
                    nombreInput.type = "text"
                    nombreInput.name = "nombre[]";
                    nombreInput.required = true;
                    nombreCell.appendChild(nombreInput);

                    const cuilInput = document.createElement("input");
                    cuilInput.type = "text";                                                                        
                    cuilInput.name = "cuil[]";
                    cuilInput.required = true;
                    cuilCell.appendChild(cuilInput);

                    const domicilioInput = document.createElement("input");
                    domicilioInput.type = "text";
                    domicilioInput.name = "domicilio[]";
                    domicilioCell.appendChild(domicilioInput);

                    const telefonoInput = document.createElement("input");
                    telefonoInput.type = "text";
                    telefonoInput.name = "telefono[]";
                    telefonoCell.appendChild(telefonoInput);

                    const escuelaInput = document.createElement("input");
                    escuelaInput.type = "text";
                    escuelaInput.value = "' . $nom_escuela . '";
                    escuelaInput.readonly = true;
                    escuelaInput.name = "escuela[]";
                    escuelaCell.appendChild(escuelaInput);

                    const id_escuelaInput = document.createElement("input");
                    id_escuelaInput.type = "hidden";
                    id_escuelaInput.value = ' . $id_escuela . ';
                    id_escuelaInput.name = "id_escuela[]";
                    escuelaCell.appendChild(id_escuelaInput);

                    const mesaInput = document.createElement("input");
                    mesaInput.type = "text";
                    mesaInput.name = "mesa[]";
                    mesaCell.appendChild(mesaInput);

                    // Add the new row to the table body
                    tableBody.appendChild(newRow);
                }
                </script>';

        echo '<style>
                .form-container {
                    width: 100%;
                    max-width: 800px;
                    margin: 0 auto;
                    font-family: Arial, sans-serif;
                    font-size: 14px;
                }

                table {
                    border-collapse: collapse;
                    width: 100%;
                    max-width: 800px;
                    margin: 20px auto;
                }

                thead th {
                    background-color: #222;
                    color: #fff;
                    font-weight: bold;
                    text-align: left;
                    padding: 10px;
                    border: 1px solid #ddd;
                }

                tbody td {
                padding: 10px;
                border: 1px solid #ddd;
                }

                input[type="text"] {
                width: 100%;
                box-sizing: border-box;
                border: none;
                padding: 5px;
                font-size: 14px;
                }

                .botones{
                    display:flex; justify-content:space-between;
                }

                button[type="submit"], button[type="button"] {
                display: block;
                margin: 0 auto;
                padding: 10px 20px;
                background-color: #4CAF50;
                color: #fff;
                border: none;
                border-radius: 3px;
                cursor: pointer;
                }

                button[type="submit"]:hover, button[type="button"]:hover {
                background-color: #3e8e41;
                }

                table {
                    border-collapse: collapse;
                    width: 100%;
                }
                
                th, td {
                    padding: 8px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }
                
                tr:nth-child(even) {
                    background-color: #f2f2f2;
                }

                button[type="submit"], button[type="button"]  {
                    margin-top: 20px;
                    padding: 10px 20px;
                    background-color: #222;
                    color: #fff;
                    font-weight: bold;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                }
                
                button[type="submit"]:hover, button[type="button"]:hover {
                    background-color: #333;
                }
        </style>';
    }
    else{
            acceso_denegado($post_id);
        }
    }

function insert_desde_tabla(){
    
    ob_start();
    global $wpdb;
    $wpdb->show_errors();

    $prefijo = $wpdb->prefix;

    $personas_t = $prefijo . "nom_personas";
    $escuelas_t = $prefijo . "nom_escuelas";
    $asistencias_t = $prefijo . "nom_asistencias";
    $posts_t = $prefijo . "nom_posts";
    
    $filas = array();
    for ($i = 0; $i < count($_POST['cuil']); $i++) {
        $fila = array(
            'nombre' => $_POST['nombre'][$i],
            'cuil' => $_POST['cuil'][$i],
            'domicilio' => $_POST['domicilio'][$i],
            'telefono' => $_POST['telefono'][$i],
            'id_escuela' => $_POST['id_escuela'][$i],
            'mesa' => $_POST['mesa'][$i]
        );
        $filas[] = $fila;
    }

    foreach ($filas as $fila) {
        $resultado = $wpdb->insert($personas_t, $fila);
        if ($resultado === false) {
            echo '<div style="background-color: #f44336; color: #fff; padding: 10px;">
                    Error: No se pudieron cargar los datos.
                </div>';
        }
    }

    wp_redirect( get_permalink($_POST['caller']) );
    exit();

    ob_end_flush();
}

function convertir_a_array_asociativo($datos) {
    $result = array();
    foreach ($datos as $key => $values) {
        $subarray = array();
        foreach ($values as $index => $value) {
            $subarray[$index] = $value;
        }
        $result[$key] = $subarray;
    }
    return $result;
}

add_shortcode( 'alta_personas_tabla', 'from_persona_table' );
add_action( "admin_post_insert_desde_tabla", "insert_desde_tabla");
add_action( "admin_post_nopriv_insert_desde_tabla", "insert_desde_tabla");

function insert_persona_masivo(){
    global $wpdb;
    $wpdb->show_errors(); 

    $prefijo = $wpdb->prefix;

    $personas_t = $prefijo . "nom_personas";
    $escuelas_t = $prefijo . "nom_escuelas";
    $asistencias_t = $prefijo . "nom_asistencias";
    $posts_t = $prefijo . "nom_posts";
    
    // Explode the CSV data into an array of values
    $data_values = explode(',', $_POST["full"]);

    // Loop through each value and insert it into the database

    for ($i = 0; $i < count($data_values); $i += 6) { // Insert 7 values per row
        $wpdb->insert($personas_t, array(
            'nombre' => $data_values[$i],
            'cuil' => $data_values[$i + 1],
            'domicilio' => $data_values[$i + 2],
            'telefono' => $data_values[$i + 3],
            'id_escuela' => $data_values[$i + 4],
            'mesa' => $data_values[$i + 5],
            )
        );
    }

    tabla_personas("ALL");

    echo '<a href="'. get_permalink(337) . '">
            <button>Volver</button>
        </a>';


    echo $wpdb->last_error; 

}
add_shortcode("masivo_personas", "form_persona_masivo" );
add_action( "admin_post_insert_persona_masivo", "insert_persona_masivo");
add_action( "admin_post_nopriv_insert_persona_masivo", "insert_persona_masivo");


function estados_personas_escuelas(){
    global $wpdb;
    $wpdb->show_errors();  

    $prefijo = $wpdb->prefix;

    $personas_t = $prefijo . "nom_personas";
    $escuelas_t = $prefijo . "nom_escuelas";
    $asistencias_t = $prefijo . "nom_asistencias";
    $posts_t = $prefijo . "nom_posts";
    
    $not_escuelas = $wpdb->get_results("SELECT * FROM $personas_t
                    WHERE id_escuela NOT IN (SELECT id_escuela FROM $escuelas_t)");   

    if($not_escuelas != NULL){
        echo "<table>
                <caption>Las siguientes personas NO pertenecen a una escuela en la Base de Datos</caption>
                <tr>
                    <th>Nombre</th>
                    <th>CUIL</th>
                    <th>Escuela Indicada</th>
                </tr>
            ";  
        foreach ($not_escuelas as $persona) {
            echo "
                <tr>
                    <td>" . $persona->nombre . "</td>
                    <td>" . $persona->cuil . "</td>
                    <td>" . $persona->id_escuela . "</td>
                </tr>
            ";       
        }
        echo "</table>"; // style no necesario porque utiliza los de la misma pagina 
    }
    else{
        echo "<p>Todas las personas se encuentran en alguna escuela.</p>";
    }

}
add_shortcode("estados", "estados_personas_escuelas");

function form_escuela_masivo(){

    echo '
    <form class="form-container" action="' . esc_url( admin_url('admin-post.php')) . '" method="post">

        <label for="full">INSERT INTO ESCUELAS + PAGE + :</label>
        <input type="text" id="full" name="full" required>
        
        <input type="submit" value="Enviar datos">

        <input type="hidden" name="action" value="insert_escuela_masivo">

    </form>';
}

function insert_escuela_masivo(){
    global $wpdb;
    $wpdb->show_errors();

    $prefijo = $wpdb->prefix;

    $personas_t = $prefijo . "nom_personas";
    $escuelas_t = $prefijo . "nom_escuelas";
    $asistencias_t = $prefijo . "nom_asistencias";
    $posts_t = $prefijo . "nom_posts";
    
    // Explode the CSV data into an array of values
    $data_values = explode(',', $_POST["full"]);
    $creadas = 0;

    // Loop through each value and insert it into the database

    for ($i = 0; $i < count($data_values); $i += 6) { 
        $wpdb->insert($escuelas_t, array(
                'nombre' => $data_values[$i],
                'direccion' => $data_values[$i + 1],
                'localidad' => $data_values[$i + 2],
                'cto' => $data_values[$i + 3],
                'mesa_inicio' => $data_values[$i + 4],
                'mesa_final' => $data_values[$i + 5],
            )
        );
        $last_insert_id = $wpdb->insert_id;
        $id_post = crear_la_pagina($data_values[$i], $data_values[$i+2]);
        
        $wpdb->insert($posts_t, array(
                'id_escuela' => $last_insert_id,
                'id_post' => $id_post,
            )
        );

        $creadas++;
    }

    echo "<p>" . $creadas . " paginas creadas.</p>";

    echo '<a href="'. get_permalink(337) . '">
            <button>Volver</button>
        </a>';


    echo $wpdb->last_error; 

}

function crear_la_pagina( $title, $direccion ) {
    $post = array(
       'post_type'    => 'post',
       'post_title'   =>  wp_strip_all_tags($title),
       'post_subtitle' =>  wp_strip_all_tags($direccion),
       'post_content' => "[encabezado_escuela][listado_personas]",
       'post_category' => array( 56 ),
       'post_status'  => 'publish',
       'comment_status' => 'closed',
    );
    $id_post = wp_insert_post( $post ); // devuel la id o 0 para error

    return $id_post;
}

add_shortcode("masivo_escuelas", "form_escuela_masivo" );
add_action( "admin_post_insert_escuela_masivo", "insert_escuela_masivo");
add_action( "admin_post_nopriv_insert_escuela_masivo", "insert_escuela_masivo");

function encabezado(){
    
}

add_shortcode( "encabezado_escuela", "encabezado" );


// MARK: Estadisticas

function stats(){

    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
      
        if (  $user_roles[0] != 'administrator' and $user_roles[0] != 'contributor' ) {
            echo '<p>Necesitas mas permisos para ver este contenido.</p>';
        }
        else{
            
            global $wpdb;
            $wpdb->show_errors();

            $prefijo = $wpdb->prefix;

            $personas_t = $prefijo . "nom_personas";
            $escuelas_t = $prefijo . "nom_escuelas";
            $asistencias_t = $prefijo . "nom_asistencias";
            $posts_t = $prefijo . "nom_posts";

            $total_esc = $wpdb->get_results("SELECT $escuelas_t.nombre, p.id_escuela, COUNT(DISTINCT p.id_persona) as total_personas, COUNT(DISTINCT a.id_persona) as total_asistencias
            FROM $personas_t p
            LEFT JOIN (
                SELECT id_persona, asistencia
                FROM $asistencias_t
                WHERE id_asistencia IN (
                    SELECT MAX(id_asistencia)
                    FROM $asistencias_t
                    GROUP BY id_persona
                ) AND asistencia = 'pre'
            ) as a
            ON p.id_persona = a.id_persona
            LEFT JOIN $escuelas_t
            ON p.id_escuela = $escuelas_t.id_escuela
            GROUP BY p.id_escuela");    

            $total_mesa = $wpdb->get_results("SELECT p.mesa, COUNT(DISTINCT p.id_persona) as total_personas, COUNT(DISTINCT a.id_persona) as total_asistencias
            FROM $personas_t p
            LEFT JOIN (
                SELECT id_persona, asistencia
                FROM $asistencias_t
                WHERE id_asistencia IN (
                    SELECT MAX(id_asistencia)
                    FROM $asistencias_t
                    GROUP BY id_persona
                ) AND asistencia = 'pre'
            ) as a
            ON p.id_persona = a.id_persona
            GROUP BY p.mesa");


            $personas_db = $wpdb->get_var("SELECT COUNT(id_persona) FROM $personas_t");
            $presentes_db = $wpdb->get_var("SELECT SUM(CASE WHEN asistencia = 'pre' THEN 1 ELSE 0 END) AS total_pre
                            FROM
                                (
                                SELECT
                                    id_persona,
                                    asistencia,
                                    timestamp,
                                    ROW_NUMBER() OVER (PARTITION BY id_persona ORDER BY timestamp DESC) AS rn
                                FROM
                                    $asistencias_t
                                ) t
                            WHERE
                                rn = 1");
                    
            echo do_shortcode( "[exportar]" );

            echo '<h2>Resultados Provisorios: </h2>
                <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                    <div style="background-color: #ADD8E6; border-radius: 50%; width: 150px; height: 150px; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 72px; font-weight: bold; color: white;">' . $presentes_db . '</span>
                    </div>
                    <div style="margin-top: 30px;">
                        <span style="font-size: 36px;">' . $personas_db . '</span>
                    </div>
                </div>
                ';
            
            echo "<table>
            <caption>Votos emitidos por escuela:</caption>
            <tr>
                <th>Escuela</th>
                <th>Total por escuela</th>
                <th>Cantidad</th>
            </tr>";
            foreach ($total_esc as $esc) {
                $per = round($esc->total_asistencias/$esc->total_personas*100,0);
                echo "<tr>
                        <td>" . $esc->nombre . ":</td>
                        <td>" . $esc->total_personas . "</td>
                        <td>" . do_shortcode( "[rprogress value=$per color='#008f39' text='$esc->total_asistencias votos']") . "</td>
                    </tr>";
            }
            echo "</table>";

            echo "<table>
                    <caption>Votos emitidos por mesa:</caption>
                    <tr>
                        <th>Mesa</th>
                        <th>Total por mesa</th>
                        <th>Cantidad</th>
                    </tr>";
            foreach ($total_mesa as $mesa ) {
                $per = round($mesa->total_asistencias/$mesa->total_personas*100,0);
                echo "<tr>
                        <td>Mesa " . $mesa->mesa . ":</th>
                        <td>" . $mesa->total_personas . "</td>
                        <td>" . do_shortcode( "[rprogress value=$per color='#008f39' text='$mesa->total_asistencias votos']") . "</td>
                    </tr>";
            }
            echo "</table>";
            
            echo "<style>
                table {
                    border-collapse: collapse;
                    width: 100%;
                    margin-top: 20px;
                }

                #contenedor-circulo {
                    
                }

                #circulo {
                    float:right;
                }
                
                caption {
                    text-align: center;
                    font-size: 1.5em;
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                
                th {
                    background-color: #f2f2f2;
                    text-align: left;
                    padding: 12px;
                    font-size: 1em;
                    font-weight: bold;
                }
                
                td {
                    text-align: left;
                    padding: 12px;
                    font-size: 0.9em;
                }
                
                tr:nth-child(odd) {
                    background-color: #f2f2f2;
                }    
            </style>";
        }

        
    }
    else{
        acceso_denegado(get_the_ID(  ));
    }
    
    

}

add_shortcode( "estadisticas", "stats" );


function acceso_denegado($post_id){
    echo "<p>Necesita entrar para ver este contenido.</p>";
    $args = array(
        'echo' => true,
        'redirect' => get_permalink( $post_id ),
        'form_id' => 'loginform',
        'label_username' => __( 'Usuario:' ),
        'label_password' => __( 'Contraseña:' ),
        'label_remember' => __( 'Recordar' ),
        'label_log_in' => __( 'Iniciar Sesion' ),
        'id_username' => 'user_login',
        'id_password' => 'user_pass',
        'id_remember' => 'rememberme',
        'id_submit' => 'wp-submit',
        'remember' => true,
        'value_username' => '',
        'value_remember' => false
    );
    wp_login_form( $args );

    echo "<style>
            #loginform {
                width: 300px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #ccc;
                }
            
            #loginform p {
            margin: 0;
            }
            
            .login-username, .login-password {
            margin-bottom: 10px;
            }
            
            .login-username label, .login-password label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            }
            
            .login-username input, .login-password input {
            width: 100%;
            padding: 5px;
            font-size: 16px;
            border: 1px solid #ccc;
            }
            
            .login-remember label {
            font-weight: normal;
            margin-left: 10px;
            }
            
            .login-submit {
            text-align: right;
            }
            
            .login-submit input {
            padding: 5px 10px;
            font-size: 16px;
            border: none;
            background-color: #0073aa;
            color: #fff;
            }
        </style>";
}


add_shortcode( "informacion", "funciones_info");

function exportando_table(){
    if (isset($_POST['submit'])) {
        global $wpdb;
        $wpdb->show_errors();

        $prefijo = $wpdb->prefix;

        $personas_t = $prefijo . "nom_personas";
        $escuelas_t = $prefijo . "nom_escuelas";
        $asistencias_t = $prefijo . "nom_asistencias";
        $posts_t = $prefijo . "nom_posts";

        $results = $wpdb->get_results("SELECT * FROM $asistencias_t JOIN $personas_t ON $asistencias_t.id_persona = $personas_t.id_persona");

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="asistencias_sie.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys((array) $results[0]));
        foreach ($results as $row) {
            fputcsv($output, (array) $row);
        }
        fclose($output);
        exit;
    }
}
add_action( "admin_post_exportando_table", "exportando_table");
add_action( "admin_post_nopriv_exportando_table", "exportando_table");

function exportar_form(){
    echo '
    <form class="form-container" action="' . esc_url( admin_url('admin-post.php')) . '" method="post">

        <input type="submit" id="submit" name="submit" value="Descargar Base de Datos">

        <input type="hidden" name="action" value="exportando_table">

    </form>
    <style>
        .form-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            background-color: #f2f2f2;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            margin: 20px auto;
            max-width: 500px;
        }
        
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        input[type="submit"]:hover {
            background-color: #3e8e41;
        }
        
        input[type="hidden"] {
            display: none;
        }
        
    </style>';
}

add_shortcode( 'exportar', 'exportar_form' );

function add_user_shortcode() {
    if ( ! is_user_logged_in() ) {
        return 'You must be logged in to add a user.';
    }
    if ( ! current_user_can( 'create_users' ) ) {
        return 'You do not have the necessary permissions to add a user.';
    }

    if(isset($_POST['cantidad'])) {

        for ($i = 1; $i <= $_POST['cantidad']; $i++) {
            $username = "Operador$i";
            $password = "de0$i";
            $email = "operador$i@sie.com";
            wp_create_user($username, $password, $email);
        }
        
        echo '<table>';
        echo '<tr>';
        echo '<th>Username</th>';
        echo '<th>Email</th>';
        echo '<th>Display Name</th>';
        echo '</tr>';

        $users = get_users();
        foreach ($users as $user) {
            echo '<tr>';
            echo '<td>' . $user->user_login . '</td>';
            echo '<td>' . $user->user_email . '</td>';
            echo '<td>' . $user->display_name . '</td>';
            echo '</tr>';
        }

        echo '</table>';

    }
    else {
        $form = '<form method="post">
            <label for="cantidad">Cantidad de usuarios a crear:</label>
            <input type="number" id="cantidad" name="cantidad" required>
            <input type="submit" name="submit" value="Añadir ususarios">
        </form>';
        return $form;
    }
}
add_shortcode( 'add_user', 'add_user_shortcode' );

?>
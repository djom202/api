<?php

class FrontController {

    static function main() {
        //Incluimos las clases necesarias para que el diseño funcione:
        require 'Libs/Config.php';                                  //Configuraciones con llamadas desde variables
        require 'Libs/SPDO.php';                                    //PDO con singleton para crear una unica instancia
        require 'Libs/ControllerBase.php';                          //Clase abstracta donde extienden todos los Controller
        require 'Libs/ModelBase.php';                               //Clase abstracta donde extienden todos los modelos
        require 'Libs/View.php';                                    //Mini motor de plantillas
        require 'Libs/Display.php';                                 //Contenido de la pagina a cargar
        require 'Libs/Partial.php';                                 //Carga Parcial de paginas o de otros origenes
        require 'Libs/QueryFactory.php';                            //Constructor manual de consultas SQL
        //require 'Libs/Time.php';                                    //Libreria con funciones basicas de tiempo
        //require 'Libs/ws/nusoap.php';                               //Libreria que consume los web services
        //require 'Libs/WebServiceBase.php';                          //Clase abstracta donde extienden los web services
        //require 'Libs/XMLBase.php';                                 //Clase abstracta donde extienden los modelos xml
        require 'Libs/Link.php';                                    //Clase para controlas las url
        require 'Libs/Log.php';                                    //Clase para controlas los logs
        require 'config.php';                                       //Archivo con configuraciones.
        //require 'Libs/recaptcha/recaptchalib.php';                  //Recaptcha desarrollado por Google
        //require 'Libs/Mobile_Detect.php';                           //Clase para reconocer Dispositivos Mobiles
        require 'Libs/Device.php';                                  //Manejador de la clase Mobile_Detect
        //require 'Libs/Images.php';                                  //Manejador de Imagenes PHP
        require 'Libs/RowObject.php';                               //Objeto para manejar filas devueltas por la bd
        require 'Libs/HTTP.php';                                    //Mensajes y otras cosas HTTP
        
        //Formamos el nombre del Controlador o en su defecto, tomamos que es el IndexController
        if (!empty($_GET['controller'])) {
            $controllerName = ucwords($_GET['controller']) . 'Controller';
        } else {
            $controllerName = 'IndexController';
        }

        //Lo mismo sucede con las acciones, si no hay accion, tomamos index como accion
        if (!empty($_GET['action'])) {
            $actionName = $_GET['action'];
        } else {
            $actionName = 'index';
        }

        //Definimos variables globales de controlador / accion
        define('ControllerName', $controllerName);
        define('ActionName', $actionName);

        //Declarando los arrays que se pasaran al controlador
        $delete = array();
        $post = array();
        $get = array();
        $put = array();

        //Obtener las variables pasadas por GET del request_uri
        if (!empty($_GET['frontGetVars'])) {
            $get = explode('/', $_GET['frontGetVars']);
        } else {
            $tmp = array();
            preg_match_all("/(?P<key>[a-zA-Z_][a-zA-Z0-9_-]*)=(?P<value>[^&]*)/", $_SERVER['REQUEST_URI'], $tmp);

            for ($i = 0; $i < count($tmp['key']); $i++) {
                $get[$tmp['key'][$i]] = $tmp['value'][$i];
            }
        }

        //Obtener las variables por metodos
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method == 'PUT' || $method == 'DELETE' || $method == 'POST') {
            $params = array();

            parse_str(file_get_contents('php://input'), $params);
            $GLOBALS["_{$method}"] = $params;
            $_REQUEST = $params + $_REQUEST;

            if ($method == 'PUT') {
                $put = $params;
            } elseif ($method == 'DELETE') {
                $delete = $params;
            } elseif ($method == 'POST') {
                $post = $params;
            }
        } else if ($method == 'GET') {
            $_GET = $get;
        }

        $controllerPath = $config->get('controllersFolder') . $controllerName . '.php';

        //Incluimos el fichero que contiene nuestra clase controladora solicitada
        if (is_file($controllerPath)) {
            require $controllerPath;
        } else {
            //Si el controlador anterior no existe, llamamos al controlador de Error
            $controllerName = 'ErrorController';
            $controllerPath = $config->get('controllersFolder') . $controllerName . '.php';
            $actionName = 'index';

            require $controllerPath;
        }

        //Si no existe la clase que buscamos y su acción, llamamos al controlador de Error
        if (!method_exists($controllerName, $actionName)) {
            $controllerName = 'ErrorController';
            $controllerPath = $config->get('controllersFolder') . $controllerName . '.php';
            $actionName = 'index';

            require $controllerPath;
        }

        //Creamos el Controlador
        $controller = new $controllerName($post, $get, $_FILES, $put, $delete);

        //Finalmente creamos una instancia del controlador y llamamos a la accion
        $controller->_Always();
        $controller->$actionName();
    }

}
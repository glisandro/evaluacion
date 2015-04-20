<?php
require_once __DIR__.'/../vendor/autoload.php';
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = require __DIR__.'/../src/app.php';
 
$app->get('/login', function(Request $request) use ($app) {
    
    $token = $app['security']->getToken();
    //echo '<pre>';
    //var_dump($token);
    if (null !== $token) {
        return $app->redirect('/admin/dashboard');
    } 

    return $app['twig']->render('login.html', array(
        'error'         => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ));
})->bind('login');


//$app->match('/admin/logout', function () use ($app) {
//    $app['session']->clear();
//    return $app->redirect('/login');
//})->bind('logout');


$app->get('/', function () use ($app) {
 
    return $app->redirect('/admin/dashboard');

})->bind('inicio');

$app->get('/admin/dashboard', function() use ($app) {

    $sql = "SELECT * FROM usuarios order by 1 desc limit 5";
    $usuarios = $app['db']->fetchAll($sql);

    $sql = "SELECT * FROM ventas where venta_fecha >= DATE_FORMAT(now(), '%Y-%m-%d') order by 1 desc limit 5";
    $ventas = $app['db']->fetchAll($sql);

    return $app['twig']->render('index.html', array(
        'usuarios' => $usuarios,
        'ventas'   => $ventas
    ));
})->bind('dashboard');

$app->get('/admin/usuario/lista', function() use ($app) {
    
    $sql = "SELECT * FROM usuarios order by 1 desc";
    $usuarios = $app['db']->fetchAll($sql);

    return $app['twig']->render('usuarios.html', array(
        'list' => $usuarios,
    ));
})->bind('usuario_lista');
 
$app->match('/admin/usuario/{id}/{accion}', function ($id, $accion) use ($app) {
 //echo 'aa';exit;
    $error = '';
    $sql = "SELECT * FROM usuarios WHERE id_usuario = " . (int) $id;
    $usuario = $app['db']->fetchAssoc($sql);


    if (!$usuario) {
        $usuario = array(
            'user'=>'',
            'pass'=>'',
            'id_usuario'=>'',
            'nombre'=>'',
            'apellido'=>'',
            'documento'=>'',
            'sexo'=>'',
            'puesto'=>'',
            'imagen'=>'',

        );
    } 
    if ("POST" === $app['request']->getMethod()) {
        if(!$error = validar()) {
            $sql = (strtolower($accion) == 'nuevo') ? 'INSERT INTO' : 'UPDATE';
            $sql .= ' usuarios SET nombre = ?, apellido = ?, documento = ?, sexo = ?, puesto = ?, imagen = ?';
            $sql .= (strtolower($accion) == 'nuevo') ? ', user = ?, pass= ?' : ' WHERE id_usuario = ' . $id;
            //echo $sql;exit;
            try {
                
                $usuario = array($app['request']->get('nombre'),
                                $app['request']->get('apellido'),
                                $app['request']->get('documento'),
                                $app['request']->get('sexo'),
                                $app['request']->get('puesto'),
                                getImagen($app['request']->get('imagen'))
                );
                
                if($accion == 'nuevo') {
                    $usuario[] = $app['request']->get('user');
                    $usuario[] = sha1($app['request']->get('pass'));
                }

                $result = $app['db']->executeUpdate($sql, $usuario);
                return $app->redirect('/admin/usuario/lista');
                
            } catch (Exception $e) {
                //solo para desarrollo
                $error = $e->getMessage();    
            }
        }
    }

    return $app['twig']->render('usuario.html', array(
        'usuario' => $usuario,
        'accion' => $accion,
        'error' => $error
    ));

})->bind('usuario_modificar_nuevo');

$app->get('/admin/usuario/nuevo', function () use ($app) {
 
    return $app->redirect('/admin/usuario/0/nuevo');

})->bind('usuario_nuevo');

$app->match('/admin/venta/alta', function () use ($app) {
    $error = '';
    
    if ("POST" === $app['request']->getMethod()) {
        // comprobar si existe el usuario
        $sql = 'INSERT INTO ventas SET venta_monto = ?, venta_fecha = ?, id_usuario = ?';

        $result = $app['db']->executeUpdate($sql, array($app['request']->get('venta_monto'),$app['request']->get('venta_fecha'),$app['request']->get('id_usuario')));
        if($result > 0) {
            //mensaje en session ok
            return $app->redirect('/admin/venta/alta');
        }
    }
    return $app['twig']->render('venta.html', array(
        'error' => $error
    ));

})->bind('venta_alta');

$app->get('/admin/webservice/{_format}/{rango}/{total_ventas}', function($rango, $total_ventas) use ($app) {

    $sql = "SELECT 
                v.*, u.nombre, u.apellido FROM ventas as v 
            INNER JOIN 
                usuarios u ON (v.id_usuario = u.id_usuario) 
            WHERE 
                u.puesto = '" . strtoupper($rango) . "'
                and " . $total_ventas . " = (SELECT COUNT(*) FROM ventas as v2 WHERE v2.id_usuario = v.id_usuario)";
                
    $ventas = $app['db']->fetchAll($sql);
    
    $format = $app['request']->getRequestFormat();
         
    return new Response($app['serializer']->serialize($ventas, 'xml'), 200, array(
        "Content-Type" => 'text/xml'
    ));
})
->value('_format','xml')->value('rango','')->value('total_ventas',0)
->assert("_format", "xml|json") // tambien se podia usar el formato json para ajax)
->bind('webservice');

$app->get('/admin/venta/reporte/{rango}/{total_ventas}', function($rango, $total_ventas) use ($app) {
       
    $ventas = simplexml_load_file('http://'.$_SERVER['HTTP_HOST'].':'.$_SERVER['SERVER_PORT'].'/admin/webservice/xml/' . (string)$app['request']->get('rango') . '/' . (int)$app['request']->get('total_ventas'));
 
    return $app['twig']->render('reporte.html', array(
        'list' => $ventas->item,
        'rango' => (string)$app['request']->get('rango'), 
        'total_ventas' => $app['request']->get('total_ventas')
    ));
})
->value('rango',0)->value('total_ventas','')->bind('reporte');

function validar() {
    //Validaciones de campos obligatorios, etc
}

function getimagen($imagen) {
    
    if (is_uploaded_file($_FILES["file"]["tmp_name"])) {
        # verificamos el formato de la imagen
        if ($_FILES["file"]["type"]=="image/jpeg" || $_FILES["file"]["type"]=="image/pjpeg" || $_FILES["file"]["type"]=="image/gif" || $_FILES["file"]["type"]=="image/bmp" || $_FILES["file"]["type"]=="image/png")
        {
            $path = __DIR__ . "/imagenes/usuarios/";
            $imagen = basename( $_FILES['file']['name']); 
            move_uploaded_file($_FILES['file']['tmp_name'], $path . $imagen); 

        }
    }
   
    return $imagen;
}

$app->run();
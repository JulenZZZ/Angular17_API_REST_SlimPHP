<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Selective\BasePath\BasePathMiddleware;
use Slim\Factory\AppFactory;



require_once __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

//Headers config
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Allow: GET, POST, OPTIONS, PUT, DELETE");
$method = $_SERVER['REQUEST_METHOD'];
if($method == "OPTIONS") {
    die();
}


// Parse json, form data and xml
$app->addBodyParsingMiddleware();

$app->addRoutingMiddleware();

$app->add(new BasePathMiddleware($app));

$app->addErrorMiddleware(true, true, true);


$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");
    return $response;
})->setName('root');

$app->get('/probando', function (Request $request, Response $response) {
    $db= new mysqli('localhost', 'root','','curso_angular17');
    $response->getBody()->write("Probando la SLIM KLK");
    var_dump($db);
    return $response;
});
/*
$app->post('/products', function (Request $request,Response $response,$app) {
    $json = $app->request->post('json');    
    $data = json_decode($json, true);
    
    var_dump($json);
    var_dump($data);
    
});
*/
//LIST ALL PRODUCTS
$app->get('/products', function (Request $request, Response $response) {
    $db= new mysqli('localhost', 'root','','curso_angular17');

    $sql = "SELECT *FROM productos ORDER BY id DESC;";

    $result = $db->query($sql);

    if ($result) {
        $productos = array();

        // Obtén cada fila como un array asociativo
        while ($producto = $result->fetch_assoc()) {
            // Agrega la fila al array de resultados
            $productos[] = $producto;
        }
        $response->getBody()->write(json_encode($productos));
        
    } else {
        // Maneja errores en la consulta
        $response->getBody()->write("Error en la consulta: " . $db->error);
        $response = $response->withStatus(500); // Código de estado 500 para error interno del servidor
    }

    // Cierra la conexión y la declaración
    $db->close();
    
    return $response;
});
//RETURN A SINGLE PRODUCT
$app->get('/products/{id}', function (Request $request, Response $response, array $args) {

    $db= new mysqli('localhost', 'root','','curso_angular17');
    $id = $args['id'];
    $sql = "SELECT *FROM productos WHERE id=".$id;

    $query = $db->query($sql);
    
    $result = array(
        "status" => "error",
        "code"=> 404,
        "message"=> "Producto no encontrado"
    );
    if ($query->num_rows == 1) {
        $producto = $query->fetch_assoc();
        $result = array(
            "status" => "success",
            "code"=> 200,
            "data"=> $producto
        );
    }

    $response->getBody()->write(json_encode($result));


    $db->close();
    return $response;
});

//DELETE PRODUCT
$app->get('/delete-product/{id}', function (Request $request, Response $response, array $args) {
   
    $db= new mysqli('localhost', 'root','','curso_angular17');
    $id = $args['id'];
    $sql = "DELETE FROM productos WHERE id=".$id;

    $query = $db->query($sql);

    if ($query){
    
        $result = array(
            "status" => "success",
            "code"=> 200,
            "message"=> "Producto eliminado"
        );
    } else {
        $result = array(
            "status" => "error",
            "code"=> 404,
            "message"=> "Producto no eliminado"
        );
    }   

    $response->getBody()->write(json_encode($result));
    
    $db->close();
    return $response;
});


//UPDATE PRODUCT
$app->post('/update-product/{id}', function (Request $request, Response $response,array $args): Response {

    $productoId = $args['id'];

    // Decodifica el JSON del cuerpo de la solicitud
    // Obtén el cuerpo de la solicitud como JSON
    $data = $request->getParsedBody();
    $html = var_export($data, true);
    $response->getBody()->write($html);
    $data2 = implode('',$data);
    $decode = json_decode($data2, true);

    if(!isset($decode['nombre'])) {
        $decode['nombre']= null;
    }

    if(!isset($decode['description'])) {
        $decode['description']= null;
    }

    if(!isset($decode['precio'])) {
        $decode['precio']= null;
    }

    if(!isset($decode['imagen'])) {
        $decode['imagen']= null;
    }

    $nombre = $decode['nombre'];
    $description = $decode['description'];
    $precio = $decode['precio'];
    $imagen = $decode['imagen'];

    // Crea una conexión a la base de datos
    $db = new mysqli('localhost', 'root', '', 'curso_angular17');

    try {
        // Verifica si la conexión fue exitosa
        if ($db->connect_error) {
            throw new Exception("Error en la conexión a la base de datos: " . $db->connect_error);
        }

        // Actualiza el producto con la información proporcionada
        $sql = "UPDATE productos SET nombre = ?, description = ?, precio = ?";

        if (isset($imagen)) {
            $sql .= " ,imagen = ?";
        }

        $sql .= " WHERE id = ?";
        $stmt = $db->prepare($sql);
        var_dump($sql);
    
        // Si se proporciona la imagen, vincula el parámetro adicional
        if (isset($imagen)) {
            $stmt->bind_param("sssdi", $nombre, $description, $precio, $imagen,$productoId);
            var_dump($stmt);
        } else {
            $stmt->bind_param("ssdi", $nombre, $description, $precio,$productoId);
            var_dump($stmt);
        }

        // Ejecuta la declaración
        $stmt->execute();

        // Cierra la conexión
        $stmt->close();
    } catch (Exception $e) {
        // Maneja la excepción
        $response->getBody()->write("Error: " . $e->getMessage());
    } finally {
        // Siempre cierra la conexión
        $db->close();
    }

    return $response;

});

//UPLOAD AN IMAGE TO A PRODUCT
$app->post('/upload-image', function (Request $request, Response $response): Response {

    $result = array(
        "status" => "error",
        "code"=> 404,
        "message"=> "El archivo no ha podido subirse"
    );

    if(isset($_FILES['uploads'])){
        $piramideUploader = new PiramideUploader();

        $upload = $piramideUploader->upload('image','uploads','uploads',array('image/jpeg','image/png','image/gif'));
        $file = $piramideUploader->getInfoFile();
        $file_name = $file['complete_name'];

        if(isset($upload) && $upload['uploaded'] == false){
            $result = array(
                "status" => "error",
                "code"=> 404,
                "message"=> "El archivo no ha podido subirse"
            );
            
        }else{
        $result = array(
            "status" => "success",
            "code"=> 200,
            "message"=> "El archivo se ha subido",
            "file_name"=> $file_name
            );
        }
    }
    $response->getBody()->write(json_encode($result));
    
    return $response;
   
});

//SAVE PRODUCTS
$app->post('/products', function (Request $request, Response $response): Response {

    // Obtén el cuerpo de la solicitud como JSON
    $data = $request->getParsedBody();
    $html = var_export($data, true);
    $response->getBody()->write($html);
    $data2 = implode('',$data);
    $decode = json_decode($data2, true);

    // Verifica si la decodificación fue exitosa
    if ($data === null) {
        // Manejar error de decodificación JSON
        $response->getBody()->write("Error al decodificar JSON");
        return $response->withStatus(400); // Código de estado 400 para solicitud incorrecta
    }

    //if values are empty will be equal null
    if(!isset($decode['nombre'])) {
        $decode['nombre']= null;
    }

    if(!isset($decode['description'])) {
        $decode['description']= null;
    }

    if(!isset($decode['precio'])) {
        $decode['precio']= null;
    }

    if(!isset($decode['imagen'])) {
        $decode['imagen']= null;
    }
    
    // Extrae los datos necesarios
    $nombre = $decode['nombre'];
    $description = $decode['description'];
    $precio = $decode['precio'];
    $imagen = $decode['imagen'];

    // Crea una conexión a la base de datos
    $db = new mysqli('localhost', 'root', '', 'curso_angular17');

    // Verifica si hay errores en la conexión
    if ($db->connect_error) {
        die("Error de conexión: " . $db->connect_error);
    }
   
    // Prepara la consulta SQL
    $sql = "INSERT INTO productos (nombre, description, precio, imagen) VALUES (?, ?, ?, ?)";

    // Prepara la declaración
    $stmt = $db->prepare($sql);

    // Vincula los parámetros
    $stmt->bind_param("ssss", $nombre, $description, $precio, $imagen);
    var_dump($sql);
    // Ejecuta la consulta
    $result = $stmt->execute();

    // Verifica si la inserción fue exitosa
    if ($result) {
        $response->getBody()->write("Inserción exitosa");
    } else {
        $response->getBody()->write("Error en la inserción: " . $stmt->error);
    }

    // Cierra la conexión y la declaración
    $stmt->close();
    $db->close();

    return $response;
   
});

try {
    $app->run();     
} catch (Exception $e) {    
  // We display a error message
  die( json_encode(array("status" => "failed", "message" => "This action is not allowed"))); 
}
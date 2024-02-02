<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addErrorMiddleware(true, true, true);

$app->setBasePath("/../angular17_backend/public/index.php"); // /myapp/api is the api folder (http://domain/myapp/api)


$app->get('/pruebas', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!");
    return $response;
});

try {
    $app->run();     
} catch (Exception $e) {    
  // We display a error message
  die( json_encode(array("status" => "failed", "message" => "This action is not allowed"))); 
}
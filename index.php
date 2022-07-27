<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';
require 'src/Factory.php';

$app = new \Slim\App;

session_start();

$app->post('/login', function (Request $request, Response $response, $args) {
    $customerId = $request->getParam("customerId");

    try {
        $result = Factory::login($customerId);
        $_SESSION['customer'] = $result;
        return $response
            ->withStatus(200)
            ->withHeader("Content-Type", 'application/json')
            ->withJson(['status' => true]);
    } catch (PDOException $e) {
        return $response
        ->withStatus(400)
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => $e->getMessage(),
                "code"  => $e->getCode()
            )
        ));
    } catch (Exception $e) {
        return $response
        ->withStatus($e->getCode())
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => $e->getMessage(),
                "code"  => $e->getCode()
            )
        ));
    }
});

$app->get('/logout', function (Request $request, Response $response, $args) {
    session_unset();
    session_destroy();
    return $response
            ->withStatus(200)
            ->withHeader("Content-Type", 'application/json')
            ->withJson(['status' => true]);
});

$app->get('/products', function (Request $request, Response $response, $args) {
    try {
        $result = Factory::getProducts();
        return $response
            ->withStatus(200)
            ->withHeader("Content-Type", 'application/json')
            ->withJson($result);
    } catch (PDOException $e) {
        return $response
        ->withStatus(400)
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => $e->getMessage(),
                "code"  => $e->getCode()
            )
        ));
    } catch (Exception $e) {
        return $response
        ->withStatus($e->getCode())
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => $e->getMessage(),
                "code"  => $e->getCode()
            )
        ));
    }
});

$app->delete('/order/delete/{productId}', function (Request $request, Response $response, $args) {
    if (!isset($_SESSION['customer'])) {
        return $response
        ->withStatus(401)
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => 'Kullanıcı girişi yapmalısınız.'
            )
        ));
    }

    $customerId = $_SESSION['customer']->id;
    $productId = $args['productId'];

    if (!is_numeric($productId)) {
        return $response
        ->withStatus(400)
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => 'productId sayısal bir değer olmalıdır.'
            )
        ));
    }

    try {
        $result = Factory::deleteOrderProduct($productId, $customerId);
        return $response
            ->withStatus(200)
            ->withHeader("Content-Type", 'application/json')
            ->withJson(['status' => 'success']);
    } catch (PDOException $e) {
        return $response
        ->withStatus(400)
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => $e->getMessage(),
                "code"  => $e->getCode()
            )
        ));
    } catch (Exception $e) {
        return $response
        ->withStatus($e->getCode())
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => $e->getMessage(),
                "code"  => $e->getCode()
            )
        ));
    }
});

$app->post('/order/add', function (Request $request, Response $response, $args) {
    if (!isset($_SESSION['customer'])) {
        return $response
        ->withStatus(401)
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => 'Kullanıcı girişi yapmalısınız.'
            )
        ));
    }

    $customerId = $_SESSION['customer']->id;
    $productId = $request->getParam("productId");
    $quantity = $request->getParam("quantity");
    
    if (!is_numeric($productId)) {
        return $response
        ->withStatus(400)
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => 'productId sayısal bir değer olmalıdır.'
            )
        ));
    } else if (!is_numeric($quantity)) {
        return $response
        ->withStatus(400)
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => 'quantity sayısal bir değer olmalıdır.'
            )
        ));
    }

    try {
        $result = Factory::addProductToOrder($productId, $quantity, $customerId);
        return $response
            ->withStatus(200)
            ->withHeader("Content-Type", 'application/json')
            ->withJson($result);
    } catch (PDOException $e) {
        return $response
        ->withStatus(400)
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => $e->getMessage(),
                "code"  => $e->getCode()
            )
        ));
    } catch (Exception $e) {
        return $response
        ->withStatus($e->getCode())
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => $e->getMessage(),
                "code"  => $e->getCode()
            )
        ));
    }
});

$app->get('/order/list', function (Request $request, Response $response, $args) {
    if (!isset($_SESSION['customer'])) {
        return $response
        ->withStatus(401)
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => 'Kullanıcı girişi yapmalısınız.'
            )
        ));
    }
    
    $customerId = $_SESSION['customer']->id;

    try {
        $result = Factory::getOrderList($customerId);
        return $response
            ->withStatus(200)
            ->withHeader("Content-Type", 'application/json')
            ->withJson($result);
    } catch (PDOException $e) {
        return $response
        ->withStatus(400)
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => $e->getMessage(),
                "code"  => $e->getCode()
            )
        ));
    } catch (Exception $e) {
        return $response
        ->withStatus($e->getCode())
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => $e->getMessage(),
                "code"  => $e->getCode()
            )
        ));
    }
});

$app->post('/order/complete', function (Request $request, Response $response, $args) {
    if (!isset($_SESSION['customer'])) {
        return $response
        ->withStatus(401)
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => 'Kullanıcı girişi yapmalısınız.'
            )
        ));
    }
    
    $customerId = $_SESSION['customer']->id;
    $orderId = $request->getParam("orderId");

    if (!is_numeric($orderId)) {
        return $response
        ->withStatus(400)
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => 'orderId sayısal bir değer olmalıdır.'
            )
        ));
    }

    try {
        $result = Factory::completeOrder($orderId, $customerId);
        return $response
            ->withStatus(200)
            ->withHeader("Content-Type", 'application/json')
            ->withJson(['status' => $result]);
    } catch (PDOException $e) {
        return $response
        ->withStatus(400)
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => $e->getMessage(),
                "code"  => $e->getCode()
            )
        ));
    } catch (Exception $e) {
        return $response
        ->withStatus($e->getCode())
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => $e->getMessage(),
                "code"  => $e->getCode()
            )
        ));
    }
});

$app->get('/order/discounts/{orderId}', function (Request $request, Response $response, $args) {
    if (!isset($_SESSION['customer'])) {
        return $response
        ->withStatus(401)
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => 'Kullanıcı girişi yapmalısınız.'
            )
        ));
    }
    
    $customerId = $_SESSION['customer']->id;
    $orderId = $args['orderId'];

    if (!is_numeric($orderId)) {
        return $response
        ->withStatus(400)
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => 'orderId sayısal bir değer olmalıdır.'
            )
        ));
    }

    try {
        $result = Factory::getOrderDiscounts($orderId, $customerId);
        return $response
            ->withStatus(200)
            ->withHeader("Content-Type", 'application/json')
            ->withJson($result);
    } catch (PDOException $e) {
        return $response
        ->withStatus(400)
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => $e->getMessage(),
                "code"  => $e->getCode()
            )
        ));
    } catch (Exception $e) {
        return $response
        ->withStatus($e->getCode())
        ->withHeader("Content-Type", 'application/json')
        ->withJson(array(
            "error" => array(
                "text"  => $e->getMessage(),
                "code"  => $e->getCode()
            )
        ));
    }
});

// Run app
$app->run();
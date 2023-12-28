<?php
require_once('bootstrap.php');

use Src\Controller\APIEndpointHandler;

switch ($_SERVER["REQUEST_METHOD"]) {
    case 'POST':

        // Check if the "CONTENT_TYPE" header is set
        if (!isset($_SERVER["CONTENT_TYPE"])) {
            http_response_code(400); // Bad Request
            header('Content-Type: application/json');
            echo json_encode(array("resp_code" => "601", "message" => "No Content-Type header provided."));
            exit;
        }

        // Check if Content-Type header is set to json
        if ($_SERVER["CONTENT_TYPE"] !== "application/json" || strpos($_SERVER["CONTENT_TYPE"], "application/json") === false) {
            http_response_code(415); // Unsupported Media Type
            header('Content-Type: application/json');
            echo json_encode(array("resp_code" => "602", "message" => "Only JSON-encoded requests are allowed."));
            exit;
        }

        // Check for Basic Authorization header
        $authorizationHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';

        if (empty($authorizationHeader)) {
            http_response_code(400); // Bad Request
            header('Content-Type: application/json');
            echo json_encode(array("resp_code" => "603", "message" => "No Authorization header information."));
            exit;
        }

        if (strpos($authorizationHeader, 'Basic') === false) {
            http_response_code(401); // Unauthorized
            header('Content-Type: application/json');
            header('WWW-Authenticate: Basic realm="API Authentication"');
            echo json_encode(array("resp_code" => "604", "message" => "Basic Authorization required."));
            exit;
        }

        // Extract username and password from the Basic Authorization header 12248579 TIAV0QMHC
        $authCredentials = explode(':', base64_decode(substr($authorizationHeader, 6)), 2);
        $authUsername = isset($authCredentials[0]) ? $authCredentials[0] : '';
        $authPassword = isset($authCredentials[1]) ? $authCredentials[1] : '';

        if (empty($authUsername) || empty($authPassword)) {
            http_response_code(401); // Unauthorized
            header('Content-Type: application/json');
            header('WWW-Authenticate: Basic realm="API Authentication"');
            echo json_encode(array("resp_code" => "605", "message" => "Authorization credentials required."));
            exit;
        }

        $expose = new APIEndpointHandler();
        $user = $expose->authenticateAccess($authUsername, $authPassword);

        if (!$user) {
            http_response_code(401); // Unauthorized
            header('Content-Type: application/json');
            header('WWW-Authenticate: Basic realm="API Authentication"');
            echo json_encode(array("resp_code" => "606", "message" => "Invalid authorization credentials."));
            exit;
        }

        $_POST = json_decode(file_get_contents("php://input"), true);
        $response = array();

        $request_uri = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
        $endpoint = '/' . basename($request_uri);

        switch ($endpoint) {
            case '/getForms':
                $response = $expose->getForms($_POST, $user);
                http_response_code(200);
                break;

            case '/purchaseForm':
                $response = $expose->purchaseForm($_POST, $user);
                http_response_code(201);
                break;

            case '/sendPurchaseInfo':
                $response = $expose->sendPurchaseInfo($_POST, $user);
                http_response_code(201);
                break;

            case '/purchaseStatus':
                $response = $expose->purchaseStatus($_POST, $user);
                http_response_code(200);
                break;

            case '/purchaseInfo':
                $response = $expose->purchaseInfo($_POST, $user);
                http_response_code(200);
                break;

            default:
                $response = array("resp_code" => "607", "message" => "Invalid endpoint: $endpoint");
                http_response_code(404);
                break;
        }

        header("Content-Type: application/json");
        echo json_encode($response);
        break;

    case 'GET':
        $request_uri = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
        $endpoint = '/' . basename($request_uri);

        if ($endpoint === '/docs') {
            header("Content-Type: text/html");
            require_once 'api-doc.html';
        } else {
            header("Content-Type: text/html");
            require_once 'advert.html';
        }
        break;

    default:
        header("HTTP/1.1 403 Forbidden");
        break;
}
exit();

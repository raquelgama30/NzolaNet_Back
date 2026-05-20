<?php

$authUser   = $authMiddleware->handle();
$controller = new BlockController($blockService);
$input      = json_decode(file_get_contents("php://input"), true) ?? [];
$action     = $_GET['action'] ?? null;

switch ($action) {

    // POST ?route=block&action=bloquear
    case 'bloquear':
        $bloqueadoId = $input['bloqueado_id'] ?? '';
        
        if (empty($bloqueadoId)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "bloqueado_id é obrigatório"]);
            exit();
        }

        $controller->block($authUser->id, $bloqueadoId);
        break;

    // DELETE ?route=block&action=desbloquear
    case 'desbloquear':
        $bloqueadoId = $input['bloqueado_id'] ?? $_GET['bloqueado_id'] ?? '';
        
        if (empty($bloqueadoId)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "bloqueado_id é obrigatório"]);
            exit();
        }

        $controller->unblock($authUser->id, $bloqueadoId);
        break;

    // GET ?route=block&action=verificar&bloqueado_id=XXX
    case 'verificar':
        $bloqueadoId = $_GET['bloqueado_id'] ?? '';
        
        if (empty($bloqueadoId)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "bloqueado_id é obrigatório"]);
            exit();
        }

        $controller->isBlocked($authUser->id, $bloqueadoId);
        break;

    // GET ?route=block&action=listarBloqueados
    case 'listarBloqueados':
        $controller->getBlocked($authUser->id);
        break;

    default:
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Ação inválida"]);
        break;
}
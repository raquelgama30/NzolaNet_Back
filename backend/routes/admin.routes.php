<?php

$adminUser  = $adminMiddleware->handle();
$input      = json_decode(file_get_contents("php://input"), true) ?? [];
$action     = $_GET['action'] ?? null;

$userController    = new UserController($userService);
$commentController = new CommentController($commentService);
$reportController  = new ReportController($reportService);

switch ($action) {

    // GET ?route=admin&action=listarUtilizadores
    case 'listarUtilizadores':
        $userController->listarTodos();
        break;

    // PUT ?route=admin&action=ativarUtilizador
    case 'ativarUtilizador':
        $id = $input['id'] ?? '';
        $userController->ativar($id);
        break;

    // PUT ?route=admin&action=desativarUtilizador
    case 'desativarUtilizador':
        $id = $input['id'] ?? '';
        $userController->desativar($id);
        break;

    // DELETE ?route=admin&action=eliminarComentario
    case 'eliminarComentario':
        $id = $input['id'] ?? '';
        $commentController->deleteByAdmin($id);
        break;

    // GET ?route=admin&action=listarReports&page=1&limit=20
    case 'listarReports':
        $page  = (int) ($_GET['page']  ?? 1);
        $limit = (int) ($_GET['limit'] ?? 20);
        $reportController->getAll($page, $limit);
        break;

    // PUT ?route=admin&action=resolverReport
    case 'resolverReport':
        $id = $input['id'] ?? '';
        $reportController->resolve($id, $adminUser->id);
        break;

    // PUT ?route=admin&action=ignorarReport
    case 'ignorarReport':
        $id = $input['id'] ?? '';
        $reportController->ignore($id);
        break;

    default:
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Ação inválida"]);
        break;
}
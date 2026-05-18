<?php

$input  = json_decode(file_get_contents("php://input"), true) ?? [];
$action = $_GET['action'] ?? null;

switch ($action) {

    // POST ?route=report&action=create  (utilizador autenticado)
    case 'create':

        $authUser   = $authMiddleware->handle();
        $controller = new ReportController($reportService);

        // Validar referencia_tipo
        $referenciaTipo = $input['referencia_tipo'] ?? '';

        if (!in_array($referenciaTipo, ['post', 'comment', 'user'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "referencia_tipo inválido. Usa: post, comment ou user"
            ]);
            exit();
        }

        // Validar motivo
        $motivosValidos = [
            'spam',
            'ofensivo',
            'inapropriado',
            'desinformacao',
            'violencia',
            'outro'
        ];

        $motivo = $input['motivo'] ?? '';

        if (!in_array($motivo, $motivosValidos)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "motivo inválido. Usa: spam, ofensivo, inapropriado, desinformacao, violencia ou outro"
            ]);
            exit();
        }

        $dto = new ReportDTO(
            id:              "",
            reporter_id:     $authUser->id,
            referencia_id:   $input['referencia_id']  ?? '',
            referencia_tipo: $referenciaTipo,
            motivo:          $motivo,
            descricao:       $input['descricao']      ?? null,
            status:          "pendente",
            resolvido_por:   null,
            criado_em:       "",
            resolvido_em:    null
        );

        $controller->create($authUser->id, $dto);
        break;

    // GET ?route=report&action=listarReports&page=1&limit=20  (admin)
    case 'listarReports':

        $adminMiddleware->handle();
        $controller = new ReportController($reportService);
        $page       = (int) ($_GET['page']  ?? 1);
        $limit      = (int) ($_GET['limit'] ?? 20);
        $controller->getAll($page, $limit);
        break;

    // PUT ?route=report&action=resolver  (admin)
    case 'resolver':

        $adminUser  = $adminMiddleware->handle();
        $controller = new ReportController($reportService);
        $reportId   = $input['id'] ?? '';
        $controller->resolve($reportId, $adminUser->id);
        break;

    // PUT ?route=report&action=ignorar  (admin)
    case 'ignorar':

        $adminMiddleware->handle();
        $controller = new ReportController($reportService);
        $reportId   = $input['id'] ?? '';
        $controller->ignore($reportId);
        break;

    default:
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Ação inválida"
        ]);
        break;
}
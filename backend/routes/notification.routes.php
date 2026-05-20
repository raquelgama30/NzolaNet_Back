<?php

$authUser   = $authMiddleware->handle();
$controller = new NotificationController($notificationService);
$input      = json_decode(file_get_contents("php://input"), true) ?? [];
$action     = $_GET['action'] ?? null;

switch ($action) {

    // GET ?route=notification&action=getAll&page=1&limit=20
    case 'getAll':
        $page  = (int) ($_GET['page']  ?? 1);
        $limit = (int) ($_GET['limit'] ?? 20);
        $controller->getByUser($authUser->id, $page, $limit);
        break;

    // PUT ?route=notification&action=markAsRead
    case 'markAsRead':
        $id = $input['id'] ?? $_GET['id'] ?? '';
        $controller->markAsRead($id);
        break;

    // PUT ?route=notification&action=markAllAsRead
    case 'markAllAsRead':
        $controller->markAllAsRead($authUser->id);
        break;
    case 'stream':
    // SSE requer headers específicos
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no'); // Desativar buffering do Nginx/Apache
    
    // Desativar output buffering
    if (ob_get_level()) ob_end_clean();
    ob_implicit_flush(true);
    
    // Loop de envio
    $lastCount = -1;
    $maxLoops = 360; // 30 minutos (360 * 5 segundos)
    $loop = 0;
    
    while ($loop < $maxLoops) {
        // Verificar se o cliente ainda está conectado
        if (connection_aborted()) break;
        
        $count = $notificationService->countUnread($authUser->id);
        
        // Só enviar se mudou (evita spam)
        if ($count !== $lastCount) {
            echo "event: notification\n";
            echo "data: " . json_encode([
                "unread" => $count,
                "timestamp" => time()
            ]) . "\n\n";
            $lastCount = $count;
        }
        
        // Heartbeat a cada 30 segundos para manter conexão
        if ($loop % 6 === 0) {
            echo ":heartbeat\n\n";
        }
        
        $loop++;
        sleep(5); // Verificar a cada 5 segundos
    }
    
    exit();

    default:
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Ação inválida"]);
        break;
}

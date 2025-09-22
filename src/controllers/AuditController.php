<?php
require '../../database.php';
require '../../src/models/Audit.php';

$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Filtros
    $q = trim($_GET['q'] ?? '');
    $action = trim($_GET['action'] ?? '');
    $entity = trim($_GET['entity_type'] ?? '');
    $userId = trim($_GET['user_id'] ?? '');
    $dateFrom = trim($_GET['date_from'] ?? '');
    $dateTo = trim($_GET['date_to'] ?? '');

    $page = max(1, intval($_GET['page'] ?? 1));
    $pageSize = min(100, max(10, intval($_GET['page_size'] ?? 25)));
    $offset = ($page - 1) * $pageSize;

    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = "(user_name LIKE :q OR JSON_SEARCH(details, 'all', :qjson) IS NOT NULL)";
        $params[':q'] = "%$q%";
        $params[':qjson'] = "%$q%";
    }
    if ($action !== '') { $where[] = 'action = :action'; $params[':action'] = $action; }
    if ($entity !== '') { $where[] = 'entity_type = :entity_type'; $params[':entity_type'] = $entity; }
    if ($userId !== '') { $where[] = 'user_id = :user_id'; $params[':user_id'] = $userId; }
    if ($dateFrom !== '') { $where[] = 'occurred_at >= :df'; $params[':df'] = $dateFrom . ' 00:00:00'; }
    if ($dateTo !== '') { $where[] = 'occurred_at <= :dt'; $params[':dt'] = $dateTo . ' 23:59:59'; }

    $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

    $countSql = "SELECT COUNT(*) AS total FROM audit_logs $whereSql";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = intval(($stmt->fetch(PDO::FETCH_ASSOC))['total'] ?? 0);

    $sql = "SELECT id, occurred_at, user_id, user_name, action, entity_type, entity_id, details
            FROM audit_logs
            $whereSql
            ORDER BY occurred_at DESC, id DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'total' => $total,
        'page' => $page,
        'page_size' => $pageSize,
        'rows' => $rows,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

<?php
session_start();

// Headers para JSON
header('Content-Type: application/json; charset=utf-8');

require '../../database.php';
require '../../src/models/CompositeTemplate.php';
require '../../src/models/Assembly.php';
require '../../src/models/Product.php';
require '../../src/models/Audit.php';

try {
    $compositeModel = new CompositeTemplate(Database::getInstance()->getConnection());
    $assemblyModel = new Assembly(Database::getInstance()->getConnection());
    $productModel = new Product(Database::getInstance()->getConnection());
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro de conexão com banco de dados: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'create_template':
            try {
                $success = $compositeModel->create($_POST);
                if ($success) {
                    // Audit pode falhar silenciosamente caso entity_type não exista no enum
                    Audit::log(Database::getInstance()->getConnection(), 'create', 'template', (int)$success, [
                        'tipo_id' => $_POST['tipo_id'],
                        'version' => $_POST['version'] ?? 1,
                        // Em POST via multipart, items pode vir string JSON; apenas contar se array
                        'items_count' => (isset($_POST['items']) && is_array($_POST['items'])) ? count($_POST['items']) : null
                    ]);
                }
                echo json_encode(['success' => (bool)$success, 'template_id' => $success]);
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao criar template: ' . $e->getMessage()]);
            }
            break;

        case 'update_template':
            $id = (int)$_POST['id'];
            $before = $compositeModel->getById($id);
            $success = $compositeModel->update($id, $_POST);
            if ($success) {
                $after = $compositeModel->getById($id);
                Audit::log(Database::getInstance()->getConnection(), 'update', 'template', $id, [
                    'before' => $before,
                    'after' => $after
                ]);
            }
            echo json_encode(['success' => $success]);
            break;

        case 'activate_template':
            $id = (int)$_POST['id'];
            $success = $compositeModel->activate($id);
            if ($success) {
                Audit::log(Database::getInstance()->getConnection(), 'status_change', 'template', $id, [
                    'action' => 'activated'
                ]);
            }
            echo json_encode(['success' => $success]);
            break;

        case 'delete_template':
            $id = (int)$_POST['id'];
            $before = $compositeModel->getById($id);
            $result = $compositeModel->delete($id);
            if ($result['success']) {
                Audit::log(Database::getInstance()->getConnection(), 'delete', 'template', $id, [
                    'before' => $before
                ]);
            }
            echo json_encode($result);
            break;

        case 'create_assembly':
            $templateId = (int)$_POST['template_id'];
            $userId = $_SESSION['user_id'] ?? null;
            $assemblyId = $assemblyModel->create($templateId, $userId);
            if ($assemblyId) {
                Audit::log(Database::getInstance()->getConnection(), 'create', 'assembly', (int)$assemblyId, [
                    'template_id' => $templateId,
                    'status' => 'in_progress'
                ]);
            }
            echo json_encode(['success' => (bool)$assemblyId, 'assembly_id' => $assemblyId]);
            break;

        case 'update_assembly_serial':
            $assemblyId = (int)$_POST['assembly_id'];
            $compositeSerial = trim($_POST['composite_serial']);
            $userId = $_SESSION['user_id'] ?? null;
            
            if (empty($compositeSerial)) {
                echo json_encode(['success' => false, 'message' => 'Serial não pode estar vazio']);
                break;
            }
            
            $result = $assemblyModel->updateSerial($assemblyId, $compositeSerial, $userId);
            if ($result) {
                Audit::log(Database::getInstance()->getConnection(), 'update', 'assembly', $assemblyId, [
                    'composite_serial' => $compositeSerial
                ]);
                echo json_encode(['success' => true, 'message' => 'Serial atualizado com sucesso']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar serial']);
            }
            break;

        case 'add_component':
            $assemblyId = (int)$_POST['assembly_id'];
            $componentProductId = (int)$_POST['component_product_id'];
            $substituteForTipoId = !empty($_POST['substitute_for_tipo_id']) ? (int)$_POST['substitute_for_tipo_id'] : null;
            $userId = $_SESSION['user_id'] ?? null;
            
            $result = $assemblyModel->addComponent($assemblyId, $componentProductId, $substituteForTipoId, $userId);
            if ($result['success']) {
                Audit::log(Database::getInstance()->getConnection(), 'add_products', 'assembly', $assemblyId, [
                    'component_product_id' => $componentProductId,
                    'substitute_for_tipo_id' => $substituteForTipoId
                ]);
            }
            echo json_encode($result);
            break;

        case 'remove_component':
            $assemblyId = (int)$_POST['assembly_id'];
            $componentProductId = (int)$_POST['component_product_id'];
            
            $result = $assemblyModel->removeComponent($assemblyId, $componentProductId);
            if ($result['success']) {
                Audit::log(Database::getInstance()->getConnection(), 'remove_products', 'assembly', $assemblyId, [
                    'component_product_id' => $componentProductId
                ]);
            }
            echo json_encode($result);
            break;

        case 'finalize_assembly':
            $assemblyId = (int)$_POST['assembly_id'];
            $compositeSerial = $_POST['composite_serial'];
            $userId = $_SESSION['user_id'] ?? null;
            
            $result = $assemblyModel->finalize($assemblyId, $compositeSerial, $userId);
            if ($result['success']) {
                Audit::log(Database::getInstance()->getConnection(), 'status_change', 'assembly', $assemblyId, [
                    'action' => 'finalized',
                    'composite_product_id' => $result['composite_product_id'],
                    'composite_serial' => $compositeSerial
                ]);
            }
            echo json_encode($result);
            break;

        case 'disassemble':
            $assemblyId = (int)$_POST['assembly_id'];
            $userId = $_SESSION['user_id'] ?? null;
            
            $result = $assemblyModel->disassemble($assemblyId, $userId);
            if ($result['success']) {
                Audit::log(Database::getInstance()->getConnection(), 'status_change', 'assembly', $assemblyId, [
                    'action' => 'disassembled'
                ]);
            }
            echo json_encode($result);
            break;

        case 'delete_assembly':
            $id = (int)$_POST['id'];
            $before = $assemblyModel->getById($id);
            $result = $assemblyModel->delete($id);
            if ($result['success']) {
                Audit::log(Database::getInstance()->getConnection(), 'delete', 'assembly', $id, [
                    'before' => $before
                ]);
            }
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Ação não reconhecida']);
            break;
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_template':
            $id = (int)$_GET['id'];
            $template = $compositeModel->getById($id);
            $items = $compositeModel->getTemplateItems($id);
            echo json_encode([
                'template' => $template,
                'items' => $items
            ]);
            break;

        case 'get_assembly':
            $id = (int)$_GET['id'];
            $assembly = $assemblyModel->getById($id);
            $components = $assemblyModel->getAssemblyComponents($id);
            echo json_encode([
                'assembly' => $assembly,
                'components' => $components
            ]);
            break;

        case 'get_composite_by_product':
            // Para produtos compostos: buscar assembly pelo composite_product_id
            $productId = (int)$_GET['product_id'];
            $sql = "SELECT a.*, t.nome as composite_tipo_name, u.name as created_by_name, p.destination as composite_destination
                    FROM assemblies a
                    LEFT JOIN composite_templates ct ON a.template_id = ct.id
                    LEFT JOIN tipos t ON ct.tipo_id = t.id
                    LEFT JOIN users u ON a.created_by = u.id
                    LEFT JOIN products p ON a.composite_product_id = p.id
                    WHERE a.composite_product_id = :product_id AND a.status = 'finalized'";
            $stmt = Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute(['product_id' => $productId]);
            $assembly = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($assembly) {
                $components = $assemblyModel->getAssemblyComponents($assembly['id']);
                echo json_encode([
                    'assembly' => $assembly,
                    'components' => $components
                ]);
            } else {
                echo json_encode([
                    'assembly' => false,
                    'components' => []
                ]);
            }
            break;

        case 'get_parent_composite':
            // Para componentes: buscar o produto composto pai
            $componentId = (int)$_GET['component_id'];
            $sql = "SELECT p.parent_composite_id FROM products p WHERE p.id = :component_id";
            $stmt = Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute(['component_id' => $componentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['parent_composite_id']) {
                // Buscar assembly pelo composite_product_id
                $sql = "SELECT a.*, t.nome as composite_tipo_name, u.name as created_by_name, p.destination as composite_destination
                        FROM assemblies a
                        LEFT JOIN composite_templates ct ON a.template_id = ct.id
                        LEFT JOIN tipos t ON ct.tipo_id = t.id
                        LEFT JOIN users u ON a.created_by = u.id
                        LEFT JOIN products p ON a.composite_product_id = p.id
                        WHERE a.composite_product_id = :composite_product_id AND a.status = 'finalized'";
                $stmt = Database::getInstance()->getConnection()->prepare($sql);
                $stmt->execute(['composite_product_id' => $result['parent_composite_id']]);
                $assembly = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($assembly) {
                    echo json_encode([
                        'assembly' => $assembly,
                        'components' => []
                    ]);
                } else {
                    echo json_encode([
                        'assembly' => false,
                        'components' => []
                    ]);
                }
            } else {
                echo json_encode([
                    'assembly' => false,
                    'components' => []
                ]);
            }
            break;

        case 'get_available_products':
            $tipoId = !empty($_GET['tipo_id']) ? (int)$_GET['tipo_id'] : null;
            
            $sql = "SELECT p.id, p.serial_number, p.warranty, t.nome as tipo_name
                    FROM products p
                    JOIN tipos t ON p.tipo_id = t.id
                    WHERE p.status NOT IN ('in_composite', 'disassembled', 'defective')
                    AND p.parent_composite_id IS NULL
                    AND t.is_composite = 0";
            
            if ($tipoId) {
                $sql .= " AND p.tipo_id = :tipo_id";
            }
            
            $sql .= " ORDER BY p.serial_number ASC";
            
            $stmt = Database::getInstance()->getConnection()->prepare($sql);
            if ($tipoId) {
                $stmt->execute(['tipo_id' => $tipoId]);
            } else {
                $stmt->execute();
            }
            
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'get_composite_tipos':
            try {
                $sql = "SELECT id, nome FROM tipos WHERE is_composite = 1 ORDER BY nome";
                $stmt = Database::getInstance()->getConnection()->prepare($sql);
                $stmt->execute();
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            } catch (Exception $e) {
                echo json_encode(['error' => 'Erro ao buscar tipos compostos: ' . $e->getMessage()]);
            }
            break;

        case 'get_individual_tipos':
            try {
                $sql = "SELECT id, nome FROM tipos WHERE is_composite = 0 ORDER BY nome";
                $stmt = Database::getInstance()->getConnection()->prepare($sql);
                $stmt->execute();
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            } catch (Exception $e) {
                echo json_encode(['error' => 'Erro ao buscar tipos individuais: ' . $e->getMessage()]);
            }
            break;

        case 'get_templates':
            try {
                echo json_encode($compositeModel->getAll());
            } catch (Exception $e) {
                echo json_encode(['error' => 'Erro ao buscar templates: ' . $e->getMessage()]);
            }
            break;

        case 'get_assemblies':
            try {
                echo json_encode($assemblyModel->getAll());
            } catch (Exception $e) {
                echo json_encode(['error' => 'Erro ao buscar assemblies: ' . $e->getMessage()]);
            }
            break;

        case 'get_composite_products':
            try {
                // Verificar se a coluna is_composite existe
                $checkSql = "SHOW COLUMNS FROM tipos LIKE 'is_composite'";
                $checkStmt = Database::getInstance()->getConnection()->prepare($checkSql);
                $checkStmt->execute();
                
                if ($checkStmt->rowCount() == 0) {
                    // Coluna não existe, retornar array vazio
                    echo json_encode([]);
                    break;
                }
                
                // Buscar produtos compostos finalizados (compatível com ONLY_FULL_GROUP_BY)
                $sql = "SELECT 
                            p.id,
                            p.serial_number,
                            p.created_at,
                            t.nome as tipo_name,
                            MAX(a.id) as assembly_id,
                            MAX(a.status) as assembly_status,
                            COUNT(DISTINCT ac.id) as components_count
                        FROM products p
                        JOIN tipos t ON p.tipo_id = t.id
                        LEFT JOIN assemblies a ON p.id = a.composite_product_id
                        LEFT JOIN assembly_components ac ON a.id = ac.assembly_id
                        WHERE t.is_composite = 1 AND p.status = 'in_stock'
                        GROUP BY p.id, p.serial_number, p.created_at, t.nome
                        ORDER BY p.created_at DESC";
                
                $stmt = Database::getInstance()->getConnection()->prepare($sql);
                $stmt->execute();
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            } catch (Exception $e) {
                echo json_encode(['error' => 'Erro ao buscar produtos compostos: ' . $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['error' => 'Ação não reconhecida']);
            break;
    }
    exit;
}

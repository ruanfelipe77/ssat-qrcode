<?php

class CompositeTemplate
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAll()
    {
        try {
            $sql = "SELECT ct.id, 
                           ct.tipo_id,
                           ct.version,
                           ct.is_active,
                           ct.notes,
                           ct.created_at,
                           ct.updated_at,
                           t.nome as tipo_name,
                           COUNT(cti.id) as items_count
                    FROM composite_templates ct
                    LEFT JOIN tipos t ON ct.tipo_id = t.id
                    LEFT JOIN composite_template_items cti ON ct.id = cti.template_id
                    WHERE t.is_composite = 1
                    GROUP BY ct.id
                    ORDER BY ct.created_at DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in CompositeTemplate::getAll(): " . $e->getMessage());
            return [];
        }
    }

    public function getById($id)
    {
        try {
            $sql = "SELECT ct.*, t.nome as tipo_name
                    FROM composite_templates ct
                    LEFT JOIN tipos t ON ct.tipo_id = t.id
                    WHERE ct.id = :id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in CompositeTemplate::getById(): " . $e->getMessage());
            return false;
        }
    }

    public function getActiveByTipoId($tipoId)
    {
        try {
            $sql = "SELECT * FROM composite_templates 
                    WHERE tipo_id = :tipo_id AND is_active = 1 
                    ORDER BY version DESC LIMIT 1";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['tipo_id' => $tipoId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in CompositeTemplate::getActiveByTipoId(): " . $e->getMessage());
            return false;
        }
    }

    public function getTemplateItems($templateId)
    {
        try {
            $sql = "SELECT cti.*, t.nome as component_tipo_name
                    FROM composite_template_items cti
                    LEFT JOIN tipos t ON cti.component_tipo_id = t.id
                    WHERE cti.template_id = :template_id
                    ORDER BY cti.id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['template_id' => $templateId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in CompositeTemplate::getTemplateItems(): " . $e->getMessage());
            return [];
        }
    }

    public function create($data)
    {
        try {
            $this->conn->beginTransaction();

            // Validar se o tipo existe e é composto
            $check = $this->conn->prepare("SELECT id, is_composite FROM tipos WHERE id = :id");
            $check->execute(['id' => $data['tipo_id']]);
            $tipo = $check->fetch(PDO::FETCH_ASSOC);
            if (!$tipo) {
                throw new Exception('Tipo informado não existe.');
            }
            if ((int)$tipo['is_composite'] !== 1) {
                throw new Exception('O tipo selecionado precisa ser marcado como composto.');
            }

            // Desativar templates anteriores do mesmo tipo
            $sql = "UPDATE composite_templates SET is_active = 0 WHERE tipo_id = :tipo_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['tipo_id' => $data['tipo_id']]);

            // Criar novo template
            $sql = "INSERT INTO composite_templates (tipo_id, version, is_active, notes) 
                    VALUES (:tipo_id, :version, 1, :notes)";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                'tipo_id' => $data['tipo_id'],
                'version' => $data['version'] ?? 1,
                'notes' => $data['notes'] ?? null
            ]);

            if (!$result) {
                $this->conn->rollBack();
                return false;
            }

            $templateId = $this->conn->lastInsertId();

            // Normalizar items (pode vir como JSON string)
            $items = [];
            if (isset($data['items'])) {
                if (is_string($data['items'])) {
                    $decoded = json_decode($data['items'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $items = $decoded;
                    }
                } elseif (is_array($data['items'])) {
                    $items = $data['items'];
                }
            }

            // Inserir itens do template
            if (!empty($items)) {
                foreach ($items as $item) {
                    $sql = "INSERT INTO composite_template_items 
                            (template_id, component_tipo_id, quantity, is_optional, notes) 
                            VALUES (:template_id, :component_tipo_id, :quantity, :is_optional, :notes)";
                    
                    $stmt = $this->conn->prepare($sql);
                    $result = $stmt->execute([
                        'template_id' => $templateId,
                        'component_tipo_id' => $item['component_tipo_id'],
                        'quantity' => $item['quantity'] ?? 1,
                        'is_optional' => $item['is_optional'] ?? 0,
                        'notes' => $item['notes'] ?? null
                    ]);

                    if (!$result) {
                        $this->conn->rollBack();
                        return false;
                    }
                }
            }

            $this->conn->commit();
            return $templateId;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error in CompositeTemplate::create(): " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data)
    {
        try {
            $this->conn->beginTransaction();

            // Atualizar template
            $sql = "UPDATE composite_templates 
                    SET notes = :notes, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = :id";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                'id' => $id,
                'notes' => $data['notes'] ?? null
            ]);

            if (!$result) {
                $this->conn->rollBack();
                return false;
            }

            // Remover itens existentes
            $sql = "DELETE FROM composite_template_items WHERE template_id = :template_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['template_id' => $id]);

            // Normalizar items (pode vir como JSON string)
            $items = [];
            if (isset($data['items'])) {
                if (is_string($data['items'])) {
                    $decoded = json_decode($data['items'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $items = $decoded;
                    }
                } elseif (is_array($data['items'])) {
                    $items = $data['items'];
                }
            }

            // Inserir novos itens
            if (!empty($items)) {
                foreach ($items as $item) {
                    $sql = "INSERT INTO composite_template_items 
                            (template_id, component_tipo_id, quantity, is_optional, notes) 
                            VALUES (:template_id, :component_tipo_id, :quantity, :is_optional, :notes)";
                    
                    $stmt = $this->conn->prepare($sql);
                    $result = $stmt->execute([
                        'template_id' => $id,
                        'component_tipo_id' => $item['component_tipo_id'],
                        'quantity' => $item['quantity'] ?? 1,
                        'is_optional' => $item['is_optional'] ?? 0,
                        'notes' => $item['notes'] ?? null
                    ]);

                    if (!$result) {
                        $this->conn->rollBack();
                        return false;
                    }
                }
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error in CompositeTemplate::update(): " . $e->getMessage());
            return false;
        }
    }

    public function activate($id)
    {
        try {
            $this->conn->beginTransaction();

            // Buscar o tipo_id do template
            $sql = "SELECT tipo_id FROM composite_templates WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['id' => $id]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$template) {
                $this->conn->rollBack();
                return false;
            }

            // Desativar todos os templates do mesmo tipo
            $sql = "UPDATE composite_templates SET is_active = 0 WHERE tipo_id = :tipo_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['tipo_id' => $template['tipo_id']]);

            // Ativar o template selecionado
            $sql = "UPDATE composite_templates SET is_active = 1 WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute(['id' => $id]);

            if ($result) {
                $this->conn->commit();
                return true;
            } else {
                $this->conn->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error in CompositeTemplate::activate(): " . $e->getMessage());
            return false;
        }
    }

    public function delete($id)
    {
        try {
            $this->conn->beginTransaction();

            // Verificar se há assemblies usando este template
            $sql = "SELECT COUNT(*) as count FROM assemblies WHERE template_id = :template_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['template_id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Não é possível excluir template em uso por montagens'];
            }

            // Deletar itens do template
            $sql = "DELETE FROM composite_template_items WHERE template_id = :template_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['template_id' => $id]);

            // Deletar template
            $sql = "DELETE FROM composite_templates WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute(['id' => $id]);

            if ($result) {
                $this->conn->commit();
                return ['success' => true];
            } else {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Erro ao excluir template'];
            }
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error in CompositeTemplate::delete(): " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function getLastInsertId()
    {
        return $this->conn->lastInsertId();
    }
}

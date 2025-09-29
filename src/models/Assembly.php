<?php

class Assembly
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAll()
    {
        try {
            $sql = "SELECT a.id,
                           a.status,
                           a.created_at,
                           a.updated_at,
                           a.composite_serial,
                           MAX(ct.version) as template_version,
                           MAX(t.nome) as composite_tipo_name,
                           MAX(u.name) as created_by_name,
                           COUNT(DISTINCT ac.id) as components_count
                    FROM assemblies a
                    LEFT JOIN composite_templates ct ON a.template_id = ct.id
                    LEFT JOIN tipos t ON ct.tipo_id = t.id
                    LEFT JOIN users u ON a.created_by = u.id
                    LEFT JOIN assembly_components ac ON a.id = ac.assembly_id
                    GROUP BY a.id, a.status, a.created_at, a.updated_at, a.composite_serial
                    ORDER BY a.created_at DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Assembly::getAll(): " . $e->getMessage());
            return [];
        }
    }

    public function getById($id)
    {
        try {
            $sql = "SELECT a.*,
                           ct.version as template_version,
                           ct.notes as template_notes,
                           t.nome as composite_tipo_name,
                           u.name as created_by_name
                    FROM assemblies a
                    LEFT JOIN composite_templates ct ON a.template_id = ct.id
                    LEFT JOIN tipos t ON ct.tipo_id = t.id
                    LEFT JOIN users u ON a.created_by = u.id
                    WHERE a.id = :id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Assembly::getById(): " . $e->getMessage());
            return false;
        }
    }

    public function getAssemblyComponents($assemblyId)
    {
        try {
            $sql = "SELECT ac.*,
                           p.serial_number as component_serial,
                           t.nome as component_tipo_name,
                           st.nome as substitute_tipo_name,
                           u.name as added_by_name
                    FROM assembly_components ac
                    LEFT JOIN products p ON ac.component_product_id = p.id
                    LEFT JOIN tipos t ON p.tipo_id = t.id
                    LEFT JOIN tipos st ON ac.substitute_for_tipo_id = st.id
                    LEFT JOIN users u ON ac.added_by = u.id
                    WHERE ac.assembly_id = :assembly_id
                    ORDER BY ac.added_at";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['assembly_id' => $assemblyId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Assembly::getAssemblyComponents(): " . $e->getMessage());
            return [];
        }
    }

    public function create($templateId, $userId = null)
    {
        try {
            $sql = "INSERT INTO assemblies (template_id, status, created_by) 
                    VALUES (:template_id, 'in_progress', :created_by)";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                'template_id' => $templateId,
                'created_by' => $userId
            ]);

            return $result ? $this->conn->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Error in Assembly::create(): " . $e->getMessage());
            return false;
        }
    }

    public function updateSerial($assemblyId, $compositeSerial, $userId = null)
    {
        try {
            $sql = "UPDATE assemblies 
                    SET composite_serial = :composite_serial, updated_at = CURRENT_TIMESTAMP
                    WHERE id = :assembly_id";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                'composite_serial' => $compositeSerial,
                'assembly_id' => $assemblyId
            ]);

            return $result;
        } catch (PDOException $e) {
            error_log("Error in Assembly::updateSerial(): " . $e->getMessage());
            return false;
        }
    }

    public function addComponent($assemblyId, $componentProductId, $substituteForTipoId = null, $userId = null)
    {
        try {
            // Validações
            $validationResult = $this->validateAddComponent($assemblyId, $componentProductId, $substituteForTipoId);
            if (!$validationResult['success']) {
                return $validationResult;
            }

            $this->conn->beginTransaction();

            // Adicionar componente
            $sql = "INSERT INTO assembly_components 
                    (assembly_id, component_product_id, substitute_for_tipo_id, added_by) 
                    VALUES (:assembly_id, :component_product_id, :substitute_for_tipo_id, :added_by)";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                'assembly_id' => $assemblyId,
                'component_product_id' => $componentProductId,
                'substitute_for_tipo_id' => $substituteForTipoId,
                'added_by' => $userId
            ]);

            if (!$result) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Erro ao adicionar componente'];
            }

            // Reservar o produto (marcar como reserved_for_assembly)
            $sql = "UPDATE products SET status = 'reserved' WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['id' => $componentProductId]);

            // Atualizar status da assembly para in_progress se ainda estiver draft
            $sql = "UPDATE assemblies SET status = 'in_progress' WHERE id = :id AND status = 'draft'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['id' => $assemblyId]);

            $this->conn->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error in Assembly::addComponent(): " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function removeComponent($assemblyId, $componentProductId)
    {
        try {
            $this->conn->beginTransaction();

            // Remover componente
            $sql = "DELETE FROM assembly_components 
                    WHERE assembly_id = :assembly_id AND component_product_id = :component_product_id";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                'assembly_id' => $assemblyId,
                'component_product_id' => $componentProductId
            ]);

            if (!$result) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Erro ao remover componente'];
            }

            // Liberar o produto (voltar para in_stock)
            $sql = "UPDATE products SET status = 'in_stock' WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['id' => $componentProductId]);

            $this->conn->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error in Assembly::removeComponent(): " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function finalize($assemblyId, $compositeSerial, $userId = null)
    {
        try {
            // Validar se pode finalizar
            $validationResult = $this->validateFinalize($assemblyId);
            if (!$validationResult['success']) {
                return $validationResult;
            }

            $this->conn->beginTransaction();

            // Buscar informações da assembly
            $assembly = $this->getById($assemblyId);
            if (!$assembly) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Assembly não encontrada'];
            }

            // Buscar template
            $sql = "SELECT ct.*, t.id as composite_tipo_id 
                    FROM composite_templates ct 
                    JOIN tipos t ON ct.tipo_id = t.id 
                    WHERE ct.id = :template_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['template_id' => $assembly['template_id']]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);

            // Criar produto composto
            $sql = "INSERT INTO products (serial_number, tipo_id, status, destination, warranty) 
                    VALUES (:serial_number, :tipo_id, 'in_stock', 'estoque', '12 meses')";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                'serial_number' => $compositeSerial,
                'tipo_id' => $template['composite_tipo_id']
            ]);

            if (!$result) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Erro ao criar produto composto'];
            }

            $compositeProductId = $this->conn->lastInsertId();

            // Atualizar assembly
            $sql = "UPDATE assemblies 
                    SET status = 'finalized', composite_product_id = :composite_product_id 
                    WHERE id = :id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'id' => $assemblyId,
                'composite_product_id' => $compositeProductId
            ]);

            // Atualizar status dos componentes para 'in_composite'
            $sql = "UPDATE products p
                    JOIN assembly_components ac ON p.id = ac.component_product_id
                    SET p.status = 'in_composite',
                        p.parent_composite_id = :composite_product_id,
                        p.updated_at = CURRENT_TIMESTAMP
                    WHERE ac.assembly_id = :assembly_id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'assembly_id' => $assemblyId,
                'composite_product_id' => $compositeProductId
            ]);

            $this->conn->commit();
            return ['success' => true, 'composite_product_id' => $compositeProductId];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error in Assembly::finalize(): " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function disassemble($assemblyId, $userId = null)
    {
        try {
            $this->conn->beginTransaction();

            // Buscar assembly
            $assembly = $this->getById($assemblyId);
            if (!$assembly || $assembly['status'] !== 'finalized') {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Assembly não pode ser desmontada'];
            }

            // Buscar componentes antes de remover
            $sql = "SELECT ac.*, p.serial_number, t.nome as tipo_name
                    FROM assembly_components ac
                    JOIN products p ON ac.component_product_id = p.id
                    JOIN tipos t ON p.tipo_id = t.id
                    WHERE ac.assembly_id = :assembly_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['assembly_id' => $assemblyId]);
            $components = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Liberar componentes
            $sql = "UPDATE products p
                    JOIN assembly_components ac ON p.id = ac.component_product_id
                    SET p.status = 'in_stock',
                        p.parent_composite_id = NULL,
                        p.updated_at = CURRENT_TIMESTAMP
                    WHERE ac.assembly_id = :assembly_id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['assembly_id' => $assemblyId]);

            // Remover registros da tabela assembly_components
            $sql = "DELETE FROM assembly_components WHERE assembly_id = :assembly_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['assembly_id' => $assemblyId]);

            // Marcar produto composto como desmontado
            if ($assembly['composite_product_id']) {
                $sql = "UPDATE products 
                        SET status = 'disassembled',
                            updated_at = CURRENT_TIMESTAMP,
                            disassembled_at = CURRENT_TIMESTAMP,
                            disassembled_by = :user_id
                        WHERE id = :id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'id' => $assembly['composite_product_id'],
                    'user_id' => $userId
                ]);
            }

            // Atualizar assembly
            $sql = "UPDATE assemblies 
                    SET status = 'disassembled',
                        updated_at = CURRENT_TIMESTAMP,
                        disassembled_by = :user_id,
                        disassembled_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'id' => $assemblyId,
                'user_id' => $userId
            ]);

            $this->conn->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error in Assembly::disassemble(): " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    private function validateAddComponent($assemblyId, $componentProductId, $substituteForTipoId = null)
    {
        try {
            // Verificar se assembly existe e não está finalizada
            $assembly = $this->getById($assemblyId);
            if (!$assembly) {
                return ['success' => false, 'message' => 'Assembly não encontrada'];
            }
            if ($assembly['status'] === 'finalized' || $assembly['status'] === 'disassembled') {
                return ['success' => false, 'message' => 'Assembly já finalizada ou desmontada'];
            }

            // Verificar se produto existe e está disponível
            $sql = "SELECT p.*, t.is_composite 
                    FROM products p 
                    JOIN tipos t ON p.tipo_id = t.id 
                    WHERE p.id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['id' => $componentProductId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                return ['success' => false, 'message' => 'Produto não encontrado'];
            }
            if ($product['status'] !== 'in_stock') {
                return ['success' => false, 'message' => 'Produto não está disponível'];
            }
            if ($product['is_composite'] == 1) {
                return ['success' => false, 'message' => 'Submontagens não são permitidas'];
            }
            if ($product['parent_composite_id'] !== null) {
                return ['success' => false, 'message' => 'Produto já faz parte de outro composto'];
            }

            // Verificar se produto já está na assembly
            $sql = "SELECT COUNT(*) as count FROM assembly_components 
                    WHERE assembly_id = :assembly_id AND component_product_id = :component_product_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'assembly_id' => $assemblyId,
                'component_product_id' => $componentProductId
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                return ['success' => false, 'message' => 'Produto já está na montagem'];
            }

            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Error in Assembly::validateAddComponent(): " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro de validação'];
        }
    }

    private function validateFinalize($assemblyId)
    {
        try {
            // Buscar template e componentes necessários
            $sql = "SELECT cti.component_tipo_id, cti.quantity, cti.is_optional, t.nome as tipo_name
                    FROM assemblies a
                    JOIN composite_templates ct ON a.template_id = ct.id
                    JOIN composite_template_items cti ON ct.id = cti.template_id
                    JOIN tipos t ON cti.component_tipo_id = t.id
                    WHERE a.id = :assembly_id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['assembly_id' => $assemblyId]);
            $requiredItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Buscar componentes adicionados
            $sql = "SELECT p.tipo_id, COUNT(*) as quantity
                    FROM assembly_components ac
                    JOIN products p ON ac.component_product_id = p.id
                    WHERE ac.assembly_id = :assembly_id
                    GROUP BY p.tipo_id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['assembly_id' => $assemblyId]);
            $addedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Criar array para facilitar comparação
            $addedByTipo = [];
            foreach ($addedItems as $item) {
                $addedByTipo[$item['tipo_id']] = $item['quantity'];
            }

            // Verificar se todos os itens obrigatórios estão presentes
            foreach ($requiredItems as $required) {
                if ($required['is_optional'] == 0) { // Item obrigatório
                    $addedQty = $addedByTipo[$required['component_tipo_id']] ?? 0;
                    if ($addedQty < $required['quantity']) {
                        return [
                            'success' => false, 
                            'message' => "Faltam " . ($required['quantity'] - $addedQty) . " unidade(s) de " . $required['tipo_name']
                        ];
                    }
                }
            }

            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Error in Assembly::validateFinalize(): " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro de validação'];
        }
    }

    public function delete($id)
    {
        try {
            $this->conn->beginTransaction();

            // Verificar se pode deletar
            $assembly = $this->getById($id);
            if (!$assembly) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Assembly não encontrada'];
            }
            if ($assembly['status'] === 'finalized') {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Não é possível excluir assembly finalizada'];
            }

            // Liberar componentes reservados
            $sql = "UPDATE products p
                    JOIN assembly_components ac ON p.id = ac.component_product_id
                    SET p.status = 'in_stock'
                    WHERE ac.assembly_id = :assembly_id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['assembly_id' => $id]);

            // Deletar componentes da assembly
            $sql = "DELETE FROM assembly_components WHERE assembly_id = :assembly_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['assembly_id' => $id]);

            // Deletar assembly
            $sql = "DELETE FROM assemblies WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute(['id' => $id]);

            if ($result) {
                $this->conn->commit();
                return ['success' => true];
            } else {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Erro ao excluir assembly'];
            }
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error in Assembly::delete(): " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }
}

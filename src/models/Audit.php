<?php

class Audit
{
    public static function log($db, $action, $entityType, $entityId = null, array $details = [])
    {
        try {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                @session_start();
            }
            $userId = $_SESSION['user_id'] ?? null;
            $userName = $_SESSION['user_name'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $sql = "INSERT INTO audit_logs (occurred_at, user_id, user_name, action, entity_type, entity_id, details, ip, user_agent)
                    VALUES (NOW(), :user_id, :user_name, :action, :entity_type, :entity_id, :details, :ip, :user_agent)";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':user_name', $userName);
            $stmt->bindValue(':action', $action);
            $stmt->bindValue(':entity_type', $entityType);
            $stmt->bindValue(':entity_id', $entityId, PDO::PARAM_INT);
            $stmt->bindValue(':details', !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : null);
            $stmt->bindValue(':ip', $ip);
            $stmt->bindValue(':user_agent', $ua);
            $stmt->execute();
        } catch (Throwable $e) {
            error_log('Audit log error: ' . $e->getMessage());
        }
    }
}

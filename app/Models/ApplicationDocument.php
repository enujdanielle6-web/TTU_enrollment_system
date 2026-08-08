<?php

namespace App\Models;

use App\Core\Database;

class ApplicationDocument
{
    public static function findByApplicationId(int $appId): array
    {
        $pdo = Database::getConnection();
        $docStmt = $pdo->prepare('SELECT * FROM application_documents WHERE application_id = :app_id ORDER BY created_at DESC');
        $docStmt->execute(['app_id' => $appId]);
        return $docStmt->fetchAll();
    }

    public static function hasUploadedDocuments(int $appId): bool
    {
        $pdo = Database::getConnection();
        $docStmt = $pdo->prepare('SELECT COUNT(*) FROM application_documents WHERE application_id = :app_id AND status != "rejected"');
        $docStmt->execute(['app_id' => $appId]);
        return $docStmt->fetchColumn() > 0;
    }

    public static function findByDocumentName(int $appId, string $documentName): ?array
    {
        $pdo = Database::getConnection();
        $checkStmt = $pdo->prepare('SELECT id, file_path FROM application_documents WHERE application_id = :app_id AND document_name = :doc_name LIMIT 1');
        $checkStmt->execute(['app_id' => $appId, 'doc_name' => $documentName]);
        $existing = $checkStmt->fetch();
        return $existing ?: null;
    }

    public static function saveUpload(int $appId, string $documentName, string $newFilename): int
    {
        $pdo = Database::getConnection();
        $existing = self::findByDocumentName($appId, $documentName);

        if ($existing) {
            $updateStmt = $pdo->prepare('UPDATE application_documents SET file_path = :file_path, status = "pending" WHERE id = :id');
            $updateStmt->execute(['file_path' => $newFilename, 'id' => $existing['id']]);
            return (int) $existing['id'];
        } else {
            $insertStmt = $pdo->prepare('INSERT INTO application_documents (application_id, document_name, file_path, status) VALUES (:app_id, :doc_name, :file_path, "pending")');
            $insertStmt->execute([
                'app_id' => $appId,
                'doc_name' => $documentName,
                'file_path' => $newFilename
            ]);
            return (int) $pdo->lastInsertId();
        }
    }
}

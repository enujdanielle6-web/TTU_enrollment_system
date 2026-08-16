<?php

namespace App\Models;

use App\Core\Database;
use PDO;

abstract class BaseModel
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    public static function find(int $id)
    {
        $pdo = Database::getConnection();
        $table = static::$table;
        $pk = static::$primaryKey;
        
        $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$pk} = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public static function all()
    {
        $pdo = Database::getConnection();
        $table = static::$table;
        
        $stmt = $pdo->query("SELECT * FROM {$table}");
        return $stmt->fetchAll();
    }

    public static function where(string $column, $operator, $value = null)
    {
        $pdo = Database::getConnection();
        $table = static::$table;
        
        // Handle where('column', 'value') shorthand for equals
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$column} {$operator} :val");
        $stmt->execute(['val' => $value]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $table = static::$table;
        
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        $table = static::$table;
        $pk = static::$primaryKey;
        
        $setClauses = [];
        foreach ($data as $key => $value) {
            $setClauses[] = "{$key} = :{$key}";
        }
        $setString = implode(', ', $setClauses);
        
        $sql = "UPDATE {$table} SET {$setString} WHERE {$pk} = :id";
        $data['id'] = $id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
    }

    public static function delete(int $id): void
    {
        $pdo = Database::getConnection();
        $table = static::$table;
        $pk = static::$primaryKey;
        
        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE {$pk} = :id");
        $stmt->execute(['id' => $id]);
    }
}

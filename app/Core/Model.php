<?php
namespace App\Core;

use PDO;
use PDOException;

/**
 * Base Model with Active Record Pattern, Relationship Engine & Validation
 */
abstract class Model
{
    protected static ?PDO $db = null;
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $attributes = [];
    protected array $errors = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Get Database Connection (PDO)
     */
    public static function getDb(): ?PDO
    {
        if (self::$db === null) {
            $config = config('database.connections.mysql');
            if (!$config) {
                return null;
            }

            try {
                $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
                self::$db = new PDO($dsn, $config['username'], $config['password'], $config['options'] ?? [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                return null;
            }
        }

        return self::$db;
    }

    /**
     * Get table name
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Magic property getter
     */
    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Magic property setter
     */
    public function __set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Fill attributes with whitelist
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (empty($this->fillable) || in_array($key, $this->fillable) || $key === $this->primaryKey) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Find record by primary key ID
     */
    public static function find($id): ?static
    {
        $instance = new static();
        $db = static::getDb();
        if (!$db) return null;

        $stmt = $db->prepare("SELECT * FROM `{$instance->table}` WHERE `{$instance->primaryKey}` = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        $model = new static();
        $model->attributes = $row;
        return $model;
    }

    /**
     * Get all records
     */
    public static function all(): array
    {
        $instance = new static();
        $db = static::getDb();
        if (!$db) return [];

        $stmt = $db->query("SELECT * FROM `{$instance->table}` ORDER BY `{$instance->primaryKey}` DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $row) {
            $model = new static();
            $model->attributes = $row;
            $results[] = $model;
        }
        return $results;
    }

    /**
     * Query records with WHERE condition
     */
    public static function where(string $column, $value, string $operator = '='): array
    {
        $instance = new static();
        $db = static::getDb();
        if (!$db) return [];

        $stmt = $db->prepare("SELECT * FROM `{$instance->table}` WHERE `{$column}` {$operator} ?");
        $stmt->execute([$value]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $row) {
            $model = new static();
            $model->attributes = $row;
            $results[] = $model;
        }
        return $results;
    }

    /**
     * Query first record with WHERE condition
     */
    public static function firstWhere(string $column, $value, string $operator = '='): ?static
    {
        $results = static::where($column, $value, $operator);
        return $results[0] ?? null;
    }

    /**
     * Create and insert new record
     */
    public static function create(array $attributes): ?static
    {
        $instance = new static();
        $instance->fill($attributes);

        if ($instance->save()) {
            return $instance;
        }
        return null;
    }

    /**
     * Save current model (Insert or Update)
     */
    public function save(): bool
    {
        $db = static::getDb();
        $isNew = empty($this->attributes[$this->primaryKey]);
        $now = date('Y-m-d H:i:s');

        if (!$db) {
            if ($isNew) {
                $this->attributes[$this->primaryKey] = time() + rand(10, 999);
                if (!isset($this->attributes['created_at'])) {
                    $this->attributes['created_at'] = $now;
                }
            }
            $this->attributes['updated_at'] = $now;
            return true;
        }

        if ($isNew) {
            $allowCreated = empty($this->fillable) || in_array('created_at', $this->fillable);
            $allowUpdated = empty($this->fillable) || in_array('updated_at', $this->fillable);

            if (!isset($this->attributes['created_at']) && $allowCreated) {
                $this->attributes['created_at'] = $now;
            }
            if (!isset($this->attributes['updated_at']) && $allowUpdated) {
                $this->attributes['updated_at'] = $now;
            }

            $fields = array_keys($this->attributes);
            $placeholders = array_fill(0, count($fields), '?');

            $sql = sprintf(
                "INSERT INTO `%s` (`%s`) VALUES (%s)",
                $this->table,
                implode('`, `', $fields),
                implode(', ', $placeholders)
            );

            $stmt = $db->prepare($sql);
            $success = $stmt->execute(array_values($this->attributes));
            if ($success) {
                $this->attributes[$this->primaryKey] = (int)$db->lastInsertId();
                return true;
            }
            return false;
        } else {
            if (empty($this->fillable) || in_array('updated_at', $this->fillable)) {
                $this->attributes['updated_at'] = $now;
            }

            $fields = [];
            $values = [];
            foreach ($this->attributes as $key => $val) {
                if ($key !== $this->primaryKey) {
                    $fields[] = "`{$key}` = ?";
                    $values[] = $val;
                }
            }
            $values[] = $this->attributes[$this->primaryKey];

            $sql = sprintf(
                "UPDATE `%s` SET %s WHERE `%s` = ?",
                $this->table,
                implode(', ', $fields),
                $this->primaryKey
            );

            $stmt = $db->prepare($sql);
            return $stmt->execute($values);
        }
    }

    /**
     * Delete record
     */
    public function delete(): bool
    {
        if (empty($this->attributes[$this->primaryKey])) {
            return false;
        }

        $db = static::getDb();
        if (!$db) return true;

        $stmt = $db->prepare("DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?");
        return $stmt->execute([$this->attributes[$this->primaryKey]]);
    }

    // ========================================================
    // RELATIONSHIPS
    // ========================================================

    /**
     * 1 : 1 Relationship (HasOne)
     */
    protected function hasOne(string $relatedClass, string $foreignKey, string $localKey = 'id'): ?Model
    {
        $localVal = $this->attributes[$localKey] ?? null;
        if ($localVal === null) return null;

        return $relatedClass::firstWhere($foreignKey, $localVal);
    }

    /**
     * 1 : N Relationship (HasMany)
     */
    protected function hasMany(string $relatedClass, string $foreignKey, string $localKey = 'id'): array
    {
        $localVal = $this->attributes[$localKey] ?? null;
        if ($localVal === null) return [];

        return $relatedClass::where($foreignKey, $localVal);
    }

    /**
     * Inverse 1 : 1 or N : 1 Relationship (BelongsTo)
     */
    protected function belongsTo(string $relatedClass, string $foreignKey, string $ownerKey = 'id'): ?Model
    {
        $foreignVal = $this->attributes[$foreignKey] ?? null;
        if ($foreignVal === null) return null;

        return $relatedClass::find($foreignVal);
    }

    // ========================================================
    // VALIDATION ENGINE
    // ========================================================

    /**
     * Validate data against specified rules
     * @param array $data Data to validate
     * @param array $rules Rules e.g. ['email' => 'required|email|unique:users,email']
     * @return array Array of errors, empty if valid
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];
        $db = static::getDb();

        foreach ($rules as $field => $ruleString) {
            $ruleList = explode('|', $ruleString);
            $val = $data[$field] ?? null;

            foreach ($ruleList as $rule) {
                $param = null;
                if (str_contains($rule, ':')) {
                    [$ruleName, $param] = explode(':', $rule, 2);
                } else {
                    $ruleName = $rule;
                }

                switch ($ruleName) {
                    case 'required':
                        if ($val === null || trim((string)$val) === '') {
                            $errors[$field][] = "Field {$field} wajib diisi.";
                        }
                        break;

                    case 'email':
                        if ($val !== null && trim((string)$val) !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "Field {$field} harus berformat email yang valid.";
                        }
                        break;

                    case 'numeric':
                        if ($val !== null && !is_numeric($val)) {
                            $errors[$field][] = "Field {$field} harus berupa angka.";
                        }
                        break;

                    case 'min':
                        if ($val !== null) {
                            $isNumericRule = in_array('numeric', $ruleList);
                            if ($isNumericRule && is_numeric($val)) {
                                if ((float)$val < (float)$param) {
                                    $errors[$field][] = "Field {$field} minimal bernilai {$param}.";
                                }
                            } elseif (is_string($val) && mb_strlen($val) < (int)$param) {
                                $errors[$field][] = "Field {$field} minimal {$param} karakter.";
                            }
                        }
                        break;

                    case 'max':
                        if ($val !== null) {
                            $isNumericRule = in_array('numeric', $ruleList);
                            if ($isNumericRule && is_numeric($val)) {
                                if ((float)$val > (float)$param) {
                                    $errors[$field][] = "Field {$field} maksimal bernilai {$param}.";
                                }
                            } elseif (is_string($val) && mb_strlen($val) > (int)$param) {
                                $errors[$field][] = "Field {$field} maksimal {$param} karakter.";
                            }
                        }
                        break;

                    case 'in':
                        if ($val !== null && $param !== null) {
                            $allowed = explode(',', $param);
                            if (!in_array($val, $allowed)) {
                                $errors[$field][] = "Nilai {$field} tidak valid. Pilihan: " . implode(', ', $allowed);
                            }
                        }
                        break;

                    case 'unique':
                        if ($val !== null && $param !== null && $db) {
                            $parts = explode(',', $param);
                            $table = $parts[0];
                            $col = $parts[1] ?? $field;
                            $ignoreId = $parts[2] ?? null;
                            $idCol = $parts[3] ?? (($ignoreId !== null && (string)$ignoreId === (string)$val) ? $col : 'id');

                            $query = "SELECT COUNT(*) FROM `{$table}` WHERE `{$col}` = ?";
                            $params = [$val];

                            if ($ignoreId !== null) {
                                $query .= " AND `{$idCol}` != ?";
                                $params[] = $ignoreId;
                            }

                            $stmt = $db->prepare($query);
                            $stmt->execute($params);
                            if ((int)$stmt->fetchColumn() > 0) {
                                $errors[$field][] = "Field {$field} sudah terdaftar dalam sistem.";
                            }
                        }
                        break;
                }
            }
        }

        return $errors;
    }
}

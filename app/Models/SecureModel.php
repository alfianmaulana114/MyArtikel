<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class SecureModel extends Model
{
    /**
     * Override the default query builder to add SQL injection protection
     */
    public function newEloquentBuilder($query): Builder
    {
        return new class($query) extends Builder {
            /**
             * Add SQL injection protection to where clauses
             */
            public function where($column, $operator = null, $value = null, $boolean = 'and')
            {
                // Validate column names to prevent SQL injection
                if (is_string($column) && !$this->isValidColumn($column)) {
                    Log::warning('SQL Injection attempt detected', [
                        'column' => $column,
                        'operator' => $operator,
                        'value' => $value,
                        'model' => get_class($this->model),
                    ]);
                    throw new \InvalidArgumentException('Invalid column name: ' . $column);
                }

                // Validate values to prevent SQL injection
                if ($value !== null && !$this->isValidValue($value)) {
                    Log::warning('SQL Injection attempt detected', [
                        'column' => $column,
                        'value' => $value,
                        'model' => get_class($this->model),
                    ]);
                    throw new \InvalidArgumentException('Invalid value provided');
                }

                return parent::where($column, $operator, $value, $boolean);
            }

            /**
             * Add SQL injection protection to orderBy
             */
            public function orderBy($column, $direction = 'asc')
            {
                // Validate column names
                if (!$this->isValidColumn($column)) {
                    Log::warning('SQL Injection attempt in orderBy', [
                        'column' => $column,
                        'direction' => $direction,
                        'model' => get_class($this->model),
                    ]);
                    throw new \InvalidArgumentException('Invalid column name for ordering: ' . $column);
                }

                // Validate direction
                $direction = strtolower($direction);
                if (!in_array($direction, ['asc', 'desc'])) {
                    Log::warning('SQL Injection attempt in orderBy direction', [
                        'column' => $column,
                        'direction' => $direction,
                        'model' => get_class($this->model),
                    ]);
                    throw new \InvalidArgumentException('Invalid direction: ' . $direction);
                }

                return parent::orderBy($column, $direction);
            }

            /**
             * Add SQL injection protection to groupBy
             */
            public function groupBy(...$groups)
            {
                foreach ($groups as $group) {
                    if (is_string($group) && !$this->isValidColumn($group)) {
                        Log::warning('SQL Injection attempt in groupBy', [
                            'group' => $group,
                            'model' => get_class($this->model),
                        ]);
                        throw new \InvalidArgumentException('Invalid group by column: ' . $group);
                    }
                }

                return parent::groupBy(...$groups);
            }

            /**
             * Add SQL injection protection to having
             */
            public function having($column, $operator = null, $value = null, $boolean = 'and')
            {
                if (is_string($column) && !$this->isValidColumn($column)) {
                    Log::warning('SQL Injection attempt in having', [
                        'column' => $column,
                        'operator' => $operator,
                        'value' => $value,
                        'model' => get_class($this->model),
                    ]);
                    throw new \InvalidArgumentException('Invalid column name in having: ' . $column);
                }

                return parent::having($column, $operator, $value, $boolean);
            }

            /**
             * Validate column names to prevent SQL injection
             */
            private function isValidColumn($column): bool
            {
                if (!is_string($column)) {
                    return true;
                }

                // Remove table prefix if present
                $column = last(explode('.', $column));

                // Check if column contains dangerous characters
                if (preg_match('/[;\'"`\\x00\\n\\r]/', $column)) {
                    return false;
                }

                // Check if column is a valid database column or SQL function
                $validColumns = $this->model->getFillable();
                $validColumns[] = $this->model->getKeyName();
                $validColumns[] = 'created_at';
                $validColumns[] = 'updated_at';
                $validColumns[] = 'deleted_at';

                // Allow raw expressions for legitimate use cases
                if (strpos($column, '(') !== false && strpos($column, ')') !== false) {
                    // Basic validation for SQL functions
                    $allowedFunctions = ['COUNT', 'SUM', 'AVG', 'MAX', 'MIN', 'DATE', 'TIME', 'YEAR', 'MONTH'];
                    $functionName = strtoupper(explode('(', $column)[0]);
                    return in_array($functionName, $allowedFunctions);
                }

                return in_array($column, $validColumns);
            }

            /**
             * Validate values to prevent SQL injection
             */
            private function isValidValue($value): bool
            {
                if (!is_string($value)) {
                    return true;
                }

                // Check for dangerous SQL patterns
                $dangerousPatterns = [
                    '/;\s*(DROP|DELETE|TRUNCATE|ALTER|CREATE|EXEC|UNION|INSERT|UPDATE)/i',
                    '/\b(UNION\s+SELECT|SELECT\s+\*|DROP\s+TABLE|DELETE\s+FROM)\b/i',
                    '/\/\*.*?\*\//s',
                    '/--.*$/m',
                    '/[\x00\x1a]/',
                ];

                foreach ($dangerousPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        return false;
                    }
                }

                return true;
            }
        };
    }

    /**
     * Secure scope for filtering by user
     */
    public function scopeForUser(Builder $query, $userId): Builder
    {
        if (!is_numeric($userId) || $userId <= 0) {
            throw new \InvalidArgumentException('Invalid user ID');
        }

        return $query->where('user_id', $userId);
    }

    /**
     * Secure scope for pagination
     */
    public function scopeSecurePaginate(Builder $query, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 15;
        }

        return $query->paginate($perPage);
    }

    /**
     * Override delete to use soft delete by default
     */
    public function delete()
    {
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this))) {
            return $this->softDelete();
        }

        return parent::delete();
    }
}
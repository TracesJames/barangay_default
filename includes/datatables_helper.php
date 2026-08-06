<?php

if (!function_exists('datatables_order_clause')) {
    function datatables_order_clause(array $columns, ?array $orderRequest, string $default = ''): string
    {
        if ($default === '') {
            $default = ' ORDER BY ' . $columns[0] . ' DESC';
        }

        if (!isset($orderRequest['0']['column'], $orderRequest['0']['dir'])) {
            return $default;
        }

        $colIndex = (int) $orderRequest['0']['column'];
        if (!isset($columns[$colIndex])) {
            return $default;
        }

        $dir = strtoupper($orderRequest['0']['dir']) === 'ASC' ? 'ASC' : 'DESC';
        return ' ORDER BY ' . $columns[$colIndex] . ' ' . $dir;
    }
}

if (!function_exists('datatables_limit_clause')) {
    function datatables_limit_clause($start, $length): string
    {
        if ((int) $length === -1) {
            return '';
        }
        return ' LIMIT ' . (int) $start . ', ' . (int) $length;
    }
}

if (!function_exists('datatables_search_like')) {
    function datatables_search_like(?string $value): string
    {
        return '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $value) . '%';
    }
}

if (!function_exists('datatables_sql_like')) {
    /**
     * Escaped LIKE pattern safe for interpolation into mysqli query strings.
     * Prefer prepared statements when possible; this is for legacy Table endpoints.
     */
    function datatables_sql_like(mysqli $con, ?string $value): string
    {
        return $con->real_escape_string(datatables_search_like($value));
    }
}


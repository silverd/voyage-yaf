<?php

class Com_DB_SqlBuilder
{
    private static $_sqlTpl = array(
        'select'       => 'SELECT {DISTINCT} {FIELD} FROM {TABLE} {JOIN} {WHERE} {GROUP} {HAVING} {ORDER} {LIMIT} {LOCK}',
        'insert'       => 'INSERT {IGNORE} INTO {TABLE} ({KEYSQL}) VALUES ({VALUESQL})',
        'replace'      => 'REPLACE INTO {TABLE} ({KEYSQL}) VALUES ({VALUESQL})',
        'update'       => 'UPDATE {TABLE} SET {SETSQL} {WHERE} {ORDER} {LIMIT}',
        'delete'       => 'DELETE FROM {TABLE} {WHERE} {ORDER} {LIMIT}',
        'batchInsert'  => 'INSERT INTO {TABLE} ({KEYSQL}) VALUES {VALUESQL}',
        'batchReplace' => 'REPLACE INTO {TABLE} ({KEYSQL}) VALUES {VALUESQL}',
    );

    private $_options = array();

    public function setOptions($options)
    {
        $this->_options = $options;

        return $this;
    }

    /**
     * 解析SQL语句（替换SQL语句中表达式）
     *
     * @return string
     */
    public function buildSelectSql()
    {
        return trim(str_replace(
            array('{TABLE}', '{DISTINCT}', '{FIELD}', '{JOIN}', '{WHERE}', '{GROUP}', '{HAVING}', '{ORDER}', '{LIMIT}', '{LOCK}'),
            array(
                $this->_parseTable($this->_options['table']),
                $this->_parseDistinct(isset($this->_options['distinct']) ? $this->_options['distinct'] : false),
                $this->_parseField(isset($this->_options['field'])       ? $this->_options['field']    : '*'),
                $this->_parseJoin(isset($this->_options['join'])         ? $this->_options['join']     : ''),
                $this->_parseWhere(isset($this->_options['where'])       ? $this->_options['where']    : ''),
                $this->_parseGroup(isset($this->_options['group'])       ? $this->_options['group']    : ''),
                $this->_parseHaving(isset($this->_options['having'])     ? $this->_options['having']   : ''),
                $this->_parseOrder(isset($this->_options['order'])       ? $this->_options['order']    : ''),
                $this->_parseLimit(isset($this->_options['limit'])       ? $this->_options['limit']    : ''),
                $this->_parseLock(isset($this->_options['lock'])         ? $this->_options['lock']     : ''),
            ),
            self::$_sqlTpl['select']
        ));
    }

    /**
     * 插入
     *
     * @param array $setArr
     * @param bool $isReplace
     * @param bool $isIgnoreDup 忽略重复
     * @return int
     */
    public function buildInsertSql(array $setArr, $isReplace = false, $isIgnoreDup = false)
    {
        $sql = $this->_buildInsertSql($setArr, $isReplace, $isIgnoreDup);

        return array(
            'sql'    => $sql,
            'params' => array_values($setArr),
        );
    }

    public function _buildInsertSql(array &$setArr, $isReplace = false, $isIgnoreDup = false)
    {
        $insertkeysql = $insertvaluesql = $comma = '';
        foreach ($setArr as $key => $value) {
            if (false === $value) {
                unset($setArr[$key]);
                continue;
            }
            $insertkeysql .= $comma . '`' . $key . '`';
            $insertvaluesql .= $comma . '?';
            $comma = ', ';
        }

        $sql = str_replace(
            array('{TABLE}', '{IGNORE}', '{KEYSQL}', '{VALUESQL}'),
            array(
                $this->_parseTable($this->_options['table']),
                $isIgnoreDup ? 'IGNORE' : '',
                $insertkeysql,
                $insertvaluesql,
            ),
            self::$_sqlTpl[$isReplace ? 'replace' : 'insert']
        );

        return $sql;
    }

    public function buildReplaceSql(array $setArr)
    {
        return $this->buildInsertSql($setArr, true);
    }

    /**
     * 更新
     *
     * @param array $setArr
     * @return int
     */
    public function buildUpdateSql(array $setArr)
    {
        $setSql = $this->_buildSetSql($setArr);

        return array(
            'sql'    => $this->_buildUpdateSql($setSql),
            'params' => array_values($setArr),
        );
    }

    private function _buildSetSql(array &$setArr)
    {
        $setSql = $comma = '';

        foreach ($setArr as $key => $value) {
            if (false === $value) { // 值为 false 表示不更新该字段
                unset($setArr[$key]);
                continue;
            } elseif (! is_array($value)) {
                $setSql .= $comma . "`{$key}` = ?";
            } else {
                if (isset($value[2])) {
                    if ('-' == $value[0]) { // 自减后保证不低于X
                        $setSql .= $comma . "`{$key}` = (CASE WHEN `{$key}` - {$value[1]} > {$value[2]} THEN `{$key}` - {$value[1]} ELSE {$value[2]} END)";
                    } elseif ('+' == $value[0]) {   // 自增后保证不大于X
                        $setSql .= $comma . "`{$key}` = (CASE WHEN `{$key}` + {$value[1]} < {$value[2]} THEN `{$key}` + {$value[1]} ELSE {$value[2]} END)";
                    }
                    unset($setArr[$key]);
                } else {
                    $setSql .= $comma . "`{$key}` = `{$key}` {$value[0]} ?";
                    $setArr[$key] = $value[1];
                }
            }
            $comma = ', ';
        }

        return $setSql;
    }

    private function _buildUpdateSql($setSql)
    {
        return str_replace(
            array('{TABLE}', '{SETSQL}', '{WHERE}', '{ORDER}', '{LIMIT}'),
            array(
                $this->_parseTable($this->_options['table']),
                $setSql,
                $this->_parseWhere(isset($this->_options['where']) ? $this->_options['where'] : ''),
                $this->_parseOrder(isset($this->_options['order']) ? $this->_options['order'] : ''),
                $this->_parseLimit(isset($this->_options['limit']) ? $this->_options['limit'] : ''),
            ),
            self::$_sqlTpl['update']
        );
    }

    /**
     * 批量插入记录
     *
     * @param array $setArrs
     * @param bool $isReplace
     * @return int
     */
    public function buildBatchInsertSql(array $setArrs, $isReplace = false)
    {
        $params = array();
        $insertkeysqlGot = false;
        if (! $setArrs || ! is_array($setArrs)) {
            return false;
        }

        $insertkeysql = $insertvaluesql = $comma = '';
        foreach ($setArrs as $setArr) {

            $insertvaluesqlNode = $commaNode = '';

            foreach ($setArr as $key => $value) {
                if (false === $value) {
                    unset($setArr[$key]);
                    continue;
                }

                if (! $insertkeysqlGot) {
                    $insertkeysql .= $commaNode . '`' . $key . '`';
                }

                $insertvaluesqlNode .= $commaNode . '?';
                $params[] = $value;

                $commaNode = ', ';
            }

            $insertvaluesql .= $comma . '(' . $insertvaluesqlNode . ')';
            $insertkeysqlGot = true;
            $comma = ', ';
        }

        $sql = str_replace(
            array('{TABLE}', '{KEYSQL}', '{VALUESQL}'),
            array(
                $this->_parseTable($this->_options['table']),
                $insertkeysql,
                $insertvaluesql,
            ),
            self::$_sqlTpl[$isReplace ? 'batchReplace' : 'batchInsert']
        );

        return array(
            'sql'    => $sql,
            'params' => $params,
        );
    }

    public function buildBatchReplaceSql(array $setArrs)
    {
        return $this->buildBatchInsertSql($setArrs, true);
    }

    public function buildDeleteSql()
    {
        return str_replace(
            array('{TABLE}', '{WHERE}', '{ORDER}', '{LIMIT}'),
            array(
                $this->_parseTable($this->_options['table']),
                $this->_parseWhere(isset($this->_options['where']) ? $this->_options['where'] : ''),
                $this->_parseOrder(isset($this->_options['order']) ? $this->_options['order'] : ''),
                $this->_parseLimit(isset($this->_options['limit']) ? $this->_options['limit'] : ''),
            ),
            self::$_sqlTpl['delete']
        );
    }

    /**
     * 字段自增
     *
     * @param string $field
     * @param int $value
     * @return int
     */
    public function buildIncrementSql($field, $value = 1)
    {
        $setSql = "`{$field}` = `{$field}` + {$value}";

        return $this->_buildUpdateSql($setSql);
    }

    /**
     * 字段自增（保证不超过最大值）
     *
     * @param string $field
     * @param int $value
     * @param int $maxValue
     * @return int
     */
    public function buildIncrementMaxSql($field, $value, $maxValue)
    {
        $setSql = "`{$field}` = (CASE WHEN `{$field}` + {$value} < {$maxValue} THEN `{$field}` + {$value} ELSE {$maxValue} END)";

        return $this->_buildUpdateSql($setSql);
    }

    /**
     * 字段自减
     *
     * @param string $field
     * @param int $value
     * @return int
     */
    public function buildDecrementSql($field, $value = 1)
    {
        $setSql = "`{$field}` = `{$field}` - {$value}";

        return $this->_buildUpdateSql($setSql);
    }

    /**
     * 字段自减（保证不低于最小值）
     *
     * @param string $field
     * @param int $value
     * @param int $minValue
     * @return int
     */
    public function buildDecrementMinSql($field, $value, $minValue)
    {
        $setSql = "`{$field}` = (CASE WHEN `{$field}` - {$value} > {$minValue} THEN `{$field}` - {$value} ELSE {$minValue} END)";

        return $this->_buildUpdateSql($setSql);
    }

    public function buildInsertOnDupateSql($insertArr, $updateArr)
    {
        $insertSql = $this->_buildInsertSql($insertArr);
        $updateSql = $this->_buildSetSql($updateArr);

        return array(
            'sql'    => $insertSql . ' ON DUPLICATE KEY UPDATE ' . $updateSql,
            'params' => array_merge(array_values($insertArr), array_values($updateArr)),
        );
    }

    private function _parseTable($tables)
    {
        if (is_string($tables)) {
            $tables = explode(',', $tables);
        }

        $tables = array_map(array($this, '_parseKey'), $tables);

        return implode(',', $tables);
    }

    private function _parseField($fields)
    {
        if (! $fields || $fields == '*') {
            return '*';
        }

        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }

        if (is_array($fields[0])) {
            $fields = $fields[0];
        }

        return implode(', ', $fields);
    }

    private function _parseDistinct($distinct)
    {
        return $distinct ? 'DISTINCT ' : '';
    }

    private function _parseWhere($whereArr)
    {
        $whereSql = $comma = '';

        if ($whereArr && is_array($whereArr)) {

            foreach ($whereArr as $field => $value) {

                $operator = '=';
                if (is_array($value)) {
                    $operator = $value[0];
                    $value    = $value[1];
                }

                // addslashes
                $value = $this->_escape($value);

                // 原生SQL条件
                if ($operator == 'SQL') {
                    $whereSql .= $comma . '`' . $field . '` ' . $value;
                } else {
                    $whereSql .= $comma . '`' . $field . '` ' . $operator;
                    switch ($operator) {
                        case 'IN':
                        case 'NOT IN':
                            $whereSql .= ' (' . (is_array($value) ? ximplode($value) : $value) . ')';
                            break;
                        case 'BETWEEN':
                            $whereSql .= ' ' . $value[0] . ' AND ' . $value[1];
                            break;
                        default:
                            $whereSql .= ' \'' . $value . '\'';
                    }
                }

                $comma = ' AND ';
            }

        } else {
            $whereSql = $whereArr;
        }

        return $whereSql ? 'WHERE ' . $whereSql : '';
    }

    private function _parseOrder($order)
    {
        return $order ? 'ORDER BY ' . $order : '';
    }

    private function _parseLimit($limit)
    {
        if (! $limit) {
            return '';
        }

        if (count($limit) == 1) {
            $limit = $limit[0];
        } else {
            $limit = $limit[0] . ', ' . $limit[1];
        }

        return $limit ? 'LIMIT ' . $limit : '';
    }

    private function _parseHaving($having)
    {
        return $having ? 'HAVING ' . $having : '';
    }

    private function _parseGroup($group)
    {
        return $group ? 'GROUP BY ' . $group : '';
    }

    private function _parseJoin($join)
    {
        return $join ? 'LEFT JOIN ' . $join : '';
    }

    private function _parseKey($key)
    {
        if (strpos($key, '.') === false) {
            return '`' . $key . '`';
        }

        $keys = explode('.', $key);
        $keys = array_map(array($this, '_parseKey'), $keys);
        return implode('.', $keys);
    }

    private function _parseLock($lock)
    {
        return $lock ? ' FOR UPDATE ' : '';
    }

    /**
     * 转义引号防止SQL注入
     *
     * @param mixed $string
     * @return mixed
     */
    private function _escape($string)
    {
        if (is_array($string)) {
            return array_map(__METHOD__, $string);
        } else {
            return is_string($string) ? addslashes($string) : $string;
        }
    }
}
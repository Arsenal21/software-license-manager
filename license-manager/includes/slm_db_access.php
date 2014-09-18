<?php

global $wpdb;
define('WP_LICENSE_MANAGER_LICENSE_TABLE_NAME', $wpdb->prefix . "lic_key_tbl");
define('WP_LICENSE_MANAGER_REG_DOMAIN_TABLE_NAME', $wpdb->prefix . "lic_reg_domain_tbl");

class LicMgrDbAccess {

    function __construct() {
        die();
    }

    function LicMgrDbAccess() {
        die();
    }

    function find($inTable, $condition) {
        global $wpdb;

        if (empty($condition)) {
            return null;
        }
        $resultset = $wpdb->get_row("SELECT * FROM $inTable WHERE $condition", OBJECT);
        return $resultset;
    }

    function findAll($inTable, $condition = null, $orderby = null) {
        global $wpdb;
        $condition = empty($condition) ? '' : ' WHERE ' . $condition;
        $condition .= empty($orderby) ? '' : ' ORDER BY ' . $orderby;
        $resultSet = $wpdb->get_results("SELECT * FROM $inTable $condition ", OBJECT);
        return $resultSet;
    }

    function delete($fromTable, $condition) {
        global $wpdb;
        $resultSet = $wpdb->query("DELETE FROM $fromTable WHERE $condition ");
        return $resultSet;
    }

    function update($inTable, $condition, $fields) {
        global $wpdb;
        $query = " UPDATE $inTable SET ";
        $first = true;
        foreach ($fields as $field => $value) {
            if ($first)
                $first = false;
            else
                $query .= ' , ';
            $query .= " $field = '" . $wpdb->escape($value) . "' ";
        }

        $query .= empty($condition) ? '' : " WHERE $condition ";
        $results = $wpdb->query($query);
        return $results;
    }

    function insert($inTable, $fields) {
        global $wpdb;
        $fieldss = '';
        $valuess = '';
        $first = true;
        foreach ($fields as $field => $value) {
            if ($first)
                $first = false;
            else {
                $fieldss .= ' , ';
                $valuess .= ' , ';
            }
            $fieldss .= " $field ";
            $valuess .= " '" . $wpdb->escape($value) . "' ";
        }

        $query .= " INSERT INTO $inTable ($fieldss) VALUES ($valuess)";

        $results = $wpdb->query($query);
        return $results;
    }

    function findCount($inTable, $fields = null, $condition = null, $orderby = null, $groupby = null) {
        global $wpdb;
        $fieldss = '';
        $first = true;

        if (empty($fields)) {
            $fieldss = 'count(*) as count';
        } else {
            foreach ($fields as $key => $value) {
                if ($first)
                    $first = false;
                else {
                    $fieldss .= ' , ';
                }
                $fieldss .= " $key AS $value";
            }
        }
        $condition = $condition ? " WHERE $condition " : null;
        $condition.= empty($orderby) ? '' : ' ORDER BY ' . $orderby;
        $condition.= empty($groupby) ? '' : ' GROUP BY ' . $groupby;

        $resultset = $wpdb->get_results("SELECT $fieldss FROM $inTable $condition ", OBJECT);
        return $resultset;
    }

}


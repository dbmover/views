<?php

/**
 * @package Dbmover
 * @subpackage Views
 *
 * Plugin to drop and recreate all views.
 */

namespace Dbmover\Views;

use Dbmover\Core;

class Plugin extends Core\Plugin
{
    private $views = [];

    public function __invoke(string $sql) : string
    {
        if (preg_match_all('@^CREATE VIEW.*?;$@ms', $sql, $views, PREG_SET_ORDER)) {
            foreach ($views as $view) {
                $sql = str_replace($view[0], '', $sql);
                $this->views[] = $view[0];
            }
        }
        $stmt = $this->loader->getPdo()->prepare(
            "SELECT TABLE_NAME
                FROM INFORMATION_SCHEMA.TABLES
                WHERE (TABLE_CATALOG = ? OR TABLE_SCHEMA = ?) AND TABLE_TYPE = 'VIEW'"
        );
        $stmt->execute([$this->loader->database, $this->loader->database]);
        while (false !== ($view = $stmt->fetchColumn())) {
            if (!$this->loader->shouldBeIgnored($view)) {
                $this->addOperation("DROP VIEW IF EXISTS $view;");
            }
        }
        return $sql;
    }

    public function __destruct()
    {
        foreach ($this->views as $view) {
            $this->addOperation($view);
        }
    }
}


<?php

/**
 * @package Dbmover
 * @subpackage Views
 *
 * Plugin to drop and recreate all views.
 */

namespace Dbmover\Views;

use Dbmover\Core;

/**
 * Plugin to (re)create all views.
 */
class Plugin extends Core\Plugin
{
    private $views = [];
    public $description = 'Dropping existing views...';

    /**
     * @param string $sql
     * @return string
     */
    public function __invoke(string $sql) : string
    {
        if (preg_match_all('@^CREATE(\s+MATERIALIZED)?\s+VIEW.*?;$@ms', $sql, $views, PREG_SET_ORDER)) {
            foreach ($views as $view) {
                $sql = str_replace($view[0], '', $sql);
                $this->defer($view[0]);
            }
        }
        $stmt = $this->loader->getPdo()->prepare(
            "SELECT TABLE_NAME
                FROM INFORMATION_SCHEMA.TABLES
                WHERE ((TABLE_CATALOG = ? AND TABLE_SCHEMA = 'public') OR TABLE_SCHEMA = ?)
                    AND TABLE_TYPE = 'VIEW'"
        );
        $stmt->execute([$this->loader->getDatabase(), $this->loader->getDatabase()]);
        while (false !== ($view = $stmt->fetchColumn())) {
            if (!$this->loader->shouldBeIgnored($view)) {
                $this->addOperation("DROP VIEW IF EXISTS $view;");
            }
        }
        return $sql;
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->description = 'Creating views...';
        parent::__destruct();
    }
}


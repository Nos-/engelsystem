<?php

namespace Engelsystem\Database\Migration;

use Engelsystem\Application;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Migrate
{
    const UP = 'up';
    const DOWN = 'down';

    /** @var Application */
    protected $app;

    /** @var SchemaBuilder */
    protected $scheme;

    /** @var callable */
    protected $output;

    /** @var string */
    protected $table = 'migrations';

    /**
     * Migrate constructor
     *
     * @param SchemaBuilder $scheme
     * @param Application   $app
     */
    public function __construct(SchemaBuilder $scheme, Application $app)
    {
        $this->app = $app;
        $this->scheme = $scheme;
        $this->output = function () { };
    }

    /**
     * Run a migration
     *
     * @param string $path
     * @param string $type (up|down)
     * @param bool   $oneStep
     */
    public function run($path, $type = self::UP, $oneStep = false)
    {
        $this->initMigration();
        $migrations = $this->getMigrations($path);
        $migrated = $this->getMigrated();

        if ($type == self::DOWN) {
            $migrations = array_reverse($migrations, true);
        }

        foreach ($migrations as $file => $migration) {
            if (
                ($type == self::UP && $migrated->contains('migration', $migration))
                || ($type == self::DOWN && !$migrated->contains('migration', $migration))
            ) {
                call_user_func($this->output, 'Skipping ' . $migration);
                continue;
            }

            call_user_func($this->output, 'Migrating ' . $migration . ' (' . $type . ')');

            $this->migrate($file, $migration, $type);
            $this->setMigrated($migration, $type);

            if ($oneStep) {
                return;
            }
        }
    }

    /**
     * Get all migrated migrations
     *
     * @return Collection
     */
    protected function getMigrated()
    {
        return $this->getTableQuery()->get();
    }

    /**
     * Migrate a migration
     *
     * @param string $file
     * @param string $migration
     * @param string $type (up|down)
     */
    protected function migrate($file, $migration, $type = self::UP)
    {
        require_once $file;

        $className = Str::studly(preg_replace('/\d+_/', '', $migration));
        /** @var Migration $class */
        $class = $this->app->make($className);

        if (method_exists($class, $type)) {
            $class->{$type}();
        }
    }

    /**
     * Set a migration to migrated
     *
     * @param string $migration
     * @param string $type (up|down)
     */
    protected function setMigrated($migration, $type = self::UP)
    {
        $table = $this->getTableQuery();

        if ($type == self::DOWN) {
            $table->where(['migration' => $migration])->delete();
            return;
        }

        $table->insert(['migration' => $migration]);
    }

    /**
     * Get a list of migration files
     *
     * @param string $dir
     * @return array
     */
    protected function getMigrations($dir)
    {
        $files = $this->getMigrationFiles($dir);

        $migrations = [];
        foreach ($files as $dir) {
            $name = str_replace('.php', '', basename($dir));
            $migrations[$dir] = $name;
        }

        asort($migrations);
        return $migrations;
    }

    /**
     * List all migration files from the given directory
     *
     * @param string $dir
     * @return array
     */
    protected function getMigrationFiles($dir)
    {
        return glob($dir . '/*_*.php');
    }

    /**
     * Setup migration tables
     */
    protected function initMigration()
    {
        if ($this->scheme->hasTable($this->table)) {
            return;
        }

        $this->scheme->create($this->table, function (Blueprint $table) {
            $table->increments('id');
            $table->string('migration');
        });
    }

    /**
     * Init a table query
     *
     * @return Builder
     */
    protected function getTableQuery()
    {
        return $this->scheme->getConnection()->table($this->table);
    }

    /**
     * Set the output function
     *
     * @param callable $output
     */
    public function setOutput(callable $output)
    {
        $this->output = $output;
    }
}

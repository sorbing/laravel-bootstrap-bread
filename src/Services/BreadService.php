<?php

namespace Sorbing\Bread\Services;

class BreadService
{
    /**
     * Render a Blade template from string with data
     * @param $string
     * @param array $data
     * @return string
     * @throws \Symfony\Component\Debug\Exception\FatalThrowableError
     */
    public static function renderBlade($string, array $data = [])
    {
        $php = \Blade::compileString($string);

        $obLevel = ob_get_level();
        ob_start();
        extract($data, EXTR_SKIP);

        try {
            eval('?' . '>' . $php);
        } catch (\Exception $e) {
            while (ob_get_level() > $obLevel) ob_end_clean();
            throw $e;
        } catch (\Throwable $e) {
            while (ob_get_level() > $obLevel) ob_end_clean();
            throw new \Symfony\Component\Debug\Exception\FatalThrowableError($e);
        }

        return ob_get_clean();
    }

    /**
     * Make a new db query from Model or DB::table($table)
     * @param string $table
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public static function makeDatabaseQueryInstance(string $table)
    {
        $model = self::detectModelByTable($table);

        if ($model) {
            /** @var $query \Illuminate\Database\Query\Builder */
            $query = $model->newQuery();
        } else {
            /** @var $query \Illuminate\Database\Eloquent\Builder */
            $query = \DB::table($table);
        }

        return $query;
    }

    /**
     * @param string $table A table name
     * @return null|\Illuminate\Database\Eloquent\Model
     */
    public static function detectModelByTable(string $table)
    {
        $model = null;
        if (class_exists($modelClass = '\\App\\' . studly_case(str_singular($table)))) {
            $model = new $modelClass; // @note Maybe $modelClass::newModelInstance() ?
        } elseif (class_exists($modelClass = '\\App\\Models\\' . studly_case(str_singular($table)))) {
            $model = new $modelClass; // @note Maybe $modelClass::newModelInstance() ?
        }

        return $model;
    }

    public static function loadOptionsRegistryForIdentificationColumns(array $columns)
    {
        $optionsRegistry = [];

        $identificationColumns = array_filter($columns, function($column) {
            return strpos($column, '_id');
        });

        foreach ($identificationColumns as $identificationColumn) {
            $options = null;
            try {
                $options = self::loadOptions($identificationColumn);
            } catch (\Exception $e) {
                /* @note Silent Mode */
                \Log::warning(sprintf('%s in %s:%s', $e->getMessage(), $e->getFile(), $e->getLine()));
            }

            // @note $options->toArray()  OR  $options->pluck('name', 'id')->all()

            $optionsRegistry[$identificationColumn] = $options;
        }

        return $optionsRegistry;
    }

    /**
     * @param string $identificationColumn
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection|null
     * @example Try getting collection with options list (id, name|title) step by step:
     * 1. scopeFetchOptions()
     * 2. MyModel::fetchOptions() or MyModel::getOptions()
     * 3. DB::table(...)
     */
    public static function loadOptions(string $identificationColumn)
    {
        $singular = preg_replace('/_id$/', '', $identificationColumn);
        $table = str_plural($singular); // Singular example: str_singular()
        $query = self::makeDatabaseQueryInstance($table);

        //$query = \DB::table($table); // @note For debug only

        $options = null;

        if ($query instanceof \Illuminate\Database\Query\Builder) {
            $options = self::getTableOptions($table);
        } else if ($query instanceof \Illuminate\Database\Eloquent\Builder) {
            try { $options = $query->fetchOptions(); } catch (\Exception $e) { /* @note Silent Mode... */ }

            if (!$options && method_exists($query->getModel(), 'fetchOptions')) $options = $query->getModel()->fetchOptions();
            if (!$options && method_exists($query->getModel(), 'getOptions')) $options = $query->getModel()->getOptions();

            if (!$options) $options = self::getTableOptions($query->getModel()->getTable());
        }

        if ($options && $options instanceof \Illuminate\Support\Collection && method_exists($options, 'keyBy')) {
            $options = $options->keyBy('id'); // @note For usage $options->get(1)
        }

        return $options;
    }

    /**
     * @param string $table
     * @return null|\Illuminate\Support\Collection
     */
    public static function getTableOptions(string $table)
    {
        // @todo Cache

        $tableColumns = self::getTableColumns($table);
        $colName = in_array('name', $tableColumns) ? 'name' : (in_array('title', $tableColumns) ? 'title' : null);

        if (!$colName) return null;

        $query = \DB::table($table);
        $options = $query->select(['id', $colName])->get();

        return $options;
    }

    public static function getTableColumns($table)
    {
        // @todo Cache

        return \Schema::getColumnListing($table);
    }
}
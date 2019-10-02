<?php

namespace Sorbing\Bread\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
//use function GuzzleHttp\Psr7\parse_query;

trait BreadControllerTrait
{
    /** Database table name */
    protected function breadTable()
    {
        return $this->breadTable;
    }

    /** Blade layout path, eq. `admin.layouts.layout` */
    protected function breadLayout()
    {
        return $this->breadLayout;
    }

    /** Browse & Form title text */
    protected function breadTitle()
    {
        if (!empty($this->breadTitle)) {
            return $this->breadTitle;
        }

        return ucfirst($this->breadTable());
    }

    protected function breadRouteNamePrefix()
    {
        if (!empty($this->breadRouteNamePrefix)) {
            return $this->breadRouteNamePrefix;
        }

        $name = request()->route()->getName();

        return substr($name, 0, strrpos($name, '.'));
    }

    protected function breadPerPage()
    {
        $defaultPerPage = request('per_page', 20);
        $defaultPerPage = $defaultPerPage <= 50 ? $defaultPerPage : 50;
        return isset($this->breadPerPage) ? $this->breadPerPage : $defaultPerPage;
    }

    /**
     * Common database query for get rows/models
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    protected function breadQuery()
    {
        $table = $this->breadTable();
        $query = app('bread')->makeDatabaseQueryInstance($table);

        return $query;
    }

    /**
     * Trying find & make Model in \App or \App\Models namespaces
     * @return null|\Illuminate\Database\Eloquent\Model
     */
    protected function breadDetectModel()
    {
        $table = $this->breadTable();
        $model = app('bread')->detectModelByTable($table);
        return $model;
    }

    /**
     * Get humanized/readable singular Entity (Table, Model)
     * @return string
     */
    protected function breadReadableSingularEntityName()
    {
        $readableName = str_singular($this->breadTable());

        $model = $this->breadDetectModel();

        if ($model && $model instanceof \Illuminate\Database\Eloquent\Model) {
            $modelClass = get_class($model);
            $path = explode('\\', $modelClass);
            $readableName = array_pop($path);

            if (defined("$modelClass::READABLE_NAME") && !empty($modelClass::READABLE_NAME)) {
                $readableName = $modelClass::READABLE_NAME;
            }
        }

        $readableName = ucfirst($readableName);

        return $readableName;
    }

    /**
     * Query for Browse route (table page)
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    protected function breadQueryBrowse()
    {
        return $this->breadQuery();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder|\Eloquent $query
     * @param array $filters Optional
     * @return \Illuminate\Database\Eloquent\Builder|\Eloquent
     * @throws \Exception
     */
    protected function breadQueryBrowseFiltered($query, array $filters = [])
    {
        if (!count($filters)) {
            $filters = $this->breadGetCurrentBrowseFilters();
        }

        foreach ($filters as $key => $val) {
            if (strpos($key, '__') !== false) {
                $relationName = explode('__', $key)[0];
                $relationColumn = explode('__', $key)[1];

                if (mb_strpos($val, '!') === 0) {
                    $val = trim($val, '!');
                    $query = $query->whereDoesntHave($relationName, function($q) use ($relationColumn, $val) {
                        $this->breadHookApplyWhereQuery($q, $relationColumn, $val);
                    });
                } else {
                    $query = $query->whereHas($relationName, function($q) use ($relationColumn, $val) {
                        $this->breadHookApplyWhereQuery($q, $relationColumn, $val);
                    });
                }
            } else {
                $this->breadHookApplyWhereQuery($query, $key, $val);
            }
        }

        if ($order = request()->input('order')) {
            $direction = strpos($order, '-') === false ? 'asc' : 'desc';
            $query->orderBy(trim($order, '-'), $direction);
        }

        return $query;
    }

    // @todo Move to BreadService and ModelSmartFiltersTrait

    /**
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Eloquent $query
     * @param string $column
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Eloquent
     * @throws \Exception
     */
    protected function breadHookApplyWhereQuery($query, $column, $value)
    {
        if (mb_strpos($value, '~') === 0) {
            $value = trim($value, '~');
            $query->where($column, 'LIKE', "%$value%");
        } elseif (mb_strpos($value, '!~') === 0) {
            $value = trim($value, '!~');
            $query->where($column, 'NOT LIKE', "%$value%");
        } elseif ($value == 'null' || $value == 'NULL') {
            $query->whereNull($column);
        } elseif (preg_match('/^(?:!|not)\s?null$/iu', $value)) {
            $query->whereNotNull($column);
        } elseif (mb_strpos($value, '*') === 0 && mb_strrpos($value, '*')+1 === mb_strlen($value)) { // @note Символ `%` нельзя передать в URL без urlencode
            $value = trim($value, '*');
            $query->where($column, 'LIKE', "%$value%");
        } elseif (mb_strpos($value, '*') === 0) {
            $value = trim($value, '*');
            $query->where($column, 'LIKE', "%$value");
        } elseif (mb_strrpos($value, '*')+1 === mb_strlen($value)) {
            $value = trim($value, '*');
            $query->where($column, 'LIKE', "$value%");
        } else if (preg_match('/^([!=<>]{1,2})(.+)$/', $value, $m)) {
            $operation = ($m[1] == '!') ? '!=' : $m[1];
            $value = $m[2];
            $query->where($column, $operation, $value);
        } else {
            $query->where($column, '=', $value);
        }

        // @todo How to implement it?
        /*} elseif ($value == 'distinct' || $value == 'unique') {
            throw new \Exception('Smart where query option `distinct` or `unique` not implemented!');
            //$query->distinct();
            //$query->addSelect("DISTINCT $column");*/

        return $query;
    }

    protected function breadGetCurrentBrowseFilters()
    {
        $exceptFilters = ['page', 'per_page', 'order'];
        $queryParams = request()->except($exceptFilters);

        // @note Using `QUERY_STRING` because Laravel replaced the `.` special chars to `_` for GET parameter name.
        //$queryParams = parse_query($_SERVER['QUERY_STRING']);

        // @note Function `parse_str()` also replaced the `.` for GET parameter name
        //parse_str(@$_SERVER['QUERY_STRING'], $queryParams);
        //$queryParams = array_diff_key($queryParams, array_flip($exceptFilters));

        // @note Guzzle function `parse_query()` not replaced a `.` char for GET parameter name
        // $queryParams = \GuzzleHttp\Psr7\parse_query()

        $filters = [];
        foreach ($queryParams as $key => $val) {
            if (preg_match('/^[a-z][a-z_\d]+$/', $key) && mb_strlen($val)) {
                $filters[$key] = $val;
            }
        }

        return $filters;
    }

    /**
     * Columns settings only (keyed array). It method only configured all available columns (not displayed all it's columns).
     * @return array Schema: ['column' => ['name' => 'Column Name', 'width' => '120', '']]
     */
    protected function breadColumnsSettings()
    {
        $columns = !empty($this->breadColumns) ? $this->breadColumns : \Schema::getColumnListing($this->breadTable());

        $columns = array_flip($columns);
        $columns = array_fill_keys(array_keys($columns), []); // @note Fill `array` as default value instead index

        if (key_exists('created_at', $columns) && $columns['created_at'] == []) {
            $columns['created_at'] = ['name'  => 'Created At', 'width' => '100'];
        }

        if (key_exists('updated_at', $columns) && $columns['updated_at'] == []) {
            $columns['updated_at'] = ['name'  => 'Updated At', 'width' => '100'];
        }

        // @note Examples:
        // 'title'       => 'The tip/hint text'
        // 'align'       => 'left',
        // 'prepare'     => '{{$id}} {{$key}} {{ $value }} {{ $item->someProp }} {{ $item->someMethod() }} {{route('admin.<entity>.show', $id)}}', // @note Blade or PHP template.
        // 'template'    => '<?php foreach() ($value as $el) { /* some action */ }', // @note Blade or PHP template.
        // 'transformer' => 'card:thumbnail,value,href(),firstCachedImage',
        // 'isEditable'  => true, # - ?

        // @note Use the `__` delimiter for correctly used filtering by relation__field, ex. `roles__name` (for users bread table); `tags__id` for post tags..
        // 'labels__name' => ['name' => 'Labels', 'width' => 170, 'template' => '{{ implode(', ' $item->labels->pluck('name')->all()) }}'

        return $columns;
    }

    protected function breadColumnsSettingsBrowse()
    {
        return array_only($this->breadColumnsSettings(), $this->breadColumnsDisplayingBrowse());
    }

    /**
     * Plain array list of the displaying columns for Browse and Form
     * @return array
     */
    protected function breadColumnsDisplayingDefault(): array
    {
        $defaultColumns = [];
        foreach ($this->breadColumnsSettings() as $key => $columnSettings) {
            if (!data_get($columnSettings, 'hide')) {
                $defaultColumns[] = $key;
            }
        }

        return $defaultColumns;
    }

    /**
     * Plain array list of the displaying columns for Browse only
     * @return array
     */
    protected function breadColumnsDisplayingBrowse()
    {
        $columns = request('_columns');

        if (!$columns) {
            // @todo Нужно брать колонки из `breadColumnsSettingsBrowse`, но этот метод может циклично запросить список отображаемых колонок
            return $this->breadColumnsDisplayingDefault();
        }

        if (!is_array($columns)) {
            $columns = explode(',', $columns);
        }

        return $columns;
    }

    protected function breadQueryForm()
    {
        return $this->breadQuery();
    }

    protected function breadColumnsSettingsForm()
    {
        return array_diff_key($this->breadColumnsSettings(), array_flip(['id', 'created_at', 'updated_at']));
    }

    protected function breadActionsBrowse()
    {
        return [
            /*[ // @todo Возможность указать имя в качестве ключа массива?
                'name' => 'Button name',
                'title' => 'Action title attribute value',
                'action' => function($item) {
                    return route('some.route.name', $item->id);
                }
            ]*/
        ];
    }

    protected function breadMassActionsBrowse()
    {
        $defaultMassActions = $this->breadMassActionsBrowseDefault();
        return $defaultMassActions;
    }

    protected function breadMassActionsBrowseDefault()
    {
        $prefix = $this->breadRouteNamePrefix();
        $exportParams = array_merge(['_export' => 'csv'], request()->all());

        return [
            ['name' => 'Export CSV', 'action' => route("$prefix.index"), 'method' => 'GET', 'params' => $exportParams, 'attrs' => ['target' => '_blank']],
            ['name' => 'Delete',     'action' => route("$prefix.destroy", 0), 'method' => 'DELETE'],
        ];
    }

    protected function breadPresetFiltersBrowseDefault()
    {
        return [
            'All' => ['query' => ''],
            'Per 50 rows' => ['query' => 'per_page=50'],
        ];
    }

    protected function breadPresetFiltersBrowseAdvanced()
    {
        return [];
    }

    protected function breadPresetFiltersBrowse()
    {
        $presets = array_merge(
            $this->breadPresetFiltersBrowseDefault(),
            $this->breadPresetFiltersBrowseAdvanced()
        );

        return $presets;
    }

    protected function breadEmptyBrowseContent()
    {
        $content = ""; // Or use view: $content = view('', [])->render();
        return $content;
    }

    /**
     * @param \Eloquent|null $query
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     *
     * @todo Move to service `bread_csv_exporter`
     */
    protected function breadExport($query = null)
    {
        // Нужно ли получать $query неявно?
        //$query = $query ?: $this->breadQueryBrowseFiltered($this->breadQueryBrowse());

        if (request('_export') == 'csv') {
            if (request('id')) {
                // @todo Отфильтровать по id
                echo "<pre>"; print_r('IDs'); echo "</pre>"; exit;
                //$query->whereId(array_wrap(request('id')))
            }

            /** @var \Illuminate\Support\Collection $collection */
            $collection = $query->get();

            /** @var \Illuminate\Database\Eloquent\Model|array|null $first */
            $first = $collection->first();
            // @todo Use a $this->breadColumnsBrowse()
            $columns = ($first instanceof \Illuminate\Database\Eloquent\Model) ? array_keys($first->getAttributes()) : array_keys((array)$first);

            // @todo Добавить в вывод самой таблицы ссылку на последнего поставщика (1688.com)
            // @todo Добавить в вывод самой таблицы ссылку на draft.supply[domain=sawius.com.ua]
            // @todo Отображать columns[0].transformer=img как превью с ссылкой
            $csv = implode("\t", $columns)."\n";
            foreach ($collection as $item) {
                $itemArray = $columns = ($first instanceof \Illuminate\Database\Eloquent\Model) ? $item->toArray() : (array)$item;

                foreach ($itemArray as $key => $val) {
                    // @todo Как экспортнуть description в CSV
                    if (str_contains($val, '"') || str_contains($val, "\n")) {
                        $itemArray[$key] = '"'.str_replace('"', '""', $val).'"';
                    }
                }

                $line = implode("\t", $itemArray) . "\n";
                $csv .= $line;
            }

            //return response($csv, 200, ["Content-Type" => "text/plain"]);
            return response($csv, 200, ["Content-Type" => "text/csv"]);
        }

        die("Not implemented! Allow export to CSV only.");
    }

    /** Browse a resources list */
    public function index()
    {
        $query = $this->breadQueryBrowse();
        $this->breadQueryBrowseFiltered($query);

        $displayingColumns = $this->breadColumnsDisplayingBrowse();

        $optionsRegistry = app('bread')->loadOptionsRegistryForIdentificationColumns($displayingColumns);

        if (request('_export')) {
            return $this->breadExport($query);
        }

        $paginator = $query->paginate($this->breadPerPage());
        // @see http://qaru.site/questions/414474/limit-amount-of-links-shown-with-laravel-pagination

        $data = [
            'paginator' => $paginator,
            'title' => $this->breadTitle(),
            'layout' => $this->breadLayout(),
            'prefix' => $this->breadRouteNamePrefix(),
            'columns' => $displayingColumns,
            'columns_settings' => $this->breadColumnsSettingsBrowse(),
            'options_registry' => $optionsRegistry,
            'actions' => $this->breadActionsBrowse(),
            'mass_actions' => $this->breadMassActionsBrowse(),
            'empty_content' => $this->breadEmptyBrowseContent(),
            'preset_filters' => $this->breadPresetFiltersBrowse(),
        ];

        //echo "<pre>"; print_r('End'); echo "</pre>"; exit;

        return view('bread::browse', $data);
    }

    /** Form for creating a new resource */
    public function create()
    {
        $data = [
            'title' => $this->breadTitle(),
            'layout' => $this->breadLayout(),
            'prefix' => $this->breadRouteNamePrefix(),
            'columns' => $this->breadColumnsSettingsForm(),
            'item' => []
        ];

        return view('bread::form', $data);
    }

    /** Store a newly created resource in storage */
    public function store()
    {
        $data = request()->except(['id', 'created_at', 'updated_at', '_token', '_method', '_prev_index_url']);

        $query = $this->breadQuery();

        if ($query instanceof \Illuminate\Database\Query\Builder) {
            $id = $query->insertGetId($data);
        } else if ($query instanceof \Illuminate\Database\Eloquent\Builder) {
            $id = $query->create($data)->id; // @note For a eloquent events works
        }

        $defaultUrl = url()->route($this->breadRouteNamePrefix().'.index');
        $prevIndexUrl = request('_prev_index_url', $defaultUrl); // back_or()
        return redirect($prevIndexUrl)->with('success', "Resource #$id stored.");
    }

    /** Display the specified resource */
    public function show() {}

    /**
     * Form for editing the resource
     * @param int $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(int $id)
    {
        $item = $this->breadQueryForm()->where('id', $id)->first(); // @note Not use `firstOrFail()`. Error: "Method \Illuminate\Database\Query\Builder::firstOrFail does not exist"

        abort_unless($item, 404); // Not Found

        $data = [
            'title' => $this->breadTitle(),
            'layout' => $this->breadLayout(),
            'prefix' => $this->breadRouteNamePrefix(),
            'columns' => $this->breadColumnsSettingsForm(),
            'id' => $id,
            'item' => $item
        ];

        return view('bread::form', $data);
    }

    /**
     * Update the resource in storage
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(int $id)
    {
        return $this->breadUpdate($id);
    }

    /**
     * It provides the ability to easily extend the update action
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function breadUpdate(int $id)
    {
        $data = request()->except(['id', 'created_at', 'updated_at', '_token', '_method', '_prev_index_url']);

        $query = $this->breadQuery();
        if ($query instanceof \Illuminate\Database\Eloquent\Model) { // @note $query is \App\Models\SomeModel
            //$query->findOrFail($id)->update();
            $isSuccess = $query->whereId($id)->update($data);
            //$isSuccess = (new \App\Models\Product)->where('id', $id)->update($data);
        } elseif ($query instanceof \Illuminate\Database\Query\Builder) {
            $int = $query->where('id', $id)->update($data);
        } else if ($query instanceof \Illuminate\Database\Eloquent\Builder) {
            $isSuccess = $query->find($id)->fill($data)->save(); // @note For a eloquent events works
        }

        $defaultUrl = url()->route($this->breadRouteNamePrefix().'.index');
        $prevIndexUrl = request('_prev_index_url', $defaultUrl); // back_or()

        $readableName = $this->breadReadableSingularEntityName();

        // @todo Check $isSuccess and handle cases
        // @todo Метод модели `Product::READABLE_NAME = 'Товар'`

        return redirect($prevIndexUrl)->with('success', sprintf('%s #%s updated.', $readableName, $id)); // Resource
    }

    /**
     * Remove the resource(s) from storage by ID, IDs, previous query filters
     * @param int|mixed $mix ID or other
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($mix = null)
    {
        return $this->breadBatchActionHandle(function($query, $browseRedirectResponse) {
            /** @var \Eloquent|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query */
            /** @var \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse $browseRedirectResponse */

            //$affectedCount = $query->delete(); // @note Not works a Model::events()!

            $affectedCount = 0;
            foreach ($query->get() as $item) {
                $isSuccess = $item->delete();
                if ($isSuccess) {
                    $affectedCount++;
                } else {
                    \Log::warning(sprintf('Failed deleted the item: %s', print_r($item->toArray(), true)));
                }
            }

            return $browseRedirectResponse->with('success', sprintf('Deleted %s items.', $affectedCount));
        });
    }

    protected function breadBatchActionHandle(callable $callbackQueryHandler)
    {
        try {
            $previousUrl = \URL::previous();

            $query = $this->breadQueryBatch();
            $count = $query->count();

            if ($count >= 10) { // $this->breadPerPage()
                $confirmedRedirectUrl = str_replace('&_confirmed_batch_action', '', $previousUrl);
                $confirmedRedirectUrl = $confirmedRedirectUrl . '&_confirmed_batch_action';// . http_build_query(['']);
                //echo "<pre>"; print_r(request()->toArray()); echo "</pre>"; exit;
                if (!strpos($previousUrl, '_confirmed_batch_action')) {
                    return redirect($confirmedRedirectUrl)->with(['warning' => sprintf('Batch action trying affected on %s items. Please, repeat this action for confirmed.', $count)]);
                }
            }

            $browseRedirectUrl = str_replace('_confirmed_batch_action', '', $previousUrl);
            $browseRedirectUrl = trim($browseRedirectUrl, '&');
            $browseRedirectResponse = redirect($browseRedirectUrl)->with('success', sprintf('Affected %s items.', $count));

            $callbackHttpResponse = $callbackQueryHandler($query, $browseRedirectResponse);

            if (!($callbackHttpResponse instanceof \Symfony\Component\HttpFoundation\Response)) {
                return $browseRedirectResponse->with(['success' => '', 'error' => sprintf('Method %s expected returns as \Symfony\Component\HttpFoundation\Response, but %s given.', __METHOD__, get_class($callbackHttpResponse))]);
            }

            return $callbackHttpResponse;

        } catch (\Exception $e) {
            return back()->with('warning', sprintf('%s', $e->getMessage()));
        }
    }

    /**
     * @param int|mixed|null $id
     * @return \Eloquent|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    protected function breadQueryBatch()
    {
        $id = array_last(request()->segments()); // @note Instead the $id argument

        //$perPage = $this->breadPerPage();
        $query = $this->breadQueryBrowse();

        if ($id > 0) {
            // @note Batch Action for one item
            $query = $query->where('id', $id);
        } else if ($ids = array_wrap(request('id'))) {
            // @note Batch Action for items by IDs
            $query = $query->whereIn('id', $ids);
            //throw_if($query->count() > $perPage, new \Exception('Maybe something went wrong, because the Batch Action is performed for more IDs than on the page!'));
        } else {
            // @note Batch Action for items by previous filtered query
            $filters = $this->breadFiltersFromPreviousPage();
            $query = $this->breadQueryBrowseFiltered($query, $filters);
        }

        return $query;
    }

    protected function breadFiltersFromPreviousPage(): array
    {
        $previousUrl = \URL::previous();
        $previousQuery = parse_url($previousUrl, PHP_URL_QUERY);

        //$filters = array_except(\GuzzleHttp\Psr7\parse_query($previousQuery), ['order', 'page', 'per_page']);

        parse_str($previousQuery, $filters);
        $filters = array_except($filters, ['order', 'page', 'per_page']);
        $filters = array_filter($filters, function($val, $field) {
            return strpos($field, '_') !== 0 && mb_strlen($val);
        }, ARRAY_FILTER_USE_BOTH);

        return $filters;
    }

}
<?php

namespace Sorbing\Bread\Controllers;

use Illuminate\Http\Request;

trait BreadControllerTrait
{
    protected function breadTable()
    {
        return $this->breadTable;
    }

    protected function breadLayout()
    {
        return $this->breadLayout;
    }

    protected function breadTitle()
    {
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

    /** @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder */
    protected function breadQuery()
    {
        $table = $this->breadTable();
        $model = $this->breadDetectModel();

        if ($model) {
            $query = $model;
        } else {
            $query = \DB::table($table);
        }

        return $query;
    }

    /**
     * @return null|\Illuminate\Database\Eloquent\Model
     */
    protected function breadDetectModel()
    {
        $table = $this->breadTable();

        $model = null;
        if (class_exists($modelClass = '\\App\\' . studly_case(str_singular($table)))) {
            $model = new $modelClass; // @note Maybe $modelClass::newModelInstance() ?
        } elseif (class_exists($modelClass = '\\App\\Models\\' . studly_case(str_singular($table)))) {
            $model = new $modelClass; // @note Maybe $modelClass::newModelInstance() ?
        }

        return $model;
    }

    protected function breadQueryBrowse()
    {
        return $this->breadQuery();
    }

    /**
     * @param \Eloquent $query
     * @return \Eloquent
     */
    protected function breadQueryBrowseFiltered($query)
    {
        $filters = $this->breadGetCurrentBrowseFilters();

        foreach ($filters as $key => $val) {
            if (strpos($val, '~') === 0) {
                $val = trim($val, '~');
                $query->where($key, 'LIKE', "%$val%");
            } elseif (str_contains($val, ['null'])) {
                if (str_contains($val, ['not', '!'])) {
                    $query->whereNotNull($key);
                } else {
                    $query->whereNull($key);
                }
            } elseif (str_contains($val, ['distinct', 'unique'])) {
                // @todo How to implement it?
                //$query->distinct();
                //$query->addSelect("DISTINCT $key");
            } elseif (str_contains($val, ['%', '*'])) {
                $query->where($key, 'LIKE', str_replace('*', '%', $val));
            } else {
                $operation = '=';
                if (preg_match('/^([!=<>]+)(.+)$/', $val, $match)) {
                    $operation = $match[1];
                    $val = $match[2];
                }

                $query->where($key, $operation, $val);
            }
        }

        if ($order = request()->input('order')) {
            $direction = strpos($order, '-') === false ? 'asc' : 'desc';
            $query->orderBy(trim($order, '-'), $direction);
        }

        return $query;
    }

    protected function breadGetCurrentBrowseFilters()
    {
        $exceptFilters = ['page', 'per_page', 'order'];

        $filters = [];
        foreach (request()->except($exceptFilters) as $key => $val) {
            if (preg_match('/^[a-z][a-z_]+$/', $key) && !empty($val)) {
                $filters[$key] = $val;
            }
        }

        return $filters;
    }

    protected function breadColumns()
    {
        $columns = !empty($this->breadColumns) ? $this->breadColumns : \Schema::getColumnListing($this->breadTable());

        $columns = array_flip($columns);

        return $columns;
    }

    protected function breadColumnsSettingsBrowse()
    {
        return $this->breadColumns();
    }

    protected function breadColumnsDefaultBrowse(): array
    {
        //$defaultColumns = array_keys($this->breadColumnsSettingsBrowse());
        $defaultColumns = [];
        foreach ($this->breadColumnsSettingsBrowse() as $key => $columnSettings) {
            if (!data_get($columnSettings, 'hide')) {
                $defaultColumns[] = $key;
            }
        }

        return $defaultColumns;
    }

    protected function breadColumnsDisplayingBrowse()
    {
        if (!$columns = request('_columns')) {
            return $this->breadColumnsDefaultBrowse();
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
        return array_diff_key($this->breadColumns(), array_flip(['id', 'created_at', 'updated_at']));
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
        /*return [
            ['name' => 'Button name', 'title' => 'Hint on button', 'action' => route('admin.users.action_name')],
        ];*/
    }

    protected function breadMassActionsBrowseDefault()
    {
        $prefix = $this->breadRouteNamePrefix();
        $exportParams = array_merge(['_export' => 'csv'], request()->all());

        return [
            ['name' => 'Delete', 'action' => route("$prefix.destroy", 0), 'method' => 'DELETE'],
            ['name' => 'Export CSV', 'action' => route("$prefix.index"), 'method' => 'GET', 'params' => $exportParams, 'attrs' => ['target' => '_blank']],
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
            'columns' => $this->breadColumnsDisplayingBrowse(),
            'columns_settings' => $this->breadColumnsSettingsBrowse(),
            'actions' => $this->breadActionsBrowse(),
            'mass_actions' => $this->breadMassActionsBrowse(),
            'empty_content' => $this->breadEmptyBrowseContent(),
            'preset_filters' => $this->breadPresetFiltersBrowse(),
        ];

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

    public function breadReadableSingularEntityName()
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
     * Remove the resource from storage
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $id = 0)
    {
        $deletedCount = 0;
        $perPage = $this->breadPerPage();

        if ($id > 0) {
            $deletedCount = $this->breadQuery()->where('id', $id)->delete();
        } else if ($ids = array_wrap(request('id'))) {
            if (count($ids) && count($ids) <= $perPage) {
                $query = $this->breadQuery()->whereIn('id', $ids);
                if ($query->count() <= $perPage) {
                    $deletedCount = $query->delete();
                }
            }
        }

        return back()->with('success', "Deleted $deletedCount items.");
    }
}
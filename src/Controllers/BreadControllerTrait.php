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

    /**
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    protected function breadQuery()
    {
        $table = $this->breadTable();
        $query = \DB::table($table);

        return $query;
    }

    protected function breadQueryBrowse()
    {
        return $this->breadQuery();
    }

    /**
     * @param \Eloquent $query
     */
    protected function breadQueryBrowseFiltered($query)
    {
        $exceptFilters = ['page', 'per_page', 'order'];
        foreach (request()->all() as $key => $val) if (!in_array($key, $exceptFilters) && preg_match('/^[a-z_]+$/', $key) && !empty($val)) {
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
                if (preg_match('/^([=<>]+)(.+)$/', $val, $match)) {
                    $operation = $match[1];
                    $val = $match[2];
                }

                $query->where($key, $operation, $val);
            }
        }

//        \DB::enableQueryLog();
//        $query->get('name');
//        echo "<pre>"; print_r(\DB::getQueryLog()); echo "</pre>"; exit;

        if ($order = request()->input('order')) {
            $direction = strpos($order, '-') === false ? 'asc' : 'desc';
            $query->orderBy(trim($order, '-'), $direction);
        }
    }

    protected function breadQueryForm()
    {
        return $this->breadQuery();
    }

    protected function breadColumns()
    {
        $columns = !empty($this->breadColumns) ? $this->breadColumns : \Schema::getColumnListing($this->breadTable());

        $columns = array_flip($columns);

        return $columns;
    }

    protected function breadColumnsBrowse()
    {
        return $this->breadColumns();
    }

    protected function breadColumnsForm()
    {
        return array_diff_key($this->breadColumns(), array_flip(['id', 'created_at', 'updated_at']));
    }

    protected function breadActionsBrowse()
    {
        return [
            /*[ // @todo Возвожность указать имя в качестве ключа массива?
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
        return [ /*['name' => '', 'title' => '', 'action' => route('admin.users.action_name')],*/ ];
    }

    protected function breadPresetFiltersBrowse()
    {
        return [
            'All' => ['query' => ''],
        ];
    }

    protected function breadEmptyBrowseContent()
    {
        $content = ""; // Or use view: $content = view('', [])->render();
        return $content;
    }

    /** Browse a resources list */
    public function index()
    {
        $query = $this->breadQueryBrowse();
        $this->breadQueryBrowseFiltered($query);
        $paginator = $query->paginate($this->breadPerPage());
        // @see http://qaru.site/questions/414474/limit-amount-of-links-shown-with-laravel-pagination

        $data = [
            'paginator' => $paginator,
            'title' => $this->breadTitle(),
            'layout' => $this->breadLayout(),
            'prefix' => $this->breadRouteNamePrefix(),
            'columns' => $this->breadColumnsBrowse(),
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
            'columns' => $this->breadColumnsForm(),
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
        $item = $this->breadQueryForm()->where('id', $id)->first();

        $data = [
            'title' => $this->breadTitle(),
            'layout' => $this->breadLayout(),
            'prefix' => $this->breadRouteNamePrefix(),
            'columns' => $this->breadColumnsForm(),
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
        if ($query instanceof \Illuminate\Database\Query\Builder) {
            $int = $query->where('id', $id)->update($data);
        } else if ($query instanceof \Illuminate\Database\Eloquent\Builder) {
            $success = $query->find($id)->fill($data)->save(); // @note For a eloquent events works
        }

        $defaultUrl = url()->route($this->breadRouteNamePrefix().'.index');
        $prevIndexUrl = request('_prev_index_url', $defaultUrl); // back_or()
        return redirect($prevIndexUrl)->with('success', "Resource #$id updated.");
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
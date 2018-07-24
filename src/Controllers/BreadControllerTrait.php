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
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     */
    protected function breadQueryBrowseFiltered($query)
    {
        if ($order = request()->input('order')) {
            $direction = strpos($order, '-') === false ? 'asc' : 'desc';
            $query->orderBy(trim($order, '-'), $direction);
        }

        foreach (request()->all() as $key => $val) if ($key != 'order' && preg_match('/^[a-z_]+$/', $key)) {
            $operation = '=';
            $value = $val;
            if (preg_match('/^([=<>]+)(.+)$/', $val, $match)) {
                $operation = $match[1];
                $value = $match[2];
            }

            $query->where($key, $operation, $value);
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
            /*'Action Name' => function($item) {
                return route('some.route.name', $item->id);
            }*/
        ];
    }

    /** Browse a resources list */
    public function index()
    {
        $query = $this->breadQueryBrowse();
        $this->breadQueryBrowseFiltered($query);
        $collection = $query->get();

        $data = [
            'title' => $this->breadTitle(),
            'layout' => $this->breadLayout(),
            'prefix' => $this->breadRouteNamePrefix(),
            'columns' => $this->breadColumnsBrowse(),
            'actions' => $this->breadActionsBrowse(),
            'query' => $query,
            'collection' => $collection,
        ];

        // @see sorbing/laravel-bootstrap-bread/src/views/browse.blade.php
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
        $data = request()->except(['id', 'created_at', 'updated_at', '_token']);
        $this->breadQuery()->insert($data);

        return redirect()->route($this->breadRouteNamePrefix().'.index')->with('success', 'Resource stored');
    }

    /** Display the specified resource */
    public function show() {}

    /** Form for editing the resource */
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

    /** Update the resource in storage */
    public function update(int $id)
    {
        $data = request()->except(['id', 'created_at', 'updated_at', '_token', '_method']);
        $this->breadQuery()->where('id', $id)->update($data);

        return redirect()->route($this->breadRouteNamePrefix().'.index')->with('success', 'Resource updated');
    }

    /** Remove the resource from storage */
    public function destroy(int $id)
    {
        $this->breadQuery()->delete($id);
        return redirect()->back()->with('success', "Resource #$id deleted");
        //return redirect()->route($this->breadRouteNamePrefix().'.index')->with('success', "Resource #$id deleted");
    }
}
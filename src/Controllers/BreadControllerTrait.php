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

    protected function breadQueryForm()
    {
        return $this->breadQuery();
    }

    protected function breadColumns()
    {
        $columns = !empty($this->breadColumns) ? $this->breadColumns : \Schema::getColumnListing($this->breadTable());

        return $columns;
    }

    protected function breadColumnsBrowse()
    {
        return $this->breadColumns();
    }

    protected function breadColumnsCreate()
    {
        return array_diff($this->breadColumns(), ['id', 'created_at', 'updated_at']);
    }

    protected function breadTransformersBrowse()
    {
        return [
            'field_url' => 'link',
            'entity.img' => 'img',
        ];
    }

    protected function breadColumnsSettings()
    {
        return [
            'some_column_name' => [
                'type' => 'select',
                'options' => function($item = null) {
                    return [
                        ['id' => '', 'name' => '']
                    ];
                }
            ]
        ];
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
        $data = [
            'title' => $this->breadTitle(),
            'layout' => $this->breadLayout(),
            'prefix' => $this->breadRouteNamePrefix(),
            'columns' => $this->breadColumnsBrowse(),
            'transformers' => $this->breadTransformersBrowse(),
            'actions' => $this->breadActionsBrowse(),
            'collection' => $this->breadQueryBrowse()->get(),
        ];

        return view('bread::browse', $data);
    }

    /** Form for creating a new resource */
    public function create()
    {
        $data = [
            'layout' => $this->breadLayout(),
            'prefix' => $this->breadRouteNamePrefix(),
            'columns' => $this->breadColumnsCreate(),
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
    public function show()
    {
        //
    }

    /** Form for editing the resource */
    public function edit(int $id)
    {
        $item = $this->breadQuery()->where('id', $id)->first();

        $data = [
            'layout' => $this->breadLayout(),
            'prefix' => $this->breadRouteNamePrefix(),
            'columns' => $this->breadColumns(),
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
        //
    }
}
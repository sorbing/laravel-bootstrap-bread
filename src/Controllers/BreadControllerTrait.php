<?php

namespace Sorbing\Bread\Controllers;

use Illuminate\Http\Request;

trait BreadControllerTrait
{
    protected $breadPerPage = 20;

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
     * @param \Eloquent $query
     */
    protected function breadQueryBrowseFiltered($query)
    {
        foreach (request()->all() as $key => $val) if (!in_array($key, ['page', 'order']) && preg_match('/^[a-z_]+$/', $key) && !empty($val)) {
            if (str_contains($val, ['null'])) {
                if (str_contains($val, ['not', '!'])) {
                    $query->whereNotNull($key);
                } else {
                    $query->whereNull($key);
                }
            } else {
                $operation = '=';
                if (preg_match('/^([=<>]+)(.+)$/', $val, $match)) {
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
            /*'Button name' => function($item) {
                return route('some.route.name', $item->id);
            }*/
        ];
    }

    protected function breadMassActionsBrowse()
    {
        return [
            // [...]
        ];
    }

    /** Browse a resources list */
    public function index()
    {
        $query = $this->breadQueryBrowse();
        $this->breadQueryBrowseFiltered($query);
        $paginator = $query->paginate($this->breadPerPage);
        // @see http://qaru.site/questions/414474/limit-amount-of-links-shown-with-laravel-pagination

        $data = [
            'paginator' => $paginator,
            'title' => $this->breadTitle(),
            'layout' => $this->breadLayout(),
            'prefix' => $this->breadRouteNamePrefix(),
            'columns' => $this->breadColumnsBrowse(),
            'actions' => $this->breadActionsBrowse(),
            'mass_actions' => $this->breadMassActionsBrowse(),
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
    public function destroy(int $id = 0)
    {
        if ($id > 0) {
            $deletedCount = $this->breadQuery()->delete($id);
        } else if ($ids = array_wrap(request('id'))) {
            $deletedCount = $this->breadQuery()->whereIn('id', $ids)->delete();
        }

        return back()->with('success', "Deleted $deletedCount items.");
    }
}
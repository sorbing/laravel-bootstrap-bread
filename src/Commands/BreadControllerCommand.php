<?php

namespace Sorbing\Bread\Commands;

use Illuminate\Console\Command;

class BreadControllerCommand extends Command
{
    protected $signature = 'bread:controller
                            {table : Database table name}
                            {name  : Controller class name, ex. "Admin\SomeName"}
                            {--layout=  : Layout template path}
                            {--extends= : Controller extends class name}';

    protected $description = 'Generate a custom BREAD Controller';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $table = $this->argument('table');
        $name  = $this->argument('name');
        $class = "\App\Http\Controllers\\" . ltrim($name, '\\');

        $path = str_replace('\\', '/', ltrim($class, '\\')) . '.php';
        $path = base_path($path);
        $path = str_replace('/App', '/app', $path);

        $layout  = $this->option('layout') ?: 'admin.layout.layout';
        $extends = $this->option('extends') ?: 'AdminController';

        $source = $this->generateSource($class, $table, $layout, $extends);
        file_put_contents($path, $source);
    }

    protected function generateSource($class, $table, $layout, $extends = null)
    {
        $ns = substr($class, 0, strrpos($class, '\\'));
        $className = substr($class, strrpos($class, '\\') + 1);

        $columns = \DB::getSchemaBuilder()->getColumnListing($table);
        $fields = array_map(function($column) {
            return sprintf("            '%s' => ['name' => '%s'],\n", $column,  str_replace('_', ' ', ucfirst($column)));
        }, $columns);
        $fields = rtrim(implode('', $fields), "\n");

        $source = $this->getTemplate();
        $source = str_replace('%NAMESPACE%', ltrim($ns, '\\'), $source);
        $source = str_replace('%CLASS_NAME%', $className, $source);
        $source = str_replace('%TABLE%', $table, $source);
        $source = str_replace('%LAYOUT%', $layout, $source);
        $source = str_replace('%EXTENDS%', ($extends ? "extends $extends" : ''), $source);
        $source = str_replace('%FIELDS%', $fields, $source);

        return $source;
    }

    protected function getTemplate()
    {
$template = <<<EOD
<?php

namespace %NAMESPACE%;

use Illuminate\Http\Request;

class %CLASS_NAME% %EXTENDS%
{
    use \Sorbing\Bread\Controllers\BreadControllerTrait;

    protected \$breadTable  = '%TABLE%';
    protected \$breadLayout = '%LAYOUT%';

    protected function breadTitle() {
        return "%TABLE% list";
    }

//    protected function breadQueryBrowse()
//    {
//        return CustomModel::query();
//    }

//    protected function breadQueryForm()
//    {
//        return parent::breadQueryForm(); // OR
//        return CustomModel::query();     // OR
//    }

    protected function breadColumnsBrowse()
    {
        return [
%FIELDS%
        ];
    }
}

EOD;

    return $template;
    }
}

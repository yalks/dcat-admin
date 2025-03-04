<?php

namespace Dcat\Admin;

use Closure;
use Dcat\Admin\Grid\Column;
use Dcat\Admin\Grid\Displayers;
use Dcat\Admin\Grid\Model;
use Dcat\Admin\Grid\Responsive;
use Dcat\Admin\Grid\Row;
use Dcat\Admin\Grid\Tools;
use Dcat\Admin\Grid\Concerns;
use Dcat\Admin\Contracts\Repository;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Traits\HasBuilderEvents;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Grid
{
    use HasBuilderEvents,
        Concerns\HasElementNames,
        Concerns\HasFilter,
        Concerns\HasTools,
        Concerns\HasActions,
        Concerns\HasPaginator,
        Concerns\HasExporter,
        Concerns\HasMultipleHeader,
        Concerns\HasQuickSearch,
        Macroable {
            __call as macroCall;
        }

    /**
     * The grid data model instance.
     *
     * @var \Dcat\Admin\Grid\Model
     */
    protected $model;

    /**
     * Collection of all grid columns.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $columns;

    /**
     * Collection of all data rows.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $rows;

    /**
     * Rows callable fucntion.
     *
     * @var \Closure[]
     */
    protected $rowsCallback = [];

    /**
     * All column names of the grid.
     *
     * @var array
     */
    protected $columnNames = [];

    /**
     * Grid builder.
     *
     * @var \Closure
     */
    protected $builder;

    /**
     * Mark if the grid is built.
     *
     * @var bool
     */
    protected $built = false;

    /**
     * All variables in grid view.
     *
     * @var array
     */
    protected $variables = [];

    /**
     * Resource path of the grid.
     *
     * @var
     */
    protected $resourcePath;

    /**
     * Default primary key name.
     *
     * @var string
     */
    protected $keyName = 'id';

    /**
     * View for grid to render.
     *
     * @var string
     */
    protected $view = 'admin::grid.table';

    /**
     * @var Closure
     */
    protected $header;

    /**
     * @var Closure
     */
    protected $footer;

    /**
     * @var Closure
     */
    protected $wrapper;

    /**
     * @var Responsive
     */
    protected $responsive;

    /**
     * @var bool
     */
    protected $addNumberColumn = false;

    /**
     * @var string
     */
    protected $tableId;

    /**
     * Options for grid.
     *
     * @var array
     */
    protected $options = [
        'show_pagination'        => true,
        'show_filter'            => true,
        'show_actions'           => true,
        'show_quick_edit_button' => false,
        'show_edit_button'       => true,
        'show_view_button'       => true,
        'show_delete_button'     => true,
        'show_row_selector'      => true,
        'show_create_btn'        => true,
        'show_quick_create_btn'  => false,
        'show_bordered'          => false,
        'show_toolbar'           => true,

        'row_selector_style'      => 'primary',
        'row_selector_circle'     => true,
        'row_selector_clicktr'    => false,
        'row_selector_label_key' => null,
        'row_selector_bg'         => 'var(--20)',

        'show_exporter'             => false,
        'show_export_all'           => true,
        'show_export_current_page'  => true,
        'show_export_selected_rows' => true,
        'export_limit'              => 50000,

        'dialog_form_area'   => ['700px', '670px'],
        'table_header_style' => 'table-header-gray',

    ];

    /**
     * Create a new grid instance.
     *
     * Grid constructor.
     * @param Repository|null $repository
     * @param null $builder
     */
    public function __construct(?Repository $repository = null, ?\Closure $builder = null)
    {
        if ($repository) {
            $this->keyName = $repository->getKeyName();
        }
        $this->model    = new Model($repository);
        $this->columns  = new Collection();
        $this->rows     = new Collection();
        $this->builder  = $builder;
        $this->tableId  = 'grid-'.Str::random(8);

        $this->model()->setGrid($this);

        $this->setupTools();
        $this->setupFilter();

        $this->callResolving();
    }

    /**
     * Get table ID.
     *
     * @return string
     */
    public function getTableId()
    {
        return $this->tableId;
    }


    /**
     * Get primary key name of model.
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->keyName ?: 'id';
    }

    /**
     * Set primary key name.
     *
     * @param string $name
     */
    public function setKeyName(string $name)
    {
        $this->keyName = $name;
    }

    /**
     * Add column to Grid.
     *
     * @param string $name
     * @param string $label
     *
     * @return Column|Collection
     */
    public function column($name, $label = '')
    {
        if (strpos($name, '.') !== false) {
            list($relationName, $relationColumn) = explode('.', $name);

            $label = empty($label) ? admin_trans_field($relationColumn) : $label;

            $name = Str::snake($relationName).'.'.$relationColumn;
        }

        $column = $this->addColumn($name, $label);

        return $column;
    }

    /**
     * Add number column.
     *
     * @param null|string $label
     * @return Column
     */
    public function number(?string $label = null)
    {
        return $this->addColumn('#', $label ?: '#')->bold();
    }

    /**
     * Batch add column to grid.
     *
     * @example
     * 1.$grid->columns(['name' => 'Name', 'email' => 'Email' ...]);
     * 2.$grid->columns('name', 'email' ...)
     *
     * @param array $columns
     *
     * @return null
     */
    public function columns($columns = [])
    {
        if (func_num_args() == 0) {
            return $this->columns;
        }

        if (func_num_args() == 1 && is_array($columns)) {
            foreach ($columns as $column => $label) {
                $this->column($column, $label);
            }

            return;
        }

        foreach (func_get_args() as $column) {
            $this->column($column);
        }
    }

    /**
     * @return Collection
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Add column to grid.
     *
     * @param string $field
     * @param string $label
     *
     * @return Column
     */
    protected function addColumn($field = '', $label = '')
    {
        $column = new Column($field, $label);
        $column->setGrid($this);

        $this->columns->put($field, $column);

        return $column;
    }

    /**
     * Get Grid model.
     *
     * @return Model
     */
    public function model()
    {
        return $this->model;
    }

    /**
     * @return array
     */
    public function getColumnNames()
    {
        return $this->columnNames;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setRowSelectorOptions(array $options = [])
    {
        if (isset($options['style'])) {
            $this->options['row_selector_style'] = $options['style'];
        }
        if (isset($options['circle'])) {
            $this->options['row_selector_circle'] = $options['circle'];
        }
        if (isset($options['clicktr'])) {
            $this->options['row_selector_clicktr'] = $options['clicktr'];
        }
        if (isset($options['label'])) {
            $this->options['row_selector_label_key'] = $options['label_name'];
        }
        if (isset($options['bg'])) {
            $this->options['row_selector_bg'] = $options['bg'];
        }

        return $this;
    }

    /**
     * Prepend checkbox column for grid.
     *
     * @return void
     */
    protected function prependRowSelectorColumn()
    {
        if (!$this->options['show_row_selector']) {
            return;
        }

        $circle = $this->options['row_selector_circle'] ? 'checkbox-circle' : '';

        $column = new Column(
            Column::SELECT_COLUMN_NAME,
            <<<HTML
<div class="checkbox checkbox-{$this->options['row_selector_style']} $circle checkbox-grid">
    <input type="checkbox" class="select-all {$this->getSelectAllName()}"><label></label>
</div>
HTML
        );
        $column->setGrid($this);

        $column->displayUsing(Displayers\RowSelector::class);

        $this->columns->prepend($column, Column::SELECT_COLUMN_NAME);
    }

    /**
     * Apply column filter to grid query.
     */
    protected function applyColumnFilter()
    {
        $this->columns->each->bindFilterQuery($this->model());
    }

    /**
     * Build the grid.
     *
     * @return void
     */
    public function build()
    {
        if ($this->built) {
            return;
        }

        $collection = $this->processFilter(false);

        $data = $collection->toArray();

        $this->prependRowSelectorColumn();
        $this->appendActionsColumn();

        Column::setOriginalGridModels($collection);

        $this->columns->map(function (Column $column) use (&$data) {
            $column->fill($data);

            $this->columnNames[] = $column->getName();
        });

        $this->buildRows($data);

        $this->built = true;

        if ($data && $this->responsive) {
            $this->responsive->build();
        }

        $this->sortHeaders();
    }

    /**
     * Build the grid rows.
     *
     * @param array $data
     *
     * @return void
     */
    protected function buildRows(array $data)
    {
        $this->rows = collect($data)->map(function ($model) {
            return new Row($this, $model);
        });

        if ($this->rowsCallback) {
            foreach ($this->rowsCallback as $value) {
                $this->rows->map($value);
            }
        }
    }

    /**
     * Set grid row callback function.
     *
     * @param Closure $callable
     *
     * @return Collection|null
     */
    public function rows(Closure $callable = null)
    {
        if (is_null($callable)) {
            return $this->rows;
        }

        $this->rowsCallback[] = $callable;
    }

    /**
     * Get create url.
     *
     * @return string
     */
    public function getCreateUrl()
    {
        $queryString = '';

        if ($constraints = $this->model()->getConstraints()) {
            $queryString = http_build_query($constraints);
        }

        return sprintf('%s/create%s',
            $this->getResource(),
            $queryString ? ('?'.$queryString) : ''
        );
    }

    /**
     * @param string $width
     * @param string $height
     * @return $this
     */
    public function setModalFormDimensions(string $width, string $height)
    {
        $this->options['dialog_form_area'] = [$width, $height];

        return $this;
    }

    /**
     * Render create button for grid.
     *
     * @return string
     */
    public function renderCreateButton()
    {
        if (!$this->options['show_create_btn'] && !$this->options['show_quick_create_btn']) {
            return '';
        }

        return (new Tools\CreateButton($this))->render();
    }

    /**
     * @return $this
     */
    public function withBorder()
    {
        $this->options['show_bordered'] = true;

        return $this;
    }

    /**
     * Set grid header.
     *
     * @param Closure|string|Renderable $content
     *
     * @return $this|Closure
     */
    public function header($content = null)
    {
        if (!$content) {
            return $this->header;
        }
        $this->header = $content;
        return $this;
    }

    /**
     * Render grid header.
     *
     * @return string
     */
    public function renderHeader()
    {
        if (!$this->header) {
            return '';
        }

        $content = Helper::render($this->header, [$this->processFilter(false)]);

        if (empty($content)) {
            return '';
        }

        if ($content instanceof Renderable) {
            $content = $content->render();
        }

        if ($content instanceof Htmlable) {
            $content = $content->toHtml();
        }

        return <<<HTML
<div class="box-header clearfix" style="border-top:1px solid #ebeff2">{$content}</div>
HTML;
    }

    /**
     * Set grid footer.
     *
     * @param Closure|string|Renderable $content
     *
     * @return $this|Closure
     */
    public function footer($content = null)
    {
        if (!$content) {
            return $this->footer;
        }

        $this->footer = $content;

        return $this;
    }

    /**
     * Render grid footer.
     *
     * @return string
     */
    public function renderFooter()
    {
        if (!$this->footer) {
            return '';
        }

        $content = Helper::render($this->footer, [$this->processFilter(false)]);

        if (empty($content)) {
            return '';
        }

        if ($content instanceof Renderable) {
            $content = $content->render();
        }

        if ($content instanceof Htmlable) {
            $content = $content->toHtml();
        }

        return <<<HTML
    <div class="box-footer clearfix">{$content}</div>
HTML;
    }

    /**
     * Get or set option for grid.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this|mixed
     */
    public function option($key, $value = null)
    {
        if (is_null($value)) {
            return $this->options[$key];
        }

        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Disable row selector.
     *
     * @return $this
     */
    public function disableRowSelector(bool $disable = true)
    {
        $this->tools->disableBatchActions($disable);

        return $this->option('show_row_selector', !$disable);
    }

    /**
     * Show row selector.
     *
     * @return $this
     */
    public function showRowSelector(bool $val = true)
    {
        return $this->disableRowSelector(!$val);
    }

    /**
     * Remove create button on grid.
     *
     * @return $this
     */
    public function disableCreateButton(bool $disable = true)
    {
        return $this->option('show_create_btn', !$disable);
    }

    /**
     * Show create button.
     *
     * @return $this
     */
    public function showCreateButton(bool $val = true)
    {
        return $this->disableCreateButton(!$val);
    }

    /**
     * @param bool $disable
     * @return $this
     */
    public function disableQuickCreateButton(bool $disable = true)
    {
        return $this->option('show_quick_create_btn', !$disable);
    }

    /**
     * @param bool $val
     * @return $this
     */
    public function showQuickCreateButton(bool $val = true)
    {
        return $this->disableQuickCreateButton(!$val);
    }

    /**
     * If allow creation.
     *
     * @return bool
     */
    public function allowCreateBtn()
    {
        return $this->options['show_create_btn'];
    }

    /**
     * If grid show quick create button.
     *
     * @return bool
     */
    public function allowQuickCreateBtn()
    {
        return $this->options['show_quick_create_btn'];
    }

    /**
     * Set resource path.
     *
     * @param string $path
     * @return $this
     */
    public function resource(string $path)
    {
        if (!empty($path)) {
            $this->resourcePath = admin_url($path);
        }
        return $this;
    }

    /**
     * Get resource path.
     *
     * @return string
     */
    public function getResource()
    {
        return $this->resourcePath ?: (
            $this->resourcePath = url(app('request')->getPathInfo())
        );
    }

    /**
     * Create a grid instance.
     *
     * @param mixed ...$params
     * @return $this
     */
    public static function make(...$params)
    {
        return new static(...$params);
    }

    /**
     * Enable responsive tables.
     * @see https://github.com/nadangergeo/RWD-Table-Patterns
     *
     * @return Responsive
     */
    public function responsive()
    {
        if (!$this->responsive) {
            $this->responsive = new Responsive($this);
        }

        return $this->responsive;
    }

    /**
     * @return bool
     */
    public function allowResponsive()
    {
        return $this->responsive ? true : false;
    }

    /**
     * @param Closure $closure
     * @return $this;
     */
    public function wrap(\Closure $closure)
    {
        $this->wrapper = $closure;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasWrapper()
    {
        return $this->wrapper ? true : false;
    }

    /**
     * Add variables to grid view.
     *
     * @param array $variables
     *
     * @return $this
     */
    public function with($variables = [])
    {
        $this->variables = $variables;

        return $this;
    }

    /**
     * Get all variables will used in grid view.
     *
     * @return array
     */
    protected function variables()
    {
        $this->variables['grid'] = $this;
        $this->variables['tableId'] = $this->tableId;

        return $this->variables;
    }

    /**
     * Set a view to render.
     *
     * @param string $view
     * @param array  $variables
     */
    public function setView($view, $variables = [])
    {
        if (!empty($variables)) {
            $this->with($variables);
        }

        $this->view = $view;
    }

    /**
     * Set grid title.
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->variables['title'] = $title;

        return $this;
    }

    /**
     * Set grid description.
     *
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->variables['description'] = $description;

        return $this;
    }

    /**
     * Set resource path for grid.
     *
     * @param string $path
     *
     * @return $this
     */
    public function setResource($path)
    {
        $this->resourcePath = $path;

        return $this;
    }

    /**
     * Get the string contents of the grid view.
     *
     * @return string
     */
    public function render()
    {
        $this->handleExportRequest(true);

        try {
            $this->callComposing();

            $this->build();
        } catch (\Throwable $e) {
            return Admin::makeExceptionHandler()->renderException($e);
        }

        return $this->doWrap();
    }

    /**
     * @return string
     */
    protected function doWrap()
    {
        $view = view($this->view, $this->variables());

        if (!$wrapper = $this->wrapper) {
            return $view->render();
        }

        return $wrapper($view);
    }

    /**
     * Add column to grid.
     *
     * @param string $name
     * @return Column|Collection
     */
    public function __get($name)
    {
        return $this->addColumn($name);
    }

    /**
     * Dynamically add columns to the grid view.
     *
     * @param $method
     * @param $arguments
     *
     * @return Column
     */
    public function __call($method, $arguments)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $arguments);
        }

        return $this->addColumn($method, $arguments[0] ?? null);
    }

}

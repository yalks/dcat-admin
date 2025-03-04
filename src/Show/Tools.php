<?php

namespace Dcat\Admin\Show;

use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Tools implements Renderable
{
    /**
     * The panel that holds this tool.
     *
     * @var Panel
     */
    protected $panel;

    /**
     * @var string
     */
    protected $resource;

    /**
     * Default tools.
     *
     * @var array
     */
    protected $tools = ['delete', 'edit', 'list'];

    /**
     * Tools should be appends to default tools.
     *
     * @var Collection
     */
    protected $appends;

    /**
     * Tools should be prepends to default tools.
     *
     * @var Collection
     */
    protected $prepends;

    /**
     * @var bool
     */
    protected $showList = true;

    /**
     * @var bool
     */
    protected $showDelete = true;

    /**
     * @var bool
     */
    protected $showEdit = true;

    /**
     * @var bool
     */
    protected $showQuickEdit = false;

    /**
     * @var array
     */
    protected $dialogFormDimensions = ['700px', '670px'];

    /**
     * Tools constructor.
     *
     * @param Panel $panel
     */
    public function __construct(Panel $panel)
    {
        $this->panel = $panel;

        $this->appends  = new Collection();
        $this->prepends = new Collection();
    }

    /**
     * Append a tools.
     *
     * @param mixed $tool
     *
     * @return $this
     */
    public function append($tool)
    {
        $this->appends->push($tool);

        return $this;
    }

    /**
     * Prepend a tool.
     *
     * @param mixed $tool
     *
     * @return $this
     */
    public function prepend($tool)
    {
        $this->prepends->push($tool);

        return $this;
    }

    /**
     * Get resource path.
     *
     * @return string
     */
    public function getResource()
    {
        if (is_null($this->resource)) {
            $this->resource = $this->panel->getParent()->getResource();
        }

        return $this->resource;
    }

    /**
     * Disable `list` tool.
     *
     * @return $this
     */
    public function disableList(bool $disable = true)
    {
        $this->showList = !$disable;

        return $this;
    }

    /**
     * Disable `delete` tool.
     *
     * @return $this
     */
    public function disableDelete(bool $disable = true)
    {
        $this->showDelete = !$disable;

        return $this;
    }

    /**
     * Disable `edit` tool.
     *
     * @return $this
     */
    public function disableEdit(bool $disable = true)
    {
        $this->showEdit = !$disable;

        return $this;
    }

    /**
     * @param bool $disable
     * @return $this
     */
    public function disableQuickEdit(bool $disable = true)
    {
        $this->showQuickEdit = !$disable;

        return $this;
    }

    /**
     * @param string $width
     * @param string $height
     * @return $this
     */
    public function showQuickEdit(?string $width = null, ?string $height = null)
    {
        $this->showQuickEdit = true;

        $width && ($this->dialogFormDimensions[0] = $width);
        $height && ($this->dialogFormDimensions[1] = $height);

        return $this;
    }

    /**
     * Get request path for resource list.
     *
     * @return string
     */
    protected function getListPath()
    {
        $url = $this->getResource();

        return url()->isValidUrl($url) ? $url : '/'.trim($url, '/');
    }

    /**
     * Get request path for edit.
     *
     * @return string
     */
    protected function getEditPath()
    {
        $key = $this->panel->getParent()->getId();

        return $this->getListPath().'/'.$key.'/edit';
    }

    /**
     * Get request path for delete.
     *
     * @return string
     */
    protected function getDeletePath()
    {
        $key = $this->panel->getParent()->getId();

        return $this->getListPath().'/'.$key;
    }

    /**
     * Render `list` tool.
     *
     * @return string
     */
    protected function renderList()
    {
        if (!$this->showList) {
            return;
        }

        $list = trans('admin.list');

        return <<<HTML
<div class="btn-group pull-right btn-mini" style="margin-right: 5px">
    <a href="{$this->getListPath()}" class="btn btn-sm btn-default">
        <i class=" ti-view-list-alt"></i><span class="hidden-xs"> {$list}</span>
    </a>
</div>
HTML;
    }

    /**
     * Render `edit` tool.
     *
     * @return string
     */
    protected function renderEdit()
    {
        if (!$this->showQuickEdit && !$this->showEdit) {
            return;
        }

        $edit = trans('admin.edit');
        $url  = $this->getEditPath();

        $quickBtn = $btn = '';

        if ($this->showEdit) {
            $btn = <<<EOF
<a href="{$url}" class="btn btn-sm btn-primary">
        <i class="ti-pencil-alt "></i><span class="hidden-xs"> {$edit}</span>
    </a>
EOF;

        }

        if ($this->showQuickEdit) {
            $id = 'show-edit-'.Str::random(8);
            list($width, $height) = $this->dialogFormDimensions;

            Form::modal($edit)
                ->click(".$id")
                ->dimensions($width, $height)
                ->success('LA.reload()')
                ->render();

            $text = $this->showEdit ? '' : "<span class='hidden-xs'> &nbsp; $edit</span>";

            $quickBtn = "<a data-url='$url' class='btn btn-sm btn-primary {$id}'><i class=' fa fa-clone'></i>$text</a>";
        }

        return <<<HTML
<div class="btn-group pull-right btn-mini" style="margin-right: 5px">{$btn}{$quickBtn}</div>
HTML;
    }

    /**
     * Render `delete` tool.
     *
     * @return string
     */
    protected function renderDelete()
    {
        if (!$this->showDelete) {
            return;
        }

        $delete = trans('admin.delete');

        return <<<HTML
<div class="btn-group pull-right btn-mini" style="margin-right: 5px">
    <a class="btn btn-sm btn-danger " data-action="delete" data-url="{$this->getDeletePath()}" data-redirect="{$this->getListPath()}">
        <i class="ti-trash"></i><span class="hidden-xs">  {$delete}</span>
    </a>
</div>
HTML;
    }

    /**
     * Render custom tools.
     *
     * @param Collection $tools
     *
     * @return mixed
     */
    protected function renderCustomTools($tools)
    {
        return $tools->map([Helper::class, 'render'])->implode(' ');
    }

    /**
     * Render tools.
     *
     * @return string
     */
    public function render()
    {
        $output = $this->renderCustomTools($this->prepends);

        foreach ($this->tools as $tool) {
            $renderMethod = 'render'.ucfirst($tool);
            $output .= $this->$renderMethod();
        }

        return $output.$this->renderCustomTools($this->appends);
    }
}

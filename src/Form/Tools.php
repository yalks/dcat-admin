<?php

namespace Dcat\Admin\Form;

use Dcat\Admin\Admin;
use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;

class Tools implements Renderable
{
    /**
     * @var Builder
     */
    protected $form;

    /**
     * Collection of tools.
     *
     * @var array
     */
    protected $tools = ['delete', 'view', 'list'];

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
    protected $showView = true;

    /**
     * Create a new Tools instance.
     *
     * @param Builder $builder
     */
    public function __construct(Builder $builder)
    {
        $this->form     = $builder;
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
     * Disable `view` tool.
     *
     * @return $this
     */
    public function disableView(bool $disable = true)
    {
        $this->showView = !$disable;

        return $this;
    }

    /**
     * Get request path for resource list.
     *
     * @return string
     */
    protected function getListPath()
    {
        return $this->form->getResource();
    }

    /**
     * Get request path for edit.
     *
     * @return string
     */
    protected function getDeletePath()
    {
        return $this->getViewPath();
    }

    /**
     * Get request path for delete.
     *
     * @return string
     */
    protected function getViewPath()
    {
        $key = $this->form->getResourceId();

        if ($key) {
            return $this->getListPath().'/'.$key;
        } else {
            return $this->getListPath();
        }
    }

    /**
     * Get parent form of tool.
     *
     * @return Builder
     */
    public function form()
    {
        return $this->form;
    }

    /**
     * Render list button.
     *
     * @return string
     */
    protected function renderList()
    {
        if (!$this->showList) {
            return;
        }

        $text = trans('admin.list');

        return <<<EOT
<div class="btn-group pull-right" style="margin-right: 5px">
    <a href="{$this->getListPath()}" class="btn btn-sm btn-default"><i class=" ti-view-list-alt"></i><span class="hidden-xs">&nbsp;$text</span></a>
</div>
EOT;
    }

    /**
     * Render list button.
     *
     * @return string
     */
    protected function renderView()
    {
        if (!$this->showView) {
            return;
        }

        $view = trans('admin.view');

        return <<<HTML
<div class="btn-group pull-right" style="margin-right: 5px">
    <a href="{$this->getViewPath()}" class="btn btn-sm btn-primary">
        <i class="ti-eye"></i><span class="hidden-xs"> {$view}</span>
    </a>
</div>
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
<div class="btn-group pull-right" style="margin-right: 5px">
    <a class="btn btn-sm btn-danger " data-action="delete" data-url="{$this->getDeletePath()}" data-redirect="{$this->getListPath()}">
        <i class="ti-trash"></i><span class="hidden-xs"> {$delete}</span>
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
        if ($this->form->isCreating()) {
            $this->disableView();
            $this->disableDelete();
        }

        if (empty($tools)) {
            return '';
        }

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

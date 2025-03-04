<?php

namespace Dcat\Admin\Grid\Concerns;

use Closure;
use Dcat\Admin\Grid;
use Illuminate\Support\Collection;

trait HasFilter
{
    /**
     * The grid Filter.
     *
     * @var Grid\Filter
     */
    protected $filter;

    /**
     * Setup grid filter.
     *
     * @return void
     */
    protected function setupFilter()
    {
        $this->filter = new Grid\Filter($this->model());
    }

    /**
     * Get filter of Grid.
     *
     * @return Grid\Filter
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Process the grid filter.
     *
     * @param bool $toArray
     *
     * @return array|Collection|mixed
     */
    public function processFilter($toArray = true)
    {
        if ($this->builder) {
            call_user_func($this->builder, $this);
        }

        $this->applyQuickSearch();
        $this->applyColumnFilter();

        return $this->filter->execute($toArray);
    }

    /**
     * Set the grid filter.
     *
     * @param Closure $callback
     * @return $this
     */
    public function filter(Closure $callback)
    {
        call_user_func($callback, $this->filter);

        return $this;
    }

    /**
     * Render the grid filter.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    public function renderFilter()
    {
        if (!$this->options['show_filter']) {
            return '';
        }

        return $this->filter->render();
    }

    /**
     * Expand filter.
     *
     * @return $this
     */
    public function expandFilter()
    {
        $this->filter->expand();

        return $this;
    }

    /**
     * Disable grid filter.
     *
     * @return $this
     */
    public function disableFilter(bool $disable = true)
    {
//        $this->tools->disableFilterButton($disable);
        $this->filter->disableCollapse($disable);

        return $this->option('show_filter', !$disable);
    }

    /**
     * Show grid filter.
     *
     * @param bool $val
     * @return $this
     */
    public function showFilter(bool $val = true)
    {
        return $this->disableFilter(!$val);
    }

    /**
     * Disable filter button.
     *
     * @param bool $disable
     * @return $this
     */
    public function disableFilterButton(bool $disable = true)
    {
        $this->tools->disableFilterButton($disable);

        return $this;
    }

    /**
     * Show filter button.
     *
     * @param bool $val
     * @return $this
     */
    public function showFilterButton(bool $val = true)
    {
        return $this->disableFilterButton(!$val);
    }
}

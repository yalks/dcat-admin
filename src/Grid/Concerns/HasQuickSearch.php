<?php

namespace Dcat\Admin\Grid\Concerns;

use Dcat\Admin\Grid\Column;
use Dcat\Admin\Grid\Tools;
use Illuminate\Support\Collection;
use Dcat\Admin\Grid\Model;
use Illuminate\Support\Str;

/**
 * @property Collection $columns
 * @property Tools $tools
 *
 * @method  Model model()
 */
trait HasQuickSearch
{
    /**
     * @var array|string|\Closure
     */
    protected $search;

    /**
     * @var Tools\QuickSearch
     */
    protected $quickSearch;

    /**
     * @param array|string|\Closure
     *
     * @return Tools\QuickSearch
     */
    public function quickSearch($search = null)
    {
        if (func_num_args() > 1) {
            $this->search = func_get_args();
        } else {
            $this->search = $search;
        }

        if ($this->quickSearch) {
            return $this->quickSearch;
        }

        return tap(new Tools\QuickSearch(), function ($search) {
            $search->setGrid($this);

            $this->quickSearch = $search;
        });
    }

    /**
     * @param string $gridName
     */
    protected function setQuickSearchQueryName($gridName)
    {
        if ($this->quickSearch) {
            $this->quickSearch->setQueryName($gridName.'__search');
        }
    }

    /**
     * @return Tools\QuickSearch
     */
    public function getQuickSearch()
    {
        return $this->quickSearch;
    }

    /**
     * @return \Illuminate\View\View|string
     */
    public function renderQuickSearch()
    {
        if (! $this->quickSearch) {
            return '';
        }

        return $this->quickSearch->render();
    }

    /**
     * Apply the search query to the query.
     *
     * @return mixed|void
     */
    public function applyQuickSearch()
    {
        if (! $this->quickSearch) {
            return;
        }

        if (! $query = request()->get($this->quickSearch->getQueryName())) {
            return;
        }

        if ($this->search instanceof \Closure) {
            return call_user_func($this->search, $this->model(), $query);
        }

        if (is_string($this->search)) {
            $this->search = [$this->search];
        }

        if (is_array($this->search)) {
            foreach ($this->search as $column) {
                $this->addWhereLikeBinding($column, true, '%' . $query . '%');
            }

        } elseif (is_null($this->search)) {
            $this->addWhereBindings($query);

        }

    }

    /**
     * Add where bindings.
     *
     * @param string $query
     */
    protected function addWhereBindings($query)
    {
        $queries = preg_split('/\s(?=([^"]*"[^"]*")*[^"]*$)/', trim($query));
        if (! $queries = $this->parseQueryBindings($queries)) {
            $this->addWhereBasicBinding($this->getKeyName(), false, '=', '___');

            return;
        }

        foreach ($queries as list($column, $condition, $or)) {
            if (preg_match('/(?<not>!?)\((?<values>.+)\)/', $condition, $match) !== 0) {
                $this->addWhereInBinding($column, $or, (bool)$match['not'], $match['values']);
                continue;
            }

            if (preg_match('/\[(?<start>.*?),(?<end>.*?)]/', $condition, $match) !== 0) {
                $this->addWhereBetweenBinding($column, $or, $match['start'], $match['end']);
                continue;
            }

            if (preg_match('/(?<function>date|time|day|month|year),(?<value>.*)/', $condition, $match) !== 0) {
                $this->addWhereDatetimeBinding($column, $or, $match['function'], $match['value']);
                continue;
            }

            if (preg_match('/(?<pattern>%[^%]+%)/', $condition, $match) !== 0) {
                $this->addWhereLikeBinding($column, $or, $match['pattern']);
                continue;
            }

            if (preg_match('/(?<pattern>[^%]+%)/', $condition, $match) !== 0) {
                $this->addWhereLikeBinding($column, $or, $match['pattern']);
                continue;
            }

            if (preg_match('/\/(?<value>.*)\//', $condition, $match) !== 0) {
                $this->addWhereBasicBinding($column, $or, 'REGEXP', $match['value']);
                continue;
            }

            if (preg_match('/(?<operator>>=?|<=?|!=|%){0,1}(?<value>.*)/', $condition, $match) !== 0) {
                $this->addWhereBasicBinding($column, $or, $match['operator'], $match['value']);
                continue;
            }
        }
    }

    /**
     * Parse quick query bindings.
     *
     * @param array $queries
     *
     * @return array
     */
    protected function parseQueryBindings(array $queries)
    {
        $columnMap = $this->columns->mapWithKeys(function (Column $column) {
            $label = $column->getLabel();
            $name = $column->getName();

            return [$label => $name, $name => $name];
        });

        return collect($queries)->map(function ($query) use ($columnMap) {
            $segments = explode(':', $query, 2);
            if (count($segments) != 2) {
                return;
            }

            $or = false;
            list($column, $condition) = $segments;

            if (Str::startsWith($column, '|')) {
                $or = true;
                $column = substr($column, 1);
            }

            $column = $columnMap[$column] ?? null;

            if (!$column) {
                return;
            }

            return [$column, $condition, $or];
        })->filter()->toArray();
    }

    /**
     * Add where like binding to model query.
     *
     * @param string $column
     * @param bool $or
     * @param string $pattern
     */
    protected function addWhereLikeBinding(?string $column, ?bool $or, ?string $pattern)
    {
        $likeOperator = 'like';
        $method = $or ? 'orWhere' : 'where';

        $this->model()->{$method}($column, $likeOperator, $pattern);
    }

    /**
     * Add where date time function binding to model query.
     *
     * @param string $column
     * @param bool $or
     * @param string $function
     * @param string $value
     */
    protected function addWhereDatetimeBinding(?string $column, ?bool $or, ?string $function, ?string $value)
    {
        $method = ($or ? 'orWhere' : 'where') . ucfirst($function);

        $this->model()->$method($column, $value);
    }

    /**
     * Add where in binding to the model query.
     *
     * @param string $column
     * @param bool $or
     * @param bool $not
     * @param string $values
     */
    protected function addWhereInBinding(?string $column, ?bool $or, ?bool $not, ?string $values)
    {
        $values = explode(',', $values);

        foreach ($values as $key => $value) {
            if ($value === 'NULL') {
                $values[$key] = null;
            }
        }

        $where = $or ? 'orWhere' : 'where';
        $method = $where . ($not ? 'NotIn' : 'In');

        $this->model()->$method($column, $values);
    }

    /**
     * Add where between binding to the model query.
     *
     * @param string $column
     * @param bool $or
     * @param string $start
     * @param string $end
     */
    protected function addWhereBetweenBinding(?string $column, ?bool $or, ?string $start, ?string $end)
    {
        $method = $or ? 'orWhereBetween' : 'whereBetween';

        $this->model()->$method($column, [$start, $end]);
    }

    /**
     * Add where basic binding to the model query.
     *
     * @param string $column
     * @param bool $or
     * @param string $operator
     * @param string $value
     */
    protected function addWhereBasicBinding(?string $column, ?bool $or, ?string $operator, ?string $value)
    {
        $method = $or ? 'orWhere' : 'where';
        $operator = $operator ?: '=';
        if ($operator == '%') {
            $operator = 'like';
            $value = "%{$value}%";
        }

        if ($value === 'NULL') {
            $value = null;
        }

        if (Str::startsWith($value, '"') && Str::endsWith($value, '"')) {
            $value = substr($value, 1, -1);
        }

        $this->model()->{$method}($column, $operator, $value);
    }

}

<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Form\Field;
use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Support\Arrayable;

class Radio extends Field
{
    protected $inline = true;

    protected $style = 'primary';

    /**
     * Set options.
     *
     * @param array|\Closure|string $options
     *
     * @return $this
     */
    public function options($options = [])
    {
        if ($options instanceof \Closure) {
            $this->options = $options;

            return $this;
        }

        $this->options = Helper::array($options);

        return $this;
    }

    /**
     * Draw inline radios.
     *
     * @return $this
     */
    public function inline()
    {
        $this->inline = true;

        return $this;
    }

    /**
     * Draw stacked radios.
     *
     * @return $this
     */
    public function stacked()
    {
        $this->inline = false;

        return $this;
    }

    /**
     * "info", "primary", "inverse", "danger", "success", "purple"
     *
     * @param $v
     * @return $this
     */
    public function style($v)
    {
        $this->style = $v;

        return $this;
    }

    /**
     * Set options.
     *
     * @param array|callable|string $values
     *
     * @return $this
     */
    public function values($values)
    {
        return $this->options($values);
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        if ($this->options instanceof \Closure) {
            $this->options(
                $this->options->call($this->getFormModel(), $this->value(), $this)
            );
        }

        $this->addVariables([
            'options' => $this->options,
            'inline' => $this->inline,
            'radioStyle' => $this->style,
        ]);

        return parent::render();
    }
}

<?php

namespace Dcat\Admin\Grid\Actions;

use Dcat\Admin\Form;
use Dcat\Admin\Grid\RowAction;

class QuickEdit extends RowAction
{
    protected static $resolvedWindow;

    /**
     * @return array|null|string
     */
    public function name()
    {
        return __('admin.quick_edit');
    }

    public function render()
    {
        if (!static::$resolvedWindow) {
            static::$resolvedWindow = true;

            list($width, $height) = $this->parent->option('dialog_form_area');

            Form::modal(trans('admin.edit'))
                ->click(".{$this->getElementClass()}")
                ->dimensions($width, $height)
                ->success('LA.reload()')
                ->render();
        }

        $this->setHtmlAttribute([
            'data-url' => "{$this->getResource()}/{$this->getKey()}/edit",
        ]);

        return parent::render(); // TODO: Change the autogenerated stub
    }
}

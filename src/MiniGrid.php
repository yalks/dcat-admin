<?php

namespace Dcat\Admin;

use Dcat\Admin\Layout\Content;
use Dcat\Admin\Repositories\Repository;

class MiniGrid extends Grid
{
    public function __construct(Repository $repository, $builder = null)
    {
        parent::__construct($repository, $builder);

        $this->setName('mini');
        $this->disableCreateButton();
        $this->disableActions();
        $this->disableExporter();
        $this->disableQuickCreateButton();

        $this->option('row_selector_clicktr', true);

        $this->tools->disableBatchActions();
    }

    protected function setupFilter()
    {
        parent::setupFilter();

        $this->disableFilter();
        $this->tools->disableFilterButton();

        $this->filter
            ->withoutInputBorder()
            ->expand()
            ->resetPosition()
            ->hiddenResetButtonText();

        Content::composing(function (Content $content) {
            $content->simple()->prepend($this->filter);
        }, true);
    }
}

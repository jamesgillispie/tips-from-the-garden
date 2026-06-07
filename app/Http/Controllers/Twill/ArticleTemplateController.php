<?php

namespace App\Http\Controllers\Twill;

use A17\Twill\Http\Controllers\Admin\ModuleController as BaseModuleController;
use A17\Twill\Models\Contracts\TwillModelContract;
use A17\Twill\Services\Forms\Fields\Input;
use A17\Twill\Services\Forms\Form;

class ArticleTemplateController extends BaseModuleController
{
    protected $moduleName = 'articleTemplates';

    public function getForm(TwillModelContract $model): Form
    {
        $form = parent::getForm($model);

        $form->add(
            Input::make()->name('title')->label('Template name')
        );

        $form->add(
            Input::make()->name('description')->label('Description')
                ->type('textarea')->rows(3)
                ->note('What kind of memo is this template for?')
        );

        $form->add(
            Input::make()->name('structure_prompt')->label('Structure prompt')
                ->type('textarea')->rows(16)
                ->note('Instructions the writer model follows to structure the article.')
        );

        $form->add(
            Input::make()->name('example_skeleton')->label('Example skeleton')
                ->type('textarea')->rows(10)
                ->note('Optional: a bare-bones example of the desired shape.')
        );

        return $form;
    }
}

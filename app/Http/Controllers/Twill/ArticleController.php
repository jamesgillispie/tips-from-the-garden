<?php

namespace App\Http\Controllers\Twill;

use A17\Twill\Http\Controllers\Admin\ModuleController as BaseModuleController;
use A17\Twill\Models\Contracts\TwillModelContract;
use A17\Twill\Services\Forms\Fields\Input;
use A17\Twill\Services\Forms\Form;
use A17\Twill\Services\Listings\Columns\Text;
use A17\Twill\Services\Listings\TableColumns;

class ArticleController extends BaseModuleController
{
    protected $moduleName = 'articles';

    protected function setUpController(): void
    {
        // Articles are born from the pipeline, not the admin.
        $this->disableCreate();
        $this->setPermalinkBase('a');
    }

    public function getForm(TwillModelContract $model): Form
    {
        $form = parent::getForm($model);

        $form->add(
            Input::make()->name('title')->label('Title')
        );

        $form->add(
            Input::make()->name('body_md')->label('Body (Markdown)')
                ->type('textarea')->rows(24)
        );

        return $form;
    }

    protected function additionalIndexTableColumns(): TableColumns
    {
        $columns = parent::additionalIndexTableColumns();

        $columns->add(
            Text::make()->field('writer')->title('Writer')
        );

        $columns->add(
            Text::make()->field('created_at')->title('Created')
        );

        return $columns;
    }
}

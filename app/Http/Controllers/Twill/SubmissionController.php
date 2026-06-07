<?php

namespace App\Http\Controllers\Twill;

use A17\Twill\Http\Controllers\Admin\ModuleController as BaseModuleController;
use A17\Twill\Models\Contracts\TwillModelContract;
use A17\Twill\Services\Forms\Fields\Input;
use A17\Twill\Services\Forms\Form;
use A17\Twill\Services\Listings\Columns\Text;
use A17\Twill\Services\Listings\TableColumns;

class SubmissionController extends BaseModuleController
{
    protected $moduleName = 'submissions';

    protected $titleColumnKey = 'uuid';

    protected function setUpController(): void
    {
        // Submissions come in through the two doors, never the admin.
        $this->disableCreate();
        $this->disablePublish();
        $this->disableBulkPublish();
    }

    public function getForm(TwillModelContract $model): Form
    {
        $form = parent::getForm($model);

        $form->add(Input::make()->name('status')->label('Status')->disabled());
        $form->add(Input::make()->name('source')->label('Source')->disabled());
        $form->add(Input::make()->name('original_filename')->label('Original file')->disabled());
        $form->add(
            Input::make()->name('error')->label('Error')
                ->type('textarea')->rows(4)->disabled()
        );

        return $form;
    }

    protected function additionalIndexTableColumns(): TableColumns
    {
        $columns = parent::additionalIndexTableColumns();

        $columns->add(Text::make()->field('status')->title('Status'));
        $columns->add(Text::make()->field('source')->title('Source'));
        $columns->add(Text::make()->field('created_at')->title('Received'));

        return $columns;
    }
}

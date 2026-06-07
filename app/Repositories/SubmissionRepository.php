<?php

namespace App\Repositories;

use A17\Twill\Repositories\ModuleRepository;
use App\Models\Submission;

class SubmissionRepository extends ModuleRepository
{
    public function __construct(Submission $model)
    {
        $this->model = $model;
    }
}

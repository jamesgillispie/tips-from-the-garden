<?php

namespace App\Http\Requests\Twill;

use A17\Twill\Http\Requests\Admin\Request;

class ArticleTemplateRequest extends Request
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'structure_prompt' => ['required', 'string'],
        ];
    }
}

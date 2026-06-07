<?php

namespace App\Http\Requests\Twill;

use A17\Twill\Http\Requests\Admin\Request;

class ArticleRequest extends Request
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body_md' => ['required', 'string'],
        ];
    }
}

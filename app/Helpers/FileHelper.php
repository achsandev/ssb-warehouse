<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class FileHelper
{
    public function generateFileName($file)
    {
        return time().Str::random().'.'.$file->getClientOriginalExtension();
    }
}

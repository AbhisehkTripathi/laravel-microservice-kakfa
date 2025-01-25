<?php

// app/Http/Requests/BulkImportRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ];
    }
}
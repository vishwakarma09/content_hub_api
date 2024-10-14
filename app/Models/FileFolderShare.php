<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileFolderShare extends Model
{
    /** @use HasFactory<\Database\Factories\FileFolderShareFactory> */
    use HasFactory;

    protected $fillable = ['file_folder_id', 'user_id'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileMetadata extends Model
{
    /** @use HasFactory<\Database\Factories\FileMetadataFactory> */
    use HasFactory;

    protected $fillable = ['file_folder_id', 'uri', 'mime_type', 'size', 'public_token'];
}

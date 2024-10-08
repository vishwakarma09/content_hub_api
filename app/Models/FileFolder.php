<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

class FileFolder extends Model
{
    use HasFactory, NodeTrait; // This trait comes from the kalnoy/nestedset package

    protected $table = 'files_and_folders';

    protected $fillable = ['name', 'type', 'parent_id', '_lft', '_rgt', 'depth'];
}

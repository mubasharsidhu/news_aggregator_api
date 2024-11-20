<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    // Define the table associated with the model (optional if using plural convention)
    protected $table = 'articles';

    // Specify the attributes that are mass assignable
    protected $fillable = [
        'title',
        'description',
        'content',
        'source',
        'author',
        'image_url',
        'articleUrl',
        'publishedAt',
        'apiSource',
    ];

}
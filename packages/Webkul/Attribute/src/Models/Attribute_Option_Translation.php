<?php

declare (strict_types=1);
namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Attribute\Contracts\Attribute_Option_Translation as AttributeOptionTranslationContract;
class Attribute_Option_Translation extends Model implements Attribute_Option_Translation_Contract
{
    public $timestamps = false;
    protected $fillable = ['label'];
}
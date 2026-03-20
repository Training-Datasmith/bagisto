<?php

declare (strict_types=1);
namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Attribute\Contracts\Attribute_Translation as AttributeTranslationContract;
class Attribute_Translation extends Model implements Attribute_Translation_Contract
{
    public $timestamps = false;
    protected $fillable = ['name'];
}
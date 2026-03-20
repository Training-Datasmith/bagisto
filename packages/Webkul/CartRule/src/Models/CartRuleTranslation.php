<?php

declare (strict_types=1);
namespace Webkul\Cart_Rule\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Cart_Rule\Contracts\Cart_Rule_Translation as CartRuleTranslationContract;
class Cart_Rule_Translation extends Model implements Cart_Rule_Translation_Contract
{
    public $timestamps = false;
    protected $fillable = ['label'];
}
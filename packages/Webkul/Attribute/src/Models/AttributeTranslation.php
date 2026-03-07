<?php

declare(strict_types=1);

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Attribute\Contracts\AttributeTranslation as AttributeTranslationContract;

class AttributeTranslation extends Model implements AttributeTranslationContract
{
    public $timestamps = false;

    protected $fillable = ['name'];
}

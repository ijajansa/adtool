<?php

namespace Tests\Fixtures;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class TenantRecord extends Model
{
    use BelongsToBusiness;

    protected $guarded = [];
}

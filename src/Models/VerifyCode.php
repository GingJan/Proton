<?php

namespace Zjien\Proton\Models;

use Illuminate\Database\Eloquent\Model;

class VerifyCode extends Model
{
    protected $table = 'verify_codes';

    protected $timestamp = false;

    const TYPE_PC = 10;
    const TYPE_PHONE = 20;
    const TYPE_EMAIL = 30;

    const AVAILABLE_TRUE = 1;
    const AVAILABLE_FALSE = 0;
}

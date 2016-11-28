<?php
namespace Zjien\Proton\Contract;

use Illuminate\Http\Request;

interface VerifyCodeInterface
{
    public function generate();

    public function check($input);

    public function abandon($code);
}

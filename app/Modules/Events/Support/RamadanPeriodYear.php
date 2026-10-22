<?php

namespace App\Modules\Events\Support;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;

class RamadanPeriodYear implements Rule, DataAwareRule
{
    private array $data = [];

    public function __construct(private string $yearField)
    {
    }

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    public function passes($attribute, $value)
    {
        return is_string($value) && substr($value, 0, 4) === (string) ($this->data[$this->yearField] ?? '');
    }

    public function message()
    {
        return 'يجب أن يقع التاريخ ضمن السنة الميلادية المحددة.';
    }
}

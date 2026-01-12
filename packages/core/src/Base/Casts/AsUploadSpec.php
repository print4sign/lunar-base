<?php

namespace Lunar\Base\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Lunar\Base\DataTransferObjects\Upload\UploadSpec;

class AsUploadSpec implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @return object|string
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes
        {
            public function get($model, $key, $value, $attributes)
            {
                if (! isset($attributes[$key]) || $attributes[$key] === null) {
                    return null;
                }

                $data = json_decode($attributes[$key], true);

                if (! is_array($data)) {
                    return null;
                }

                return UploadSpec::fromArray($data);
            }

            public function set($model, $key, $value, $attributes)
            {
                if ($value === null) {
                    return [$key => null];
                }

                if ($value instanceof UploadSpec) {
                    return [$key => json_encode($value->toArray())];
                }

                if (is_array($value)) {
                    return [$key => json_encode($value)];
                }

                return [$key => null];
            }
        };
    }
}

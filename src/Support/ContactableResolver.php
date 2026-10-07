<?php

namespace IlBronza\Contacts\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class ContactableResolver
{
    public static function classes() : array
    {
        $classes = [];

        foreach (config('contacts.associableModelTypesFullClass', []) as $class) {
            if (! is_subclass_of($class, Model::class))
                continue;

            $class = method_exists($class, 'getProjectClassName') ? $class::getProjectClassName() : $class;

            if (is_subclass_of($class, Model::class) && method_exists($class, 'contacts'))
                $classes[] = $class;
        }

        return array_values(array_unique($classes));
    }

    public static function resolve(string $type, string $key, bool $forUpdate = false) : Model
    {
        // Resolve only configured models; never instantiate a class supplied by the URL.
        foreach (static::classes() as $class) {
            $placeholder = new $class;

            if ($type !== $class && $type !== $placeholder->getMorphClass())
                continue;

            $model = $class::findOrFail($key);
            static::authorize($model, 'view', 'userCanSee');

            if ($forUpdate)
                static::authorize($model, 'update', 'userCanUpdate');

            return $model;
        }

        abort(404);
    }

    private static function authorize(Model $model, string $ability, string $method) : void
    {
        if (method_exists($model, $method)) {
            abort_unless($model->$method(), 403);
            return;
        }

        Gate::authorize($ability, $model);
    }
}

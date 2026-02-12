<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PlaywrightController
{
    public function login(Request $request): mixed
    {
        $attributes = $request->input('attributes', []);

        if (empty($attributes)) {
            $user = $this->factoryBuilder(
                $this->userClassName(),
                $request->input('state', [])
            )->create();
        } else {
            $user = app($this->userClassName())
                ->newQuery()
                ->where($attributes)
                ->first();

            if (! $user) {
                $user = $this->factoryBuilder(
                    $this->userClassName(),
                    $request->input('state', [])
                )->create($attributes);
            }
        }

        $user->load($request->input('load', []));

        return tap($user, function ($user): void {
            auth()->login($user);

            $user->setHidden([])->setVisible([]);
        });
    }

    public function logout(): void
    {
        auth()->logout();
    }

    public function factory(Request $request): mixed
    {
        return $this->factoryBuilder(
            $request->input('model'),
            $request->input('state', [])
        )
            ->count(intval($request->input('count', 1)))
            ->create($request->input('attributes'))
            ->each(fn ($model) => $model->setHidden([])->setVisible([]))
            ->load($request->input('load', []))
            ->pipe(fn ($collection) => $collection->count() > 1
                ? $collection
                : $collection->first());
    }

    public function update(Request $request): mixed
    {
        $model = $request->input('model');
        $id = $request->input('id');
        $attributes = $request->input('attributes', []);

        $instance = $model::findOrFail($id);
        $instance->update($attributes);

        return tap($instance->fresh(), fn ($model) => $model->setHidden([])->setVisible([]));
    }

    public function csrfToken(): JsonResponse
    {
        return response()->json(csrf_token());
    }

    protected function userClassName(): string
    {
        return config('auth.providers.users.model');
    }

    /**
     * @param  array<int|string, mixed>  $states
     */
    protected function factoryBuilder(string $model, array $states = []): mixed
    {
        $factory = $model::factory();

        $states = Arr::wrap($states);

        foreach ($states as $state => $attributes) {
            if (is_int($state)) {
                $state = $attributes;
                $attributes = [];
            }

            $attributes = Arr::wrap($attributes);

            $factory = $factory->{$state}(...$attributes);
        }

        return $factory;
    }
}

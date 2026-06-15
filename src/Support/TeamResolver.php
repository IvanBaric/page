<?php

namespace IvanBaric\Pages\Support;

final class TeamResolver
{
    public function resolve(): ?int
    {
        $resolver = config('pages.team_resolver');

        if (! is_string($resolver) || ! class_exists($resolver)) {
            $resolver = config('pages.corexis.team_resolver');
        }

        if (! is_string($resolver) || ! class_exists($resolver)) {
            return null;
        }

        $instance = app($resolver);

        foreach (['resolve', 'currentTeamId', 'teamId', 'id'] as $method) {
            if (method_exists($instance, $method)) {
                $value = $instance->{$method}();

                return is_object($value) && isset($value->id) ? (int) $value->id : ($value === null ? null : (int) $value);
            }
        }

        return null;
    }
}

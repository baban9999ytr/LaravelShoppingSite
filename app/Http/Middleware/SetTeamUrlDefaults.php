<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class SetTeamUrlDefaults
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->currentTeam) {
            URL::defaults([
                'current_team' => $user->currentTeam->slug ?? $user->currentTeam->id,
                'team'         => $user->currentTeam->slug ?? $user->currentTeam->id,
            ]);
        } else {
            URL::defaults([
                'current_team' => 'none',
                'team'         => 'none',
            ]);
        }

        return $next($request);
    }
}
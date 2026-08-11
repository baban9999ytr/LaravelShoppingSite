<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\RedirectsToCurrentTeam;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    use RedirectsToCurrentTeam;

    // public function toResponse($request): Response
    // {
    //     return $request->wantsJson()
    //         ? new JsonResponse(['two_factor' => false], 200)
    //         : redirect()->intended($this->redirectPathForCurrentTeam($request, Fortify::redirects('login')));
    // }
    /**
     * Create an HTTP response that represents the given object.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toResponse($request): Response
    {
        return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect()->intended(route('shop.index'));
    }
}

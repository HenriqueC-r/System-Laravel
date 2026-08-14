<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateJwt
{
    public function __construct(private readonly JwtService $jwt) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return $this->unauthorized('Token não informado.');
        }

        try {
            $payload = $this->jwt->decode($token);
            $user = User::find($payload->sub ?? null);
        } catch (Throwable) {
            return $this->unauthorized('Token inválido ou expirado.');
        }

        if (! $user) {
            return $this->unauthorized('Usuário do token não encontrado.');
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'status' => false,
            'mensagem' => $message,
        ], 401);
    }
}

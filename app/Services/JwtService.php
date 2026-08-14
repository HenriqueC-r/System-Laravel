<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;
use stdClass;

class JwtService
{
    public function createFor(User $user): string
    {
        $now = now()->getTimestamp();

        return JWT::encode([
            'iss' => config('app.url'),
            'sub' => (string) $user->getKey(),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + ((int) config('jwt.ttl', 60) * 60),
            'jti' => (string) str()->uuid(),
        ], $this->secret(), config('jwt.algorithm', 'HS256'));
    }

    public function decode(string $token): stdClass
    {
        return JWT::decode(
            $token,
            new Key($this->secret(), config('jwt.algorithm', 'HS256')),
        );
    }

    private function secret(): string
    {
        $secret = (string) config('jwt.secret');

        if ($secret === '') {
            throw new RuntimeException('Configure JWT_SECRET ou APP_KEY antes de emitir tokens.');
        }

        return $secret;
    }
}

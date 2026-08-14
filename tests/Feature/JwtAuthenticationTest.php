<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwtAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login_with_frontend_contract(): void
    {
        $this->postJson('/api/cadastro.php', [
            'nome' => 'Maria Silva',
            'email' => 'maria@example.com',
            'senha' => 'segredo123',
        ])->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('usuario.nome', 'Maria Silva')
            ->assertJsonMissingPath('usuario.password');

        $this->postJson('/api/login.php', [
            'email' => 'maria@example.com',
            'senha' => 'segredo123',
        ])->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['token', 'usuario' => ['id', 'nome', 'email']]);
    }

    public function test_token_allows_profile_and_user_listing(): void
    {
        $user = User::factory()->create(['password' => 'segredo123']);

        $token = $this->postJson('/api/login.php', [
            'email' => $user->email,
            'senha' => 'segredo123',
        ])->json('token');

        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/api/perfil.php', $headers)
            ->assertOk()
            ->assertJsonPath('usuario.email', $user->email);

        $this->getJson('/api/usuarios.php', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'usuarios');
    }

    public function test_protected_routes_reject_missing_or_invalid_token(): void
    {
        $this->getJson('/api/perfil.php')
            ->assertUnauthorized()
            ->assertJsonPath('status', false);

        $this->getJson('/api/perfil.php', ['Authorization' => 'Bearer token-invalido'])
            ->assertUnauthorized()
            ->assertJsonPath('status', false);
    }
}

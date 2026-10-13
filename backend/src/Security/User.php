<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private string $email,
        private string $password,
        private string $name,
        private string $role,
        private int $id = 0,
        private array $profile = [],
    ) {}

    public function getUserIdentifier(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getRoles(): array { return array_unique(['ROLE_USER', $this->role]); }
    public function eraseCredentials(): void {}

    public function toArray(): array
    {
        return ['id' => $this->id, 'email' => $this->email, 'name' => $this->name, 'admin' => $this->role === 'ROLE_ADMIN',
            'department' => $this->profile['department'] ?? '', 'jobTitle' => $this->profile['job_title'] ?? '',
            'bio' => $this->profile['bio'] ?? '', 'readingSize' => $this->profile['reading_size'] ?? 'standard',
            'accent' => $this->profile['accent'] ?? 'indigo',
            'avatarUrl' => ($this->profile['has_avatar'] ?? false) ? '/api/profile/avatar?v='.($this->profile['avatar_version'] ?? 0) : null];
    }
}

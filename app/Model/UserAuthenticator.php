<?php
declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Nette\Security\Identity;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;

final class UserAuthenticator implements Authenticator
{
    public function __construct(
        private Explorer $database,
        private Passwords $passwords,
    ) {}

    public function authenticate(string $username, string $password): SimpleIdentity
    {
        $row = $this->database->table('users')->where('email', mb_strtolower(trim($username)))->fetch();

        if (!$row || !$this->passwords->verify($password, (string) $row->password)) {
            throw new AuthenticationException('Neplatný e-mail nebo heslo.', Authenticator::InvalidCredential);
        }
        if (!(bool) $row->active) {
            throw new AuthenticationException('Účet je deaktivován.', Authenticator::NotApproved);
        }

        if ($this->passwords->needsRehash((string) $row->password)) {
            $row->update(['password' => $this->passwords->hash($password)]);
        }
        $row->update(['lastSignInDate' => new \DateTimeImmutable()]);

        return new SimpleIdentity(
            (int) $row->id,
            [(string) $row->role_id],
            [
                'email' => (string) $row->email,
                'firstName' => (string) ($row->firstName ?? ''),
                'surname' => (string) ($row->surname ?? ''),
                'idFacr' => $row->idFacr !== null ? (int) $row->idFacr : null,
            ],
        );
    }
}

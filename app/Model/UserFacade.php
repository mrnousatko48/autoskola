<?php

declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Security\Passwords;
use Nette\Security\Authenticator;
use Nette\Security\SimpleIdentity;
use Nette\Security\AuthenticationException;

/**
 * Manages user-related operations such as authentication, adding new users, and modifying user details.
 */
final class UserFacade implements Authenticator
{
    // Minimum password length requirement for users
    public const PasswordMinLength = 7;

    // Database table and column names
    private const
        TableName = 'users',
        ColumnId = 'id',
        ColumnUsername = 'username',
        ColumnPasswordHash = 'password',
        ColumnRole = 'role';


    // Dependency injection of database explorer and password utilities
    public function __construct(
        private Nette\Database\Explorer $database,
        private Passwords $passwords,
    ) {
    }

    /**
     * Authenticate a user based on provided credentials.
     * Throws an AuthenticationException if authentication fails.
     */
    public function authenticate(string $username, string $password): SimpleIdentity
    {
        // Fetch the user details from the database by username
        $row = $this->database->table(self::TableName)
            ->where(self::ColumnUsername, $username)
            ->fetch();

        // Authentication checks
        if (!$row) {
            throw new AuthenticationException('The username is incorrect.', self::IdentityNotFound);
        } elseif (!$this->passwords->verify($password, $row[self::ColumnPasswordHash])) {
            throw new AuthenticationException('The password is incorrect.', self::InvalidCredential);
        } elseif ($this->passwords->needsRehash($row[self::ColumnPasswordHash])) {
            $row->update([
                self::ColumnPasswordHash => $this->passwords->hash($password),
            ]);
        }

        // Return user identity without the password hash
        $arr = $row->toArray();
        unset($arr[self::ColumnPasswordHash]);

        return new SimpleIdentity($row[self::ColumnId], $row[self::ColumnRole], $arr);
    }

    /**
     * Get a user by their ID.
     */
    public function getUserById(int $id)
    {
        $user = $this->database
            ->table(self::TableName)
            ->get($id);

        if (!$user) {
            throw new Nette\Application\BadRequestException('User not found');
        }

        return $user;
    }

    /**
     * Change the password of a user.
     */
    public function changePassword(int $userId, string $newPassword): void
    {
        $user = $this->database->table(self::TableName)->get($userId);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $user->update([
            self::ColumnPasswordHash => $this->passwords->hash($newPassword),
        ]);
    }
}

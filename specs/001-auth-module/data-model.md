# Data Model: Authentication Module

## Entities

### User
Represents a registered account.

Fields:
- `name` (string)
- `email` (string, unique)
- `passwordHash` (string)
- `createdAt` (datetime)
- `updatedAt` (datetime)

Validation rules:
- `email` must be unique and valid format.
- `password` must meet minimum strength requirements.

### PasswordResetRequest
Represents a password reset request for a user.

Fields:
- `userId` (reference)
- `token` (string)
- `expiresAt` (datetime)
- `createdAt` (datetime)

Validation rules:
- `token` must be valid and unexpired to reset password.

## Relationships
- `PasswordResetRequest.userId` references `User`.

# User API Module

The User API module provides comprehensive management of user accounts within the application. It handles user registration (via Auth), profile retrieval, and administrative CRUD operations.

---

## Architectural Overview

This module follows the project's standard service-driven architecture:

- **Controller**: [UserController.php](file:///Users/amit/Htdocs/api/app/Http/Controllers/Api/V1/UserController.php) - Handles routing and response formatting.
- **Service**: [UserService.php](file:///Users/amit/Htdocs/api/app/Services/UserService.php) - Manages business logic and database transactions.
- **Model**: [User.php](file:///Users/amit/Htdocs/api/app/Models/User.php) - Encapsulates user data and utilizes UUIDs for secure routing.
- **Resource**: [UserResource.php](file:///Users/amit/Htdocs/api/app/Http/Resources/Api/V1/UserResource.php) - Standardizes the JSON output.

---

## API Endpoints

All endpoints require **Sanctum Authentication** via the `Authorization: Bearer {token}` header.

| Method   | Endpoint               | Description                                        |
| :------- | :--------------------- | :------------------------------------------------- |
| `GET`    | `/api/v1/users`        | List all users with pagination.                    |
| `POST`   | `/api/v1/users`        | Create a new user manually.                        |
| `GET`    | `/api/v1/users/{uuid}` | Retrieve detailed information for a specific user. |
| `PUT`    | `/api/v1/users/{uuid}` | Update an existing user's information.             |
| `DELETE` | `/api/v1/users/{uuid}` | Remove a user account.                             |

---

## Data Structure

### User Object

The API returns user data in the following standardized format:

```json
{
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john@example.com",
    "dob": "1990-01-01",
    "company_name": "Tech Corp",
    "salary": 75000,
    "contact_number": "+1234567890",
    "status": "active",
    "account_type": "standard",
    "created_at": "2024-01-10T12:00:00Z",
    "updated_at": "2024-01-10T12:00:00Z"
}
```

---

## Technical Implementation

### Validation

- **StoreUserRequest**: Enforces required name, unique email, and confirmed password.
- **UpdateUserRequest**: Allows partial updates while maintaining unique constraints.

### Events & Observers

- **UserObserver**: Listens for model events.
- **UserCreatedEvent**: Dispatched upon successful registration or manual creation.
- **UserCreatedListener**: Handles post-creation tasks like sending welcome notifications.

### Security

- **Strict Typing**: All methods in this module use PHP 8 strict types.
- **UUID Routing**: Users are identified via immutable UUIDs in URLs to prevent ID enumeration.
- **Transaction Safety**: All write operations in `UserService` are wrapped in database transactions.

---

## Testing

The module is fully covered by feature tests in `tests/Feature/Api/V1/UserTest.php`.

```bash
php artisan test --filter=UserTest
```

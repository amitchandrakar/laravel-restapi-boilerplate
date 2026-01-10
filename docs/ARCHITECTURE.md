# Architecture Documentation

This document provides a detailed overview of the architectural design, patterns, and principles implemented in this API boilerplate.

---

## Design Principles

### 1. Strict Type Safety

We enforce PHP strict typing across the entire codebase to reduce runtime errors and improve IDE support.

```php
declare(strict_types=1);
```

Every class, method, and property must have explicit type declarations.

### 2. Service-Driven Architecture

To keep controllers clean and logic reusable, we implement a service layer.

- **Controllers**: Responsible only for request validation and returning standardized responses.
- **Services**: Contain the core business logic, transaction handling, and third-party integrations.

### 3. Event-Driven Logic

The boilerplate encourages using observers and events for side effects (e.g., clearing cache, sending notifications) to keep the primary service logic focused.

---

## Data & Request Flow

The following flow is standard for every API endpoint:

1.  **Request**: Incoming HTTP request.
2.  **Form Request**: Validation occurs before the controller is even hit.
3.  **Controller**: Receives sanitized data and delegates to a **Service**.
4.  **Service**: Executes business logic and interacts with **Models**.
5.  **Post-Processing**: (Optional) Observers trigger Events/Listeners for async tasks.
6.  **Response**: Data is transformed via **API Resources** and returned in a standard JSON format.

---

## Modular Scaffolding

We utilize a custom module pattern. A "Module" is a cohesive set of files representing a business entity. Using `php artisan make:api-module {Name}`, the system scaffolds:

| Layer          | Responsibility                              |
| :------------- | :------------------------------------------ |
| **Model**      | Database schema and relationships.          |
| **Controller** | Endpoint routing and input/output handling. |
| **Service**    | Core business logic and calculations.       |
| **Request**    | Input validation and authorization rules.   |
| **Resource**   | Transformation of model data into JSON.     |
| **Observer**   | Automating side effects on model changes.   |
| **Test**       | Feature testing for the whole module.       |

---

## Error Handling

Errors are handled globally via `app/Exceptions/Handler.php`. We use a unified error response structure:

```json
{
    "success": false,
    "message": "Friendly error message",
    "errors": { ... }
}
```

---

## Development Standards

- **PSR-12**: Enforced via Laravel Pint.
- **Static Analysis**: Enforced via Larastan (PHPStan) at Level 5+.
- **Testing**: 100% Feature test coverage for all generated modules.
- **Git Hooks**: A pre-commit hook ensures no unformatted or broken code is committed.

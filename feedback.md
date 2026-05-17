- All the side effect code like logging, notifications, etc. should be triggered from model observers. Right now controllers are doing this.
- Apply the strict code quality rules to the codebase. For example, every if statement must have a space before and after the condition for better code readability. If a variable is defined but never user. If a controller has imported a class with Use statement, but the class is not used in the controller. Similarly, apply very strict version of the rules to the codebase.
- Apply the strict rule for writing code comments. Every function, class, method, etc. must have a comment. The comment must be a short description of the code. The comment must be written in the same language as the code.
- Repeated files for same model actions. CandidateProfileSectionController, CandidateContactRequestController, CandidateDiscoveryController, etc. Should be merged into a single file. Also, the model that is being used by these controllers is User. I would suggest keeping all the codebase inside UserController.
- Create a separate api endpoints for mobile app and web app.
- Generate the postman collection for the api endpoints with all the possible request and response examples.
- Refresh the token after 1 hour.
- Generate the knowledge base for cursor ai, vscode, and any other ai tools to understand the codebase better.
- Merge all the migration files into a single file since we are still in development mode and product is not yet released.
- Implement a feature that do not trigger any queue jobs when running php artisan migrate:fresh --seed.
- Add a security feature in the codebase to prevent the thief of the codebase like license theft, code theft, etc.
- Implement observability features in the codebase to monitor the codebase performance and health.
- load testing, stress testing, and performance testing features in the codebase.
- apply caching, rate limiting, and other security features in the codebase.
- use dumpable trait in all the models to dump the data in a readable format.
- perform a security testing like sql injection, xss, csrf, session hijacking,etc.
- Check for OWASP Top 10 Vulnerabilities:
    - SQL Injection
    - Cross-Site Scripting (XSS)
    - Cross-Site Request Forgery (CSRF)
    - Session Hijacking
    - Insecure Direct Object References
    - Security Misconfiguration
    - Sensitive Data Exposure
    - Insufficient Attack Protection
    - Insufficient Logging & Monitoring
    - Insufficient Transport Layer Protection
    - Insufficient Session Management
- Write CI/CD pipeline (staging and production), Dockerize the codebase, and deploy the codebase to the cloud.
- Run "php artisan optimize:clear && php artisan optimize" after every code deployment.
- When deploying the code, down the server and post the notification to the team. Once the server is up, post the notification to the team.
-
- Create a helper to check if any migration is not run. If so then run the migration.

use jsonapi resource in the codebase.
use scramble api documentation in the codebase. dedoc/scramble.
upgrade testing framework to pest 4

- make use of cache and all the query optimization techniques in the codebase.

-

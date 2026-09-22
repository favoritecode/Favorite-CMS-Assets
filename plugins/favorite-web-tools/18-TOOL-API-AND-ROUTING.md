# 18 — TOOL API AND ROUTING



## 1. Purpose



The **Tool API and Routing System** defines how Favorite Web Tools exposes:



* Public tool pages

* Public tool discovery

* Category discovery

* Tool execution

* Tool result/download access

* Administrative tool management

* Python service management

* Tool testing



The system must integrate with the existing Favorite CMS routing and request architecture.



It must not create a parallel router, API framework, authentication system, or request lifecycle.



---



# 2. Core Principle



Favorite Web Tools owns its plugin routes and API behavior, while Favorite CMS remains responsible for the underlying application routing infrastructure.



Conceptually:



```text

Browser

   │

   ▼

Favorite CMS Router

   │

   ▼

Favorite Web Tools Route

   │

   ├── Public Page

   ├── Public API

   ├── Execution API

   └── Admin API/Page

```



The exact route registration mechanism must be determined from the actual repository.



---



# 3. Repository-First Rule



Before implementing routes, the AI agent must inspect:



* Existing route definitions.

* Existing route registration APIs.

* Existing controller structure.

* Existing API routes.

* Existing middleware.

* Existing authentication middleware.

* Existing admin route protection.

* Existing CSRF handling.

* Existing response helpers.

* Existing HTTP exception/error handling.



The agent must follow existing CMS conventions.



It must not invent a new routing architecture simply because the plugin needs routes.



---



# 4. Route Ownership



All Favorite Web Tools routes must belong to the plugin namespace.



Conceptually:



```text

favorite-web-tools

       │

       ├── Public Routes

       ├── API Routes

       └── Admin Routes

```



Routes must not conflict with:



* CMS core routes.

* Existing plugin routes.

* Theme routes.

* Other Favorite Web Tools modules.



---



# 5. Public Tool Page Route



Each ACTIVE tool should have a public page.



Conceptual route:



```text

GET /tools/{tool-slug}

```



Example:



```text

/tools/html-formatter

/tools/json-validator

/tools/css-minifier

```



This is a conceptual public URL.



The exact route prefix and registration mechanism must follow the actual Favorite CMS repository.



---



# 6. Tool Slug Resolution



The tool page should resolve the tool using its stable slug.



Conceptually:



```text

/tools/{slug}

        │

        ▼

Tool Registry

        │

        ▼

Tool

```



The route must not depend on exposing an internal database ID in the public URL.



---



# 7. Public Tool Visibility



Public tool pages should expose only tools with:



```text

status = ACTIVE

```



DRAFT and DISABLED tools must not be normally accessible as public tools.



---



# 8. Public Tool Metadata API



Where the frontend requires dynamic metadata, the plugin may expose a public API.



Conceptual route:



```text

GET /api/tools

```



It may return:



* Tool name

* Slug

* Description

* Category

* Icon

* Thumbnail

* Access mode

* Public display metadata



It must not return:



* API credentials

* Internal service credentials

* Secret configuration

* Private filesystem paths

* Internal implementation details

* Admin-only configuration



---



# 9. Public Tool Detail API



Where required, a single tool's public metadata may be retrieved through:



```text

GET /api/tools/{tool-slug}

```



The response should contain only public-safe metadata.



Conceptually:



```text

{

  "name": "...",

  "slug": "...",

  "description": "...",

  "category": "...",

  "access\_mode": "...",

  "status": "ACTIVE",

  "inputs": \[...],

  "outputs": \[...]

}

```



Only configuration fields intended for frontend rendering should be exposed.



---



# 10. Public Category API



Where required, category discovery may use:



```text

GET /api/tools/categories

```



The API may return:



* Category name

* Slug

* Description

* Icon

* Thumbnail

* Display order

* Public tool count where appropriate



Do not expose internal database details unnecessarily.



---



# 11. Category Detail API



Where needed:



```text

GET /api/tools/categories/{category-slug}

```



may return the public category and its ACTIVE tools.



The exact endpoint is optional and depends on the frontend implementation.



Do not create redundant endpoints without a real requirement.



---



# 12. Public Search



Tool search may be implemented through:



```text

GET /api/tools?search={query}

```



or through the existing CMS search/request architecture if already available.



Search should operate on public-safe fields.



Search results should include only ACTIVE public tools.



---



# 13. Public Filtering



Where required, public tool discovery may support category filtering.



Conceptually:



```text

GET /api/tools?category={category-slug}

```



Additional filters should only be introduced when actually required.



Do not expose admin-only filters through public endpoints.



---



# 14. Pagination



If the public tool catalog becomes large, public discovery may support pagination.



Conceptually:



```text

GET /api/tools?page=1

```



or the pagination convention already used by Favorite CMS.



Pagination is a presentation/data-retrieval mechanism.



It is not a usage limit.



---



# 15. Sorting



Public discovery may support a controlled sorting mechanism.



Examples:



```text

display\_order

name

created\_at

```



Only approved sortable fields should be accepted.



Do not allow arbitrary database expressions through query parameters.



---



# 16. Tool Execution API



The central execution endpoint is conceptually:



```text

POST /api/tools/{tool-slug}/execute

```



This endpoint is the primary bridge between the frontend and the Tool Engine System.



---



# 17. Execution Flow



The execution route must follow this order:



```text

Request

  ↓

Route Resolution

  ↓

Tool Lookup

  ↓

Tool Status Check

  ↓

Access Control

  ↓

Input Validation

  ↓

Engine Resolution

  ↓

Execution

  ↓

Result Normalization

  ↓

Response

```



Authorization must occur before expensive processing.



---



# 18. Tool Lookup



The execution route must resolve the requested tool through the central Tool Registry.



It must not directly duplicate tool lookup logic.



Conceptually:



```text

tool-slug

    ↓

Tool Registry

    ↓

Tool Configuration

```



---



# 19. Tool Status Enforcement



Execution is allowed only when:



```text

status = ACTIVE

```



The execution API must reject:



```text

DRAFT

DISABLED

```



tools.



---



# 20. Access Enforcement



Execution must use the centralized Access Control System defined in:



`04-ACCESS-CONTROL.md`



Supported modes are exactly:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



---



# 21. FREE Execution



For:



```text

FREE

```



anonymous and authenticated users may execute the tool.



No membership check is required.



---



# 22. LOGIN\_REQUIRED Execution



For:



```text

LOGIN\_REQUIRED

```



the execution route must verify that the user is authenticated using the existing Favorite CMS authentication system.



If not authenticated, execution must be rejected.



---



# 23. MEMBERSHIP\_REQUIRED Execution



For:



```text

MEMBERSHIP\_REQUIRED

```



the execution route must verify:



1. User is authenticated.

2. User has an active membership.



The existing CMS/membership system must be used.



---



# 24. Unlimited Membership



An active membership grants unlimited access.



The execution API must not implement:



* Daily limits

* Monthly limits

* Hourly limits

* Credits

* Tokens

* Quotas

* Usage counters

* Remaining attempts



---



# 25. Request Authentication



Authentication must use the existing Favorite CMS authentication/session mechanism.



Do not create:



* Separate login tokens.

* Separate user tables.

* Separate session systems.

* Duplicate authentication middleware.



---



# 26. CSRF Protection



For browser-based state-changing requests, use the existing Favorite CMS CSRF protection mechanism where applicable.



The plugin must not create a separate CSRF implementation unless the repository has no suitable mechanism and the architecture explicitly requires one.



---



# 27. Execution Request Format



Conceptually, a normal execution request may contain:



```text

{

  "inputs": {

    "input\_name": "value"

  }

}

```



The exact request parsing must follow the existing CMS request abstraction.



---



# 28. File Execution Requests



Tools accepting files may use:



```text

multipart/form-data

```



where appropriate.



The execution route must correctly handle:



* Uploaded file.

* File metadata.

* Other input fields.

* Upload errors.



The backend must independently validate uploaded files.



---



# 29. Input Validation



Before engine execution:



```text

Request

 ↓

Input Schema

 ↓

Validation

 ↓

Engine

```



Validation must cover the configured requirements.



Examples:



* Required.

* Type.

* Length.

* Number range.

* URL format.

* Allowed option.

* JSON validity.

* File constraints.



---



# 30. Unknown Request Fields



The execution endpoint must not blindly pass arbitrary request fields to an engine.



Only configured/expected inputs should be processed.



---



# 31. Engine Resolution



After successful access and input validation:



```text

Tool Configuration

       ↓

Engine

```



Supported engines:



```text

HTML

CSS

JAVASCRIPT

PHP

PYTHON\_API

```



The route must not contain duplicated engine-specific execution logic.



---



# 32. Engine Delegation



The API route should act as a bridge/controller rather than becoming the engine itself.



Conceptually:



```text

Execution Controller

       ↓

Engine Resolver

       ↓

Selected Engine

       ↓

Execution

```



This keeps routing independent from processing implementation.



---



# 33. HTML Tool Execution



HTML tools may perform operations such as:



* Format.

* Minify.

* Validate.

* Encode.

* Decode.

* Preview preparation.



The execution API simply passes validated input to the configured HTML engine.



---



# 34. CSS Tool Execution



CSS tools may perform:



* Format.

* Minify.

* Prefix.

* Color conversion.

* Validation.

* Preview preparation.



The route must delegate processing to the CSS engine.



---



# 35. JavaScript Tool Execution



JavaScript tools may perform processing such as:



* Format.

* Minify.

* Validate.



The execution API must not interpret arbitrary submitted JavaScript as trusted server code.



---



# 36. PHP Tool Execution



PHP tools must reference controlled PHP handlers.



The API must never execute arbitrary PHP source submitted through a public request.



No:



```text

eval()

```



or equivalent arbitrary source execution.



---



# 37. Python API Execution



For Python API tools:



```text

Execution API

    ↓

Python Service Registry

    ↓

Configured Service

    ↓

Configured Endpoint

    ↓

Python API

```



The browser must not receive private service credentials.



---



# 38. Python Service Selection



A Python tool may reference a configured service using its internal service relationship.



Conceptually:



```text

tool.python\_service\_id

```



The public request must not be allowed to override the configured service.



---



# 39. Python Endpoint Protection



The public user request must not arbitrarily specify:



```text

base\_url

endpoint

host

port

credential

```



The server must use the tool's configured service and endpoint.



This prevents the execution API from becoming an unrestricted outbound request proxy.



---



# 40. Python HTTP Method



The configured Python service may support methods such as:



```text

GET

POST

```



Additional methods may be implemented only when actually required.



---



# 41. Python Timeout



Python API calls should use the configured timeout or the repository's appropriate default.



Timeout handling must produce a safe API error.



Do not expose internal connection details.



---



# 42. Python Response Validation



The response from a Python service must be validated before returning it to the frontend.



The system must not blindly trust:



* Response structure.

* MIME type.

* Metadata.

* File references.

* Error messages.



---



# 43. Standard Success Response



The execution API should normalize successful results into a consistent structure.



Conceptually:



```text

{

  "success": true,

  "result": {

    "type": "TEXT",

    "data": "..."

  }

}

```



The exact response helper/schema should follow Favorite CMS conventions.



---



# 44. Standard Error Response



Errors should use a consistent structure.



Conceptually:



```text

{

  "success": false,

  "error": {

    "code": "...",

    "message": "..."

  }

}

```



The API should not expose stack traces or sensitive internal details.



---



# 45. HTTP Status Codes



Use HTTP status codes consistently with existing Favorite CMS conventions.



Conceptual examples:



```text

200 → Successful execution

400 → Invalid request/input

401 → Authentication required

403 → Access denied

404 → Tool not found

422 → Validation failure where CMS uses it

500 → Unexpected server error

502/503 → External service failure where appropriate

504 → External service timeout where appropriate

```



The exact status mapping must follow the repository's established API conventions.



---



# 46. Tool Not Found



If the requested tool does not exist:



```text

404

```



or the CMS-standard equivalent should be returned.



Do not reveal database implementation details.



---



# 47. Tool Disabled/Draft



A DRAFT or DISABLED tool must not execute.



The response should follow the CMS/plugin's appropriate unavailable/not-found convention.



---



# 48. Authentication Error



For a LOGIN\_REQUIRED tool accessed anonymously, return the CMS-standard authentication-required response.



The frontend can then display the appropriate login action.



---



# 49. Membership Error



For a MEMBERSHIP\_REQUIRED tool without active membership, return the appropriate authorization/access response.



Do not reveal unnecessary membership implementation details.



---



# 50. Validation Error



Validation failures should identify the relevant fields when possible.



Conceptually:



```text

{

  "success": false,

  "error": {

    "code": "VALIDATION\_ERROR",

    "message": "Input validation failed.",

    "fields": {

      "html": "This field is required."

    }

  }

}

```



The exact structure may follow existing CMS validation conventions.



---



# 51. Processing Error



If the engine fails:



* Return a safe error.

* Log technical details server-side where appropriate.

* Do not expose stack traces.

* Do not expose secrets.

* Do not expose internal filesystem paths.



---



# 52. External Service Error



If a Python service fails:



```text

Python Service

      ↓

Error

      ↓

Execution API

      ↓

Normalized Error

      ↓

Frontend

```



The frontend should receive a user-safe message.



---



# 53. Download Route



If a tool produces a downloadable file, a controlled download route may be used.



Conceptual route:



```text

GET /api/tools/download/{reference}

```



The exact route must follow existing CMS conventions.



---



# 54. Download Authorization



Download references must be validated server-side.



The system must determine whether the current user/request is allowed to access the result.



Do not rely only on an obscure/random URL.



---



# 55. Download Reference



The frontend must receive a controlled reference.



It must not receive:



```text

C:\\server\\storage\\...

/var/www/...

../../private/file

```



or other filesystem paths.



---



# 56. Temporary Result Files



If execution creates temporary files:



* Store them using existing CMS/plugin storage mechanisms.

* Use safe filenames/references.

* Clean them according to the result lifecycle.

* Do not expose storage internals.



---



# 57. Admin Route Namespace



Administrative routes should be integrated into the existing Favorite CMS admin area.



Conceptually:



```text

/admin/web-tools

```



Possible sections:



```text

Tools

Categories

Python Services

Settings

```



The exact route must follow existing CMS admin conventions.



---



# 58. Admin Authentication



All administrative pages/actions must use the existing CMS admin authentication and authorization system.



Do not create plugin-specific admin login.



---



# 59. Admin Permissions



Tool management must use the existing CMS permission/role architecture.



Possible capabilities conceptually include:



```text

view tools

create tools

edit tools

activate tools

disable tools

delete tools

manage categories

manage Python services

manage settings

```



The exact permission model must be adapted to the actual CMS.



---



# 60. Admin Tool Routes



Conceptual actions:



```text

GET    /admin/web-tools

GET    /admin/web-tools/create

POST   /admin/web-tools

GET    /admin/web-tools/{id}/edit

POST   /admin/web-tools/{id}

POST   /admin/web-tools/{id}/activate

POST   /admin/web-tools/{id}/disable

POST   /admin/web-tools/{id}/test

DELETE /admin/web-tools/{id}

```



These are conceptual only.



The agent must use the repository's existing route/action conventions.



---



# 61. Admin Category Routes



Conceptual actions:



```text

GET    /admin/web-tools/categories

GET    /admin/web-tools/categories/create

POST   /admin/web-tools/categories

GET    /admin/web-tools/categories/{id}/edit

POST   /admin/web-tools/categories/{id}

POST   /admin/web-tools/categories/{id}/disable

DELETE /admin/web-tools/categories/{id}

```



Only implement routes actually needed by the final admin UI.



---



# 62. Python Service Routes



Conceptual actions:



```text

GET    /admin/web-tools/python-services

GET    /admin/web-tools/python-services/create

POST   /admin/web-tools/python-services

GET    /admin/web-tools/python-services/{id}/edit

POST   /admin/web-tools/python-services/{id}

POST   /admin/web-tools/python-services/{id}/test

POST   /admin/web-tools/python-services/{id}/disable

DELETE /admin/web-tools/python-services/{id}

```



Dependency checks must occur before disabling/deleting a service.



---



# 63. Admin Test Execution



Tool testing should use the same core execution architecture where practical.



Conceptually:



```text

Admin Test

    ↓

Tool Configuration

    ↓

Validation

    ↓

Engine

    ↓

Result

```



Admin testing must not create a completely separate execution engine.



---



# 64. Admin vs Public Authorization



The system must distinguish:



```text

Public User

Admin

```



Admin privileges must not accidentally become available through public endpoints.



Public execution must still obey the tool's configured access mode.



---



# 65. Route Middleware



Where the CMS provides middleware:



* Authentication middleware.

* Admin middleware.

* CSRF middleware.

* Request validation.

* Rate limiting/abuse protection.



the plugin should reuse them.



Do not duplicate middleware unnecessarily.



---



# 66. Rate Limiting



Infrastructure-level rate limiting may be used to protect the service from abuse where appropriate.



It must not become a product usage quota.



Do not expose:



```text

5 uses/day

100 uses/month

credits remaining

```



as part of the tool model.



---



# 67. Caching



Public metadata may be cached where appropriate.



However:



```text

CACHE

```



must never bypass:



* Authentication.

* Authorization.

* Membership verification.

* Tool status.



Protected execution results must not be incorrectly shared between users.



---



# 68. Route-Level Security



Every state-changing route must use appropriate CMS security controls.



Examples:



```text

Create

Update

Delete

Activate

Disable

Test

Configuration change

```



must be protected appropriately.



---



# 69. Public API Security



Public APIs must:



* Validate input.

* Restrict accepted parameters.

* Avoid leaking internal data.

* Avoid arbitrary URL forwarding.

* Avoid arbitrary filesystem access.

* Avoid arbitrary code execution.



---



# 70. SSRF Protection



If a tool accepts URLs and the server fetches them:



* Validate the URL.

* Restrict protocols where appropriate.

* Apply SSRF protections.

* Prevent access to internal/private network targets where required.

* Do not allow users to override configured Python service destinations.



This must be implemented according to the actual tool's requirements.



---



# 71. Request Size



The plugin may enforce technical request/file-size limits required for server stability.



These are infrastructure constraints.



They must not be represented as user usage quotas.



---



# 72. Request Logging



Technical request/error logging may be used for debugging and maintenance.



Logs must not unnecessarily store:



* Passwords.

* API keys.

* Bearer tokens.

* Private credentials.

* Sensitive user input.

* Full uploaded private files.



---



# 73. No Usage Tracking



The routing/API layer must not create usage counters such as:



```text

tool\_usage

daily\_usage

monthly\_usage

credits

tokens

quota

remaining\_requests

```



unless a future specification explicitly changes the product model.



---



# 74. API Connector Separation



Favorite API Connector remains a separate plugin.



Favorite Web Tools must not recreate a general-purpose API Connector.



The relationship is:



```text

Favorite Web Tools

        │

        └── May consume required external API functionality

                 │

                 ▼

       Favorite API Connector

```



where integration is actually appropriate.



Python service functionality defined in this project is specifically for Python processing services and must not become a duplicate general API platform.



---



# 75. Frontend Integration



The frontend may call:



```text

GET  Public Tool API

GET  Category API

POST Tool Execution API

GET  Controlled Download API

```



The exact API paths must be generated from the actual route registration system.



---



# 76. API Versioning



Do not introduce API versioning such as:



```text

/api/v1/

```



unless the existing Favorite CMS API architecture uses versioning or the project genuinely requires it.



Avoid unnecessary complexity.



---



# 77. Content Negotiation



Use the existing CMS/API response conventions for:



* JSON.

* HTML.

* File responses.



Do not create a custom response protocol if the CMS already provides one.



---



# 78. Route Naming



Route names should be stable and descriptive.



Conceptually:



```text

favorite\_web\_tools.tools.index

favorite\_web\_tools.tools.show

favorite\_web\_tools.tools.execute

favorite\_web\_tools.categories.index

favorite\_web\_tools.admin.tools.index

```



These names are conceptual.



Use the repository's actual naming convention.



---



# 79. Route Collision Prevention



Before registering any route, the agent must inspect existing routes and verify that:



* The path does not conflict.

* The route name does not conflict.

* The HTTP method does not create ambiguity.

* Existing CMS routes remain unaffected.



---



# 80. Error Handling



Route/controller errors should flow through existing CMS error handling where possible.



Do not expose plugin-specific debug pages in production.



---



# 81. Authentication State in Public Metadata



Public tool metadata may indicate:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



but must not expose:



* User session details.

* Membership database information.

* Internal role IDs.

* Private authorization metadata.



---



# 82. Membership Recheck



For protected execution requests, membership must be evaluated at request time using the existing membership system.



The frontend must not send:



```text

"is\_member": true

```



as an authority signal.



---



# 83. Direct API Access



The execution API must be secure even when called outside the official frontend.



Assume a user can:



* Inspect network requests.

* Modify requests.

* Call endpoints directly.

* Disable JavaScript.

* Change request payloads.



All authorization and validation must therefore happen server-side.



---



# 84. Idempotency and Repeated Requests



The API should safely handle repeated requests.



The system should not accidentally:



* Create duplicate persistent records.

* Corrupt files.

* Reuse another user's result.

* Bypass access control.



Tool-specific operations should determine whether idempotency handling is required.



---



# 85. Long-Running Execution



If a tool requires long-running processing, the routing design may later support asynchronous execution.



Possible future conceptual model:



```text

POST /execute

       ↓

Job Created

       ↓

Job Status

       ↓

Result

```



Do not implement job queues/status APIs unless an actual tool requires them.



---



# 86. Webhook/Callback



No public webhook or callback system is required by default.



If a future Python service requires callbacks, it must be specified separately with:



* Authentication.

* Signature verification.

* Replay protection.

* Authorization.

* Request validation.



Do not introduce it prematurely.



---



# 87. API Documentation



The plugin should document implemented API routes for developers.



Documentation should identify:



* Method.

* Route.

* Purpose.

* Authentication requirement.

* Request format.

* Response format.

* Error behavior.



Documentation must reflect actual implementation rather than conceptual routes that were never implemented.



---



# 88. Testing Requirements



The route/API implementation must test at minimum:



### Public



* Tool listing.

* Tool detail.

* Category listing.

* Category filtering/search where implemented.

* ACTIVE tool access.



### Execution



* FREE tool.

* LOGIN\_REQUIRED anonymous request.

* LOGIN\_REQUIRED authenticated request.

* MEMBERSHIP\_REQUIRED without membership.

* MEMBERSHIP\_REQUIRED with active membership.

* DRAFT tool.

* DISABLED tool.

* Invalid input.

* Unknown input.

* Invalid tool slug.

* Engine failure.

* Python service failure.

* Timeout.

* File upload.



### Admin



* Unauthorized access.

* Authorized admin access.

* Tool creation.

* Tool update.

* Activation.

* Disable.

* Delete.

* Category management.

* Python service management.

* Tool test.



---



# 89. AI Agent Implementation Rules



The AI agent must:



1. Inspect the repository routing system before writing routes.

2. Identify existing public routes.

3. Identify existing API routes.

4. Identify existing admin routes.

5. Identify authentication middleware.

6. Identify CSRF mechanisms.

7. Identify response/error helpers.

8. Reuse existing CMS systems.

9. Register only plugin-owned routes.

10. Avoid a parallel router.

11. Avoid a parallel API framework.

12. Avoid a parallel authentication system.

13. Avoid a parallel CSRF system.

14. Keep route logic thin.

15. Delegate execution to the Tool Engine System.

16. Delegate authorization to Access Control.

17. Delegate metadata to the Tool Registry.

18. Delegate Python processing to the Python Service system.

19. Keep Favorite API Connector separate.

20. Never expose credentials.

21. Never execute arbitrary PHP.

22. Never execute arbitrary Python source.

23. Never turn user-controlled URLs into unrestricted server-side requests.

24. Never expose filesystem paths.

25. Never introduce usage limits, credits, tokens, or quotas.

26. Never modify CMS core unnecessarily.

27. Keep implementation isolated inside `favorite-web-tools`.

28. Follow existing repository naming conventions.

29. Add tests for every implemented route group.

30. Document the final implemented routes.



---



# 90. Final API and Routing Model



The complete model is:



```text

                         FAVORITE CMS ROUTER

                                  │

              ┌───────────────────┼───────────────────┐

              │                   │                   │

              ▼                   ▼                   ▼

        PUBLIC PAGES          PUBLIC API          ADMIN ROUTES

              │                   │                   │

              │             ┌─────┼─────┐             │

              │             │     │     │             │

              │          Tools Categories Search      │

              │             │                         │

              └─────────────┼─────────────────────────┘

                            │

                            ▼

                    TOOL EXECUTION API

                            │

                            ▼

                       TOOL REGISTRY

                            │

                            ▼

                       STATUS CHECK

                            │

                            ▼

                      ACCESS CONTROL

                            │

                            ▼

                     INPUT VALIDATION

                            │

                            ▼

                     ENGINE RESOLVER

                            │

          ┌─────────────────┼──────────────────┐

          │                 │                  │

         HTML              CSS           JAVASCRIPT

          │                 │                  │

          ├─────────────────┼──────────────────┤

          │                                    │

         PHP                              PYTHON API

                                               │

                                               ▼

                                      PYTHON SERVICE

                                               │

                                               ▼

                                           RESULT

                                               │

                                               ▼

                                      STANDARD RESPONSE

                                               │

                                               ▼

                                          FRONTEND

```



The fundamental routing rule is:



```text

CMS ROUTER

    ↓

PLUGIN ROUTE

    ↓

PLUGIN SERVICE

    ↓

CENTRAL TOOL SYSTEM

    ↓

AUTHORIZED EXECUTION

    ↓

STANDARD RESPONSE

```



Routes are an integration layer—not a second application architecture.



The implementation must follow the actual Favorite CMS repository and preserve complete isolation from CMS core and unrelated plugins.




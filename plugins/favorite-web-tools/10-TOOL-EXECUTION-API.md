# Favorite Web Tools — Tool Execution API



## 1. Purpose



This document defines the execution API contract for the `favorite-web-tools` plugin.



The execution API is the controlled bridge between the frontend Tool UI and the actual tool-processing layer.



It must support tools using:



* HTML

* CSS

* JavaScript

* PHP

* Python API



The API must work with the existing Favorite CMS architecture and must not create a parallel routing, authentication, database, or API framework.



---



## 2. Core Principle



The frontend must never directly decide whether a tool is allowed to execute.



The authoritative execution flow is:



```text

Frontend

   ↓

Tool Execution Request

   ↓

Favorite CMS / Plugin Route

   ↓

Tool Registry

   ↓

Tool Status Check

   ↓

Access Control

   ↓

Input Validation

   ↓

Engine Resolver

   ↓

Selected Engine

   ↓

Tool Execution

   ↓

Standard Result

   ↓

Frontend

```



Access control must always happen before actual tool execution.



---



## 3. Execution Endpoint



The plugin should expose a centralized tool-execution endpoint using the existing Favorite CMS routing/API conventions.



Conceptually:



```text

POST /api/tools/{tool-slug}/execute

```



The exact route, controller structure, middleware, request handling, and API conventions must follow the actual Favorite CMS repository.



Do not create a second routing system.



If the existing CMS already provides an appropriate API/controller mechanism, reuse it.



---



## 4. Request Method



The primary execution method should be:



```text

POST

```



because tool requests may contain:



* text

* structured JSON

* multiple input values

* files

* URLs

* large input

* Python API payloads



GET requests should not be used for actual tool execution unless a specific tool has a legitimate read-only requirement and the existing CMS architecture supports it.



---



## 5. Tool Identification



The preferred public identifier is the tool slug.



Example:



```text

POST /api/tools/json-formatter/execute

```



The backend must resolve the slug through the Tool Registry.



Do not trust a frontend-supplied internal database ID when the public slug is sufficient.



The backend must verify that:



* the tool exists

* the tool is registered

* the tool is enabled

* the tool configuration is valid

* the requested tool matches the route



---



## 6. Tool Status Validation



Before execution, check the tool status.



### ACTIVE



Execution is allowed if the user's access requirements are satisfied.



### DRAFT



Public execution must not be allowed.



### DISABLED



Execution must not be allowed.



The backend must not rely only on hiding DRAFT or DISABLED tools from the frontend.



---



## 7. Access Control



The execution API must use the centralized Access Control system defined in:



```text

04-ACCESS-CONTROL.md

```



Supported access modes are exactly:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



### FREE



Anonymous users may execute the tool.



Logged-in users may also execute it.



No membership is required.



### LOGIN\_REQUIRED



The user must be authenticated.



Membership status does not matter.



### MEMBERSHIP\_REQUIRED



The user must:



1. be authenticated

2. have an active membership



An active membership provides unlimited use.



There must be:



* no daily limit

* no monthly limit

* no hourly limit

* no credits

* no tokens

* no usage counter

* no quota system



---



## 8. Access Must Be Checked Server-Side



Frontend access controls are for user experience only.



A user may:



* modify JavaScript

* modify HTML

* modify browser state

* manually send HTTP requests

* call the API directly



Therefore, every protected execution request must independently perform the access check.



Conceptually:



```text

Request

  ↓

Tool

  ↓

Status

  ↓

Access Control

  ↓

Allowed?

  ├── No → Access Error

  └── Yes

       ↓

     Execute

```



---



## 9. Authentication



Authentication must use the existing Favorite CMS authentication/session mechanism.



Do not create:



* separate tool login

* separate tool sessions

* separate user table

* separate password system

* duplicate authentication middleware



The execution layer should consume the authenticated user information supplied by the CMS.



---



## 10. Membership Verification



For:



```text

MEMBERSHIP\_REQUIRED

```



the execution API must use the existing Favorite CMS membership system/plugin.



The plugin should not create a second membership database or subscription system.



Membership status must be evaluated when the protected request is received.



If the existing membership system is temporarily unavailable, the plugin must not accidentally grant protected access.



---



## 11. Request Payload



A common conceptual request format is:



```json

{

  "inputs": {

    "text": "example input"

  }

}

```



For multiple inputs:



```json

{

  "inputs": {

    "html": "<div>Hello</div>",

    "format": "beautify",

    "indent": 2

  }

}

```



The exact request format may be adapted to existing Favorite CMS API conventions.



The Tool Registry configuration determines which input fields are valid.



---



## 12. Input Validation



The execution API must validate input before passing it to an engine.



Validation may include:



* required fields

* data type

* string length

* numeric range

* URL format

* allowed file types

* file size

* JSON validity

* allowed select values

* checkbox/radio values

* engine-specific requirements



Frontend validation improves UX.



Backend validation is authoritative.



---



## 13. Unknown Inputs



The backend must not blindly process arbitrary request fields.



Where the tool configuration defines a known input schema, unknown or unsupported inputs should be rejected or safely ignored according to the plugin's standardized validation behavior.



Do not allow arbitrary request parameters to become executable configuration.



---



## 14. File Uploads



Tools that require files may use:



```text

multipart/form-data

```



The API must safely handle:



* upload validation

* allowed file extensions

* MIME validation where appropriate

* size limits

* temporary storage

* cleanup

* tool-specific processing



Uploaded files must not automatically become executable server-side code.



---



## 15. Engine Resolution



After access and validation, the API resolves the configured engine.



Supported engines:



```text

HTML

CSS

JAVASCRIPT

PHP

PYTHON\_API

```



Conceptually:



```text

Tool

 ↓

engine

 ↓

Engine Resolver

 ↓

Selected Engine

```



The execution API must not contain large engine-specific processing logic itself.



That responsibility belongs to the Engine System defined in:



```text

06-ENGINE-SYSTEM.md

```



---



## 16. HTML Engine Requests



HTML tools may process content such as:



* formatter

* minifier

* validator

* encoder/decoder

* preview-related processing



The API should pass validated input to the HTML engine and return the standardized result.



If a tool is entirely browser-side, it may not need an API execution request.



The tool configuration must determine the appropriate execution model.



---



## 17. CSS Engine Requests



CSS tools may process:



* CSS formatting

* CSS minification

* prefixing

* color conversion

* validation

* other supported CSS transformations



The execution API passes validated input to the CSS engine.



CSS preview content must remain isolated from the Favorite CMS interface.



---



## 18. JavaScript Engine Requests



JavaScript tools may process:



* formatting

* minification

* validation

* transformation

* analysis



The system must distinguish between:



```text

JavaScript processing

```



and:



```text

Executing arbitrary user JavaScript

```



The execution API must not automatically provide arbitrary server-side JavaScript execution.



Any execution capability must be explicitly designed and isolated as a separate supported feature.



---



## 19. PHP Engine Requests



PHP tools must use controlled server-side handlers.



The execution API may resolve a configured PHP tool handler and pass validated input to it.



The system must never execute arbitrary PHP source supplied by a user.



Do not use:



```php

eval()

```



or equivalent mechanisms to turn user input into executable PHP code.



---



## 20. Python API Requests



For:



```text

PYTHON\_API

```



the execution API must use the Python Service configuration defined in:



```text

07-PYTHON-API-SERVICE.md

```



Conceptual flow:



```text

Tool

 ↓

Python Service

 ↓

Endpoint

 ↓

Request Mapping

 ↓

Python API

 ↓

Response

 ↓

Normalization

```



Python source code must not be stored in the Tool Registry for direct CMS execution.



---



## 21. Python API Authentication



If the Python service requires credentials, those credentials must remain server-side.



They must not be exposed through:



* HTML

* frontend JavaScript

* public tool metadata

* browser network payloads

* frontend configuration



The browser should communicate with the Favorite Web Tools execution API, not directly with a protected Python service.



---



## 22. Python API Timeout



Python API calls must have a configured timeout.



A tool must not leave a web request waiting indefinitely.



The timeout should come from the configured Python Service or existing plugin configuration.



Do not invent arbitrary retry behavior.



Retries should only be added when specifically required and safe for the operation.



---



## 23. Standard Success Response



The API should normalize successful tool execution into a common response structure.



Conceptually:



```json

{

  "success": true,

  "data": {

    "type": "TEXT",

    "value": "processed result"

  }

}

```



The exact response envelope must follow existing Favorite CMS API conventions if they already exist.



---



## 24. Result Types



The execution API must support the result types defined by the frontend system:



```text

TEXT

HTML

JSON

FILE

IMAGE

AUDIO

VIDEO

DOWNLOAD

```



Examples:



### Text



```json

{

  "type": "TEXT",

  "value": "result"

}

```



### JSON



```json

{

  "type": "JSON",

  "value": {

    "status": "ok"

  }

}

```



### File



A file result may return a controlled download reference rather than embedding the entire file in JSON.



The exact implementation must follow the existing CMS file/download architecture.



---



## 25. Error Response



Errors must use a predictable structure.



Conceptually:



```json

{

  "success": false,

  "error": {

    "code": "INVALID\_INPUT",

    "message": "The provided input is invalid."

  }

}

```



Possible error categories include:



```text

TOOL\_NOT\_FOUND

TOOL\_DISABLED

TOOL\_NOT\_AVAILABLE

AUTHENTICATION\_REQUIRED

MEMBERSHIP\_REQUIRED

INVALID\_INPUT

INVALID\_FILE

ENGINE\_ERROR

PYTHON\_SERVICE\_ERROR

PYTHON\_SERVICE\_TIMEOUT

PROCESSING\_ERROR

INTERNAL\_ERROR

```



Actual HTTP status codes and error formatting must follow existing Favorite CMS conventions.



Do not expose internal stack traces, credentials, filesystem paths, or sensitive configuration to users.



---



## 26. Authentication Error



For a `LOGIN\_REQUIRED` tool accessed by an anonymous user, the API should return a standardized authentication-required response.



The frontend can then display an appropriate login action.



The backend must not execute the tool before authentication is confirmed.



---



## 27. Membership Error



For a `MEMBERSHIP\_REQUIRED` tool accessed by a user without active membership, the API should return a standardized membership-required response.



The frontend can then display the appropriate membership action.



No tool processing should occur before membership authorization.



---



## 28. Tool Not Found



If the slug does not correspond to a registered tool:



```text

TOOL\_NOT\_FOUND

```



must be returned according to CMS API conventions.



Do not attempt to execute an unknown tool.



---



## 29. Disabled or Draft Tool



If a tool is:



```text

DRAFT

```



or:



```text

DISABLED

```



public execution must be rejected.



The frontend may hide these tools, but backend enforcement is mandatory.



---



## 30. Duplicate Requests



The frontend may prevent accidental double-clicks.



However, the backend must not implement usage counters or quotas to solve this problem.



There is no:



* request credit

* execution credit

* daily quota

* monthly quota

* token deduction



system.



Any idempotency behavior should only be introduced if a specific operation requires it.



---



## 31. No Usage Tracking



The execution API must not record usage counters such as:



```text

daily\_usage

monthly\_usage

credits\_used

tokens\_used

requests\_remaining

quota\_remaining

```



These are explicitly outside the project scope.



Normal technical logs may still exist where supported by the CMS for debugging or operational purposes, but they must not become a user quota system.



---



## 32. Response Normalization



Every engine should return a normalized internal result.



Conceptually:



```text

Engine Result

    ↓

Execution API

    ↓

Normalize

    ↓

HTTP/API Response

```



This allows the frontend to handle tools consistently even when their underlying engines are different.



---



## 33. Large Results



Some tools may produce:



* large text

* large JSON

* generated files

* images

* audio

* video



The execution API should avoid unnecessarily embedding large binary data directly into JSON.



Where appropriate, use the existing CMS storage/file/download mechanisms and return a controlled result reference.



Temporary files must be cleaned up according to the tool's processing lifecycle.



---



## 34. Client-Side Tools



Not every tool requires a server request.



A tool may be configured for client-side processing when:



* the operation is safe to perform in the browser

* no secret is required

* no server resource is needed

* the processing logic does not need to remain proprietary



Examples may include simple:



* text transformations

* formatting

* encoding/decoding

* UI previews



The tool architecture must support both client-side and server-side execution where appropriate.



---



## 35. Proprietary Processing



If a tool contains proprietary processing logic that should not be exposed to users, the processing must not depend entirely on browser JavaScript.



Use:



```text

PHP

```



or:



```text

Python API

```



for appropriate server-side processing.



Browser-side code must always be treated as inspectable.



---



## 36. CSRF and Request Security



The execution API must follow the Favorite CMS's existing CSRF/request protection mechanism where applicable.



Do not invent a separate CSRF system if the CMS already provides one.



All existing CMS security middleware and request validation conventions should be reused.



---



## 37. Authorization Before Expensive Processing



The order must remain:



```text

Tool lookup

 ↓

Status validation

 ↓

Authentication/access validation

 ↓

Input validation

 ↓

Engine execution

```



Do not perform expensive PHP/Python processing before authorization.



This is particularly important for membership-protected and Python-backed tools.



---



## 38. Execution Logging



If the Favorite CMS already provides an appropriate application/audit/error logging mechanism, the plugin may use it.



Logs may contain technical information required for:



* debugging

* failed API calls

* Python service errors

* system errors



Logs must not expose:



* API secrets

* passwords

* authentication tokens

* sensitive user input unnecessarily



Logging must not become a usage-limit system.



---



## 39. API Connector Separation



Favorite Web Tools must not duplicate the responsibilities of:



```text

Favorite API Connector

```



If a future tool needs an external API that is intended to be managed by Favorite API Connector, the Web Tools execution layer should integrate with that plugin according to its actual public/service interface.



Do not create a second generic third-party API credential manager inside Favorite Web Tools.



---



## 40. Admin Testing



The Admin Panel may provide a tool test action.



The test request should use the same execution/engine architecture wherever practical.



Admin testing must still respect:



* tool configuration

* engine configuration

* input validation

* Python service availability



Admin testing must not create a separate execution engine.



If the existing CMS supports a safe admin-only execution mechanism, it should be reused.



---



## 41. API and Frontend Separation



The frontend should know only the public execution contract.



It should not need to know:



* database table names

* internal engine classes

* Python credentials

* internal service IDs unless safely exposed as metadata

* filesystem paths

* CMS internal implementation details



This keeps the frontend independent from the backend implementation.



---



## 42. Request Lifecycle Example



Example:



```text

User opens:

 /tools/json-formatter



        ↓



Frontend loads tool metadata



        ↓



User enters JSON



        ↓



POST execution request



        ↓



Backend resolves:

 json-formatter



        ↓



Tool Registry



        ↓



Status = ACTIVE



        ↓



Access Mode = FREE



        ↓



Access allowed



        ↓



Validate JSON input



        ↓



Engine = JAVASCRIPT / PHP

(as configured)



        ↓



Execute



        ↓



Normalize result



        ↓



Return result



        ↓



Frontend displays formatted JSON

```



---



## 43. Membership-Protected Example



```text

User opens protected tool



        ↓



POST execution request



        ↓



Tool Registry



        ↓



Status = ACTIVE



        ↓



Access Mode =

MEMBERSHIP\_REQUIRED



        ↓



Authentication check



        ↓



Membership check



        ↓

   ┌───────────────┐

   │ Active member │

   └───────┬───────┘

           ↓

       Execute tool



If inactive:

           ↓

   MEMBERSHIP\_REQUIRED

```



No execution should occur for unauthorized users.



---



## 44. Python Tool Example



```text

Frontend

   ↓

POST /api/tools/image-enhancer/execute

   ↓

Tool Registry

   ↓

Access Control

   ↓

Input/File Validation

   ↓

Engine Resolver

   ↓

PYTHON\_API

   ↓

Python Service Registry

   ↓

Configured Endpoint

   ↓

Server-side authenticated request

   ↓

Python API

   ↓

Validate response

   ↓

Normalize result

   ↓

Frontend

```



The browser never receives the Python service credential.



---



## 45. HTTP Status Behavior



HTTP status codes should follow the conventions already used by Favorite CMS.



Conceptually:



```text

200 → successful execution

400 → invalid request/input

401 → authentication required

403 → access denied/membership required

404 → tool not found

422 → validation failure, if CMS convention uses it

500 → internal processing error

502/503 → external service failure, if appropriate

504 → external service timeout, if appropriate

```



The exact mapping must be confirmed against the actual repository implementation before coding.



Do not introduce a conflicting API error standard.



---



## 46. No Parallel API Framework



The plugin must not introduce another API framework or routing architecture if Favorite CMS already provides:



* routes

* controllers

* middleware

* request objects

* response objects

* authentication

* CSRF

* API helpers



Reuse the existing system.



---



## 47. Configuration



Execution-related configuration should come from:



* Tool Registry

* Python Service Registry

* plugin settings

* existing CMS configuration



depending on the specific requirement.



Do not hard-code:



* API credentials

* production service URLs

* environment-specific secrets

* arbitrary limits



inside frontend files.



---



## 48. Future Extensibility



The execution API must remain engine-agnostic.



Adding a future engine should require:



```text

New Engine

\+

Engine Registration

\+

Tool Configuration

```



rather than rewriting the entire API.



The execution flow remains:



```text

Request

 → Registry

 → Status

 → Access

 → Validation

 → Resolver

 → Engine

 → Result

```



---



## 49. Repository Compatibility Rule



Before implementation, the AI agent must inspect the actual Favorite CMS repository and identify:



* existing API routes

* controller patterns

* request/response classes

* authentication middleware

* CSRF implementation

* membership integration

* error response conventions

* file upload handling

* existing API/service abstractions



The agent must adapt this specification to the repository rather than inventing missing framework structures.



If an existing CMS mechanism can perform the required function, reuse it.



---



## 50. Filesystem Isolation



Implementation must remain inside the intended plugin scope:



```text

Favorite-CMS-Universal/plugins/favorite-web-tools/

```



and its corresponding asset scope in:



```text

Favorite-CMS-Assets/plugin-assets/favorite-web-tools/

```



Do not modify Favorite CMS core files or unrelated existing plugins.



If a core integration point appears necessary, stop and identify the existing extension mechanism before changing anything.



---



## 51. Implementation Rules for AI Agent



The AI agent must:



1. Read this specification before implementation.

2. Inspect the actual repository.

3. Identify existing CMS API/routing conventions.

4. Reuse existing authentication and membership systems.

5. Reuse existing request/response mechanisms.

6. Reuse existing CSRF protection.

7. Reuse existing file handling where applicable.

8. Implement the execution API inside `favorite-web-tools`.

9. Keep engine logic separated from the API layer.

10. Enforce access before execution.

11. Never introduce usage limits or credits.

12. Never execute arbitrary user PHP.

13. Never expose Python/API credentials.

14. Never create a duplicate API connector.

15. Keep implementation isolated from CMS core.

16. Test each engine independently.

17. Test all three access modes.

18. Test anonymous and authenticated requests.

19. Test inactive membership against protected tools.

20. Test disabled and draft tools.

21. Test invalid inputs.

22. Test Python API failure and timeout handling.

23. Test standardized success/error responses.

24. Report any repository conflict before making architectural changes.



---



## 52. Acceptance Criteria



This specification is complete when:



* A tool can be resolved by slug.

* Tool status is checked before execution.

* Access is checked before execution.

* `FREE` tools work for anonymous users.

* `LOGIN\_REQUIRED` tools require authentication.

* `MEMBERSHIP\_REQUIRED` tools require active membership.

* Active membership provides unlimited use.

* No usage quota/counter/credit system exists.

* Inputs are validated server-side.

* Files are handled safely where required.

* The correct engine is resolved.

* HTML tools can use the HTML engine.

* CSS tools can use the CSS engine.

* JavaScript tools can use the JavaScript engine.

* PHP tools use controlled server-side handlers.

* Python tools communicate through configured Python services.

* Python credentials remain server-side.

* Results use a consistent response structure.

* Errors use a consistent response structure.

* Large/binary results can use controlled file/download responses.

* Draft tools cannot be publicly executed.

* Disabled tools cannot be publicly executed.

* Frontend-only access controls are not trusted as authorization.

* Favorite API Connector responsibilities are not duplicated.

* Existing Favorite CMS API/routing/auth/security mechanisms are reused.

* No CMS core modification is required for normal plugin operation.

* The implementation remains isolated within the plugin scope.

* The system remains extensible for future engines.




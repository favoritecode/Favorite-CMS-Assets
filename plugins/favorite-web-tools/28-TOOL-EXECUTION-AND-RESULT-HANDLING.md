# 28 — TOOL EXECUTION AND RESULT HANDLING



## 1. Purpose



This document defines the complete execution lifecycle for Favorite Web Tools.



It connects:



```text

Frontend

→ Execution API

→ Tool Registry

→ Status

→ Access Control

→ Input Validation

→ Engine

→ Processing

→ Result Normalization

→ Output Rendering

```



This document is the implementation contract for normal tool execution.



---



# 2. Core Execution Flow



Every tool execution should conceptually follow:



```text

User

 ↓

Tool Page

 ↓

Execution Request

 ↓

Resolve Tool

 ↓

Check Status

 ↓

Check Access

 ↓

Validate Inputs

 ↓

Resolve Engine

 ↓

Execute

 ↓

Normalize Result

 ↓

Return Response

 ↓

Frontend Renderer

```



---



# 3. Execution Entry Point



The frontend sends an execution request through the centralized Tool Execution API.



Conceptual endpoint:



```text

POST /api/tools/{tool-slug}/execute

```



The exact route must follow the existing Favorite CMS routing architecture.



---



# 4. Tool Resolution



The backend receives the tool slug.



It must resolve the tool through the central Tool Registry.



Conceptually:



```text

tool-slug

 ↓

Tool Registry

 ↓

Tool Definition

```



Do not create a separate tool lookup system.



---



# 5. Tool Not Found



If the requested tool does not exist:



```text

404 / appropriate CMS response

```



The response should use the existing CMS API/error conventions.



Do not expose internal database details.



---



# 6. Tool Status Check



After resolving the tool, check its status.



Allowed for public execution:



```text

ACTIVE

```



Blocked:



```text

DRAFT

DISABLED

```



---



# 7. Draft Tool



A DRAFT tool must not be executable through the normal public execution endpoint.



It may be tested through the authorized admin test workflow.



---



# 8. Disabled Tool



A DISABLED tool must not execute.



The backend should return a controlled unavailable/disabled response.



---



# 9. Access Control



After status validation, the system evaluates the configured access mode.



Supported values:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



---



# 10. FREE Execution



For:



```text

FREE

```



anonymous and authenticated users may execute the tool.



Normal validation and technical security controls still apply.



---



# 11. LOGIN\_REQUIRED Execution



For:



```text

LOGIN\_REQUIRED

```



the existing Favorite CMS authentication/session system must confirm that the user is logged in.



If not authenticated:



```text

401 / existing CMS authentication response

```



Use the repository's actual convention.



---



# 12. MEMBERSHIP\_REQUIRED Execution



For:



```text

MEMBERSHIP\_REQUIRED

```



the system must verify:



```text

Authenticated

\+

Active Membership

```



If membership is not active:



```text

403 / existing CMS authorization response

```



Use the actual CMS convention.



---



# 13. Membership Is Not Stored as Permanent Authorization



Membership status should be checked through the existing membership system when protected execution occurs.



Do not rely only on an old cached authorization state if the existing CMS provides a current membership check.



---



# 14. No Usage Limits



Execution must not contain:



```text

Daily Limit

Monthly Limit

Hourly Limit

Credits

Tokens

Remaining Uses

Quota

Usage Counter

```



A user with active membership has unlimited access to membership-required tools.



---



# 15. Request Payload



For JSON-compatible tools, the conceptual request is:



```json

{

  "inputs": {

    "field\_name": "value"

  }

}

```



The exact request format must follow the CMS request/response conventions.



---



# 16. File Requests



Tools requiring files may use:



```text

multipart/form-data

```



The execution API must support the common File Input System.



---



# 17. Input Definition Source



The backend must obtain expected inputs from the server-side Tool Configuration.



The public request does not define the tool's input schema.



---



# 18. Unknown Input Fields



Unknown fields must not be blindly processed.



Depending on the CMS convention, the system may:



* Reject unknown fields.

* Ignore them safely.



The choice should be consistent across the execution system.



---



# 19. Required Inputs



If a required input is missing:



```text

Validation Error

```



The engine must not execute.



---



# 20. Type Validation



Inputs must be checked against their configured types.



Supported types:



```text

TEXT

TEXTAREA

NUMBER

URL

FILE

SELECT

CHECKBOX

RADIO

JSON

```



---



# 21. Text Validation



Text fields may validate:



* Required.

* Minimum length.

* Maximum length.

* Allowed format where configured.



Do not alter user content unnecessarily.



---



# 22. Number Validation



Number inputs may validate:



* Numeric format.

* Minimum.

* Maximum.

* Integer/decimal requirement where configured.



---



# 23. URL Validation



URL inputs must be validated according to the tool's expected URL requirements.



A URL supplied by a user must not automatically become a server-side fetch destination.



---



# 24. Select Validation



Selected values must match the configured option list.



Do not trust arbitrary option values supplied by the browser.



---



# 25. Checkbox/Radio Validation



Values must be normalized according to the configured field definition.



---



# 26. JSON Validation



JSON input must be parsed and validated before the engine receives it.



Invalid JSON must produce a validation error.



---



# 27. File Validation



Uploaded files must be validated before processing.



Where applicable validate:



```text

Upload status

File size

MIME type

Extension

Expected format

Filename safety

```



---



# 28. File Storage



Uploaded files should use:



* Controlled temporary storage.

* Existing CMS storage utilities where appropriate.

* Safe generated filenames.



Never trust a client-provided filesystem path.



---



# 29. File Cleanup



Temporary files should be cleaned after:



```text

Success

Failure

Timeout

Exception

```



where technically possible.



---



# 30. Input Sanitization



Sanitization must be context-aware.



Do not blindly strip characters from:



* HTML source.

* CSS source.

* JavaScript source.

* JSON.

* Code-like input.



Instead validate according to the intended operation and safely handle the output.



---



# 31. Access Before Expensive Processing



Authorization must happen before expensive operations whenever possible.



Example:



```text

Request

 ↓

Access Check

 ↓

File Processing

 ↓

Python API

```



not:



```text

Request

 ↓

Python API

 ↓

Access Check

```



---



# 32. Engine Resolution



After access and input validation, resolve the engine.



Supported engines:



```text

HTML

CSS

JAVASCRIPT

PHP

PYTHON\_API

```



---



# 33. Engine Configuration



The engine receives its configuration from the server-side Tool Registry.



The public request cannot modify:



```text

engine

configuration

handler

service

endpoint

```



---



# 34. Engine Execution



Conceptually:



```text

Validated Tool

 +

Validated Inputs

 +

Trusted Configuration

 ↓

Engine

```



---



# 35. HTML Execution



HTML tools may perform configured operations such as:



* Formatting.

* Minification.

* Validation.

* Encoding.

* Decoding.



Rendered previews must remain isolated.



---



# 36. CSS Execution



CSS tools may perform configured operations such as:



* Formatting.

* Minification.

* Prefixing.

* Color conversion.

* Validation.



CSS previews must not affect the main CMS page.



---



# 37. JavaScript Execution



JavaScript processing tools should treat source as data.



Examples:



* Formatting.

* Minification.

* Syntax validation.



Processing must not automatically execute the submitted JavaScript.



---



# 38. JavaScript Runtime Tools



If a tool intentionally executes JavaScript:



* Execution must be explicitly configured.

* Execution must be isolated.

* Privileged CMS objects must not be exposed.

* The result must be safely normalized.



JavaScript execution is not the default behavior.



---



# 39. PHP Execution



PHP tools use controlled server-side handlers.



The execution system must never interpret public input as arbitrary PHP source.



Forbidden:



```php

eval($input);

```



and equivalent dynamic code execution.



---



# 40. Python API Execution



Python tools use the Python API Engine.



Flow:



```text

Validated Input

 ↓

Python Service

 ↓

Configured Endpoint

 ↓

API Request

 ↓

Response Validation

 ↓

Result

```



---



# 41. Python Service Security



The public request cannot override:



```text

Service ID

Base URL

Endpoint

Authentication

Credentials

```



These come from trusted server-side configuration.



---



# 42. API Connector Integration



If an operation requires a third-party API supported by Favorite API Connector:



```text

Favorite Web Tools

 ↓

Favorite API Connector

 ↓

Third-Party API

```



Do not implement duplicate API connector logic inside Tool Execution.



---



# 43. Execution Result



Every engine must return a normalized result.



Conceptual success:



```json

{

  "success": true,

  "data": {

    "type": "TEXT",

    "value": "Result"

  }

}

```



The exact response structure must follow the existing CMS API conventions.



---



# 44. Result Types



Supported result types:



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



---



# 45. TEXT Result



Plain text should be returned as text data.



The frontend renderer is responsible for safe display.



---



# 46. HTML Result



HTML results must distinguish between:



```text

HTML Source

```



and:



```text

Rendered HTML Preview

```



Rendered HTML must use the defined isolation mechanism.



---



# 47. JSON Result



JSON results remain structured.



The frontend must safely render JSON rather than blindly inserting it into HTML.



---



# 48. File Result



Files should be stored or referenced using controlled mechanisms.



Never expose internal paths such as:



```text

/var/www/...

C:\\...

/home/...

```



---



# 49. Image Result



Image outputs may use a controlled file/reference URL.



The frontend renders the image using the Output Renderer.



---



# 50. Audio Result



Audio outputs may use a controlled media reference.



The frontend should use the appropriate media player/output renderer.



---



# 51. Video Result



Video outputs may use a controlled media reference.



The frontend should use the appropriate video output renderer.



---



# 52. Download Result



Downloadable results must use the controlled download system.



The browser should receive a safe download reference/URL rather than a filesystem path.



---



# 53. Download Authorization



If a result is protected or associated with an authenticated execution, download access must respect the appropriate authorization rules.



Do not make protected files publicly accessible simply because a download URL exists.



---



# 54. Download Reference



A download reference should be opaque or otherwise controlled.



It must not directly encode an unsafe filesystem path.



---



# 55. Temporary Output



Generated temporary files should have an appropriate lifecycle.



The implementation may:



* Remove them after download.

* Expire them.

* Store them temporarily using existing CMS storage.



Exact behavior depends on the CMS/storage architecture.



---



# 56. Persistent Output



If a tool intentionally creates persistent files, it must use the existing CMS/plugin storage architecture.



Do not create a separate unmanaged storage system.



---



# 57. Multiple Results



A tool may return multiple outputs when required.



Conceptually:



```json

{

  "success": true,

  "data": {

    "outputs": \[

      {

        "type": "TEXT",

        "value": "..."

      },

      {

        "type": "DOWNLOAD",

        "reference": "..."

      }

    ]

  }

}

```



The exact structure must follow the common Output System implementation.



---



# 58. Empty Result



If processing succeeds but produces no meaningful output:



```text

EMPTY\_RESULT

```



may be returned where appropriate.



The frontend should show a useful empty state.



---



# 59. Processing Error



If engine processing fails:



```text

PROCESSING\_ERROR

```



should be returned through the normalized error structure.



---



# 60. Validation Error



Invalid user input should produce:



```text

VALIDATION\_ERROR

```



Field-level errors may be included.



---



# 61. Configuration Error



If the tool configuration is invalid:



```text

CONFIGURATION\_ERROR

```



The tool should not execute.



This should normally be prevented by activation validation.



---



# 62. Dependency Error



If a required dependency is unavailable:



```text

DEPENDENCY\_ERROR

```



The system must fail safely.



---



# 63. Python Service Error



Python-related failures may map to:



```text

SERVICE\_UNAVAILABLE

TIMEOUT

AUTHENTICATION\_FAILED

INVALID\_RESPONSE

PROCESSING\_ERROR

```



---



# 64. Network Error



Network failures should be normalized.



Do not expose internal infrastructure information.



---



# 65. Error Response



Conceptual structure:



```json

{

  "success": false,

  "error": {

    "code": "VALIDATION\_ERROR",

    "message": "Please provide a valid input.",

    "fields": {

      "source": "This field is required."

    }

  }

}

```



The actual response should follow CMS conventions.



---



# 66. User-Facing Error Messages



Messages should be:



* Clear.

* Concise.

* Actionable where possible.

* Safe.



Do not expose:



* Stack traces.

* Database errors.

* API credentials.

* Internal IP addresses.

* Filesystem paths.

* Server configuration.



---



# 67. HTTP Status Codes



Use the existing Favorite CMS/API status conventions.



Typical categories may include:



```text

2xx → Success

400 → Invalid request/input

401 → Authentication required

403 → Access denied

404 → Tool/result not found

409 → Conflict where applicable

422 → Validation failure where CMS uses it

5xx → Server/service failure

```



Do not invent a new status system if the CMS already defines one.



---



# 68. Frontend Loading State



During execution the frontend may show:



```text

Processing...

```



or engine-specific honest states:



```text

Uploading...

Sending...

Processing...

Preparing result...

```



---



# 69. No Fake Progress



Do not display fake percentages such as:



```text

37%

64%

91%

```



unless actual progress information is available.



---



# 70. Duplicate Submission



The frontend may temporarily disable the execute button while a request is in progress.



This is a UX mechanism, not a usage limit.



---



# 71. Request Cancellation



Where supported, long-running requests may allow cancellation.



Cancellation must clean up temporary resources where possible.



---



# 72. Browser Disconnect



If the browser disconnects, the server should handle the request according to the runtime/CMS behavior.



Do not leave unnecessary temporary resources indefinitely.



---



# 73. Idempotency



If a tool performs an operation where duplicate execution can cause unwanted side effects, an idempotency mechanism may be added when genuinely required.



Do not add complex transaction infrastructure without a real need.



---



# 74. Concurrency



Multiple users may execute the same tool concurrently.



Tool execution must avoid shared mutable state that could leak one user's input/result to another user.



---



# 75. Configuration Snapshot



A request should execute using a consistent server-side tool configuration.



A public user cannot change the configuration during execution.



---



# 76. Configuration Changes



If an administrator changes a tool configuration while requests are active, behavior should follow the CMS/runtime lifecycle.



New requests should use the current valid configuration.



---



# 77. Disabled During Execution



If a tool becomes disabled after a request has already started, do not assume that an already-running process can always be terminated.



However:



* New requests must be rejected.

* Existing processing should follow safe runtime cleanup behavior.



---



# 78. Logging



Technical execution errors may be logged using the existing CMS logging system.



Logging must not become a usage analytics system.



---



# 79. No Usage Tracking



Do not create execution records merely to count:



```text

How many times user used tool

How many credits consumed

How many requests remain

```



---



# 80. Technical Logs



If technical logs are necessary, they may include:



```text

Tool identifier

Engine

Error category

Timestamp

Technical request ID

```



Avoid sensitive payloads.



---



# 81. Request ID



A technical request/correlation ID may be used for debugging.



It must not become a user usage counter.



---



# 82. Caching



Caching may be used where appropriate for:



* Static tool metadata.

* Safe deterministic results.

* Public discovery data.



Do not cache protected execution results in a way that can expose one user's result to another.



---



# 83. Authorization-Aware Caching



Access control must be evaluated independently from any public cache.



Caching must never turn a protected tool into a public tool.



---



# 84. Deterministic Tools



Some tools may be deterministic:



```text

Base64 Encode

URL Encode

UUID

Hash

Formatter

```



Caching may be considered only if it provides a real performance benefit and does not create security/privacy issues.



---



# 85. Sensitive Results



Sensitive outputs should not be stored or cached unnecessarily.



---



# 86. Large Results



Large results should use controlled references when appropriate.



Avoid sending unnecessarily huge JSON payloads.



---



# 87. Output Cleanup



Temporary result files should be cleaned according to the storage lifecycle.



Failed execution must also attempt cleanup.



---



# 88. Frontend Rendering



The frontend receives the normalized result and selects the appropriate Output Renderer.



Conceptually:



```text

Result Type

 ↓

Output Renderer

 ↓

UI

```



---



# 89. Renderer Mapping



Example:



```text

TEXT     → Text Renderer

HTML     → HTML Renderer

JSON     → JSON Renderer

IMAGE    → Image Renderer

AUDIO    → Audio Renderer

VIDEO    → Video Renderer

DOWNLOAD → Download Renderer

FILE     → File Renderer

```



---



# 90. Copy Action



Copy should be available for compatible text outputs.



The frontend should use safe browser APIs.



---



# 91. Download Action



Download should be available for compatible file/output types.



It must use the controlled download mechanism.



---



# 92. Clear Action



The frontend may clear:



* Input.

* Result.

* Error.

* Preview state.



This does not affect server-side configuration.



---



# 93. Reset Action



Reset returns the tool UI to its configured default state.



---



# 94. Error Recovery



The frontend should allow retry where appropriate.



A retry simply creates another normal execution request.



It must not bypass:



* Access control.

* Validation.

* Configuration.

* Security checks.



---



# 95. Access Errors in Frontend



If execution returns an authentication error:



```text

Show Login action/message

```



If membership is required and inactive:



```text

Show Membership action/message

```



The exact navigation must use existing CMS authentication/membership routes.



---



# 96. Tool Availability Error



If a tool is disabled/unavailable:



```text

Show safe unavailable message

```



Do not expose internal configuration.



---



# 97. Python Processing UI



For Python tools, the frontend may show honest processing states.



Example:



```text

Uploading file...

Sending to processing service...

Processing...

Preparing result...

```



---



# 98. File Upload Progress



Real upload progress may be shown if technically available.



Do not fabricate processing progress.



---



# 99. Theme Integration



Execution result rendering must use the active CMS theme.



The plugin must:



* Render inside the active theme.

* Use active Header/Footer.

* Follow active dark/light state.

* Respond to `body.dark` where the theme uses it.

* Avoid creating a duplicate theme switcher.



---



# 100. Result Isolation



User-generated HTML/CSS/JS result content must remain isolated from the CMS interface.



Result rendering must not accidentally modify:



```text

Header

Footer

Navigation

Admin UI

Theme state

Other tool cards

```



---



# 101. Browser Trust Boundary



The browser is untrusted.



Users may:



* Inspect requests.

* Modify requests.

* Modify JavaScript.

* Modify local UI state.

* Call API endpoints manually.



The server must remain authoritative.



---



# 102. Server Authority



The server decides:



```text

Tool exists?

Tool active?

Access allowed?

Input valid?

Engine allowed?

Configuration valid?

```



---



# 103. Configuration Tampering



If a client attempts:



```json

{

  "engine": "PHP",

  "access\_mode": "FREE"

}

```



inside the public request, those fields must not alter server-side configuration.



---



# 104. API Credential Protection



No execution response should return:



* Python API keys.

* Bearer tokens.

* Basic credentials.

* Internal service configuration.



---



# 105. File Reference Protection



Download/result references must not allow path traversal or arbitrary file access.



---



# 106. Request Size Protection



The execution API may enforce technical request-size limits for stability.



These are infrastructure safeguards, not product usage limits.



---



# 107. Processing Time Protection



Technical processing timeouts may be used where necessary.



They are not usage quotas.



---



# 108. Rate Limiting



Infrastructure-level rate limiting may be used to protect the server from abuse.



It must not be represented as:



```text

Daily user quota

Monthly user quota

Tool credits

```



---



# 109. Execution Architecture Reuse



Admin test execution should reuse the same:



```text

Registry

Validation

Engine

Result

```



architecture where possible.



Admin authorization remains separate and uses existing CMS permissions.



---



# 110. No Duplicate Execution Systems



Do not create:



* One public execution system.

* One separate admin execution engine.

* One separate Python execution system.



Use the central architecture.



---



# 111. Plugin Isolation



All Web Tools execution code must remain within the plugin-owned filesystem unless an existing CMS integration point requires otherwise.



Do not modify CMS core files.



---



# 112. Database Isolation



Execution must use the plugin/CMS database architecture.



Do not create a second database or ORM.



---



# 113. Route Isolation



Execution routes must use the existing CMS/plugin routing system.



Do not create a parallel router.



---



# 114. Authentication Isolation



Execution must use existing CMS authentication.



Do not create a second login/session system.



---



# 115. Membership Isolation



Execution must use the existing membership system/adapter.



Do not create a second membership database or membership state system.



---



# 116. Final Execution Lifecycle



```text

                    USER

                      │

                      ▼

                 TOOL PAGE

                      │

                      ▼

              EXECUTION REQUEST

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

        ┌─────────────┼─────────────┐

        ▼             ▼             ▼

     Browser        PHP        Python API

    Processing     Handler        Service

        │             │             │

        └─────────────┼─────────────┘

                      ▼

               RESULT NORMALIZER

                      │

                      ▼

                OUTPUT RESULT

                      │

                      ▼

              FRONTEND RENDERER

                      │

          ┌───────────┼───────────┐

          ▼           ▼           ▼

        TEXT        MEDIA      DOWNLOAD

                      │

                      ▼

                     USER

```



---



# 117. AI Agent Implementation Rules



The AI agent must:



1. Inspect the repository before implementation.

2. Reuse existing CMS routing.

3. Reuse existing CMS request handling.

4. Reuse existing authentication.

5. Reuse existing membership integration.

6. Reuse existing CSRF protection.

7. Reuse existing validation utilities where appropriate.

8. Reuse existing HTTP/client utilities.

9. Reuse existing storage utilities.

10. Reuse existing response/error conventions.

11. Use the central Tool Registry.

12. Use the central Access Control system.

13. Use the central Input System.

14. Use the central Engine Resolver.

15. Use the central Output System.

16. Never bypass access control.

17. Never trust client-side configuration.

18. Never execute arbitrary PHP.

19. Never execute arbitrary Python.

20. Never execute arbitrary shell/PowerShell commands.

21. Keep JavaScript processing separate from execution.

22. Isolate HTML/CSS/JS previews.

23. Protect Python credentials.

24. Validate external API responses.

25. Safely handle uploaded files.

26. Safely handle generated files.

27. Clean temporary resources.

28. Normalize errors.

29. Normalize outputs.

30. Avoid unnecessary persistent execution logs.

31. Do not implement usage counters.

32. Do not implement credits.

33. Do not implement tokens.

34. Do not implement quotas.

35. Do not implement daily/monthly usage limits.

36. Keep Favorite API Connector separate.

37. Preserve active theme integration.

38. Preserve dark/light compatibility.

39. Preserve plugin filesystem isolation.

40. Add appropriate tests.



---



# 118. Required Acceptance Criteria



Implementation is complete when:



* Public execution endpoint works.

* Tool resolution uses the central registry.

* Only ACTIVE tools execute publicly.

* DRAFT tools are blocked publicly.

* DISABLED tools are blocked.

* FREE access works anonymously.

* LOGIN\_REQUIRED uses existing authentication.

* MEMBERSHIP\_REQUIRED uses existing membership.

* Active membership provides unlimited execution.

* No usage quotas exist.

* Input schema comes from server-side configuration.

* Required fields are validated.

* Types are validated.

* Files are validated.

* Unknown/tampered configuration fields cannot alter tool behavior.

* Correct engine is resolved.

* HTML tools execute correctly.

* CSS tools execute correctly.

* JavaScript processing tools do not automatically execute source.

* PHP tools use controlled handlers.

* Python tools use configured services.

* API credentials remain server-side.

* Python responses are validated.

* Results are normalized.

* TEXT results render correctly.

* HTML results are isolated.

* JSON results render safely.

* File results use controlled references.

* Image/audio/video results render correctly.

* Download results use controlled downloads.

* Temporary files are cleaned.

* Errors are normalized.

* HTTP status handling follows CMS conventions.

* Loading states work.

* Fake progress is not used.

* Retry does not bypass security.

* Technical logging does not become usage tracking.

* Protected results cannot be downloaded without appropriate authorization.

* Theme Header/Footer remain active.

* Dark/light mode follows the active theme.

* `body.dark` compatibility works where applicable.

* No duplicate theme system exists.

* CMS core remains untouched.

* Existing authentication/membership remains untouched.

* Existing routing remains untouched.

* Existing database architecture remains untouched.

* Favorite API Connector remains separate.

* Appropriate tests pass.



---



# Final Principle



**Every tool execution must pass through one controlled server-side pipeline: resolve the registered tool, verify its active status, enforce access, validate inputs, resolve the configured engine, process safely, normalize the result, and render it through the frontend Output System. No client request can redefine the tool, bypass access, expose credentials, execute arbitrary server code, or access uncontrolled files.**




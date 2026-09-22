# 27 — PYTHON SERVICE AND API INTEGRATION



## 1. Purpose



This document defines the implementation-level contract for integrating Python-powered tools into the `favorite-web-tools` plugin.



Python processing must run through an external HTTP/API service.



The Favorite CMS PHP application must not execute arbitrary Python source code directly.



---



# 2. Core Architecture



The architecture is:



```text

User

 ↓

Favorite Web Tools Frontend

 ↓

Tool Execution API

 ↓

Tool Registry

 ↓

Status Check

 ↓

Access Control

 ↓

Input Validation

 ↓

Python API Engine

 ↓

Python Service Registry

 ↓

Configured Python Service

 ↓

Python Processing

 ↓

HTTP Response

 ↓

Response Validation

 ↓

Standard Tool Result

 ↓

Frontend Output Renderer

```



---



# 3. Python Service vs Python Tool



These are separate concepts.



### Python Service



A remote/local HTTP service capable of processing requests.



### Python Tool



A Favorite Web Tools tool that uses one configured Python Service.



Example:



```text

Python Service:

Favorite AI Processing Server



Python Tool:

Audio Enhancer

```



One Python Service may support multiple tools/endpoints.



---



# 4. Service Location



A Python Service may run:



* On the same physical server.

* On another server.

* On a private/internal network.

* In a separate cloud/container environment.

* In another supported infrastructure environment.



The architecture must not assume that Python and Favorite CMS run on the same machine.



---



# 5. Service Registry



Favorite Web Tools maintains a registry of configured Python services using the plugin's database/configuration architecture.



Conceptual service fields:



```text

id

name

base\_url

authentication\_type

credential\_reference

default\_timeout

status

created\_at

updated\_at

```



Exact field names must follow repository conventions.



---



# 6. Service Status



Supported statuses:



```text

ACTIVE

DISABLED

```



Only ACTIVE services may be used by active public tools.



---



# 7. Service Identity



Each service must have:



* Unique ID.

* Human-readable name.

* Base URL.

* Authentication configuration if required.

* Default technical timeout.

* Status.



---



# 8. Base URL



The base URL is configured by an authorized administrator.



Example:



```text

https://python.example.com

```



Tool configuration should reference the service rather than allowing the public user to supply the base URL.



---



# 9. Endpoint



Each Python tool may define an endpoint relative to its configured service.



Example:



```text

Base URL:

https://python.example.com



Endpoint:

/api/audio/enhance

```



The final request destination is constructed from trusted server-side configuration.



---



# 10. Public URL Override Prohibited



A public user request must never be allowed to override:



```text

base\_url

endpoint

service\_id

authentication

credentials

```



These values come from server-side configuration.



---



# 11. HTTP Methods



The integration should support at minimum:



```text

GET

POST

```



Additional methods may be supported only when required by an actual service.



Most processing tools will normally use:



```text

POST

```



---



# 12. Request Content Types



The integration may support:



```text

application/json

multipart/form-data

application/x-www-form-urlencoded

```



Only expose the content types actually required by configured services.



---



# 13. JSON Request



Example conceptual request:



```json

{

  "text": "input value",

  "options": {

    "mode": "standard"

  }

}

```



The actual request must be generated from the tool's server-side request mapping.



---



# 14. Multipart Request



Tools requiring file uploads may send:



```text

multipart/form-data

```



Example:



```text

audio → uploaded file

strength → numeric value

```



The browser must upload to the Favorite Web Tools execution endpoint first unless the architecture explicitly supports another secure flow.



---



# 15. File Upload Flow



For a Python file-processing tool:



```text

User

 ↓

Browser Upload

 ↓

Favorite CMS

 ↓

File Validation

 ↓

Temporary/Controlled Storage

 ↓

Python API Request

 ↓

Python Processing

 ↓

Response

 ↓

Temporary Resource Cleanup

```



---



# 16. File Validation



Before forwarding a file to Python, the backend must validate where applicable:



* Upload success.

* File size.

* MIME type.

* Extension.

* Expected format.

* Filename safety.

* Temporary storage state.



The exact limits must be technical safeguards appropriate to the service.



They are not product usage quotas.



---



# 17. File Naming



Do not trust the original filename for filesystem operations.



Use controlled temporary names or the CMS storage mechanism.



Never allow user input to construct arbitrary filesystem paths.



---



# 18. Request Mapping



Python tools must support configurable request mapping.



Conceptually:



```text

Tool Input

    ↓

Request Mapping

    ↓

Python API Request

```



Example:



```text

Tool Input:

source



Python API:

input\_text

```



---



# 19. Mapping Types



Where useful, mapping may support:



```text

Input field → API field

Input field → query parameter

Input field → JSON property

Input file → multipart field

Configured constant → API field

```



Only trusted administrator-defined mappings may be used.



---



# 20. Constants



A tool may require a fixed server-side value.



Example:



```text

model = "enhance-v1"

```



Such values are configuration, not user-controlled input.



Secrets must not be stored as ordinary public configuration.



---



# 21. Request Headers



The integration may construct server-side headers such as:



```text

Content-Type

Authorization

Accept

Configured service headers

```



Only safe configured headers should be supported.



---



# 22. Authentication



Possible authentication methods include:



```text

NONE

API\_KEY

BEARER

BASIC

CUSTOM\_CONFIGURED

```



Only methods actually required by the implementation should be enabled.



---



# 23. API Key



If API-key authentication is used:



```text

Tool

 ↓

Python Service

 ↓

Server-side credential

 ↓

HTTP Header

```



The key must never be exposed to:



* Browser JavaScript.

* HTML.

* Public API metadata.

* Tool configuration returned to users.



---



# 24. Bearer Token



Bearer tokens must remain server-side.



The frontend must never receive the token merely because the tool uses a Python service.



---



# 25. Basic Authentication



If Basic Authentication is required, credentials must be stored using the existing secure configuration/secret mechanism.



Do not store passwords in public tool metadata.



---



# 26. Credential Reference



Where the CMS provides an appropriate secure configuration/secret mechanism, the service should store a reference to the credential rather than exposing the secret directly through tool metadata.



The exact implementation must follow the repository's existing conventions.



---



# 27. Environment Configuration



Environment-specific credentials should be supported where the CMS/runtime already provides an environment configuration mechanism.



Example:



```text

Development → Development Python Service

Production  → Production Python Service

```



Do not hard-code production secrets into plugin source files.



---



# 28. Timeout



Every Python API request must have a technical timeout.



Possible levels:



```text

Tool-specific timeout

        ↓

Service default timeout

        ↓

System-safe fallback

```



Exact values should be chosen according to the actual processing workload and infrastructure.



---



# 29. Timeout Is Not a Usage Limit



A timeout only prevents a request from running indefinitely.



It must not be presented as:



* User quota.

* Usage restriction.

* Credit system.

* Daily/monthly limit.



---



# 30. Connection Errors



Possible errors include:



```text

SERVICE\_UNAVAILABLE

CONNECTION\_FAILED

DNS/NETWORK\_ERROR

TIMEOUT

```



The user should receive a safe message.



Internal infrastructure details should remain hidden.



---



# 31. HTTP Status Handling



The integration must distinguish successful and failed HTTP responses.



Conceptually:



```text

2xx → Process response

4xx → Service/request error

5xx → Service/server error

timeout → Timeout error

network failure → Service unavailable

```



Exact behavior should follow the CMS HTTP/client conventions.



---



# 32. Response Content Types



The integration may receive:



```text

application/json

text/plain

text/\*

application/octet-stream

image/\*

audio/\*

video/\*

```



Only formats required by the configured tool should be accepted.



---



# 33. JSON Response



Example:



```json

{

  "success": true,

  "result": "processed output"

}

```



The Python API Engine validates and maps the response.



---



# 34. Response Mapping



The tool configuration may define how a Python response becomes the standard tool result.



Example:



```text

API response:

{

  "result": "..."

}



Mapping:

result → output



Final Tool Result:

TEXT

```



---



# 35. Required Response Fields



If a tool expects a specific field, the engine must verify that it exists.



Missing required fields should produce:



```text

INVALID\_RESPONSE

```



rather than silently returning incorrect output.



---



# 36. Response Validation



Validation should check:



* HTTP status.

* Content type.

* Expected structure.

* Required fields.

* Expected output type.

* File/result reference validity where applicable.



---



# 37. Untrusted API Response



Python service responses must be treated as untrusted external data.



Never assume the service response is safe merely because the service is configured by an administrator.



---



# 38. HTML Response



If Python returns HTML:



```text

Python API

 ↓

HTML Result

 ↓

Output Renderer

```



The frontend must apply the appropriate output handling/isolation defined by the Output System.



---



# 39. JSON Response



JSON should remain structured and should be rendered safely.



Do not blindly concatenate JSON values into HTML.



---



# 40. File Response



If Python returns a generated file:



```text

Python Service

 ↓

File Response

 ↓

Controlled Temporary/Persistent Storage

 ↓

Tool Result Reference

 ↓

Frontend

```



The client must receive a controlled result/download reference rather than a server filesystem path.



---



# 41. Generated File Security



Generated files should:



* Use controlled storage.

* Avoid predictable unsafe paths.

* Avoid exposing internal filesystem structure.

* Have appropriate content metadata.

* Be cleaned when temporary.

* Use controlled download handling.



---



# 42. Download Result



The Python service may produce:



```text

DOWNLOAD

FILE

IMAGE

AUDIO

VIDEO

```



The Tool Output System determines how the frontend presents the result.



---



# 43. Streaming



Streaming should not be implemented by default.



If a specific Python service genuinely requires streaming:



* Inspect existing CMS HTTP capabilities.

* Define a dedicated integration contract.

* Preserve authentication/access control.

* Avoid exposing internal service credentials.

* Test timeout and connection handling.



---



# 44. Long-Running Processing



Long-running Python processing should be supported only when required by actual tools.



Possible architecture:



```text

Request

 ↓

Python Service

 ↓

Processing

 ↓

Final Result

```



An asynchronous job system should not be created merely for architectural complexity.



If later required, define it as a separate extension.



---



# 45. Progress Reporting



Do not display fake progress percentages.



If the Python service provides real progress information and the architecture later supports it, it may be exposed through a dedicated mechanism.



Until then use honest states such as:



```text

Uploading

Sending

Processing

Preparing result

```



---



# 46. Retry



Automatic retries must not be enabled blindly.



Retries may be considered for clearly transient failures where:



* The operation is safe to retry.

* Duplicate processing does not create harmful side effects.

* The service supports it.

* Timeout and infrastructure behavior are understood.



---



# 47. Idempotency



For operations where duplicate requests can cause unwanted processing, an appropriate idempotency mechanism may be used if supported by the service.



Do not invent unnecessary distributed transaction systems.



---



# 48. Access Control Order



Python requests must follow:



```text

Request

 ↓

Tool Exists

 ↓

Tool ACTIVE

 ↓

Access Allowed

 ↓

Input Valid

 ↓

Python Service Valid

 ↓

Python Request

```



Do not contact the Python service before authorization.



---



# 49. FREE Python Tools



A `FREE` Python tool can be used by anonymous users, subject to:



* Tool status.

* Input validation.

* Technical security controls.

* Service availability.



---



# 50. LOGIN\_REQUIRED Python Tools



A `LOGIN\_REQUIRED` Python tool requires existing CMS authentication before the Python service is contacted.



---



# 51. MEMBERSHIP\_REQUIRED Python Tools



A `MEMBERSHIP\_REQUIRED` Python tool requires an active membership according to the existing CMS/membership system.



Active membership means unlimited use.



---



# 52. No Product Usage Limits



Python integration must not implement:



```text

Daily limit

Monthly limit

Credits

Tokens

Remaining uses

Usage counter

Quota

```



---



# 53. Technical Protection



Python integration may implement infrastructure safeguards such as:



* Request timeout.

* Upload size limit.

* Response size protection.

* Connection limits.

* Infrastructure-level rate limiting where necessary.



These are technical protections, not user quotas.



---



# 54. SSRF Protection



The Python service destination must come from trusted server-side configuration.



Do not allow a public user to submit:



```text

url=https://some-server.example

```



and cause Favorite CMS to make an arbitrary server-side request.



---



# 55. Endpoint Validation



When an administrator configures a Python service, the implementation should validate the service URL according to the application's security requirements.



Avoid unsafe URL schemes.



---



# 56. Internal Network Protection



If private/internal Python services are supported, the implementation must ensure that public users cannot turn the integration into an arbitrary internal network request mechanism.



The configured service destination remains trusted configuration.



---



# 57. Python Service Health Test



The admin panel may provide:



```text

Test Connection

```



The test should:



1. Resolve the configured service.

2. Use configured authentication.

3. Call an appropriate health/test endpoint.

4. Validate the response.

5. Report success/failure.



Do not expose credentials.



---



# 58. Health Endpoint



If the Python service provides a dedicated health endpoint, use it.



Example:



```text

GET /health

```



The actual endpoint is service-specific.



Do not assume every Python service has `/health`.



---



# 59. Service Dependency



A Python tool may depend on:



```text

Python Service A

```



The admin system must know which tools depend on each service.



---



# 60. Disable Service



Before disabling a Python service, the admin UI should identify dependent tools.



Dependent tools must not silently execute against a disabled service.



---



# 61. Delete Service



Before deletion:



```text

Find dependent tools

 ↓

Show dependency warning

 ↓

Prevent unsafe deletion or require safe dependency resolution

```



Do not leave active tools referencing a deleted service.



---



# 62. Service Configuration Updates



Changing:



* Base URL.

* Authentication.

* Credential reference.

* Timeout.



must affect future requests using that service.



The system must not expose old credentials to users.



---



# 63. Tool Configuration



A Python tool may contain:



```text

Engine:

PYTHON\_API



Service:

Python Service ID



Endpoint:

/api/process



Method:

POST



Request Mapping:

...



Response Mapping:

...



Timeout:

...

```



---



# 64. Tool Input Configuration



Python tools use the common Input System.



Examples:



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



The Python Engine should not invent a separate input system.



---



# 65. Multiple Inputs



Example:



```text

file

strength

mode

format

```



The mapping layer converts these into the API request expected by the Python service.



---



# 66. Optional Inputs



Optional fields may be omitted or mapped to configured defaults according to the Tool Configuration.



The behavior must be deterministic.



---



# 67. Invalid Input



Invalid input must stop processing before the Python service is contacted whenever possible.



Example:



```text

Invalid file

 ↓

Validation Error

 ↓

No Python API request

```



---



# 68. Authentication Failure



If the Python service rejects configured authentication:



```text

Authentication Failure

 ↓

Safe Error

```



Do not expose:



* API keys.

* Tokens.

* Authorization headers.

* Raw credential configuration.



---



# 69. Service Configuration Failure



If the service configuration is incomplete:



```text

Invalid Service Configuration

 ↓

Tool Execution Blocked

```



Do not attempt an unsafe fallback.



---



# 70. Disabled Service



If the referenced service is `DISABLED`:



```text

Tool Request

 ↓

Service Disabled

 ↓

Controlled Error

```



---



# 71. Missing Service



If the configured service no longer exists:



```text

Dependency Error

```



The tool must not attempt to construct an arbitrary destination.



---



# 72. Python API Error Normalization



External errors should be converted into the common tool error structure.



Possible internal categories:



```text

SERVICE\_UNAVAILABLE

AUTHENTICATION\_FAILED

INVALID\_REQUEST

TIMEOUT

INVALID\_RESPONSE

PROCESSING\_ERROR

DEPENDENCY\_ERROR

```



---



# 73. User-Facing Error Messages



User-facing messages should be understandable.



Example:



```text

The processing service is temporarily unavailable.

```



Do not expose:



```text

Connection refused at 10.x.x.x:8000

Authorization header...

Internal stack trace...

```



---



# 74. Logging



Technical information may be logged through the existing CMS logging mechanism.



Logs must redact:



```text

API keys

Bearer tokens

Passwords

Authorization headers

Sensitive credentials

```



---



# 75. Request Logging



Do not log complete user files or sensitive payloads by default.



Only log information necessary for debugging and system operation.



---



# 76. Response Logging



Do not store complete Python API responses unnecessarily.



Avoid logging potentially sensitive generated data.



---



# 77. Python Service Test Mode



Admin testing should use the same underlying service client and Python API Engine used by normal execution where practical.



Do not create a completely separate HTTP integration implementation for admin tests.



---



# 78. Admin Test Security



Admin test actions must use existing CMS admin authentication, authorization, and CSRF protection.



---



# 79. Frontend Integration



The frontend should communicate only with Favorite Web Tools.



Conceptually:



```text

Browser

 ↓

Favorite Web Tools API

 ↓

Python Service

```



The browser should not directly receive private Python service credentials.



---



# 80. Browser-to-Python Direct Calls



Direct browser calls to authenticated private Python services should not be the default architecture.



Server-side proxying through Favorite Web Tools should be used when credentials or private service access are required.



---



# 81. Public Python Service



If a Python service is intentionally public and requires no secret, direct browser access may be considered only if it is explicitly designed and security-reviewed.



The default implementation remains server-side integration.



---



# 82. CORS



Python service CORS configuration must not be used as a substitute for Favorite CMS authorization.



The Favorite Web Tools backend remains the authoritative access boundary.



---



# 83. Response Normalization



The final flow must be:



```text

Python Response

 ↓

Validate

 ↓

Map

 ↓

Normalize

 ↓

Standard Tool Result

```



---



# 84. Output Types



Python tools may produce:



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



Only configured output types should be returned.



---



# 85. Output Renderer



The Python Engine must not generate the complete frontend UI.



It returns normalized data.



The frontend Output Renderer handles presentation.



---



# 86. Theme Independence



Python service processing must not depend on the active CMS theme.



Theme integration belongs to the frontend rendering layer.



---



# 87. Dark/Light Mode



Python processing is theme-independent.



The frontend result UI follows the active theme's dark/light state.



---



# 88. Service Client Abstraction



If the repository architecture supports services/classes, create a reusable Python service client rather than implementing HTTP logic separately inside every Python tool.



Conceptually:



```text

Python Service Client

        ↓

Python API Engine

        ↓

Python Tools

```



---



# 89. No Duplicate HTTP Framework



Reuse the CMS's existing HTTP/client utilities if available.



Do not introduce a second HTTP abstraction without a real requirement.



---



# 90. Dependency Management



Any new HTTP/client/library dependency must:



* Be compatible with the existing project.

* Follow existing package management.

* Be necessary.

* Be documented.

* Not replace existing CMS infrastructure unnecessarily.



---



# 91. Configuration Storage



Python service configuration must use the plugin/CMS configuration architecture defined in previous specifications.



Do not create an unrelated configuration database.



---



# 92. Database Boundary



Python service metadata belongs to the plugin-owned data model.



Credentials should use the existing secure configuration mechanism whenever available.



---



# 93. Migration Compatibility



Python service tables/configuration must be installed and upgraded through the CMS migration system.



Do not create a separate Python migration system.



---



# 94. Service Deletion Safety



Deleting a Python service must not leave active tools with broken references.



Safe dependency handling is mandatory.



---



# 95. Service Caching



Service metadata may be cached for performance if appropriate.



Caching must never:



* Bypass access control.

* Expose credentials.

* Cause disabled services to remain executable incorrectly.



---



# 96. Configuration Cache



If the CMS uses configuration caching, follow its conventions.



After service configuration changes, invalidate/reload relevant cache according to the CMS architecture.



---



# 97. Concurrent Requests



The service client must safely handle multiple users making independent requests.



Avoid shared mutable request state.



---



# 98. Temporary Resource Cleanup



After Python processing:



```text

Successful request → Cleanup

Failed request → Cleanup

Timeout → Cleanup

Exception → Cleanup

```



Cleanup must occur wherever the runtime permits.



---



# 99. Partial Failure



If Python processing succeeds but storing the output fails:



```text

Output Storage Error

```



must be returned safely.



Do not return an invalid download reference.



---



# 100. Security Principle



The Python integration must never become:



```text

User

 ↓

Arbitrary URL

 ↓

Server HTTP Request

```



or:



```text

User

 ↓

Python Source

 ↓

Python Execution

```



It is strictly:



```text

User

 ↓

Configured Tool

 ↓

Configured Python Service

 ↓

Controlled Request

```



---



# 101. AI Agent Implementation Rules



The AI agent must:



1. Inspect the actual repository before implementation.

2. Inspect existing HTTP/client utilities.

3. Inspect existing configuration systems.

4. Inspect existing secret/credential handling.

5. Inspect existing migration conventions.

6. Inspect existing admin service patterns.

7. Reuse existing CMS systems.

8. Create only plugin-owned Python integration components.

9. Keep Python services separate from Python tools.

10. Keep credentials server-side.

11. Never expose credentials to frontend JavaScript.

12. Never accept public base URLs.

13. Never accept public endpoint overrides.

14. Never execute arbitrary Python source.

15. Never execute arbitrary shell commands.

16. Validate inputs before contacting Python where possible.

17. Validate Python responses.

18. Normalize errors.

19. Normalize outputs.

20. Safely handle files.

21. Clean temporary resources.

22. Use technical timeouts.

23. Avoid unnecessary retries.

24. Avoid unnecessary streaming/job infrastructure.

25. Reuse the central Tool Execution API.

26. Reuse Access Control.

27. Reuse Tool Registry.

28. Reuse Output System.

29. Keep Favorite API Connector separate.

30. Preserve plugin filesystem isolation.



---



# 102. Required Acceptance Criteria



Implementation is complete when:



* Python Service Registry exists.

* Services can be created/configured by authorized admins.

* Services support ACTIVE/DISABLED states.

* Python tools can reference a configured service.

* Python tools can specify endpoint/method.

* Request mapping works.

* Response mapping works.

* JSON requests work where configured.

* Multipart/file requests work where configured.

* Authentication remains server-side.

* Credentials are never exposed to frontend.

* Public users cannot override service configuration.

* Access control runs before Python requests.

* Input validation runs before processing.

* Python response validation works.

* Python errors are normalized.

* Timeout handling works.

* Service unavailable handling works.

* Authentication failure handling works.

* Generated files use controlled storage/references.

* Temporary files are cleaned.

* Download results use controlled download handling.

* Service dependencies are tracked.

* Disabling a service does not accidentally allow execution.

* Deleting a service does not leave unsafe active references.

* Admin connection testing works where supported.

* Existing CMS HTTP/configuration/migration systems are reused.

* No arbitrary Python execution exists.

* No arbitrary server-side URL fetching exists.

* No product usage limits exist.

* No credits exist.

* No tokens exist.

* No quotas exist.

* Favorite API Connector remains separate.

* Plugin isolation remains intact.

* Appropriate tests pass.



---



# 103. Final Python Integration Model



```text

                         USER

                           │

                           ▼

                  FAVORITE WEB TOOLS

                           │

                           ▼

                   EXECUTION API

                           │

                           ▼

                   ACCESS CONTROL

                           │

                           ▼

                  INPUT VALIDATION

                           │

                           ▼

                  PYTHON API ENGINE

                           │

                           ▼

                 PYTHON SERVICE REGISTRY

                           │

                           ▼

                 CONFIGURED SERVICE

                           │

                  ┌────────┴────────┐

                  │                 │

                  ▼                 ▼

              Request           Credentials

              Mapping          Server-Side

                  │                 │

                  └────────┬────────┘

                           ▼

                    PYTHON SERVICE

                           │

                           ▼

                    API RESPONSE

                           │

                           ▼

                  RESPONSE VALIDATION

                           │

                           ▼

                  RESPONSE MAPPING

                           │

                           ▼

                  STANDARD RESULT

                           │

                           ▼

                  OUTPUT RENDERER

                           │

                           ▼

                         USER

```



# Final Principle



**Favorite Web Tools does not run arbitrary Python. It securely connects configured tools to configured Python services through a controlled server-side API integration. The service destination, endpoint, authentication, and credentials are trusted server-side configuration; user input is limited to the tool's declared inputs; responses are validated and normalized before reaching the frontend.**




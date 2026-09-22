# 16 — TOOL SECURITY AND SANDBOX



## 1. Purpose



The **Tool Security and Sandbox System** defines the security boundaries for Favorite Web Tools.



It protects:



* Favorite CMS

* Favorite Web Tools plugin

* Administrators

* Logged-in users

* Anonymous users

* Python API services

* Uploaded files

* Tool execution endpoints

* Tool configuration

* Secrets and credentials

* Frontend preview environments



Security must be implemented using the existing Favorite CMS security architecture wherever possible.



This document defines the security requirements. Exact implementation must follow the actual repository structure and existing CMS mechanisms.



---



# 2. Core Security Principle



User-provided tool input must always be treated as **untrusted data**.



Conceptually:



```text

USER INPUT

   ↓

UNTRUSTED

   ↓

VALIDATE

   ↓

PROCESS IN CONTROLLED CONTEXT

   ↓

SAFE RESULT

```



Never assume that input is safe because it comes from:



* A logged-in user

* A membership user

* An administrator-created tool page

* A browser

* A frontend JavaScript application

* A file upload



---



# 3. Security Boundaries



The plugin must maintain clear boundaries between:



```text

Browser

   │

   ▼

Frontend

   │

   ▼

Execution API

   │

   ▼

Access Control

   │

   ▼

Input Validation

   │

   ▼

Tool Engine

   │

   ├── HTML

   ├── CSS

   ├── JavaScript

   ├── PHP

   └── Python API

```



Each layer must enforce its own relevant security responsibility.



---



# 4. Browser Is Untrusted



The browser must never be considered a trusted environment.



A user can potentially:



* Inspect JavaScript.

* Modify frontend state.

* Modify requests.

* Replay requests.

* Send requests manually.

* Remove frontend restrictions.

* Change form values.

* Call execution endpoints directly.



Therefore:



```text

Frontend Security = UX Protection

Backend Security = Authority

```



All important authorization and validation must be enforced server-side.



---



# 5. Access Control Security



The centralized Access Control system defined in:



`04-ACCESS-CONTROL.md`



must be enforced before protected tool execution.



Access modes are exactly:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



The backend must independently verify:



* Authentication.

* Membership status where required.

* Tool status.

* Access mode.



Frontend access controls must never be treated as authorization.



---



# 6. Authorization Order



The recommended execution order is:



```text

Request

 ↓

Identify Tool

 ↓

Verify Tool Exists

 ↓

Verify Tool Status

 ↓

Verify User Access

 ↓

Validate Input

 ↓

Resolve Engine

 ↓

Execute

 ↓

Validate Output

 ↓

Return Result

```



Authorization must happen before expensive processing whenever practical.



---



# 7. No Usage-Based Security Model



The security system must not introduce:



* Daily usage limits

* Monthly usage limits

* Hourly usage limits

* Credits

* Tokens

* Quotas

* Usage counters



Membership means unlimited tool usage.



Security controls such as authentication, authorization, request validation, rate limiting for abuse protection, payload-size limits, and infrastructure protections are separate from product usage limits.



---



# 8. CSRF Protection



State-changing or authenticated requests must use the existing Favorite CMS CSRF protection mechanisms where applicable.



The plugin must not invent a second CSRF framework.



For browser requests involving authenticated sessions:



```text

Request

 ↓

CMS CSRF Verification

 ↓

Authorization

 ↓

Processing

```



If the CMS already provides middleware or helpers for CSRF validation, use them.



---



# 9. Authentication



Authentication must use the existing Favorite CMS authentication/session system.



Do not create:



* Another login system.

* Another session system.

* Another password system.

* Another user database.



The plugin only needs to ask the existing CMS whether the current user is authenticated.



---



# 10. Membership Security



Membership verification must use the existing CMS/membership plugin integration.



For:



```text

MEMBERSHIP\_REQUIRED

```



the backend must verify the user's current membership state.



Do not trust:



* Frontend membership flags.

* Browser storage.

* Query parameters.

* Hidden form fields.

* Client-side JavaScript variables.



Membership status must be authoritative on the server.



---



# 11. Tool Configuration Protection



Tool configuration must never be modifiable through public tool execution requests.



A public request must not be able to override:



```text

engine

access\_mode

status

python\_service\_id

endpoint

handler

configuration

```



The server must load these values from the registered tool configuration.



---



# 12. Admin Authorization



Tool management actions must require appropriate existing CMS administrator permissions.



Protected actions include:



* Create tool.

* Edit tool.

* Change engine.

* Change access mode.

* Change status.

* Configure Python services.

* Configure endpoints.

* Delete tool.

* Manage categories.

* Test protected services.



Use existing CMS authorization/permission mechanisms.



Do not create a separate admin permission framework.



---



# 13. HTML Security



HTML input is untrusted.



Potentially dangerous HTML can contain:



* Script elements.

* Event handlers.

* Dangerous URLs.

* Embedded objects.

* Frames.

* Other active content.



The system must distinguish between:



```text

HTML SOURCE

```



and:



```text

RENDERED HTML PREVIEW

```



Raw HTML must not automatically be injected into the main CMS document.



---



# 14. HTML Preview Isolation



If a tool provides rendered HTML preview, it must use an isolated environment.



Preferred conceptual model:



```text

Main CMS Page

      │

      └── Isolated Preview Context

              │

              └── User HTML

```



The preview must not gain access to:



* CMS authentication state.

* Parent-page DOM.

* Parent-page privileged JavaScript.

* CMS internal APIs.

* Sensitive browser storage where avoidable.



Where browser isolation mechanisms are used, configure them according to the actual security requirements of the preview.



---



# 15. CSS Security



CSS input is also untrusted.



Malicious or unexpected CSS may attempt to affect surrounding page content.



A CSS preview must not allow user CSS to unintentionally modify:



* CMS navigation.

* Admin UI.

* Tool configuration UI.

* Other tool content.

* Global page styles.



Use an isolated preview context where CSS is rendered.



---



# 16. JavaScript Security



JavaScript is the highest-risk browser-side tool category.



The system must distinguish between:



```text

JavaScript Processing

```



and:



```text

JavaScript Execution

```



Formatter/minifier/validator tools do not require execution.



---



# 17. Arbitrary JavaScript Execution



Arbitrary user JavaScript must not run with access to the main Favorite CMS page.



If JavaScript execution is intentionally supported in the future, it must run inside an explicitly isolated environment.



The isolation must prevent unnecessary access to:



* Parent DOM.

* CMS session state.

* Sensitive storage.

* Privileged APIs.

* Internal application state.



Do not provide arbitrary JavaScript execution simply because a tool has the JavaScript engine.



---



# 18. Browser JavaScript Is Not Secret



Any JavaScript delivered to the browser must be considered inspectable.



Never place in frontend JavaScript:



* API secrets.

* Private API keys.

* Python service credentials.

* Database credentials.

* Internal authentication secrets.

* Private signing keys.

* Proprietary server-only algorithms that must remain secret.



If proprietary processing must remain private, move it to server-side PHP or a protected Python API service.



---



# 19. PHP Security Boundary



PHP tools must never become arbitrary PHP execution.



The system must not execute user-provided PHP source code.



Do not use:



```text

eval()

```



or equivalent mechanisms to execute arbitrary PHP.



PHP tools must use controlled server-side implementations/handlers.



Conceptually:



```text

Tool

 ↓

Registered PHP Handler

 ↓

Validated Input

 ↓

Controlled Processing

 ↓

Result

```



---



# 20. Shell and System Command Security



Tools must not expose arbitrary server command execution.



Do not provide user-controlled execution of:



* Shell commands.

* CMD commands.

* PowerShell commands.

* System utilities.

* Arbitrary executables.



If a future tool genuinely requires a server executable, it must use a tightly controlled implementation with:



* Fixed executable.

* Fixed argument structure.

* Validated inputs.

* Restricted permissions.

* Controlled working directory.

* Resource limits where appropriate.



Never concatenate raw user input into shell commands.



---



# 21. Python Security Boundary



Python tools use external API services.



Favorite Web Tools must not execute arbitrary user Python source on the CMS server.



Conceptually:



```text

User

 ↓

Favorite Web Tools

 ↓

Validation

 ↓

Python API Service

 ↓

Controlled Python Implementation

 ↓

Result

```



The Python service itself must implement its own security boundaries.



---



# 22. Python API Credentials



Python service credentials must remain server-side.



Credentials must never be returned in:



* HTML.

* JavaScript.

* Tool metadata.

* Public API responses.

* Browser network payloads.



Use existing secure environment/configuration/secret mechanisms.



Do not hard-code secrets into source files when the repository provides a safer mechanism.



---



# 23. External API Security



Python/API requests must validate:



* Allowed service.

* Allowed endpoint.

* HTTP method.

* Request structure.

* Response format.



A public user must not be able to change:



```text

https://configured-service.example

```



into an arbitrary destination.



---



# 24. SSRF Protection



Server-side HTTP requests create a potential SSRF risk.



The plugin must not allow arbitrary user-supplied URLs to become server-side destinations unless the tool explicitly requires URL processing and implements appropriate restrictions.



For configured Python services:



```text

Tool

 ↓

Registered Service

 ↓

Configured Endpoint

```



The destination should come from trusted server-side configuration.



Do not accept an arbitrary API destination from a normal public execution request.



---



# 25. User-Supplied URL Tools



Some legitimate tools may intentionally process user-provided URLs.



Examples:



* URL parser.

* URL encoder/decoder.

* Metadata processing.

* Link validation.



If a server-side tool fetches a user-provided URL, it must apply appropriate SSRF protections.



Potential controls include:



* Allowed protocols.

* Blocking local/private network destinations.

* Blocking loopback.

* Blocking internal hostnames.

* Restricting redirects.

* Validating resolved addresses.

* Limiting response size.

* Limiting connection time.



The exact implementation depends on the tool's requirements.



---



# 26. File Upload Security



Uploaded files are untrusted.



The system must validate:



* File size.

* MIME type.

* Extension.

* Expected file format.

* Upload error state.

* Storage location.



Do not trust the filename or browser-provided MIME type alone.



---



# 27. Upload Storage



Uploaded files should use the existing Favorite CMS storage mechanisms where appropriate.



Temporary processing files should:



* Use controlled temporary locations.

* Have restricted permissions.

* Be cleaned after processing.

* Not become executable server files.



Do not store uploads inside publicly executable code directories.



---



# 28. File Name Security



Never directly use a user-provided filename as a server path.



Avoid path traversal such as:



```text

../../some-file

```



Generate controlled internal names or use the CMS storage abstraction.



---



# 29. File Type Security



Extension checks alone are insufficient.



Where relevant, validate:



* Actual MIME type.

* File signature/magic bytes.

* Parser compatibility.



Only process formats explicitly supported by the tool.



Do not automatically process arbitrary uploaded files.



---



# 30. Archive Security



If archive formats are ever supported, archive extraction must protect against:



* Path traversal.

* Zip Slip.

* Excessive expansion.

* Recursive archives.

* Unexpected executable files.



Archive processing should be implemented only when specifically required by a tool.



---



# 31. Large Input Protection



Tools must protect infrastructure from excessively large requests.



Possible technical controls:



* Maximum request body size.

* Maximum input length.

* Maximum uploaded file size.

* Maximum API response size.

* Processing timeout.



These are **technical safety controls**, not product usage limits.



They must not be represented as:



```text

daily quota

monthly quota

credits

tokens

usage limit

```



---



# 32. Processing Timeout



Long-running tools must have appropriate execution timeouts.



Python API requests should have configured timeouts.



A timeout should return a controlled error.



Example:



```text

PROCESSING\_TIMEOUT

```



Do not leave server requests indefinitely open.



---



# 33. Memory and Resource Protection



Where tools process large inputs, implementation should use appropriate resource controls.



Examples:



* Streaming files.

* Temporary files.

* Bounded buffers.

* Processing timeouts.

* Maximum payload sizes.



These controls protect infrastructure and are not user-usage quotas.



---



# 34. Rate Limiting



Infrastructure-level rate limiting may be used when necessary to protect the service from abuse or denial-of-service conditions.



If implemented, it must remain a security/infrastructure control.



It must not become a product model based on:



* Daily user limits.

* Monthly user limits.

* Tool credits.

* Tool tokens.

* Membership usage quotas.



Membership remains unlimited under the product model.



---



# 35. XSS Protection



The plugin must protect against stored and reflected XSS.



Important areas include:



* Tool names.

* Descriptions.

* Help text.

* Category names.

* User inputs.

* Tool outputs.

* Error messages.

* Search parameters.

* Admin configuration fields.



Output encoding must use the appropriate context.



Do not blindly HTML-escape data that is intentionally being returned as code/source output; instead, render it safely according to its intended output context.



---



# 36. Stored Tool Metadata



Administrator-created tool metadata must still be treated carefully.



Fields such as:



```text

name

description

help

SEO fields

```



must be safely escaped when rendered.



Do not assume administrator input is automatically safe in every rendering context.



---



# 37. JSON Security



JSON input must be parsed using safe JSON parsing mechanisms.



Invalid JSON must return a controlled validation error.



Do not:



* Execute JSON as code.

* Convert JSON into arbitrary PHP expressions.

* Treat JSON keys as executable commands.



JSON output must use proper JSON serialization.



---



# 38. Regular Expression Security



If a Regex tool is implemented, regular expressions are untrusted input.



The implementation should protect against excessive regex processing where the selected engine/library is vulnerable to catastrophic backtracking.



Where supported, apply:



* Input-size limits.

* Processing timeouts.

* Safe regex libraries/settings.

* Controlled execution environments.



---



# 39. Encoding/Decoding Tools



Encoding/decoding tools must treat decoded content as data.



For example:



```text

Base64 Decode

```



must not automatically execute the decoded result.



Likewise:



```text

URL Decode

HTML Decode

```



must return data rather than treating it as trusted executable content.



---



# 40. Download Security



Download results must use controlled references.



Do not expose raw server filesystem paths.



Conceptually:



```text

Tool Result

 ↓

Controlled File Reference

 ↓

Authorized Download Handler

 ↓

File

```



Where appropriate, download references should be:



* Unpredictable.

* Validated.

* Time-limited where needed.

* Bound to the relevant request/user context where necessary.



---



# 41. Path Traversal Protection



Never construct filesystem paths directly from untrusted input without validation.



Protect against:



```text

../

..\\

absolute paths

encoded traversal

```



Use existing CMS storage/path utilities whenever available.



---



# 42. SQL Injection Protection



All database operations must use the existing Favorite CMS database abstraction and prepared queries.



Never concatenate user input directly into SQL.



Examples of untrusted values include:



* Search terms.

* Tool slugs.

* Category filters.

* IDs.

* Input values.

* Admin form data.



---



# 43. Tool Slug Security



Tool slugs are user-facing identifiers but must still be validated.



The server must:



* Validate slug format.

* Resolve the slug through the registry.

* Avoid interpreting slug content as filesystem paths.

* Avoid using raw slug values in SQL.



---



# 44. Search Security



Tool discovery/search must treat search input as untrusted.



Search must:



* Use safe parameterized queries.

* Search only intended public fields.

* Not expose internal configuration.

* Not expose disabled/draft tools publicly.

* Avoid arbitrary query fragments.



---



# 45. Error Handling



Public errors must be safe.



Do not expose:



* Stack traces.

* Database credentials.

* SQL statements.

* Filesystem paths.

* API credentials.

* Internal server topology.

* Private endpoint credentials.

* Sensitive configuration.



Example public error:



```text

The tool could not process your request.

```



Internal logs may contain additional technical information where appropriate.



---



# 46. Logging Security



Logs must not unnecessarily contain:



* Passwords.

* API keys.

* Bearer tokens.

* Cookies.

* Session secrets.

* Private credentials.



Input logging must be carefully considered because tool inputs may contain sensitive user-provided data.



Log technical metadata rather than entire user payloads unless there is a clear operational requirement.



---



# 47. Secret Redaction



If an error or diagnostic structure contains credentials or secrets, they must be redacted before logging or returning the data.



Examples:



```text

API\_KEY=\*\*\*\*\*\*\*\*

Authorization=\*\*\*\*\*\*\*\*

```



Never return raw authorization headers to the browser.



---



# 48. Admin Test Security



Admin tool testing must still enforce:



* Admin authorization.

* CSRF protection where applicable.

* Input validation.

* Safe output rendering.

* Python service credential protection.



Admin test mode must not become a hidden arbitrary execution endpoint.



---



# 49. Preview Security



Preview functionality must be treated as an execution boundary.



For HTML/CSS/JavaScript previews:



```text

User Content

 ↓

Isolated Preview

```



Never place untrusted preview content directly into privileged CMS/admin DOM unless it has been appropriately sanitized and the feature explicitly requires it.



---



# 50. Content Security Policy



Where compatible with the existing CMS and preview architecture, appropriate Content Security Policy controls should be used.



Do not introduce a plugin-wide CSP that unexpectedly breaks the existing CMS.



For isolated previews, stronger restrictions may be applied specifically to the preview context.



---



# 51. iframe Security



If iframes are used for previews or external content:



* Restrict capabilities.

* Use appropriate sandboxing.

* Avoid unnecessary permissions.

* Prevent unnecessary parent-page access.

* Validate external origins where relevant.



Do not grant broad iframe privileges by default.



---



# 52. External Content



Tools that display external content must treat it as untrusted.



External content must not automatically gain access to:



* CMS credentials.

* Parent application state.

* Admin functionality.

* Internal APIs.



---



# 53. API Response Security



Python API responses must be treated as untrusted external data.



The plugin must validate expected response structures before rendering or forwarding them.



Do not blindly trust:



```text

HTML

JSON

URLs

File references

```



returned by an external service.



---



# 54. Output Rendering Security



Output rendering depends on output type.



### TEXT



Render as text.



### HTML



Render only through an intentionally safe HTML output/preview path.



### JSON



Serialize/render safely.



### FILE



Use controlled file references.



### IMAGE/AUDIO/VIDEO



Use validated media references.



### DOWNLOAD



Use controlled download handling.



Do not treat every result as trusted HTML.



---



# 55. Authentication State Leakage



Tool pages and API responses must not expose unnecessary authentication information.



Do not expose:



* Session identifiers.

* Authentication tokens.

* Membership credentials.

* Internal user database fields.



Only the minimum public access state needed for UI should be returned.



---



# 56. Membership State Leakage



For membership-protected tools, public responses should not reveal unnecessary internal membership information.



The frontend only needs enough information to communicate the access requirement, such as:



```text

Membership Required

```



Detailed membership records remain within the existing CMS membership system.



---



# 57. Timing and Enumeration Considerations



The implementation should avoid unnecessarily revealing internal information through:



* Different errors for sensitive resources.

* Predictable private file references.

* Internal IDs.

* Hidden service names.

* Database details.



However, do not introduce complex security mechanisms unless they are actually required.



---



# 58. Tool Enumeration



Public discovery should expose only intended public tool metadata.



DRAFT and DISABLED tools should not be publicly enumerable through normal discovery APIs.



Protected ACTIVE tools may be discoverable because access requirements are part of the public tool experience.



Private configuration must remain hidden.



---



# 59. Python Service Enumeration



Python service records are administrative infrastructure.



Public APIs must never expose:



* Service IDs unnecessarily.

* Private service URLs where inappropriate.

* Authentication type details when sensitive.

* Credentials.

* Internal health information.



Only safe tool-level metadata should be public.



---



# 60. Dependency Security



A tool must not execute against an unexpected dependency.



For example:



```text

Tool A

 ↓

Configured Python Service

```



must not be changed by a public request to:



```text

Attacker-controlled Service

```



Dependencies must be resolved from trusted configuration.



---



# 61. Configuration Integrity



Tool configuration must be validated before execution.



If a configuration record is malformed or inconsistent:



```text

Execution = rejected

```



Do not attempt unsafe fallback behavior.



---



# 62. Fail-Safe Principle



When an important security dependency cannot be verified, fail closed.



Examples:



```text

Membership verification unavailable

→ deny protected execution

```



```text

Python service configuration invalid

→ reject execution

```



```text

Tool status unavailable

→ reject execution

```



Do not grant access because verification failed.



---



# 63. Security and Caching



Cached public metadata must never bypass:



* Authentication.

* Membership checks.

* Tool status checks.

* Authorization.



Authorization must use authoritative current state.



Do not cache a user's protected access decision as public tool metadata.



---



# 64. Security and Client State



The server must not trust:



* Cookies beyond normal CMS session validation.

* Local storage.

* Session storage.

* Hidden inputs.

* JavaScript variables.

* URL parameters claiming membership.

* Client-side access flags.



Client state is presentation data, not authorization.



---



# 65. Security and API Methods



Execution requests must use the HTTP methods appropriate to the CMS/API design.



State-changing or processing endpoints should not rely on GET requests when that would create unnecessary security risk.



Exact routing and method conventions must follow Favorite CMS.



---



# 66. Security and Database Records



Plugin-owned records must be protected through:



* Authorized admin operations.

* Prepared queries.

* Validated IDs.

* Controlled relationships.

* Existing CMS database mechanisms.



Users must never be able to directly choose arbitrary database records outside their permitted operation.



---



# 67. Security and Migrations



Database migrations must only modify plugin-owned schema.



A migration must not:



* Modify CMS authentication tables unnecessarily.

* Modify unrelated plugin tables.

* Remove security-related CMS structures.

* Create duplicate security infrastructure.



Follow:



`13-DATABASE-MIGRATION-SYSTEM.md`



---



# 68. Security and File System



The plugin must remain within its filesystem boundary.



Do not allow user input to determine arbitrary plugin paths.



Do not write generated files into PHP-executable plugin directories unless explicitly required and secured.



Generated files should use appropriate CMS storage or controlled temporary storage.



---



# 69. Security and Plugin Isolation



The plugin must not modify:



* Favorite CMS core.

* Existing authentication.

* Existing membership system.

* Existing routing framework.

* Existing database abstraction.

* Existing admin framework.

* Unrelated plugins.



Security must be achieved through integration with the existing architecture.



---



# 70. Security Testing



Before production activation, test at minimum:



### Authentication



* Anonymous access.

* Logged-in access.

* Logged-in user without membership.

* Active member access.



### Authorization



* Free tool.

* Login-required tool.

* Membership-required tool.

* Disabled tool.

* Draft tool.



### Input



* Empty values.

* Oversized values.

* Invalid URLs.

* Invalid JSON.

* Invalid files.

* Malformed data.



### Frontend



* XSS payloads.

* HTML preview isolation.

* CSS isolation.

* JavaScript isolation.



### Server



* SQL injection attempts.

* Path traversal attempts.

* SSRF attempts.

* Unauthorized configuration changes.

* Unauthorized tool execution.



### API



* Invalid Python service.

* Invalid endpoint.

* Timeout.

* Malformed response.

* Credential exposure checks.



---



# 71. Security Test Principle



Security tests must verify that the attacker cannot bypass server-side controls by modifying the frontend.



Example:



```text

Frontend says:

"Membership Required"



Attacker modifies frontend:

"FREE"



Backend:

→ still checks actual membership

→ access denied

```



The backend remains authoritative.



---



# 72. Security vs Product Features



Security mechanisms must not accidentally become product restrictions.



For example:



```text

Maximum upload size

```



is an infrastructure protection.



It must not be represented as:



```text

User has 10 credits

```



The product model remains:



```text

FREE → unlimited

LOGIN\_REQUIRED → unlimited

MEMBERSHIP\_REQUIRED → unlimited for active members

```



---



# 73. AI Agent Implementation Rules



When implementing this security system, the AI agent must:



1. Inspect the actual repository before implementation.

2. Identify existing CMS security mechanisms.

3. Reuse existing authentication.

4. Reuse existing authorization.

5. Reuse existing CSRF protection.

6. Reuse existing database security.

7. Reuse existing storage mechanisms.

8. Reuse existing logging facilities.

9. Implement plugin-specific security only where required.

10. Never create a second authentication system.

11. Never create a second authorization system.

12. Never create a second CSRF framework.

13. Never create a second database abstraction.

14. Never expose secrets to the browser.

15. Never execute arbitrary PHP.

16. Never execute arbitrary Python source.

17. Never execute arbitrary shell commands.

18. Never trust browser authorization state.

19. Never trust user-provided filesystem paths.

20. Never allow arbitrary API destinations for configured services.

21. Protect server-side HTTP requests against SSRF where applicable.

22. Protect previews from affecting the CMS.

23. Validate all uploaded files.

24. Validate external API responses.

25. Use safe output rendering.

26. Fail closed when critical authorization/dependency checks fail.

27. Do not introduce product usage limits.

28. Keep Favorite API Connector separate.

29. Do not modify CMS core or unrelated plugins.

30. Follow actual repository conventions rather than assumptions.



---



# 74. Required Acceptance Criteria



The implementation is acceptable only when:



* Browser state cannot bypass authorization.

* Anonymous access follows the configured access mode.

* Login-required tools require authentication server-side.

* Membership-required tools require active membership server-side.

* Disabled tools cannot execute.

* Draft tools cannot execute publicly.

* Tool configuration cannot be overridden through public requests.

* HTML previews are isolated.

* CSS previews cannot unexpectedly modify CMS UI.

* JavaScript processing does not imply arbitrary execution.

* Arbitrary JavaScript execution is isolated if ever supported.

* Arbitrary PHP execution is prohibited.

* Arbitrary Python source execution is prohibited.

* Arbitrary shell execution is prohibited.

* Python API credentials remain server-side.

* Configured API destinations cannot be replaced by attacker-controlled destinations.

* Applicable SSRF risks are mitigated.

* File uploads are validated.

* Uploaded filenames cannot cause path traversal.

* Generated files are stored safely.

* Download results do not expose filesystem paths.

* SQL queries use safe parameterization/CMS database mechanisms.

* XSS risks are addressed.

* JSON is parsed and rendered safely.

* External API responses are validated.

* Sensitive errors are not exposed publicly.

* Secrets are not written to logs unnecessarily.

* Technical resource limits may exist for infrastructure protection but are not product usage quotas.

* No credits, tokens, or usage counters are introduced.

* Existing CMS security systems are reused.

* Plugin isolation is preserved.

* Favorite API Connector remains separate.

* CMS core remains unchanged.



---



# 75. Final Security Model



The complete security boundary is:



```text

                         USER

                           │

                           ▼

                       BROWSER

                           │

                    UNTRUSTED INPUT

                           │

                           ▼

                    FRONTEND VALIDATION

                           │

                           ▼

                    EXECUTION REQUEST

                           │

                           ▼

                 ┌────────────────────┐

                 │ SERVER-SIDE CHECKS │

                 ├────────────────────┤

                 │ Tool Exists        │

                 │ Tool ACTIVE        │

                 │ Authentication     │

                 │ Membership         │

                 │ Input Validation   │

                 │ Configuration      │

                 └─────────┬──────────┘

                           │

                           ▼

                    ENGINE RESOLUTION

                           │

             ┌─────────────┼─────────────┐

             │             │             │

            HTML          CSS           JS

             │             │             │

             │             │        Isolated if

             │             │        execution exists

             │             │

             └─────────────┼─────────────┘

                           │

                    ┌──────┴──────┐

                    │             │

                   PHP        PYTHON API

                    │             │

             Controlled       Registered

               Handler          Service

                    │             │

                    └──────┬──────┘

                           │

                           ▼

                    OUTPUT VALIDATION

                           │

                           ▼

                      SAFE RESULT

```



The fundamental rule is:



```text

UNTRUSTED USER INPUT

        ≠

TRUSTED EXECUTABLE CODE

```



Favorite Web Tools must remain a controlled tool platform, not an arbitrary code-execution platform.




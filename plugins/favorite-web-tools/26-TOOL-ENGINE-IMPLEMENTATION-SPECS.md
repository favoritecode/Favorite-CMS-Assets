# 26 — TOOL ENGINE IMPLEMENTATION SPECS



## 1. Purpose



This document defines the implementation contract for the five supported engines of `favorite-web-tools`:



```text

HTML

CSS

JAVASCRIPT

PHP

PYTHON\_API

```



The goal is to provide a consistent architecture for:



* Input processing.

* Validation.

* Execution.

* Output generation.

* Error handling.

* Frontend interaction.

* Server-side processing.

* External API processing.



The implementation must follow the previously defined:



* Tool Registry.

* Tool Configuration.

* Access Control.

* Input/Output System.

* Execution API.

* Security/Sandbox.

* Python Service System.



---



# 2. Engine Architecture



All engines follow the same high-level flow:



```text

User

 ↓

Tool Page

 ↓

Execution Request

 ↓

Tool Registry

 ↓

Status Check

 ↓

Access Control

 ↓

Input Validation

 ↓

Engine Resolver

 ↓

Selected Engine

 ↓

Processing

 ↓

Standard Result

 ↓

Frontend Renderer

```



---



# 3. Engine Responsibilities



Each engine is responsible only for its own processing.



```text

HTML Engine

CSS Engine

JavaScript Engine

PHP Engine

Python API Engine

```



The engines must not implement:



* Authentication.

* Membership.

* Tool discovery.

* Admin permissions.

* Routing framework.

* Database framework.



Those responsibilities belong to the existing CMS/plugin systems.



---



# 4. Common Engine Contract



Every engine should conceptually support:



```text

Can Handle

Validate

Execute

Return Result

```



Exact class/interface names must follow the actual Favorite CMS coding conventions.



---



# 5. Can Handle



The engine resolver determines whether an engine can process a configured tool.



Conceptually:



```text

Tool Engine

      ↓

ENGINE = HTML

      ↓

HTML Engine

```



---



# 6. Validate



Before processing, the engine may validate engine-specific requirements.



Examples:



```text

HTML → valid input structure

CSS → valid CSS-related input

JS → valid source/processing configuration

PHP → valid controlled handler

Python → valid service/endpoint configuration

```



---



# 7. Execute



The engine receives validated tool inputs and executes the configured processing logic.



It must not receive arbitrary configuration from the public request.



---



# 8. Standard Result



Every engine must return the common execution result format.



Conceptually:



```json

{

  "success": true,

  "data": {

    "type": "TEXT",

    "value": "..."

  }

}

```



Failure:



```json

{

  "success": false,

  "error": {

    "code": "PROCESSING\_ERROR",

    "message": "Unable to process the input."

  }

}

```



The actual response format must follow Favorite CMS conventions.



---



# 9. HTML ENGINE



## 9.1 Purpose



The HTML Engine handles controlled HTML-related processing.



Potential tools include:



* HTML Formatter.

* HTML Minifier.

* HTML Validator.

* HTML Encoder.

* HTML Decoder.

* HTML Preview.

* HTML-related converters/processors.



---



## 9.2 HTML Processing



The engine may perform transformations such as:



```text

HTML Input

 ↓

Parser/Processor

 ↓

Formatted HTML

```



or:



```text

HTML Input

 ↓

Minifier

 ↓

Minified HTML

```



---



## 9.3 HTML Formatter



A formatter should:



* Parse/recognize HTML structure.

* Apply consistent indentation.

* Preserve meaningful content.

* Return formatted source.



It must not execute arbitrary embedded server-side code.



---



## 9.4 HTML Minifier



The minifier may remove unnecessary:



* Whitespace.

* Comments where configured.

* Formatting characters.



It must preserve meaningful HTML behavior.



---



## 9.5 HTML Validator



Validation should identify malformed HTML where the selected implementation supports it.



The result should distinguish:



```text

Valid

Invalid

```



and provide useful error information where available.



---



## 9.6 HTML Encoder/Decoder



Encoding and decoding tools should treat input as data.



They must not automatically execute decoded HTML.



---



## 9.7 HTML Preview



HTML preview is a rendering feature.



Previewed HTML must be isolated from the main CMS application.



Appropriate mechanisms may include:



* Sandboxed iframe.

* Restricted preview document.

* Suitable CSP.



---



## 9.8 HTML Security



User HTML must never gain access to:



* CMS session.

* Admin DOM.

* Parent application data.

* CMS privileged APIs.



---



# 10. CSS ENGINE



## 10.1 Purpose



The CSS Engine handles controlled CSS processing.



Potential tools include:



* CSS Formatter.

* CSS Minifier.

* CSS Prefixer.

* CSS Color Converter.

* CSS Validator.

* CSS Preview.



---



## 10.2 CSS Formatter



The formatter should normalize CSS structure and indentation.



---



## 10.3 CSS Minifier



The minifier may remove unnecessary:



* Whitespace.

* Comments.

* Formatting characters.



It must preserve valid CSS semantics as far as the selected processor supports.



---



## 10.4 CSS Prefixer



A prefixer may add required vendor prefixes according to the selected processing implementation.



The implementation should not invent browser support rules.



Use a maintained processing library where appropriate and compatible with the project.



---



## 10.5 CSS Color Converter



Color conversion tools may support configured formats such as:



```text

HEX

RGB

RGBA

HSL

HSLA

```



Only formats actually implemented should be exposed in the tool configuration.



---



## 10.6 CSS Validator



The validator should identify malformed or invalid CSS according to the selected processing implementation.



---



## 10.7 CSS Preview



User CSS must be rendered in an isolated preview.



It must not modify:



* CMS Header.

* CMS Footer.

* Admin interface.

* Other site components.

* Parent application styles.



---



## 10.8 CSS Security



Never directly append untrusted CSS into the main application's global stylesheet.



---



# 11. JAVASCRIPT ENGINE



## 11.1 Purpose



The JavaScript Engine handles JavaScript-related processing.



Potential tools include:



* JavaScript Formatter.

* JavaScript Minifier.

* JavaScript Validator.

* JavaScript Beautifier.

* JavaScript Encoder/Decoder where appropriate.



---



## 11.2 Processing vs Execution



This distinction is mandatory.



```text

PROCESSING

≠

EXECUTION

```



Processing means treating JavaScript as source/data.



Execution means actually running JavaScript.



---



## 11.3 JavaScript Formatter



A formatter should:



* Parse/format source.

* Apply configured indentation.

* Return formatted source.

* Not execute the source.



---



## 11.4 JavaScript Minifier



The minifier should process source code into a compact representation.



It must not execute the supplied source.



---



## 11.5 JavaScript Validator



Validation may parse the source and identify syntax problems.



It must not execute the supplied source merely for validation.



---



## 11.6 JavaScript Preview



If a tool intentionally provides JavaScript execution, execution must occur in an isolated environment.



The default behavior should be processing rather than arbitrary execution.



---



## 11.7 JavaScript Execution Boundary



User-supplied JavaScript must not gain access to:



* CMS authentication state.

* Admin APIs.

* Parent page privileged objects.

* Server credentials.

* Internal plugin configuration.



---



## 11.8 Browser Visibility



Any JavaScript shipped to the browser should be considered public/inspectable.



Never place secrets inside:



```text

HTML

CSS

JavaScript

Browser configuration

```



---



# 12. PHP ENGINE



## 12.1 Purpose



The PHP Engine provides controlled server-side PHP-based tool processing.



PHP tools may be used for:



* Complex server-side transformations.

* File processing.

* Database-independent utilities.

* Specialized backend operations.



---



## 12.2 Controlled Handlers



PHP tools must reference predefined/controlled handlers.



Conceptually:



```text

Tool

 ↓

PHP Handler ID

 ↓

Controlled PHP Implementation

```



---



## 12.3 No Arbitrary PHP



The system must never interpret a public/admin configuration field as arbitrary PHP source.



Do not design the engine around:



```php

eval($user\_code);

```



or equivalent dynamic source execution.



---



## 12.4 Handler Registry



If the repository architecture supports it, controlled PHP handlers may be registered through a handler/engine registry.



The exact implementation must follow Favorite CMS conventions.



---



## 12.5 PHP Input



Inputs arrive through the standard Tool Execution API.



The PHP handler must validate and normalize inputs according to its tool definition.



---



## 12.6 PHP Output



PHP handlers must return the standard tool result format.



Possible outputs:



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



## 12.7 File Processing



PHP tools that process uploaded files must:



* Validate upload state.

* Validate MIME/type.

* Validate extension where relevant.

* Use safe temporary storage.

* Avoid trusting original filenames.

* Clean temporary files appropriately.



---



## 12.8 Filesystem Boundary



A PHP tool must not allow user input to directly determine arbitrary filesystem paths.



Use controlled storage/reference mechanisms.



---



## 12.9 Command Execution



The PHP Engine must not provide a generic shell command execution capability.



Do not allow tool configuration to execute:



```text

CMD

PowerShell

Shell

System commands

```



---



# 13. PYTHON API ENGINE



## 13.1 Purpose



The Python API Engine allows Favorite Web Tools to use Python processing through an external service.



It does not execute arbitrary Python source inside Favorite CMS.



---



## 13.2 Architecture



```text

User

 ↓

Favorite Web Tools

 ↓

Python API Engine

 ↓

Python Service

 ↓

Processing

 ↓

API Response

 ↓

Favorite Web Tools

 ↓

User

```



---



## 13.3 Python Service



A Python Service is a separately configured service.



It may run:



* On the same server.

* On another server.

* On a private network where supported.

* In another infrastructure environment.



---



## 13.4 Service Configuration



The Python Service system may contain:



```text

Service Name

Base URL

Authentication

Credential Reference

Timeout

Status

```



Credentials remain server-side.



---



## 13.5 Tool Configuration



A Python tool may reference:



```text

python\_service\_id

endpoint

method

request\_mapping

response\_mapping

timeout

```



---



## 13.6 User Request



The user supplies only configured tool inputs.



The public request must not be able to override:



```text

Base URL

Endpoint

Credentials

Authentication

Service ID

```



unless an explicit trusted admin operation requires it.



---



## 13.7 Request Mapping



Example:



```text

Tool Input

    │

    ▼

Request Mapping

    │

    ▼

Python API

```



Example:



```text

Tool field:

text



API field:

input\_text

```



---



## 13.8 Response Mapping



The response mapping converts the Python service response into the standard tool result.



Example:



```text

Python API

{

  "result": "processed data"

}

        ↓

Tool Output

TEXT

```



---



## 13.9 Python Authentication



Supported authentication should follow the Python Service architecture.



Possible mechanisms:



* API key.

* Bearer token.

* Basic authentication.

* Other configured mechanisms.



Credentials must never be sent to the browser.



---



## 13.10 Python Timeout



Each request must have an appropriate technical timeout.



Timeouts prevent indefinitely hanging requests.



They are not usage limits.



---



## 13.11 Python Response Validation



The engine must validate:



* HTTP status.

* Expected response format.

* Required fields.

* File/result references where applicable.



---



## 13.12 Python Error Handling



Possible errors:



```text

SERVICE\_UNAVAILABLE

TIMEOUT

AUTHENTICATION\_FAILED

INVALID\_RESPONSE

PROCESSING\_ERROR

```



The user should receive a safe, understandable error.



Internal credentials and infrastructure details must remain hidden.



---



# 14. ENGINE RESOLVER



The Engine Resolver selects the correct engine from tool configuration.



Conceptually:



```text

Tool

 ↓

engine

 ↓

Resolver

 ├── HTML

 ├── CSS

 ├── JAVASCRIPT

 ├── PHP

 └── PYTHON\_API

```



---



# 15. Engine Registration



The engine system should support registration according to the CMS/plugin architecture.



Do not hard-code engine logic into the main request controller if the repository provides an extensible service/hook system.



---



# 16. Engine Independence



Adding a future engine should not require rewriting:



* Tool Registry.

* Access Control.

* Frontend architecture.

* Discovery system.

* Execution API.



It should plug into the existing engine contract.



---



# 17. Access Before Execution



Every server-side execution must follow:



```text

Request

 ↓

Tool Exists?

 ↓

ACTIVE?

 ↓

Access Allowed?

 ↓

Input Valid?

 ↓

Engine

```



Never execute the engine before authorization.



---



# 18. FREE Tools



For:



```text

FREE

```



tools, anonymous and authenticated users may execute subject to normal technical/security validation.



---



# 19. LOGIN\_REQUIRED Tools



For:



```text

LOGIN\_REQUIRED

```



the existing CMS authentication state must be checked before execution.



---



# 20. MEMBERSHIP\_REQUIRED Tools



For:



```text

MEMBERSHIP\_REQUIRED

```



the existing membership state must be checked before execution.



Active membership provides unlimited access.



---



# 21. No Usage Limits



No engine may implement:



```text

Daily usage

Monthly usage

Credits

Tokens

Quota

Remaining uses

```



---



# 22. Technical Resource Controls



Engines may use technical controls necessary for system stability, such as:



* Maximum request body.

* Maximum upload size.

* Processing timeout.

* Memory-safe processing.

* Maximum output size where necessary.

* Infrastructure rate limiting for abuse protection.



These are technical safeguards, not product usage quotas.



---



# 23. Input Contract



Every engine receives inputs defined by the Tool Configuration.



Example:



```json

{

  "inputs": {

    "source": "<input>"

  }

}

```



Unknown input fields should not be blindly trusted.



---



# 24. Engine Validation



Engine-specific validation should complement—not replace—the central input validation system.



---



# 25. Output Contract



All engines should normalize results into the standard Output System.



Example:



```text

Engine

 ↓

Normalized Result

 ↓

Output Renderer

```



---



# 26. TEXT Result



Use TEXT for plain textual results.



The frontend should display it safely.



---



# 27. HTML Result



HTML output must distinguish:



```text

HTML Source

```



from:



```text

Rendered HTML Preview

```



Rendered content must be isolated.



---



# 28. JSON Result



JSON results should remain structured.



Do not convert JSON to unsafe HTML manually without proper escaping/rendering.



---



# 29. File Result



File results should use controlled file references.



Never expose:



```text

C:\\server\\...

/var/www/...

/home/...

```



filesystem paths to the client.



---



# 30. Media Result



Media outputs should use controlled references/URLs.



The frontend should render them using the appropriate output renderer.



---



# 31. Download Result



Downloads should use the controlled download system defined by the Execution API.



Authorization must be checked where required.



---



# 32. Large Results



For large outputs:



* Avoid unnecessarily embedding huge content in JSON.

* Use controlled file/result references where appropriate.

* Clean temporary resources according to the CMS/storage architecture.



---



# 33. Client-Side Processing



HTML/CSS/JavaScript tools may process data entirely in the browser when:



* The operation is safe.

* No secret is required.

* No proprietary server-side logic needs protection.

* Performance is acceptable.



---



# 34. Server-Side Processing



Server-side processing should be used when:



* Proprietary logic must remain server-side.

* Large/complex processing requires the server.

* Files require controlled backend processing.

* PHP is required.

* Python API is required.

* Secrets/API credentials are involved.



---



# 35. Hybrid Tools



A tool may use both:



```text

Browser UI

\+

Server Processing

```



where appropriate.



The split must be explicit.



---



# 36. Browser-Side Security



Client-side code is not a security boundary.



Never rely on frontend code to enforce:



```text

Login

Membership

Secret API key

Admin permissions

```



---



# 37. Engine Errors



Engines should return normalized errors rather than leaking raw exceptions.



Possible categories:



```text

INVALID\_INPUT

CONFIGURATION\_ERROR

PROCESSING\_ERROR

DEPENDENCY\_ERROR

SERVICE\_ERROR

TIMEOUT

OUTPUT\_ERROR

```



---



# 38. Logging



Technical errors may be logged using the existing CMS logging system.



Logs must not contain:



* Passwords.

* API keys.

* Bearer tokens.

* Sensitive user input unless necessary.

* Secret configuration.



---



# 39. Engine Testing



Each engine should have unit/integration tests appropriate to its implementation.



Minimum coverage:



```text

Valid Input

Invalid Input

Valid Configuration

Invalid Configuration

Successful Processing

Processing Failure

Output Normalization

Security Boundary

```



---



# 40. HTML Test Cases



Test:



* Formatting.

* Minification.

* Validation.

* Encoding/decoding.

* Preview isolation.



---



# 41. CSS Test Cases



Test:



* Formatting.

* Minification.

* Prefixing where implemented.

* Color conversion.

* Validation.

* Preview isolation.



---



# 42. JavaScript Test Cases



Test:



* Formatting.

* Minification.

* Syntax validation.

* Non-execution of processing tools.

* Execution isolation if an execution tool is intentionally implemented.



---



# 43. PHP Test Cases



Test:



* Controlled handler resolution.

* Valid input.

* Invalid input.

* Output normalization.

* File processing where applicable.

* Rejection of arbitrary source execution.



---



# 44. Python Test Cases



Test:



* Service resolution.

* Authentication.

* Request mapping.

* Successful response.

* Invalid response.

* Timeout.

* Service unavailable.

* Credential protection.



---



# 45. Engine Dependency Failure



If a required engine dependency is unavailable:



```text

Tool Request

 ↓

Dependency unavailable

 ↓

Controlled Error

```



Do not silently execute an unsafe fallback.



---



# 46. Engine Configuration Versioning



Configuration versioning may be introduced only if the actual implementation requires it.



Do not create unnecessary versioning complexity.



---



# 47. Library Selection



Where a processing library is required:



* Prefer maintained libraries.

* Check compatibility with the CMS/runtime.

* Avoid unnecessary dependencies.

* Follow the repository's package management conventions.

* Document the dependency.



Do not introduce a new framework merely to implement one tool.



---



# 48. Engine Security Boundary



The engine system must never become a generic server execution framework.



Forbidden generic capabilities include:



```text

Arbitrary PHP

Arbitrary Python

Arbitrary shell

Arbitrary PowerShell

Arbitrary executable binaries

```



---



# 49. API Connector Boundary



If a tool requires a third-party API:



```text

Favorite Web Tools

       ↓

Favorite API Connector

       ↓

Third-Party API

```



where the API Connector is the designated integration layer.



Do not duplicate API Connector functionality inside every engine.



---



# 50. Python API vs API Connector



The Python API Engine is specifically responsible for configured Python services.



Favorite API Connector remains responsible for its own supported third-party API integrations.



The two systems should integrate through clear interfaces rather than duplicate functionality.



---



# 51. Engine Configuration Source



Engine configuration must come from the Tool Registry/Configuration system.



The public request cannot redefine engine behavior.



---



# 52. Request Tampering



If a public request attempts to change:



```text

engine

handler

service

endpoint

configuration

access\_mode

```



the backend must ignore/reject those fields.



---



# 53. Configuration Immutability During Execution



A request executes against the server-side registered configuration.



The user cannot dynamically modify the tool definition through input data.



---



# 54. Concurrency



The engines should safely handle multiple independent requests according to the CMS/runtime capabilities.



No engine should use unsafe global mutable state that can leak data between users.



---



# 55. Temporary Files



If an engine creates temporary files:



* Use controlled temporary storage.

* Avoid user-controlled paths.

* Clean up after processing.

* Handle failures during cleanup safely.



---



# 56. Output Cleanup



Temporary generated outputs should have an appropriate lifecycle.



Persistent outputs should use the existing CMS storage mechanism where applicable.



---



# 57. Engine Performance



The implementation should avoid unnecessary processing.



Examples:



```text

Browser-safe formatter → Browser

Simple encoding → Browser

Large/proprietary processing → Server

Python AI processing → Python API

```



The final decision must be based on the individual tool requirements.



---



# 58. Engine Selection in Admin



The admin selects the engine during Tool Builder configuration.



Changing the engine must trigger configuration validation.



---



# 59. Engine Change



Example:



```text

Tool

Engine: JAVASCRIPT

        ↓

Change

        ↓

PHP

        ↓

Validate PHP configuration

```



Old engine-specific configuration must not accidentally remain active if incompatible.



---



# 60. Activation Requirement



A tool can become ACTIVE only if:



```text

Registry

\+

Engine

\+

Configuration

\+

Inputs

\+

Outputs

\+

Dependencies

```



are valid.



---



# 61. Engine Result Renderer



The engine should not directly control the complete frontend UI.



Instead:



```text

Engine

 ↓

Standard Result

 ↓

Output Renderer

 ↓

Frontend

```



---



# 62. Engine/UI Separation



Engine logic and UI rendering must remain separate.



This allows:



* Same engine with multiple UI layouts.

* Future themes.

* Multiple output renderers.

* Future frontend improvements.



---



# 63. Theme Independence



Engine code must not depend on:



* A specific theme.

* Theme colors.

* Theme HTML shell.

* Theme dark/light implementation.



The frontend renderer handles presentation.



---



# 64. Dark/Light Mode



Engine processing is theme-independent.



Result/input UI must follow the active CMS theme through the frontend system.



---



# 65. Future Engines



Future engines may include specialized processors if needed.



A future engine must implement the common engine architecture rather than creating a parallel execution system.



---



# 66. AI Agent Implementation Rules



The AI agent must:



1. Inspect the repository before implementation.

2. Inspect existing PHP/service/interface conventions.

3. Inspect existing package dependencies.

4. Reuse existing CMS architecture.

5. Implement the common engine contract.

6. Implement only required engines initially.

7. Keep engines modular.

8. Keep Access Control outside engine logic.

9. Keep Tool Registry outside engine logic.

10. Keep discovery outside engine logic.

11. Keep admin outside engine logic.

12. Keep frontend rendering outside engine logic.

13. Never use arbitrary `eval()`.

14. Never execute arbitrary PHP.

15. Never execute arbitrary Python source.

16. Never execute arbitrary shell/PowerShell commands.

17. Keep JavaScript processing separate from execution.

18. Isolate HTML/CSS/JS previews.

19. Keep Python credentials server-side.

20. Keep API Connector separate.

21. Normalize engine results.

22. Normalize engine errors.

23. Reuse existing logging.

24. Add appropriate tests.

25. Preserve plugin filesystem isolation.



---



# 67. Required Acceptance Criteria



The implementation is complete when:



* Engine Resolver exists.

* HTML engine is supported.

* CSS engine is supported.

* JavaScript engine is supported.

* PHP engine is supported.

* Python API engine is supported.

* All engines follow a common execution architecture.

* Access is checked before execution.

* Input validation occurs before processing.

* Results use the standard output model.

* Errors use the standard error model.

* HTML preview is isolated.

* CSS preview is isolated.

* JavaScript processing does not automatically execute source.

* Any intentional JavaScript execution is isolated.

* PHP uses controlled handlers.

* Arbitrary PHP execution is impossible.

* Python uses configured external APIs.

* Arbitrary Python source execution is impossible.

* Python credentials remain server-side.

* User requests cannot override engine configuration.

* Files are safely handled.

* Large results use controlled references where necessary.

* Temporary files are cleaned appropriately.

* Technical timeouts are implemented where required.

* No product usage limits exist.

* No credits exist.

* No tokens exist.

* No quotas exist.

* No usage counters exist.

* Favorite API Connector remains separate.

* Existing CMS architecture remains unchanged.

* Plugin isolation remains intact.

* Engine tests pass.



---



# 68. Final Engine Model



```text

                    TOOL EXECUTION

                          │

                          ▼

                  STATUS + ACCESS

                          │

                          ▼

                   INPUT VALIDATION

                          │

                          ▼

                    ENGINE RESOLVER

                          │

        ┌─────────────────┼─────────────────┐

        │        │        │        │        │

        ▼        ▼        ▼        ▼        ▼

      HTML      CSS       JS      PHP    PYTHON API

        │        │        │        │        │

        └────────┴────────┴────────┴────────┘

                          │

                          ▼

                  STANDARD RESULT

                          │

                          ▼

                  OUTPUT RENDERER

                          │

                          ▼

                    USER INTERFACE

```



## Final Principle



**The Engine System is a controlled processing layer—not a generic code execution platform. Each engine receives validated inputs, uses server-side registered configuration, performs only its intended operation, returns a standardized result, and remains independent from authentication, membership, discovery, admin, and theme systems.**




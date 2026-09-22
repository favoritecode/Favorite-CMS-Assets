# 31 — TESTING AND QUALITY ASSURANCE



## 1. Purpose



This document defines the testing and quality-assurance requirements for the `favorite-web-tools` plugin.



The purpose is to verify that the plugin:



* Works correctly.

* Integrates correctly with Favorite CMS.

* Respects existing CMS architecture.

* Enforces access control.

* Executes tools safely.

* Handles inputs and outputs correctly.

* Integrates with the active theme.

* Works on mobile and desktop.

* Does not modify or break CMS core functionality.

* Does not introduce unauthorized usage limits.

* Remains maintainable and extensible.



---



# 2. Testing Principle



Testing must validate the actual repository implementation.



The AI agent must inspect existing:



* Test framework.

* Test directory.

* PHPUnit configuration.

* Frontend testing conventions.

* Database testing utilities.

* Authentication testing utilities.

* Plugin testing patterns.

* Migration testing patterns.



Do not create a second testing framework when the CMS already provides one.



---



# 3. Test Layers



Testing should be organized into:



```text

Unit Tests

Integration Tests

Database Tests

API Tests

Access Control Tests

Engine Tests

Frontend Tests

Security Tests

Theme Integration Tests

Migration Tests

End-to-End Tests

Regression Tests

Final Acceptance Tests

```



---



# 4. Unit Testing



Unit tests should cover isolated plugin logic.



Potential areas:



* Tool Registry.

* Tool configuration validation.

* Category handling.

* Access Control.

* Engine Resolver.

* Input validation.

* Output normalization.

* Tool lifecycle validation.

* Python service configuration.

* Result formatting.

* Error normalization.



Use the existing CMS testing framework.



---



# 5. Tool Registry Tests



Verify:



* Tool registration works.

* Tool lookup by ID works.

* Tool lookup by slug works.

* Invalid tool lookup fails safely.

* Duplicate slugs are rejected.

* Invalid configuration is rejected.

* Disabled tools are not treated as active.

* Draft tools are not publicly executable.

* Registry data is correctly normalized.



---



# 6. Category Tests



Verify:



* Categories can be created.

* Categories can be retrieved.

* Category slugs are unique.

* Tools can reference valid categories.

* Invalid category references are rejected.

* Disabled categories behave according to the configured discovery rules.

* Category filtering works.



---



# 7. Access Control Tests



The three supported access modes must be tested independently:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



---



# 8. FREE Access Tests



Verify:



```text

Anonymous User → Allowed

Logged-in User → Allowed

Active Member → Allowed

```



No login or membership requirement should be incorrectly applied.



---



# 9. LOGIN\_REQUIRED Tests



Verify:



```text

Anonymous User → Denied

Logged-in User → Allowed

Active Member → Allowed

```



Membership must not be required.



---



# 10. MEMBERSHIP\_REQUIRED Tests



Verify:



```text

Anonymous User → Denied

Logged-in User Without Active Membership → Denied

Logged-in User With Active Membership → Allowed

```



---



# 11. Membership Recheck



Membership status must be evaluated when a protected execution request is processed.



Do not rely only on:



* Page-load state.

* JavaScript variables.

* Cached authorization.

* Browser storage.



---



# 12. No Usage Limit Tests



Verify that the plugin contains no product-level:



```text

Daily Limit

Monthly Limit

Hourly Limit

Credits

Tokens

Quota

Remaining Uses

Maximum Uses

```



The plugin must not reject a valid user because they have used the tool previously.



---



# 13. Unlimited Membership Test



For a membership-required tool:



```text

Active Member

     ↓

Execute

     ↓

Execute Again

     ↓

Execute Again

     ↓

Allowed

```



There must be no usage counter or quota rejection.



---



# 14. Tool Lifecycle Tests



Test:



```text

CREATE

DRAFT

CONFIGURE

VALIDATE

TEST

ACTIVATE

ACTIVE

UPDATE

DISABLE

DELETE

```



---



# 15. Draft Tool Tests



Verify:



* Draft tools are not publicly discoverable.

* Draft tools cannot be publicly executed.

* Authorized admin testing can test draft tools where supported.

* Draft configuration can be edited.



---



# 16. Active Tool Tests



Verify:



* Active tools appear in public discovery.

* Active tool pages render.

* Valid authorized requests execute.

* Invalid requests fail safely.



---



# 17. Disabled Tool Tests



Verify:



* Disabled tools disappear from public discovery.

* Disabled tools cannot be executed publicly.

* Existing direct URLs do not bypass disabled status.

* Admin can re-enable a valid disabled tool.



---



# 18. Configuration Validation Tests



Verify invalid configurations are rejected.



Examples:



```text

Missing Name

Missing Slug

Invalid Slug

Missing Category

Invalid Engine

Invalid Access Mode

Invalid Input Schema

Invalid Output Schema

Invalid Engine Configuration

Missing Python Service

Invalid Dependency

```



---



# 19. Activation Tests



A tool must not become ACTIVE if required configuration is invalid.



Test:



```text

Invalid Configuration

       ↓

Activation Attempt

       ↓

Rejected

       ↓

Tool Remains DRAFT

```



---



# 20. Active Update Tests



When editing an active tool:



```text

Existing Active Configuration

          ↓

New Configuration

          ↓

Validation

          ↓

Valid → Apply

Invalid → Keep Previous Valid Configuration

```



The system must not leave an active tool in a broken state.



---



# 21. Input Validation Tests



Test every supported input type:



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



# 22. Required Input Tests



Verify:



* Missing required field is rejected.

* Optional field may be omitted.

* Default values are applied where configured.

* Empty values are handled correctly.



---



# 23. Text Validation Tests



Test:



* Minimum length.

* Maximum length.

* Empty values.

* Unicode.

* Bengali text.

* English text.

* Mixed-language text.

* Special characters.



---



# 24. Number Validation Tests



Test:



* Valid number.

* Invalid number.

* Minimum.

* Maximum.

* Decimal values where allowed.

* Negative values where allowed.

* Empty optional value.



---



# 25. URL Validation Tests



Test:



* Valid URLs.

* Invalid URLs.

* Empty optional URLs.

* Unsupported schemes.

* Malformed URLs.



If a tool fetches URLs server-side, additional SSRF protections must be tested.



---



# 26. SELECT Tests



Verify:



* Valid configured option is accepted.

* Unknown option is rejected.

* User cannot inject a new option through the request.



---



# 27. CHECKBOX Tests



Verify:



* Boolean values are handled correctly.

* Configured values are respected.

* Unexpected values are rejected or normalized safely.



---



# 28. RADIO Tests



Verify:



* Valid configured option is accepted.

* Invalid option is rejected.

* Multiple unexpected values cannot bypass validation.



---



# 29. JSON Input Tests



Test:



* Valid JSON.

* Invalid JSON.

* Empty JSON where allowed.

* Unicode JSON.

* Nested JSON.

* Large technical payloads within allowed infrastructure constraints.



---



# 30. File Upload Tests



Test:



* Valid file.

* Invalid extension.

* Invalid MIME type.

* Oversized file.

* Empty file where not allowed.

* Upload error.

* Unsafe filename.

* Path traversal filename.

* Multiple files where supported.

* Unsupported file count.



---



# 31. File Cleanup Tests



Verify temporary files are removed after:



* Successful processing.

* Failed processing.

* Validation failure.

* Timeout.

* Exception.

* User cancellation where supported.



---



# 32. Unknown Input Tests



A request containing unexpected fields must not automatically become executable configuration.



Example:



```text

{

  "inputs": {

    "valid\_input": "value",

    "engine": "PHP",

    "python\_service": "attacker-controlled"

  }

}

```



The server must ignore/reject unauthorized configuration fields according to the request-validation architecture.



---



# 33. HTML Engine Tests



Test:



```text

Formatter

Minifier

Validator

Encoder

Decoder

Preview

```



---



# 34. HTML Formatter Tests



Verify:



* Valid HTML formats correctly.

* Nested elements remain structurally correct.

* Attributes are preserved.

* Text content is preserved.

* Unicode content is preserved.

* Invalid input is handled safely.



---



# 35. HTML Minifier Tests



Verify:



* Valid HTML remains valid.

* Safe whitespace reduction occurs.

* Content is not unexpectedly removed.

* Output is deterministic where expected.



---



# 36. HTML Validator Tests



Test:



* Valid HTML.

* Invalid HTML.

* Missing/incorrect structures where detectable.

* Warning/error output.

* Structured JSON response.



---



# 37. HTML Encoder/Decoder Tests



Test:



* Standard entities.

* Special characters.

* Quotes.

* Ampersands.

* Bengali text.

* Unicode characters.

* Encode/decode consistency.



---



# 38. HTML Preview Security Tests



Verify:



* Preview is isolated.

* Preview cannot access the parent CMS DOM.

* Preview cannot modify the site Header.

* Preview cannot modify the site Footer.

* Preview cannot modify theme state.

* Unsafe scripts are handled according to the preview security policy.



---



# 39. CSS Engine Tests



Test:



```text

Formatter

Minifier

Validator

Prefixer

Color Converter

Preview

```



---



# 40. CSS Formatter Tests



Verify:



* Valid CSS formats correctly.

* Selectors remain intact.

* Properties remain intact.

* Values remain intact.

* Comments behave according to implementation.



---



# 41. CSS Minifier Tests



Verify:



* Valid CSS remains functionally valid.

* Unnecessary formatting is reduced.

* Important values are preserved.



---



# 42. CSS Validator Tests



Verify:



* Valid CSS.

* Invalid CSS.

* Structured validation output.

* Safe error handling.



---



# 43. CSS Prefixer Tests



Verify:



* Supported properties are processed correctly.

* Unsupported input is handled safely.

* Existing prefixes are not corrupted.



Exact behavior depends on the selected implementation/library.



---



# 44. CSS Color Converter Tests



Test supported formats such as:



```text

HEX

RGB

RGBA

HSL

HSLA

```



Only test formats actually implemented.



---



# 45. CSS Preview Security Tests



Verify:



* Preview CSS cannot alter the parent document.

* Preview cannot modify CMS navigation.

* Preview cannot change theme state.

* Preview remains visually isolated.



---



# 46. JavaScript Engine Tests



Test:



```text

Formatter

Minifier

Validator

```



---



# 47. JavaScript Formatter Tests



Verify:



* Valid source formats correctly.

* Strings remain intact.

* Comments behave correctly.

* Unicode is preserved.

* Syntax is not unnecessarily changed.



---



# 48. JavaScript Minifier Tests



Verify:



* Valid JavaScript remains syntactically valid.

* Output is reduced where expected.

* Strings and important syntax are preserved.



---



# 49. JavaScript Validator Tests



Verify:



* Valid JavaScript.

* Invalid JavaScript.

* Syntax errors.

* Structured validation output.



---



# 50. JavaScript Execution Security Tests



If JavaScript execution is not enabled:



```text

Submitted JS

     ↓

Processing Only

```



must be enforced.



The system must not accidentally execute submitted JavaScript in the privileged page.



If intentional execution is implemented later, it must have dedicated isolation/security tests.



---



# 51. PHP Engine Tests



Test:



```text

PHP Formatter

PHP Validator

PHP Minifier

```



where implemented.



---



# 52. PHP Safety Tests



Verify that submitted PHP source is never treated as executable application code.



Test against attempts such as:



```text

eval()

system()

exec()

shell\_exec()

passthru()

```



and other dangerous execution mechanisms.



The PHP tool may analyze/process source code, but must not execute arbitrary user PHP.



---



# 53. Shell Execution Tests



Verify that tool input cannot cause:



* Shell execution.

* CMD execution.

* PowerShell execution.

* OS command execution.



---



# 54. Python API Engine Tests



Python tools must be tested through the configured Python API service.



Test:



```text

Valid Request

Invalid Request

Authentication Failure

Connection Failure

Timeout

HTTP Error

Invalid Response

Valid Response

File Upload

File Result

```



where applicable.



---



# 55. Python Credential Security Tests



Verify:



* Credentials remain server-side.

* Credentials are not present in HTML.

* Credentials are not present in frontend JavaScript.

* Credentials are not returned through public tool metadata.

* Credentials are not exposed in error messages.



---



# 56. Python Endpoint Protection Tests



Verify that public users cannot override:



```text

Base URL

Endpoint

HTTP Method

Authentication

Headers

Credential Reference

```



through request payloads.



---



# 57. Python SSRF Tests



If the Python service configuration contains a server-side URL:



* Public input must not replace it.

* Public input must not redirect it to an arbitrary host.

* Endpoint configuration must remain server-controlled.



---



# 58. Python Timeout Tests



Verify that an unresponsive Python service results in a controlled timeout error.



The request must not hang indefinitely.



---



# 59. Python Response Validation Tests



Test:



* Valid JSON.

* Invalid JSON.

* Unexpected content type.

* Missing expected fields.

* Unexpected fields.

* Binary/file response where configured.



---



# 60. Execution API Tests



Test the central execution flow:



```text

Request

 ↓

Tool Resolution

 ↓

Status Check

 ↓

Access Check

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



Every stage must be independently testable where the existing architecture supports it.



---



# 61. Execution API Authentication Tests



Test:



* Anonymous FREE request.

* Anonymous LOGIN\_REQUIRED request.

* Logged-in LOGIN\_REQUIRED request.

* Anonymous MEMBERSHIP\_REQUIRED request.

* Logged-in non-member MEMBERSHIP\_REQUIRED request.

* Active member MEMBERSHIP\_REQUIRED request.



---



# 62. Execution API Tampering Tests



Attempt to modify:



```text

engine

access\_mode

status

handler

python\_service\_id

endpoint

configuration

```



through the public request.



The server must continue using registered configuration.



---



# 63. Execution API Error Tests



Test appropriate responses for:



```text

Tool Not Found

Tool Disabled

Tool Draft

Authentication Required

Membership Required

Invalid Input

Invalid Configuration

Dependency Failure

Engine Failure

Service Failure

Timeout

Unexpected Error

```



---



# 64. Result Normalization Tests



Every supported result type should be tested:



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



# 65. Download Security Tests



Verify:



* Download references are controlled.

* Internal filesystem paths are never exposed.

* Unauthorized users cannot access protected results.

* Invalid download references fail safely.

* Expired temporary results behave correctly where expiration exists.



---



# 66. Output Rendering Tests



Verify that the frontend correctly renders:



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



# 67. HTML Output Tests



Verify:



* HTML source is escaped/displayed safely.

* HTML preview uses isolation.

* Arbitrary returned HTML cannot escape its intended context.



---



# 68. JSON Output Tests



Verify:



* Valid JSON displays correctly.

* Nested objects display correctly.

* Special characters display correctly.

* Unexpected response structures do not break the page.



---



# 69. Media Output Tests



For configured:



```text

IMAGE

AUDIO

VIDEO

```



verify:



* Correct rendering.

* Correct source handling.

* Failed media loads show useful errors.

* Internal paths are not exposed.



---



# 70. Frontend Tool Page Tests



Verify:



* Tool page loads.

* Tool metadata renders.

* Inputs render dynamically.

* Access state renders.

* Submit works.

* Clear works.

* Processing state works.

* Result renders.

* Error state works.



---



# 71. Dynamic Input Renderer Tests



Each supported input type must be tested independently.



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



# 72. Frontend Validation Tests



Verify that frontend validation:



* Detects obvious invalid input.

* Displays field-specific errors.

* Does not replace backend validation.

* Does not expose internal errors.



---



# 73. Loading State Tests



Verify:



* Submit enters processing state.

* Duplicate submissions are prevented at the UX level.

* Processing state ends after success/failure.

* No fake progress percentage is displayed.



---



# 74. Network Failure Tests



Simulate:



* Network interruption.

* Server unavailable.

* Timeout.

* Malformed response.



The UI must remain usable.



---



# 75. Retry Tests



Verify retry:



* Sends a normal execution request.

* Rechecks server-side access.

* Revalidates inputs.

* Does not bypass configuration/security.



---



# 76. Clear/Reset Tests



Verify:



* Inputs reset correctly.

* Result state clears.

* Error state clears where appropriate.

* Processing state is handled safely.



---



# 77. Search and Discovery Tests



Verify:



* Active tools appear.

* Draft tools do not appear publicly.

* Disabled tools do not appear publicly.

* Search by name works.

* Search by slug works.

* Search by description works.

* Category filtering works.

* Pagination works.

* Empty results work.



---



# 78. Protected Tool Discovery Tests



Verify that protected tools can remain discoverable.



Example:



```text

Tool Card

Name: Example Tool

Access: Membership Required

Action: Membership/Login

```



Discovery must not incorrectly imply that the tool is FREE.



---



# 79. Admin Tests



Verify authorized administrators can:



* Create tools.

* Edit tools.

* Configure inputs.

* Configure outputs.

* Select engines.

* Select access modes.

* Configure Python services.

* Validate tools.

* Test tools.

* Activate tools.

* Disable tools.

* Re-enable tools.

* Delete tools where allowed.



---



# 80. Admin Authorization Tests



Verify unauthorized users cannot access protected Web Tools administration.



Use the existing CMS admin permission system.



---



# 81. Admin CSRF Tests



Verify all applicable state-changing admin actions use the CMS's existing CSRF mechanism.



---



# 82. Dynamic Builder Tests



Test:



```text

Add Input

Edit Input

Remove Input

Reorder Input

Change Input Type

Configure Validation

Configure Options

Configure Output

Change Engine

Change Access

Save Draft

Validate

Test

Activate

```



---



# 83. Dependency Tests



Verify that a Python service cannot be disabled/deleted in a way that silently breaks active dependent tools.



The system should:



* Detect dependencies.

* Warn/block according to configured dependency policy.

* Preserve system consistency.



---



# 84. Migration Tests



Test:



```text

Fresh Install

Migration

Upgrade

Repeated Migration

Rollback

```



according to the CMS migration architecture.



---



# 85. Fresh Installation Test



On a clean supported environment:



```text

Install CMS

 ↓

Install Web Tools

 ↓

Run Migration

 ↓

Seed Catalog

 ↓

Open Tools

```



The plugin should install without modifying unrelated CMS tables/files.



---



# 86. Repeated Migration Test



Running the migration/seed process again must not:



* Duplicate categories.

* Duplicate tools.

* Destroy existing configuration.

* Duplicate indexes incorrectly.



---



# 87. Upgrade Test



Upgrade from a previous plugin version and verify:



* Existing tools remain.

* Existing configuration remains.

* Existing admin changes remain.

* New required schema changes apply correctly.

* New default tools are added only according to the seed policy.



---



# 88. Database Isolation Test



Verify that migrations only modify plugin-owned schema.



They must not unexpectedly modify:



```text

CMS core tables

CMS authentication tables

CMS theme tables

Other plugin tables

```



---



# 89. Plugin Isolation Test



Verify that the implementation remains inside the plugin's intended filesystem boundaries.



No unnecessary changes should appear in:



```text

app/

config/

bootstrap.php

core application files

existing plugins

existing theme files

```



unless an existing documented CMS integration point explicitly requires a change.



---



# 90. Theme Integration Tests



Verify:



* Active theme Header renders.

* Active theme Footer renders.

* Tool page uses active theme layout.

* Theme switching works.

* Light mode works.

* Dark mode works.

* `body.dark` works where applicable.

* No duplicate theme toggle exists.

* Plugin CSS does not globally override the theme.



---



# 91. Responsive Tests



Test at minimum:



```text

Mobile

Tablet

Desktop

```



Verify:



* Inputs fit screen.

* Buttons remain accessible.

* Code areas scroll correctly.

* Result panels remain usable.

* No horizontal page overflow is introduced.



---



# 92. Accessibility Tests



Verify:



* Form labels.

* Keyboard navigation.

* Focus visibility.

* Error accessibility.

* Loading-state accessibility.

* Button semantics.

* Appropriate ARIA usage.

* Logical heading hierarchy.



---



# 93. Browser Compatibility



Test against browsers supported by the existing Favorite CMS project.



At minimum, where appropriate:



```text

Chrome

Edge

Firefox

```



Safari/mobile browser testing should be performed if the supported deployment audience requires it.



---



# 94. Security Tests



Security testing must include:



```text

XSS

SQL Injection

CSRF

Path Traversal

File Upload Abuse

SSRF

Authorization Bypass

Configuration Tampering

Credential Exposure

Unsafe HTML Preview

Unsafe CSS Preview

Unsafe JavaScript Execution

Arbitrary PHP Execution

Arbitrary Command Execution

```



---



# 95. XSS Tests



Test user-controlled:



* Tool input.

* Tool output.

* Tool name/description where admin-configured.

* Search query.

* Error messages.

* Filenames.



Verify correct context-aware escaping.



---



# 96. SQL Injection Tests



Test:



* Tool search.

* Category search.

* Slug lookup.

* Admin filters.

* Tool configuration queries.



Use the existing CMS database abstraction/prepared-query architecture.



---



# 97. CSRF Tests



Test all applicable state-changing:



* Admin actions.

* Tool configuration actions.

* Service configuration actions.

* Other protected plugin operations.



Use the existing CMS CSRF system.



---



# 98. Path Traversal Tests



Attempt paths such as:



```text

../

..\\ 

../../

```



through:



* Filenames.

* Download references.

* File operations.



The server must prevent unauthorized filesystem access.



---



# 99. Arbitrary Code Execution Tests



Verify users cannot turn Web Tools into:



```text

PHP Executor

Python Executor

Shell Executor

CMD Executor

PowerShell Executor

OS Command Executor

```



through manipulated configuration or request data.



---



# 100. Configuration Tampering Tests



Attempt to modify configuration through:



* POST data.

* JSON payload.

* Query parameters.

* Hidden form fields.

* Browser developer tools.



Server-side stored configuration must remain authoritative.



---



# 101. Credential Exposure Tests



Search generated frontend output and network responses for:



* API keys.

* Bearer tokens.

* Passwords.

* Python service credentials.

* Secret configuration.



No credentials should be exposed.



---



# 102. Logging Tests



Verify technical logs do not accidentally contain:



* Passwords.

* API keys.

* Bearer tokens.

* Full sensitive request bodies.

* Sensitive file contents.



---



# 103. Error Handling Tests



Every major failure path should return:



* Safe message.

* Appropriate HTTP status.

* Structured error where applicable.

* No sensitive internal information.



---



# 104. Performance Tests



Test:



* Tool catalog loading.

* Search.

* Tool page loading.

* Execution API.

* Large text processing.

* File uploads.

* Python service calls.



Performance testing must use realistic payloads.



---



# 105. Technical Resource Protection



Where required, test:



* Request size.

* File size.

* Processing timeout.

* Memory safety.

* Output size.

* Regex safety.



These are technical protections.



They must not become product-level usage quotas.



---



# 106. No Usage Tracking Regression



During QA, verify that no implementation accidentally introduces:



```text

usage\_count

daily\_count

monthly\_count

credits

tokens

quota

remaining\_uses

```



as a requirement for normal tool execution.



---



# 107. Concurrency Tests



Where practical, test multiple independent requests to the same tool.



Verify:



* Requests do not leak data between users.

* Temporary files do not collide.

* Results remain associated with the correct request.

* One user's output cannot be accessed by another user.



---



# 108. Temporary File Isolation



For file-processing tools:



```text

User A Upload

        ↓

Temporary Resource A



User B Upload

        ↓

Temporary Resource B

```



Verify resources cannot cross-access.



---



# 109. Cache Tests



If caching is implemented:



* Public metadata caching is allowed where safe.

* Authorization must not be bypassed.

* Protected execution results must not leak between users.

* Cached data must not contain secrets.



---



# 110. Regression Testing



After any major plugin change, rerun the relevant regression suite.



At minimum verify:



* Plugin boot.

* Tool registry.

* Access control.

* Execution API.

* Default tools.

* Admin panel.

* Theme integration.

* Database migrations.



---



# 111. Test Data



Test data should include:



* Simple input.

* Empty input.

* Invalid input.

* Unicode.

* Bengali.

* English.

* Mixed text.

* Special characters.

* Large technical payloads.

* Malformed payloads.



Do not use real user secrets or production credentials in tests.



---



# 112. Production Safety



Production tests must not intentionally execute destructive operations against:



* CMS core.

* Production database.

* Production filesystem.

* Real payment systems.

* Real external services.



Use safe test environments wherever possible.



---



# 113. Test Environment



The AI agent should identify the repository's existing supported environment before running tests.



Verify:



* PHP version.

* Database.

* Composer dependencies.

* Node/frontend tooling if present.

* Test runner.

* Required PHP extensions.

* Required external services for Python tools.



Do not invent unsupported environment requirements.



---



# 114. Python Test Environment



If Python tools are included in testing:



* Use a dedicated test Python service where possible.

* Do not use production credentials.

* Do not expose private service credentials.

* Use deterministic test responses where appropriate.



---



# 115. External API Test Isolation



External APIs should not be called unnecessarily during automated tests.



Where the existing architecture supports it, use:



* Mock responses.

* Test services.

* Fixtures.

* Controlled integration environments.



Real API tests may be separate integration tests.



---



# 116. Test Naming



Tests should follow the repository's existing naming conventions.



If no convention exists, use clear names based on behavior.



Examples:



```text

test\_free\_tool\_allows\_anonymous\_user

test\_login\_required\_blocks\_guest

test\_membership\_required\_allows\_active\_member

test\_draft\_tool\_is\_not\_public

test\_invalid\_tool\_configuration\_cannot\_activate

```



---



# 117. Test Organization



Where supported by the repository, organize tests conceptually as:



```text

tests/

└── plugins/

    └── favorite-web-tools/

        ├── Unit/

        ├── Integration/

        ├── API/

        ├── Security/

        └── Frontend/

```



The exact directory structure must follow the actual CMS testing conventions.



Do not force this structure if the repository already has another established pattern.



---



# 118. Automated Test Requirement



The AI agent should automate tests wherever the existing project allows.



Manual testing should supplement automated testing, not replace it.



---



# 119. Manual QA Checklist



After automated tests pass, manually verify:



```text

□ Install plugin

□ Open Tools catalog

□ Search tool

□ Open tool

□ Execute FREE tool

□ Test login-required tool

□ Test membership-required tool

□ Test invalid input

□ Test result copy

□ Test download

□ Test mobile UI

□ Test desktop UI

□ Test dark mode

□ Test light mode

□ Test theme Header/Footer

□ Test admin creation

□ Test admin activation

□ Test disable/re-enable

```



---



# 120. Final End-to-End Test



Perform a complete flow:



```text

Install

 ↓

Migration

 ↓

Seed Catalog

 ↓

Open Tools

 ↓

Search Tool

 ↓

Open Tool

 ↓

Enter Input

 ↓

Execute

 ↓

Validate

 ↓

Process

 ↓

Render Result

 ↓

Copy/Download

```



The full flow must complete successfully for representative tools.



---



# 121. Protected End-to-End Test



Test:



```text

Protected Tool

      ↓

Anonymous Visitor

      ↓

Access Blocked

      ↓

Login

      ↓

Membership Check if Required

      ↓

Authorized

      ↓

Execute

      ↓

Result

```



---



# 122. Theme End-to-End Test



Test:



```text

Theme A

 ↓

Tool Page

 ↓

Header/Footer

 ↓

Light Mode

 ↓

Dark Mode

 ↓

Switch Theme

 ↓

Tool Page Still Works

```



---



# 123. Security End-to-End Test



Test:



```text

Untrusted Input

      ↓

Frontend

      ↓

Execution API

      ↓

Server Validation

      ↓

Access Control

      ↓

Engine

      ↓

Safe Result

```



No stage may allow a user to bypass the server-side security boundary.



---



# 124. Acceptance Gate



A feature should not be considered complete merely because it visually works.



It must pass the applicable:



```text

Functional Tests

Integration Tests

Security Tests

Access Tests

Regression Tests

Theme Tests

Migration Tests

```



---



# 125. Failure Handling



If a critical test fails:



```text

Test Failure

    ↓

Identify Root Cause

    ↓

Fix Implementation

    ↓

Re-run Failed Test

    ↓

Re-run Related Regression Tests

```



Do not simply disable the failing test to make the suite pass.



---



# 126. Critical Test Failures



The following should block release:



* Authorization bypass.

* Membership bypass.

* Arbitrary PHP execution.

* Arbitrary command execution.

* Credential exposure.

* Path traversal.

* Stored/reflected XSS.

* SQL injection.

* Unsafe preview escaping isolation.

* Cross-user result leakage.

* Broken migration.

* CMS core regression.

* Active tool executing with invalid configuration.



---



# 127. Non-Critical UI Issues



Minor visual issues may be tracked separately when they do not affect:



* Security.

* Data integrity.

* Access control.

* Core functionality.

* Accessibility.

* Stability.



They should still be fixed before final production release where practical.



---



# 128. Test Evidence



The AI agent should record useful verification evidence such as:



* Test command.

* Number of tests.

* Passed tests.

* Failed tests.

* Relevant error output.

* Migration result.

* Installation result.

* Manual QA findings.



Do not include secrets in test reports.



---



# 129. Final Verification Report



Before implementation is considered complete, generate a concise report containing:



```text

Plugin:

favorite-web-tools



Repository:

Favorite CMS



Automated Tests:

PASS/FAIL



Integration Tests:

PASS/FAIL



Security Tests:

PASS/FAIL



Access Control:

PASS/FAIL



Engine Tests:

PASS/FAIL



Frontend Tests:

PASS/FAIL



Theme Tests:

PASS/FAIL



Migration Tests:

PASS/FAIL



Regression Tests:

PASS/FAIL



Final Status:

PASS/FAIL

```



---



# 130. AI Agent Rules



The AI agent must:



1. Inspect the existing test architecture first.

2. Reuse existing test tools.

3. Never create a parallel testing framework unnecessarily.

4. Test plugin-owned functionality.

5. Test integration boundaries.

6. Test all three access modes.

7. Test all implemented engines.

8. Test input/output handling.

9. Test security boundaries.

10. Test Python service integration where implemented.

11. Test database migrations.

12. Test theme integration.

13. Test responsive behavior.

14. Test accessibility.

15. Test plugin isolation.

16. Run regression tests after major changes.

17. Never disable tests simply to achieve a passing result.

18. Never use production secrets in tests.

19. Never intentionally damage CMS core during testing.

20. Report unresolved failures honestly.



---



# 131. Final Release Checklist



Before production release:



```text

□ Repository inspection completed

□ Plugin boot verified

□ Database migrations verified

□ Seed process verified

□ Tool Registry verified

□ Category system verified

□ Access Control verified

□ Membership integration verified

□ Engine Resolver verified

□ HTML tools verified

□ CSS tools verified

□ JavaScript tools verified

□ PHP tools verified

□ Python integration verified where implemented

□ Input system verified

□ Output system verified

□ Execution API verified

□ Search/discovery verified

□ Admin panel verified

□ Dynamic builder verified

□ Theme Header verified

□ Theme Footer verified

□ Dark mode verified

□ Light mode verified

□ body.dark compatibility verified where applicable

□ Responsive UI verified

□ Accessibility verified

□ Security tests passed

□ Download security verified

□ Credential exposure checked

□ No arbitrary code execution

□ No usage quotas/credits/tokens

□ No CMS core regression

□ Plugin isolation verified

□ Regression suite passed

□ Manual QA passed

□ Final release report completed

```



---



# 132. Final Quality Model



```text

                  FAVORITE WEB TOOLS

                         │

                         ▼

                 ┌───────────────┐

                 │   Functional  │

                 │    Testing    │

                 └───────┬───────┘

                         │

          ┌──────────────┼──────────────┐

          ▼              ▼              ▼

       Security       Integration     Frontend

          │              │              │

          └──────────────┼──────────────┘

                         ▼

                   Access Control

                         │

                         ▼

                      Engines

                         │

                         ▼

                   Database/Migration

                         │

                         ▼

                    Theme/Responsive

                         │

                         ▼

                    Regression QA

                         │

                         ▼

                  FINAL ACCEPTANCE

```



---



# Final Principle



**Favorite Web Tools is not considered production-ready simply because the tools execute successfully. The complete system must pass functional, integration, access-control, security, database, frontend, theme, responsive, accessibility, and regression testing while remaining isolated from Favorite CMS core. All critical security or authorization failures block release.**




---

# Release-Candidate Acceptance Addendum

A green unit/integration suite is required but is not sufficient to declare a build release-ready.

Before release, the exact distributable ZIP must be installed and verified. Record:

- package root;
- file count;
- ZIP size;
- SHA-256;
- plugin version metadata;
- clean install/upgrade result;
- cache-cleared real-browser admin result;
- cache-cleared real-browser public tool result;
- active-theme shell result;
- access-mode smoke tests.

If any file is changed or the ZIP is rebuilt and its SHA-256 changes, the rebuilt package is a new release candidate and must repeat exact-artifact verification.

Where a plugin registers admin menus globally, browser acceptance must also include at least one non-plugin admin route (for example the main dashboard/profile) to detect menu-rendering type incompatibilities that isolated controller tests may miss.

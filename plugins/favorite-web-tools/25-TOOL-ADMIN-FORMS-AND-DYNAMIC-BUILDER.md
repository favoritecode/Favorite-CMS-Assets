# 25 — TOOL ADMIN FORMS AND DYNAMIC BUILDER



## 1. Purpose



This document defines the Admin-side Tool Form and Dynamic Builder for `favorite-web-tools`.



The builder allows an authorized CMS administrator to:



* Create tools.

* Edit tools.

* Configure engines.

* Select access modes.

* Assign categories.

* Build input fields.

* Configure outputs.

* Configure frontend presentation.

* Configure Python API connections.

* Validate configurations.

* Test tools.

* Activate or disable tools.



The builder must use the existing Favorite CMS admin architecture.



---



# 2. Core Principle



The Dynamic Builder is a configuration interface.



It is **not a code editor or arbitrary code execution system**.



```text

Admin

 ↓

Tool Builder

 ↓

Structured Configuration

 ↓

Validation

 ↓

Tool Registry

 ↓

Execution Engine

```



---



# 3. Existing CMS Admin



The plugin must reuse:



* Existing CMS admin layout.

* Existing admin authentication.

* Existing admin authorization/permissions.

* Existing CSRF protection.

* Existing form handling.

* Existing validation.

* Existing notifications.

* Existing database layer.



Do not create a second admin framework.



---



# 4. Tool Creation



When an administrator creates a new tool, the initial state should be:



```text

DRAFT

```



A tool should not become public merely because it was created.



---



# 5. Tool Builder Sections



The form should logically be divided into:



```text

Tool Information

Category

Engine

Access

Status

Input Builder

Output Configuration

Engine Configuration

Frontend Configuration

Dependencies

Validation

Testing

Activation

```



The exact UI layout should follow the existing CMS admin style.



---



# 6. Tool Information



Required fields may include:



* Tool Name.

* Slug.

* Short Description.

* Full Description.

* Icon.

* Thumbnail.

* Help/instructions where supported.

* SEO metadata where supported.



Only fields actually required by the CMS/plugin should be implemented.



---



# 7. Tool Name



Tool name must:



* Be required.

* Be human-readable.

* Be unique where required.

* Support the CMS-supported languages.



---



# 8. Slug



Slug must:



* Be URL-safe.

* Be unique.

* Follow existing CMS slug conventions.

* Be validated before activation.



If the CMS provides a slug generator, reuse it.



---



# 9. Description



The administrator may provide:



```text

Short Description

Full Description

```



The short description is primarily for cards/discovery.



The full description is primarily for the tool page.



---



# 10. Category



The administrator selects a category from the central category system.



Example:



```text

HTML

CSS

JavaScript

Developer

Text

PHP

Python

```



Do not create a category separately inside each tool.



---



# 11. Engine Selection



The builder must support exactly:



```text

HTML

CSS

JAVASCRIPT

PHP

PYTHON\_API

```



The engine determines how the tool is processed.



---



# 12. Engine Configuration



After selecting an engine, the builder should display only the configuration relevant to that engine.



Conceptually:



```text

Engine: HTML



HTML Configuration

├── Formatter

├── Minifier

├── Validator

├── Encoder/Decoder

└── Preview

```



---



# 13. Access Mode



The administrator must select exactly one:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



---



# 14. Access Explanation



The admin UI should clearly explain:



### FREE



Anyone can use the tool.



### LOGIN\_REQUIRED



User must be authenticated.



### MEMBERSHIP\_REQUIRED



User must be authenticated and have an active membership.



---



# 15. No Usage Configuration



The builder must not contain fields for:



```text

Daily Limit

Monthly Limit

Credits

Tokens

Quota

Maximum Uses

Remaining Uses

```



These concepts do not exist in the product architecture.



---



# 16. Membership



Membership is an access requirement only.



An active member has unlimited access to tools configured as:



```text

MEMBERSHIP\_REQUIRED

```



No usage counter is required.



---



# 17. Status



The administrator may select:



```text

DRAFT

ACTIVE

DISABLED

```



However, activation must pass validation.



---



# 18. Default Status



New tools should default to:



```text

DRAFT

```



This prevents incomplete tools from becoming public.



---



# 19. Input Builder



The Input Builder allows administrators to define what the user enters.



Supported input types:



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



# 20. Input Builder UI



Conceptually:



```text

Inputs

────────────────────────────



\[ + Add Input ]



Input 1

├── ID

├── Label

├── Type

├── Required

├── Placeholder

├── Help Text

├── Default

└── Validation



Input 2

├── ID

├── Label

├── Type

├── Required

└── ...

```



---



# 21. Add Input



Clicking:



```text

\+ Add Input

```



creates a new configurable input block.



The new input should start with safe defaults.



---



# 22. Input Identity



Each input should have a stable identifier.



Example:



```text

html\_input

css\_input

json\_input

url

file

```



The identifier is used by the execution system.



---



# 23. Input Label



Each input should have a user-facing label.



Example:



```text

HTML Code

CSS Code

JSON Data

URL

Upload File

```



---



# 24. Input Help



Optional help text may explain what the user should enter.



Example:



```text

Paste your JSON data here.

```



---



# 25. Input Type



The administrator selects one supported type.



Changing the type should update the available configuration fields dynamically.



---



# 26. Required Input



Each input may be:



```text

Required

Optional

```



Backend validation remains authoritative.



---



# 27. Placeholder



Text-based inputs may support a placeholder.



Example:



```text

Paste your code here...

```



---



# 28. Default Value



Where appropriate, an input may have a default value.



Default values must be validated against the selected input type.



---



# 29. Input Validation



The builder may configure validation such as:



* Required.

* Minimum length.

* Maximum length.

* Minimum number.

* Maximum number.

* URL format.

* JSON format.

* Allowed options.

* File type.

* File size.



Only validation rules actually supported by the engine should be exposed.



---



# 30. Text Input



TEXT may support:



* Required.

* Length constraints.

* Placeholder.

* Default.

* Help.



---



# 31. Textarea



TEXTAREA may support:



* Required.

* Length constraints.

* Placeholder.

* Default.

* Help.

* Code-oriented presentation where appropriate.



---



# 32. Number



NUMBER may support:



* Minimum.

* Maximum.

* Step.

* Default.

* Required.



---



# 33. URL



URL may support:



* URL validation.

* Required.

* Placeholder.

* Allowed schemes where appropriate.



Do not automatically fetch arbitrary user URLs.



---



# 34. File



FILE may support:



* Accepted MIME types.

* Accepted extensions.

* Maximum technical upload size.

* Required/optional.

* Multiple files only if the engine supports it.



Technical file-size limits are infrastructure/security controls, not user usage quotas.



---



# 35. Select



SELECT should provide an option builder.



Example:



```text

Option

Label: JSON

Value: json



Option

Label: XML

Value: xml

```



---



# 36. Checkbox



CHECKBOX may support:



* Label.

* Default checked state.

* Required where appropriate.



---



# 37. Radio



RADIO should provide multiple selectable options.



Each option requires:



* Label.

* Value.



---



# 38. JSON Input



JSON input may provide:



* JSON-specific formatting.

* Validation.

* Placeholder.

* Required state.



The backend must validate the JSON.



---



# 39. Input Ordering



Inputs should support ordering.



Possible UI:



```text

☰ Input 1

☰ Input 2

☰ Input 3

```



The actual drag/drop implementation should follow existing admin frontend capabilities.



---



# 40. Input Duplication



If useful, an input block may support:



```text

Duplicate

```



The duplicated input must receive a new unique identifier.



---



# 41. Input Removal



Inputs may be removed.



The admin UI should request confirmation where appropriate.



Removing an input may affect engine configuration, so validation must detect broken references.



---



# 42. Output Configuration



The administrator defines the expected tool output.



Supported output types:



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



# 43. Output Builder



Conceptually:



```text

Outputs

────────────────



Output 1

├── Type

├── Label

├── Format

└── Configuration

```



Multiple outputs may be supported if the engine requires them.



---



# 44. Text Output



TEXT is appropriate for:



* Formatted text.

* Encoded values.

* Hash results.

* Converted values.

* Plain processing results.



---



# 45. HTML Output



HTML output may represent:



* HTML source.

* Rendered preview.



These must remain distinct.



Rendered HTML must use appropriate isolation.



---



# 46. JSON Output



JSON output should remain structured.



The frontend may provide:



* Pretty view.

* Raw view.

* Copy.

* Expand/collapse where useful.



---



# 47. File Output



FILE output must use controlled file references.



Never expose server filesystem paths.



---



# 48. Media Output



IMAGE, AUDIO, and VIDEO outputs must use controlled URLs/references generated by the backend.



---



# 49. Download Output



DOWNLOAD should use the controlled download mechanism defined by the execution API.



---



# 50. Engine Configuration — HTML



HTML tools may expose configuration options such as:



```text

Formatter

Minifier

Validator

Encoder

Decoder

Preview

```



Only relevant options should be shown.



---



# 51. Engine Configuration — CSS



CSS tools may expose:



```text

Formatter

Minifier

Prefixer

Color Converter

Validator

Preview

```



---



# 52. Engine Configuration — JavaScript



JavaScript tools must distinguish between:



```text

Processing

```



and:



```text

Execution

```



Processing is preferred.



Arbitrary JavaScript execution must not be enabled simply because an admin selected JavaScript as the engine.



---



# 53. JavaScript Execution



If a specific tool intentionally needs JavaScript execution:



* It must be explicitly configured.

* It must be isolated.

* It must not gain CMS privileges.

* It must not access privileged application data.

* It must follow the security architecture.



No arbitrary privileged JS execution is allowed.



---



# 54. Engine Configuration — PHP



PHP tools must reference controlled server-side handlers.



The builder must not provide a generic field such as:



```text

Execute PHP Code

```



and then run arbitrary source.



---



# 55. PHP Handler



A PHP tool should conceptually reference:



```text

Handler Identifier

```



The actual handler implementation remains controlled plugin code.



---



# 56. No PHP Eval



The builder must never generate a design where:



```php

eval(...)

```



is required to execute tool configuration.



---



# 57. Engine Configuration — Python API



Python tools require a configured Python Service.



The admin selects:



```text

Python Service

Endpoint

HTTP Method

Request Mapping

Response Mapping

Timeout

```



---



# 58. Python Service Selection



The builder should show only configured services available to the administrator.



Example:



```text

Python Service:

\[ Python Tools Server ▼ ]

```



Credentials remain server-side.



---



# 59. Python Endpoint



The endpoint should be a controlled service configuration value.



A public user must not be able to replace it through request parameters.



---



# 60. HTTP Method



Supported methods should include what the Python service architecture supports.



At minimum:



```text

GET

POST

```



Additional methods only when actually required.



---



# 61. Request Mapping



The builder may map tool inputs to Python API fields.



Conceptually:



```text

Tool Input: text

        ↓

API Field: input\_text

```



---



# 62. Response Mapping



The builder may map the Python API response to the configured output.



Example:



```text

API response:

{

  "result": "..."

}



Output:

result

```



---



# 63. Python Timeout



Timeout may be configured within safe technical boundaries.



This is a technical processing setting.



It is not a usage quota.



---



# 64. Frontend Configuration



The builder may provide presentation settings such as:



* Tool layout.

* Result layout.

* Show help.

* Show preview.

* Show copy button.

* Show download button.

* Loading presentation.

* Result presentation.



These settings must not control authorization.



---



# 65. Frontend Settings Boundary



Frontend configuration should never be used to:



* Grant access.

* Remove backend authorization.

* Expose secrets.

* Change Python credentials.

* Change protected endpoints.



---



# 66. Tool Dependencies



The builder should identify dependencies where applicable.



Examples:



```text

Python Service

Favorite API Connector

Favorite Membership System

```



---



# 67. Dependency Validation



Before activation:



```text

Tool

 ↓

Dependencies

 ↓

Available?

 ↓

Configuration valid?

```



If a required dependency is unavailable, activation must fail safely.



---



# 68. Input/Output Dependency Validation



The builder should detect configuration inconsistencies such as:



```text

Output references missing input

API mapping references unknown field

Removed input still referenced by engine configuration

```



---



# 69. Validation Panel



The builder should provide a validation action.



Example:



```text

\[ Validate Configuration ]

```



The result should clearly identify:



```text

✓ Valid

```



or:



```text

✗ Configuration errors found

```



---



# 70. Validation Categories



Validation may cover:



### Identity



* Name.

* Slug.



### Category



* Valid category.



### Engine



* Supported engine.



### Access



* Valid access mode.



### Inputs



* Valid input configuration.



### Outputs



* Valid output configuration.



### Engine Configuration



* Required engine settings.



### Dependencies



* Required services/dependencies.



---



# 71. Validation Before Activation



The tool must pass validation before:



```text

DRAFT → ACTIVE

```



---



# 72. Validation Does Not Execute



Static configuration validation should not be confused with tool execution.



Validation checks configuration.



Testing executes the configured tool using controlled test input.



---



# 73. Test Tool



The admin should have:



```text

\[ Test Tool ]

```



where supported.



Testing should reuse the central execution architecture.



---



# 74. Test Input



The admin may provide test input according to the configured input schema.



Example:



```text

HTML Code:

<div>Hello</div>

```



---



# 75. Test Result



The admin should see:



* Success.

* Result.

* Validation error.

* Engine error.

* Dependency error.

* Timeout where applicable.



Internal secrets must remain hidden.



---



# 76. Test Does Not Activate



Successful testing must not automatically change:



```text

DRAFT

```



to:



```text

ACTIVE

```



Activation remains an explicit administrative action.



---



# 77. Activation Confirmation



The activation action may show a confirmation such as:



```text

This tool will become publicly discoverable after activation.

```



---



# 78. Activation Flow



```text

Save

 ↓

Validate

 ↓

Test

 ↓

Activate

 ↓

ACTIVE

 ↓

Public Discovery

```



---



# 79. Invalid Activation



If validation fails:



```text

DRAFT

 ↓

Activation blocked

```



The admin should receive actionable validation errors.



---



# 80. Active Tool Editing



When editing an ACTIVE tool:



```text

Edit

 ↓

Change Configuration

 ↓

Validate

 ↓

Save

```



Invalid configuration must not silently replace the currently valid configuration.



---



# 81. Safe Active Update



Where practical:



```text

Current Valid Config

        ↓

New Config

        ↓

Validate

        ↓

Apply only if valid

```



---



# 82. Disable



Admin may disable an ACTIVE tool.



Flow:



```text

ACTIVE

 ↓

DISABLE

 ↓

DISABLED

```



The tool disappears from public discovery.



---



# 83. Re-enable



A disabled tool must be validated before returning to:



```text

ACTIVE

```



---



# 84. Delete



Deletion should be handled carefully.



Before deletion:



* Check dependencies.

* Check references.

* Prevent accidental destructive operations where necessary.



Disabling is preferred when historical configuration should be preserved.



---



# 85. Category Management



The tool builder should use the central Category Management system.



It should not create duplicate category records.



---



# 86. Python Service Management



Python services should be managed through the Python Service system.



The Tool Builder only references them.



---



# 87. Service Dependency



If a Python service is disabled while tools depend on it, the admin system should detect those dependencies.



---



# 88. Admin Permissions



Only users with the appropriate CMS permissions should be able to:



* Create tools.

* Edit tools.

* Configure tools.

* Test tools.

* Activate tools.

* Disable tools.

* Delete tools.

* Manage Python services.



Use existing CMS permissions.



---



# 89. CSRF



All state-changing admin actions must use the existing CMS CSRF mechanism.



Do not create a second CSRF implementation.



---



# 90. Validation Errors



Errors should appear near the relevant field when possible.



Example:



```text

Slug

\[json-formater]



✗ Slug already exists.

```



---



# 91. Unsaved Changes



If supported by the existing admin UI, warn administrators before leaving a form with unsaved changes.



---



# 92. Form Persistence



The builder should preserve valid entered values when validation fails.



Do not force the administrator to recreate the entire configuration.



---



# 93. Dynamic Sections



Changing the engine should dynamically show the relevant engine configuration.



Changing input type should dynamically show relevant input options.



Changing output type should dynamically show relevant output options.



---



# 94. Configuration Serialization



The builder should serialize configuration into the structured Tool Configuration format defined in:



`14-TOOL-CONFIGURATION-SYSTEM.md`



Do not invent a second configuration format.



---



# 95. Database Boundary



The builder writes only plugin-owned data through the existing CMS database architecture.



It must not modify CMS core tables unless an existing CMS integration explicitly requires it.



---



# 96. No Arbitrary Executable Configuration



The builder must never allow administrators to create:



```text

Arbitrary PHP

Arbitrary Python

Arbitrary shell command

Arbitrary PowerShell

Arbitrary server command

```



through tool configuration.



---



# 97. Browser JavaScript Boundary



If the builder stores JavaScript-related configuration:



* Treat browser JavaScript as inspectable.

* Never place secrets in it.

* Never assume client-side logic is private.



---



# 98. API Credentials



The builder may reference credentials through the existing secure service/configuration mechanism.



It must never expose raw credentials in:



* Public tool metadata.

* Frontend JavaScript.

* Tool HTML.

* Discovery API.



---



# 99. Preview



If the admin builder provides a frontend preview:



* Preview should use the configured schema.

* Preview should not bypass access control.

* Preview should not expose secrets.

* HTML/CSS/JS preview must remain isolated.



---



# 100. Responsive Admin UI



The builder should work on:



* Desktop.

* Tablet.

* Mobile where the existing CMS admin supports it.



Complex configuration sections may use collapsible panels.



---



# 101. Accessibility



Admin forms should support:



* Labels.

* Keyboard navigation.

* Focus states.

* Error announcements.

* Accessible dynamic sections.

* Accessible buttons.

* Clear validation messages.



---



# 102. Reusable Components



Where practical, the builder should use reusable components for:



```text

Tool Information

Input Builder

Output Builder

Engine Configuration

Python Mapping

Validation

Dependency Display

```



Avoid duplicating form logic.



---



# 103. Tool Configuration Preview



The admin may display a summarized configuration before activation.



Example:



```text

Name: JSON Formatter

Category: Developer

Engine: JavaScript

Access: FREE

Inputs: 1

Outputs: 1

Status: DRAFT

```



---



# 104. Activation Checklist



Before activation, the admin may see:



```text

✓ Tool identity

✓ Category

✓ Engine

✓ Access

✓ Inputs

✓ Outputs

✓ Engine configuration

✓ Dependencies

✓ Test

```



Any failed required item should block activation.



---



# 105. No Usage Checklist



The activation checklist must not contain:



```text

Usage Limit

Credit Balance

Token Balance

Quota

```



---



# 106. Audit Integration



If Favorite CMS already provides an audit log, important administrative actions may integrate with it:



* Created.

* Updated.

* Activated.

* Disabled.

* Deleted.



Do not create a duplicate audit framework.



---



# 107. Admin Notifications



Use existing CMS notification/toast/flash mechanisms.



Examples:



```text

Tool saved successfully.

```



```text

Tool configuration is invalid.

```



```text

Tool activated successfully.

```



---



# 108. Security Boundary



The Admin Builder is trusted only according to the permissions granted by the CMS.



Even administrators should not be given an architecture that enables arbitrary server command execution.



---



# 109. AI Agent Implementation Rules



The AI agent must:



1. Inspect the actual Favorite CMS admin form architecture.

2. Reuse existing admin layout.

3. Reuse existing permissions.

4. Reuse existing CSRF.

5. Reuse existing validation.

6. Reuse existing database layer.

7. Reuse existing notification system.

8. Reuse Tool Registry.

9. Reuse Tool Configuration.

10. Reuse Input/Output System.

11. Reuse Engine System.

12. Reuse Python Service System.

13. Do not create a second admin framework.

14. Do not create a second configuration format.

15. Do not create arbitrary code execution fields.

16. Do not use `eval()`.

17. Do not allow shell/PowerShell execution.

18. Do not expose API credentials.

19. Keep new tools DRAFT by default.

20. Validate before activation.

21. Test before activation where applicable.

22. Never auto-activate after testing.

23. Protect ACTIVE configuration from invalid replacement.

24. Detect dependency failures.

25. Preserve plugin isolation.

26. Respect existing CMS architecture.



---



# 110. Required Acceptance Criteria



The implementation is complete when:



* Admin can create a tool.

* New tools start as DRAFT.

* Admin can edit tool information.

* Admin can select category.

* Admin can select engine.

* Admin can select access mode.

* Admin can configure status.

* Admin can add inputs dynamically.

* Admin can remove inputs.

* Admin can reorder inputs where supported.

* Admin can configure all supported input types.

* Admin can configure validation.

* Admin can configure outputs.

* Admin can configure engine-specific settings.

* Admin can configure Python API tools.

* Admin can select Python services.

* Admin can configure request/response mapping.

* Admin can configure frontend presentation.

* Admin can validate configuration.

* Invalid configuration blocks activation.

* Admin can test tools.

* Testing uses the central execution architecture.

* Successful testing does not auto-activate.

* Admin can explicitly activate.

* Active tools remain protected from invalid configuration replacement.

* Admin can disable tools.

* Disabled tools disappear from public discovery.

* Admin can safely re-enable valid tools.

* Dependencies are checked.

* Existing CMS permissions are respected.

* Existing CMS CSRF is respected.

* Existing CMS database architecture is reused.

* Existing CMS admin UI is reused.

* No arbitrary PHP execution exists.

* No arbitrary Python source execution exists.

* No shell/PowerShell execution exists.

* No API credentials are exposed.

* No usage limits are introduced.

* No credits are introduced.

* No tokens are introduced.

* No quotas are introduced.

* No usage counters are introduced.

* Plugin isolation is preserved.



---



# 111. Final Builder Model



```text

                 ADMIN

                   │

                   ▼

             TOOL BUILDER

                   │

       ┌───────────┼───────────┐

       │           │           │

    Identity     Access      Category

       │           │           │

       └───────────┼───────────┘

                   │

                   ▼

                ENGINE

                   │

          ┌────────┼────────┐

          │        │        │

        Inputs   Outputs   Config

          │        │        │

          └────────┼────────┘

                   │

                   ▼

              Dependencies

                   │

                   ▼

               VALIDATE

                   │

                   ▼

                 TEST

                   │

                   ▼

               ACTIVATE

                   │

                   ▼

                ACTIVE

                   │

                   ▼

           PUBLIC DISCOVERY

```



## Final Principle



**The Dynamic Builder creates structured configuration—not executable code. The Tool Registry stores that configuration, the Engine System executes only controlled handlers/services, Access Control determines who may use the tool, and Favorite CMS remains responsible for authentication, permissions, CSRF, database, and admin infrastructure.**




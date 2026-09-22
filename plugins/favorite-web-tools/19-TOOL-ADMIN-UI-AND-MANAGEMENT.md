# 19 — TOOL ADMIN UI AND MANAGEMENT



## 1. Purpose



The **Tool Admin UI and Management System** defines how administrators manage Favorite Web Tools from the Favorite CMS administration area.



It covers:



* Tool listing

* Tool creation

* Tool editing

* Tool configuration

* Category management

* Engine configuration

* Access configuration

* Input builder

* Output configuration

* Python service selection

* Validation

* Testing

* Activation

* Disabling

* Deletion

* Dependency handling

* Admin feedback

* Admin permissions



The implementation must reuse the existing Favorite CMS admin architecture.



---



# 2. Core Principle



Favorite Web Tools provides the domain-specific management UI.



Favorite CMS remains responsible for:



* Admin authentication

* Admin authorization

* Permissions

* CSRF

* Layout

* Navigation

* Forms

* Flash messages

* Validation conventions

* Request handling

* Database access



The plugin must not create a separate admin framework.



---



# 3. Admin Entry Point



The plugin should provide an entry point inside the existing CMS admin area.



Conceptually:



```text

Admin

  ↓

Favorite Web Tools

  ├── Tools

  ├── Categories

  ├── Python Services

  └── Settings

```



Exact menu registration must follow the actual CMS architecture.



---



# 4. Admin Permission Boundary



Only authorized administrators should be able to manage tools.



The system must use the existing Favorite CMS role/permission system.



Do not create:



* Plugin-specific login.

* Separate admin users.

* Separate roles.

* Separate permission database.



---



# 5. Tool Management Dashboard



The main Tool Management page may display:



* Total tools

* Active tools

* Draft tools

* Disabled tools

* Total categories

* Python services

* Active Python services



Do not display usage statistics.



Do not display:



* Total executions

* Daily usage

* Monthly usage

* Credits

* Tokens

* Remaining quota



---



# 6. Tool List



The Tool List should provide a clear table/list of tools.



Recommended columns:



```text

Name

Slug

Category

Engine

Access

Status

Updated

Actions

```



The exact columns should follow the existing CMS admin UI patterns.



---



# 7. Tool Search



Admin users should be able to search tools by relevant fields such as:



* Name

* Slug

* Description



Search should use the existing CMS/database query conventions.



---



# 8. Tool Filters



Useful filters may include:



```text

Category

Engine

Access Mode

Status

```



Available filters should reflect the actual management needs.



Do not add unnecessary filters.



---



# 9. Tool Sorting



Where the CMS supports sorting, tools may be sorted by:



* Name

* Display order

* Created date

* Updated date

* Status



Sorting must use controlled fields.



---



# 10. Create Tool



The admin should have a:



```text

Create Tool

```



action.



A newly created tool should normally start as:



```text

DRAFT

```



It must not automatically become publicly active before configuration validation.



---



# 11. Create Tool Form



The initial form should collect the core identity/configuration fields.



Conceptually:



```text

Tool Name

Slug

Description

Category

Engine

Access Mode

Status

```



Additional configuration should be shown based on the selected engine.



---



# 12. Tool Name



Tool Name is required.



It is used for:



* Admin display.

* Public tool title.

* Tool discovery.

* Tool cards.



The value should follow existing CMS validation conventions.



---



# 13. Tool Slug



Slug must be:



* Unique.

* URL-safe.

* Stable.

* Suitable for public tool URLs.



If Favorite CMS already provides a slug utility, reuse it.



The admin may edit the slug where the CMS architecture allows it.



Slug changes must consider existing links and SEO implications.



---



# 14. Description



The admin should be able to define a public description.



Where supported, distinguish between:



* Short description.

* Full description.



The frontend should use the appropriate field.



---



# 15. Category Selection



Each tool should select its primary category.



The category must exist and be valid.



Category does not determine access.



For example:



```text

Category: Developer

Access: Membership Required

```



is valid.



---



# 16. Engine Selection



Each tool must have exactly one engine.



Supported engines:



```text

HTML

CSS

JAVASCRIPT

PHP

PYTHON\_API

```



Changing the engine must trigger configuration validation.



---



# 17. Access Mode Selection



Each tool must have exactly one access mode:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



The admin UI should clearly explain the meaning of each.



---



# 18. Access Mode Rules



### FREE



Anyone can use the tool.



### LOGIN\_REQUIRED



Authentication is required.



### MEMBERSHIP\_REQUIRED



Authentication and active membership are required.



Active membership provides unlimited usage.



---



# 19. No Usage Configuration



The Tool Admin UI must not contain fields for:



* Daily limit

* Monthly limit

* Hourly limit

* Credits

* Tokens

* Quota

* Maximum uses

* Remaining uses



These are not part of the product model.



---



# 20. Status Selection



Supported statuses:



```text

DRAFT

ACTIVE

DISABLED

```



New tools should default to DRAFT.



---



# 21. Configuration Sections



The Tool Editor should organize configuration into logical sections.



Possible structure:



```text

Basic Information

Category \& Access

Engine Configuration

Inputs

Outputs

Frontend Settings

Dependencies

Validation

Testing

```



The exact UI may follow existing CMS form conventions.



---



# 22. Engine-Specific Configuration



After selecting an engine, show only the configuration relevant to that engine.



Conceptually:



```text

HTML

 → HTML Configuration



CSS

 → CSS Configuration



JavaScript

 → JavaScript Configuration



PHP

 → PHP Handler Configuration



Python API

 → Python Service Configuration

```



Do not display irrelevant settings.



---



# 23. HTML Configuration



HTML tools may configure operations such as:



* Format

* Minify

* Validate

* Encode

* Decode

* Preview



Only supported operations should appear.



---



# 24. CSS Configuration



CSS tools may configure:



* Format

* Minify

* Prefix

* Color conversion

* Validation

* Preview



Only required configuration should be shown.



---



# 25. JavaScript Configuration



JavaScript tools must distinguish:



```text

Processing

```



from:



```text

Execution

```



Processing tools may include:



* Formatter.

* Minifier.

* Validator.



Actual JavaScript execution must be explicitly designed and isolated.



Do not create an automatic "execute arbitrary JS" feature simply because the engine is JavaScript.



---



# 26. PHP Configuration



PHP tools must reference controlled server-side handlers.



The admin UI must not provide a generic:



```text

PHP Source Code

```



field intended for arbitrary runtime execution.



No `eval()`-based tool execution is permitted.



---



# 27. Python API Configuration



Python API tools should provide fields such as:



```text

Python Service

Endpoint

HTTP Method

Request Mapping

Response Mapping

Timeout

```



Only the configuration actually required by the tool should be displayed.



---



# 28. Python Service Selection



The admin should select an existing configured Python service.



Conceptually:



```text

Python Service:

\[ Favorite Python Processing API ▼ ]

```



The tool should reference the service rather than storing private credentials inside the tool configuration.



---



# 29. Python Endpoint



The endpoint should be configured by the administrator.



The public user must not be able to override it.



The system must validate the endpoint according to the Python Service architecture.



---



# 30. Python Authentication



Authentication credentials must be managed by the Python Service configuration.



Do not duplicate credentials in every tool.



Conceptually:



```text

Python Service

   ├── Base URL

   ├── Authentication

   └── Credentials



Tool

   └── Service Reference

```



---



# 31. Input Builder



The Tool Editor should provide a dynamic input builder.



Admin should be able to define multiple inputs.



Conceptually:



```text

Inputs



1\. HTML Code

   Type: TEXTAREA

   Required: Yes



2\. Output Format

   Type: SELECT

   Required: Yes

```



---



# 32. Input Order



Input fields should support a configurable order.



The frontend should render inputs according to this configured order.



Display order is presentation configuration.



---



# 33. Input Identity



Each input should have a stable identifier.



Example:



```text

html

format

output

```



Input IDs should be unique within a tool.



---



# 34. Input Label



Each input should have a human-readable label.



Example:



```text

HTML Code

```



Labels should be accessible.



---



# 35. Input Help Text



Optional help text may be configured.



Example:



```text

Paste the HTML code you want to format.

```



---



# 36. Input Type



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



The admin builder should expose only supported types.



---



# 37. Input Required State



Each input may be:



```text

Required

Optional

```



The generated configuration must be validated before activation.



---



# 38. Input Default Value



Where appropriate, an input may have a default value.



The default must be compatible with its input type.



Sensitive defaults must not be stored in public frontend metadata.



---



# 39. Input Placeholder



Optional placeholder text may be configured.



Placeholder is a UX feature and must not be used as the only label.



---



# 40. Input Validation Configuration



Depending on the type, admin may configure:



* Minimum length.

* Maximum length.

* Minimum value.

* Maximum value.

* Allowed options.

* Accepted file types.

* File size constraints where supported.



The exact options should depend on the input type.



---



# 41. Select/Radio Options



For SELECT and RADIO inputs, the admin should be able to define:



```text

Label

Value

```



Values must be unique within the field.



---



# 42. File Configuration



For FILE inputs, admin may configure:



* Allowed extensions.

* Allowed MIME types.

* Maximum file size where appropriate.



Backend validation remains authoritative.



---



# 43. Output Configuration



The Tool Editor should define expected result types.



Supported:



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



# 44. Multiple Outputs



If a tool requires multiple result values, the configuration may support multiple outputs where the engine architecture allows it.



Example:



```text

Output 1 → JSON

Output 2 → Download

```



Do not introduce complexity for tools that need only one result.



---



# 45. Frontend Settings



Where supported, admin may configure presentation behavior such as:



* Show copy button.

* Show download button.

* Show preview.

* Result layout.

* Help display.

* Loading message.

* Input layout.



These settings affect presentation only.



They must not override backend authorization or security.



---



# 46. Preview Configuration



If preview is enabled, the admin UI should clearly identify the preview type.



Examples:



```text

HTML Preview

CSS Preview

JavaScript Preview

```



Preview configuration must follow the security requirements defined in:



`16-TOOL-SECURITY-AND-SANDBOX.md`



---



# 47. Dependency Configuration



Tools may depend on:



* Python service.

* Controlled PHP handler.

* Existing CMS capability.

* Other plugin-provided service where explicitly supported.



Dependencies should be identifiable.



---



# 48. Dependency Validation



Before activation, the system must verify that required dependencies exist and are usable.



Examples:



```text

Python Tool

 ↓

Python Service exists?

 ↓

Service active?

 ↓

Endpoint configured?

```



---



# 49. Tool Validation



The admin should have a clear validation mechanism.



Validation must check:



### Identity



* Name.

* Slug.



### Category



* Category exists.

* Category is valid.



### Engine



* Supported engine.

* Engine configuration valid.



### Access



* Valid access mode.



### Inputs



* Valid schema.

* Unique input IDs.

* Valid types.

* Valid options.



### Outputs



* Valid result types.



### Dependencies



* Required service/handler exists.



---



# 50. Validation Result



Validation should provide structured feedback.



Example:



```text

Tool cannot be activated.



Errors:

• Python service is not configured.

• Input "format" has no options.

• Output type is invalid.

```



Errors should be understandable to administrators.



---



# 51. Activation



Activation should be an explicit action.



Conceptually:



```text

DRAFT

 ↓

Validate

 ↓

Test

 ↓

Activate

 ↓

ACTIVE

```



A tool must not become ACTIVE if required configuration is invalid.



---



# 52. Test Tool



Admin should be able to test a tool before activation.



The test should use the real tool architecture:



```text

Tool

 ↓

Access/Context

 ↓

Validation

 ↓

Engine

 ↓

Result

```



Avoid creating a second test-only execution engine.



---



# 53. Test Result



A successful test should show:



* Result.

* Output type.

* Errors if any.

* Relevant processing information.



Technical details may be available to administrators where safe.



---



# 54. Test Does Not Activate



Passing a test must not automatically activate a DRAFT tool.



The admin must explicitly choose:



```text

Activate

```



---



# 55. Failed Test



If testing fails:



* Tool remains DRAFT if it was DRAFT.

* Tool should not automatically activate.

* Admin receives useful error information.



---



# 56. Active Tool Update



Editing an ACTIVE tool must be handled carefully.



Conceptually:



```text

ACTIVE

 ↓

Edit

 ↓

Validate New Configuration

 ↓

Apply Only If Valid

```



An invalid configuration must not leave the active tool in a broken state.



---



# 57. Configuration Save



Saving a tool configuration should validate the data appropriate to the current state.



Draft tools may be saved with incomplete configuration where the CMS workflow permits it.



Activation requires complete configuration.



---



# 58. Disable Tool



An ACTIVE tool can be disabled.



Conceptually:



```text

ACTIVE

 ↓

Disable

 ↓

DISABLED

```



A disabled tool must not execute publicly.



---



# 59. Reactivate Tool



A DISABLED tool may be reactivated.



Before activation:



* Validate configuration.

* Validate dependencies.

* Confirm engine configuration.

* Confirm access configuration.



---



# 60. Delete Tool



Deletion should be available only when safe.



The system should consider:



* Existing references.

* Category relationships.

* Dependencies.

* Existing result/file references where applicable.



Disabling is preferred when permanent deletion is unnecessary.



---



# 61. Delete Confirmation



Deletion should require an explicit confirmation using the existing CMS confirmation pattern.



Do not delete simply because an admin opened an edit page or clicked an accidental action.



---



# 62. Category Management



The Category section should allow administrators to:



* Create category.

* Edit category.

* Reorder category where supported.

* Disable category.

* Delete category when safe.



---



# 63. Category Fields



Conceptually:



```text

Name

Slug

Description

Icon

Thumbnail

Display Order

Status

```



Only required fields should be implemented.



---



# 64. Category Dependencies



Before deleting or disabling a category, determine whether tools depend on it.



If tools are assigned to the category:



* Prevent unsafe deletion.

* Require reassignment where appropriate.

* Or follow the CMS/plugin's safe dependency strategy.



Never silently orphan tools.



---



# 65. Python Service Management



The Python Services section should allow administrators to:



* Create service.

* Edit service.

* Test service.

* Enable/disable service.

* Delete service when safe.



---



# 66. Python Service Fields



Conceptually:



```text

Service Name

Base URL

Authentication Type

Credential Reference

Timeout

Status

```



Credentials must use the existing secure server-side configuration mechanism.



---



# 67. Test Python Service



The admin should be able to test connectivity to a Python service.



A successful test may verify:



* Reachability.

* Authentication.

* Expected response.



A failed test should show a safe diagnostic.



Do not expose credentials.



---



# 68. Python Service Dependency Check



Before disabling or deleting a Python service:



```text

Service

 ↓

Find dependent tools

 ↓

If dependencies exist

 ↓

Warn / Prevent unsafe operation

```



Do not silently break active tools.



---



# 69. Tool Configuration Import/Export



Import/export is not required by default.



If implemented later, it must:



* Validate imported configuration.

* Never import executable arbitrary code.

* Never expose credentials.

* Preserve compatibility.

* Avoid silently overwriting active configuration.



This feature should be specified separately before implementation.



---



# 70. Bulk Actions



Bulk actions may be implemented only if they fit existing CMS admin patterns.



Possible examples:



* Disable selected tools.

* Change category where safe.



Bulk activation should still perform per-tool validation.



---



# 71. Admin Notifications



Use existing CMS feedback mechanisms for:



* Saved.

* Updated.

* Activated.

* Disabled.

* Deleted.

* Validation failed.

* Test succeeded.

* Test failed.



Do not build a separate notification framework.



---



# 72. Unsaved Changes



If the frontend uses dynamic configuration forms, it may warn admins about unsaved changes.



This is a UX feature only.



---



# 73. Dynamic Configuration UI



The Tool Editor should react to configuration choices.



Example:



```text

Engine = PYTHON\_API

        ↓

Show Python Service configuration



Engine = PHP

        ↓

Show PHP Handler configuration

```



Avoid showing irrelevant configuration fields.



---



# 74. Input Builder UX



The input builder should allow:



```text

Add Input

Edit Input

Remove Input

Reorder Input

```



It should prevent obvious configuration mistakes before saving.



---



# 75. Output Builder UX



Where multiple outputs are supported:



```text

Add Output

Edit Output

Remove Output

Reorder Output

```



---



# 76. Configuration Preview



The admin may have a preview of how the tool frontend will appear.



Preview should use the actual tool configuration.



It must not bypass backend access/security.



---



# 77. Admin Test Context



Admin testing must be clearly distinguished from public execution.



An admin may test a tool even if the tool's public access mode is:



```text

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



only where the CMS's existing administrative permissions explicitly permit administrative testing.



The test mechanism must not weaken public authorization.



---



# 78. Security Rules



Admin configuration must:



* Use CSRF protection.

* Use existing admin authorization.

* Validate all submitted values.

* Sanitize/escape admin UI output appropriately.

* Protect credentials.

* Prevent arbitrary code injection.

* Prevent arbitrary URL destinations.

* Prevent filesystem path manipulation.

* Prevent unsafe configuration overrides.



---



# 79. Credential Handling



Credentials must never appear in:



* Public tool metadata.

* Frontend JavaScript.

* Public API responses.

* Tool cards.

* Tool pages.

* Browser-visible configuration.



Admin UI should mask sensitive credential values where appropriate.



---



# 80. No Arbitrary Code Configuration



The admin tool editor must not become a code execution panel.



Do not allow administrators to configure arbitrary:



* PHP source.

* Python source.

* Shell commands.

* CMD commands.

* PowerShell commands.



unless a completely separate, explicitly specified and secured system is introduced in the future.



---



# 81. Admin URL/Endpoint Security



Python service URLs and other outbound destinations must be controlled by server-side configuration.



Do not let public users modify service destinations through execution requests.



---



# 82. Audit Logging



If Favorite CMS already has audit logging, tool management actions may integrate with it.



Useful events include:



* Tool created.

* Tool updated.

* Tool activated.

* Tool disabled.

* Tool deleted.

* Category changed.

* Python service changed.



This is technical/admin auditing, not user usage tracking.



---



# 83. No Usage Analytics



The admin panel must not require:



* Execution analytics.

* User usage charts.

* Daily usage charts.

* Monthly usage charts.

* Credit consumption.

* Token consumption.



These are outside the current product model.



---



# 84. Responsive Admin UI



The admin UI should follow the existing CMS admin responsive behavior.



Do not create a separate mobile admin application.



---



# 85. Accessibility



Admin forms should support:



* Proper labels.

* Keyboard navigation.

* Focus states.

* Error messages.

* Accessible dynamic controls.

* Clear action labels.



Dynamic input/output builders must remain usable with keyboard navigation where practical.



---



# 86. AI Agent Repository Inspection



Before implementation, the AI agent must inspect:



* Existing admin routes.

* Existing admin controllers.

* Existing admin layouts.

* Existing admin forms.

* Existing permission checks.

* Existing CSRF implementation.

* Existing validation helpers.

* Existing flash/notification system.

* Existing database/query patterns.

* Existing plugin admin implementations.



The implementation must match the actual repository.



---



# 87. AI Agent Implementation Rules



The AI agent must:



1. Reuse existing CMS admin layout.

2. Reuse existing admin navigation.

3. Reuse existing permission system.

4. Reuse existing CSRF protection.

5. Reuse existing validation.

6. Reuse existing notification/flash system.

7. Reuse existing database layer.

8. Keep management logic inside the plugin.

9. Use the central Tool Registry.

10. Use the central Tool Configuration System.

11. Use the central Lifecycle/Validation System.

12. Use the central Engine System.

13. Use the central Access Control System.

14. Use the Python Service System for Python tools.

15. Never create duplicate admin infrastructure.

16. Never expose credentials.

17. Never allow arbitrary code execution.

18. Never create usage limits or credits.

19. Never bypass access control.

20. Never activate invalid tools.

21. Never silently break dependencies.

22. Never modify CMS core unnecessarily.

23. Never modify unrelated plugins.

24. Follow repository conventions.

25. Add tests for management workflows.



---



# 88. Required Acceptance Criteria



The implementation is acceptable only when:



* Admin can view tools.

* Admin can search/filter tools.

* Admin can create tools.

* New tools start as DRAFT.

* Admin can edit tools.

* Admin can select category.

* Admin can select engine.

* Admin can select access mode.

* Admin can configure supported inputs.

* Admin can configure outputs.

* Engine-specific configuration appears correctly.

* Python tools can select a configured Python service.

* PHP tools reference controlled handlers.

* No arbitrary PHP source execution exists.

* No arbitrary Python source execution exists.

* No shell/CMD/PowerShell execution exists.

* Admin can validate configuration.

* Invalid configuration blocks activation.

* Admin can test tools.

* Test uses the real execution architecture.

* Successful test does not auto-activate.

* Admin can activate valid tools.

* Admin can disable tools.

* Disabled tools cannot execute publicly.

* Admin can reactivate valid tools.

* Deletion is protected and dependency-aware.

* Categories can be managed safely.

* Python services can be managed safely.

* Python service dependencies are detected.

* Credentials remain server-side.

* Public users cannot override service configuration.

* Existing CMS authentication/permissions are reused.

* Existing CMS CSRF is reused.

* Existing CMS admin UI is reused.

* No usage counters exist.

* No credits/tokens/quotas exist.

* No parallel admin framework exists.

* Plugin isolation is preserved.

* CMS core remains unchanged.



---



# 89. Final Admin Management Model



The complete workflow is:



```text

                    FAVORITE CMS ADMIN

                           │

                           ▼

                 FAVORITE WEB TOOLS

                           │

        ┌──────────────────┼──────────────────┐

        │                  │                  │

        ▼                  ▼                  ▼

      TOOLS            CATEGORIES       PYTHON SERVICES

        │                  │                  │

        ▼                  │                  │

   CREATE TOOL             │                  │

        │                  │                  │

        ▼                  │                  │

      DRAFT                │                  │

        │                  │                  │

        ▼                  │                  │

   CONFIGURATION            │                  │

        │                  │                  │

   ┌────┼────┬────┐        │                  │

   │    │    │    │        │                  │

 Engine Access Input Output │                  │

   │    │    │    │        │                  │

   └────┴────┴────┴────────┼──────────────────┘

                           │

                           ▼

                      VALIDATION

                           │

                    ┌──────┴──────┐

                    │             │

                  FAIL          PASS

                    │             │

                    ▼             ▼

                  DRAFT          TEST

                                  │

                           ┌──────┴──────┐

                           │             │

                         FAIL          PASS

                           │             │

                           ▼             ▼

                         DRAFT        ACTIVATE

                                         │

                                         ▼

                                       ACTIVE

                                         │

                                  ┌──────┴──────┐

                                  │             │

                                UPDATE       DISABLE

                                  │             │

                                  ▼             ▼

                              VALIDATE       DISABLED

                                                │

                                                ▼

                                             REACTIVATE

                                                │

                                                ▼

                                              ACTIVE

```



The fundamental admin rule is:



```text

CREATE

  ↓

DRAFT

  ↓

CONFIGURE

  ↓

VALIDATE

  ↓

TEST

  ↓

ACTIVATE

  ↓

ACTIVE

  ↓

UPDATE / DISABLE / DELETE

```



The Admin UI manages configuration and lifecycle; it does not replace the CMS's authentication, authorization, routing, database, security, or admin infrastructure.




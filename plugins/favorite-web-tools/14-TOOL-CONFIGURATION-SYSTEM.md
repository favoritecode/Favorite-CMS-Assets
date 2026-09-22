# 14 — TOOL CONFIGURATION SYSTEM



## 1. Purpose



The **Tool Configuration System** defines how each Favorite Web Tools tool is configured, validated, stored, loaded, edited, and activated.



It provides a single structured configuration model for:



* Tool identity and metadata

* Category

* Engine

* Access mode

* Status

* Input fields

* Output configuration

* Engine-specific settings

* Python API service mapping

* Frontend behavior

* Validation rules



The configuration system must work with the existing Favorite CMS architecture and must not introduce a separate configuration framework.



---



# 2. Core Principle



A tool's behavior must be driven by its structured configuration and controlled implementation.



The system must not depend on scattered hard-coded settings across unrelated files.



Conceptually:



```text

Tool

 ↓

Tool Configuration

 ↓

Registry

 ↓

Access Control

 ↓

Input/Output System

 ↓

Engine Resolver

 ↓

Selected Engine

 ↓

Execution

```



The exact implementation must follow the existing Favorite CMS plugin architecture discovered from the repository.



---



# 3. Configuration Ownership



Tool configuration belongs to the `favorite-web-tools` plugin.



The plugin must:



* Define its own configuration model.

* Store only plugin-owned tool configuration.

* Reuse CMS configuration utilities where available.

* Reuse CMS database/query infrastructure.

* Reuse existing validation and serialization mechanisms where available.



Do not create:



* A second configuration framework.

* A second ORM.

* A second database abstraction.

* A second authentication configuration system.

* A second routing system.



---



# 4. Tool Configuration Structure



Conceptually, a tool configuration contains:



```text

Tool Configuration

├── Identity

├── Display

├── Category

├── Engine

├── Access

├── Status

├── Inputs

├── Outputs

├── Engine Settings

├── Frontend Settings

└── Integration Settings

```



The exact storage structure must follow the database and plugin conventions of Favorite CMS.



---



# 5. Identity Configuration



Required identity fields:



* `name`

* `slug`



Optional:



* `short\_description`

* `description`



### Name



The tool's human-readable name.



Example:



```text

HTML Formatter

```



### Slug



The stable URL-safe identifier.



Example:



```text

html-formatter

```



Slug must be:



* Unique.

* URL-safe.

* Stable after publication unless an explicit migration/update is performed.

* Suitable for tool lookup.



Use an existing CMS slug utility if available.



---



# 6. Display Configuration



A tool may contain:



* Icon

* Thumbnail

* Short description

* Full description

* Help/instructions

* SEO title

* SEO description

* Display order



Only fields actually supported by the product should be implemented.



The frontend must not expose internal configuration values.



---



# 7. Category Configuration



Each tool must reference its configured category according to the data model defined in:



`05-TOOL-REGISTRY-AND-DATA-MODEL.md`



and



`13-DATABASE-MIGRATION-SYSTEM.md`



The configuration must not duplicate category data unnecessarily.



Conceptually:



```text

Tool

 ↓

category\_id

 ↓

Category

```



Category does not determine:



* Access level

* Membership requirement

* Usage limits

* Pricing



Category and access remain independent.



---



# 8. Engine Configuration



Every tool must have exactly one primary engine.



Supported engines:



```text

HTML

CSS

JAVASCRIPT

PHP

PYTHON\_API

```



The configuration UI must dynamically display engine-specific settings.



Example:



```text

Engine = PYTHON\_API



Show:

\- Python Service

\- Endpoint

\- HTTP Method

\- Request Mapping

\- Response Mapping

\- Timeout

```



Changing the engine must not leave incompatible configuration active without validation.



---



# 9. Access Configuration



Every tool must have exactly one access mode.



Supported values:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



Meaning:



### FREE



Anonymous and logged-in users can use the tool.



### LOGIN\_REQUIRED



The user must be authenticated.



### MEMBERSHIP\_REQUIRED



The user must be authenticated and have an active membership.



Active membership provides unlimited access.



The configuration system must never create fields for:



* Daily limits

* Monthly limits

* Hourly limits

* Credits

* Tokens

* Quotas

* Usage counters



---



# 10. Status Configuration



Supported statuses:



```text

DRAFT

ACTIVE

DISABLED

```



### DRAFT



Tool is being configured or tested.



It must not appear as a publicly usable tool.



### ACTIVE



Tool is publicly available subject to its access mode.



### DISABLED



Tool is unavailable to users.



Disabled tools must not execute even if the user otherwise has access.



---



# 11. Input Configuration



Tool inputs must use the standardized Input System from:



`11-TOOL-INPUT-OUTPUT-SYSTEM.md`



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



Each input may define:



* ID

* Name

* Label

* Help text

* Type

* Required

* Default value

* Placeholder

* Validation rules

* Options

* Accepted file types

* Display order



Example:



```text

Input:

  id: html

  label: HTML Code

  type: TEXTAREA

  required: true

```



The configuration system must support multiple inputs.



---



# 12. Input Validation Configuration



Validation rules must be structured rather than embedded as arbitrary executable code.



Possible validation properties include:



* Required

* Minimum length

* Maximum length

* Minimum number

* Maximum number

* URL format

* JSON format

* Allowed options

* File type

* File size

* Input-specific constraints



The exact validation capabilities should be implemented according to actual requirements.



Frontend validation improves UX.



Backend validation is authoritative.



---



# 13. Output Configuration



Tools must define expected output types where useful.



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



A tool may have one or multiple outputs when required.



Example:



```text

Output:

  type: TEXT

  label: Formatted HTML

```



Another example:



```text

Outputs:

  - type: TEXT

  - type: DOWNLOAD

```



The output configuration must be compatible with the execution engine's returned result.



---



# 14. HTML Engine Configuration



HTML tools may provide configurations for tasks such as:



* Formatting

* Minification

* Validation

* Encoding/decoding

* Preview



The exact configuration depends on the tool.



HTML preview must be isolated from the main Favorite CMS application UI.



Raw user HTML must not automatically become trusted application markup.



---



# 15. CSS Engine Configuration



CSS tools may provide configurations for:



* Formatting

* Minification

* Prefixing

* Color conversion

* Validation

* Preview



CSS preview must be isolated so user-provided CSS cannot unintentionally modify the CMS interface.



---



# 16. JavaScript Engine Configuration



JavaScript tools may provide configurations for:



* Formatting

* Minification

* Validation

* Transformation

* Preview where explicitly supported



The configuration must clearly distinguish:



```text

JavaScript processing

```



from:



```text

JavaScript execution

```



A formatter or minifier does not require arbitrary script execution.



If execution/preview is intentionally implemented, it must use an isolated environment.



Never assume browser-side JavaScript is secret.



---



# 17. PHP Engine Configuration



PHP tools must reference controlled server-side handlers or implementations.



The configuration must NOT provide an arbitrary PHP source-code field intended for runtime execution.



The system must not use:



```text

eval()

```



or equivalent arbitrary PHP execution mechanisms.



Conceptually:



```text

Tool

 ↓

PHP Engine

 ↓

Registered/controlled handler

 ↓

Result

```



---



# 18. Python API Configuration



For:



```text

engine = PYTHON\_API

```



the tool configuration must reference a registered Python service.



Conceptually:



```text

python\_service\_id

endpoint

method

request\_mapping

response\_mapping

timeout

```



The tool must not store Python source code for execution.



Python credentials must remain server-side.



The browser must never receive private API credentials.



The system must follow:



`07-PYTHON-API-SERVICE.md`



for Python service behavior.



---



# 19. Python Service Dependency



A Python API tool must not be activated if its required Python service configuration is invalid or unavailable.



Example:



```text

Tool:

  HTML-to-PDF



Engine:

  PYTHON\_API



Service:

  Python Processing Server

```



If the service is missing:



```text

Activation = rejected

```



unless the tool configuration explicitly supports another valid implementation.



The system must prevent broken dependencies from silently becoming active tools.



---



# 20. Favorite API Connector Separation



`Favorite API Connector` remains a separate plugin.



Favorite Web Tools must not duplicate its general-purpose API management system.



If a future tool needs functionality provided by Favorite API Connector, integration should happen through an explicit plugin boundary.



Do not copy its internal implementation into Favorite Web Tools.



---



# 21. Frontend Configuration



A tool may have frontend display configuration where required.



Possible settings:



* Input layout

* Result layout

* Show copy button

* Show download button

* Show preview

* Help text

* Empty-state behavior

* Loading-state behavior



These settings must affect presentation only.



They must never replace backend authorization or validation.



---



# 22. Configuration Versioning



Configuration versioning should only be introduced if the actual implementation requires it.



Do not add unnecessary version fields simply for theoretical future use.



If a configuration migration becomes necessary later:



```text

Old Configuration

       ↓

Migration

       ↓

New Configuration

```



Existing administrator settings must not be silently overwritten.



---



# 23. Configuration Validation



Before a tool can become `ACTIVE`, the system must validate its complete configuration.



Validation should confirm:



* Name exists.

* Slug exists and is valid.

* Slug is unique.

* Category exists when required.

* Engine is supported.

* Access mode is valid.

* Status transition is valid.

* Required inputs are valid.

* Output configuration is valid.

* Engine-specific configuration is valid.

* Python service exists for Python tools.

* Required endpoint/method configuration exists.

* Referenced dependencies are available.

* No forbidden executable configuration exists.



If validation fails:



```text

DRAFT → ACTIVE

```



must be rejected.



The administrator must receive a useful validation error.



---



# 24. Configuration Loading



When a tool is requested:



```text

Tool Slug

   ↓

Registry Lookup

   ↓

Load Configuration

   ↓

Validate/Resolve Configuration

   ↓

Access Check

   ↓

Engine Execution

```



Configuration loading must use the central Tool Registry.



Individual tools must not implement unrelated configuration-loading mechanisms.



---



# 25. Configuration Caching



Caching may be introduced where it improves performance and is compatible with Favorite CMS.



However:



* Cached configuration must not bypass authorization.

* Disabled tools must not remain executable because of stale authorization state.

* Membership state must never be trusted from public metadata cache.

* Configuration changes must invalidate or refresh relevant cached data.



Caching is an implementation optimization, not a separate configuration source of truth.



---



# 26. Admin Configuration Workflow



Recommended workflow:



```text

Create Tool

    ↓

Enter Metadata

    ↓

Select Category

    ↓

Select Engine

    ↓

Select Access Mode

    ↓

Configure Inputs

    ↓

Configure Outputs

    ↓

Configure Engine Settings

    ↓

Validate

    ↓

Save as DRAFT

    ↓

Test

    ↓

Activate

```



The exact UI flow must follow the existing Favorite CMS admin architecture.



---



# 27. Edit Workflow



An administrator must be able to edit:



* Metadata

* Category

* Access mode

* Inputs

* Outputs

* Engine configuration

* Display configuration

* Status



Changes must be validated before becoming active.



Changing an active tool's configuration must not create an inconsistent runtime state.



---



# 28. Engine Change



Changing an existing tool from one engine to another is a potentially significant configuration change.



Example:



```text

PHP

→

PYTHON\_API

```



The system must:



1. Detect incompatible old settings.

2. Require the new engine configuration.

3. Validate the resulting configuration.

4. Prevent activation if invalid.



Do not silently reinterpret incompatible configuration fields.



---



# 29. Access Change



Administrators may change:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



The new access mode must take effect through the centralized Access Control system.



No separate access logic may be embedded in individual frontend pages.



---



# 30. Tool Configuration and Execution



Configuration determines which engine and inputs are used.



Execution must still follow:



`10-TOOL-EXECUTION-API.md`



Conceptually:



```text

Request

 ↓

Tool Configuration

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

Output Validation

 ↓

Standard Result

```



---



# 31. Configuration and Security Boundary



Configuration is trusted only to the extent that it is created and validated by authorized administrators.



Users must never be able to modify tool configuration through public tool requests.



Public requests may provide tool inputs only.



They must not be able to override:



* Engine

* Access mode

* Python service

* Endpoint

* Credentials

* Handler

* Internal configuration

* Output routing



For example, a request must not be able to submit:



```text

engine=PHP

```



and force a tool configured as HTML to use PHP.



The server must resolve configuration from the registered tool.



---



# 32. No Arbitrary Executable Configuration



The configuration system must never become an arbitrary code execution mechanism.



Do not store or execute:



* Arbitrary PHP code

* Arbitrary Python source

* Arbitrary privileged JavaScript

* Arbitrary shell commands

* Arbitrary server commands



Tool behavior must come from controlled implementations and registered engines/services.



---



# 33. Configuration Export/Import



Export/import should only be implemented if it is genuinely required.



If introduced later:



* Validate imported configuration.

* Do not import credentials into public/browser-accessible data.

* Do not automatically activate unsafe configuration.

* Detect duplicate slugs.

* Respect existing CMS/plugin conventions.

* Preserve administrator control.



Import must never become a path to arbitrary executable code.



---



# 34. Configuration Error Handling



Configuration errors should be categorized clearly.



Examples:



```text

INVALID\_TOOL\_CONFIGURATION

INVALID\_ENGINE\_CONFIGURATION

INVALID\_ACCESS\_CONFIGURATION

INVALID\_INPUT\_CONFIGURATION

INVALID\_OUTPUT\_CONFIGURATION

MISSING\_CATEGORY

MISSING\_PYTHON\_SERVICE

INVALID\_PYTHON\_ENDPOINT

UNSUPPORTED\_ENGINE

```



Public users should receive safe, understandable errors.



Internal details should remain server-side where appropriate.



---



# 35. Logging



Technical configuration errors may be logged using existing Favorite CMS logging facilities.



Logs may contain:



* Tool identifier

* Configuration validation failure

* Engine

* Error category

* Timestamp

* Relevant technical context



Do not introduce usage counters or usage analytics.



---



# 36. Configuration and Database



Persistent configuration must use the plugin-owned database structures defined in:



`13-DATABASE-MIGRATION-SYSTEM.md`



The database must remain the source of truth for persisted administrator-managed configuration where database storage is selected.



No duplicate configuration database may be introduced.



---



# 37. Configuration and Files



Tool implementation files must remain inside the plugin boundary.



Conceptually:



```text

Favorite-CMS-Universal/

└── plugins/

    └── favorite-web-tools/

```



The exact directory structure must follow the actual repository/plugin conventions.



Do not create parallel tool systems outside the plugin.



---



# 38. Configuration and Themes



Tool configuration must not assume a specific CMS theme.



Frontend rendering must use the existing Favorite CMS/theme integration mechanisms.



Theme-specific styling must not become a dependency of the core tool configuration model.



---



# 39. Configuration and API



The execution API must expose only the configuration necessary for the current operation.



Public API responses must not expose:



* Credentials

* Internal service URLs where inappropriate

* Private configuration

* Internal filesystem paths

* Handler implementation details

* Secrets



Public tool metadata may expose safe fields such as:



* Name

* Slug

* Description

* Category

* Access indicator

* Input definitions required by the frontend



---



# 40. Configuration and Membership



For:



```text

MEMBERSHIP\_REQUIRED

```



the configuration only declares the access mode.



It does not implement membership logic.



Membership verification must be performed by the centralized Access Control system using the existing Favorite CMS membership infrastructure.



Active membership means unlimited use.



No usage counter is required.



---



# 41. Configuration and Tool Status



The configuration system must respect:



```text

DRAFT

ACTIVE

DISABLED

```



Public discovery:



```text

ACTIVE only

```



Public execution:



```text

ACTIVE + Access Allowed

```



DRAFT and DISABLED tools must not be publicly executable.



---



# 42. Configuration Extensibility



The configuration system must allow future tools without redesigning the entire platform.



A future engine should be able to add its own structured configuration while still using:



```text

Tool Registry

Access Control

Input/Output System

Execution API

Admin Configuration

```



Do not hard-code every possible future tool into the platform.



---



# 43. AI Agent Implementation Rules



When implementing this specification, the AI agent must:



1. Inspect the repository before changing files.

2. Read the existing plugin architecture.

3. Identify existing CMS configuration/database/admin utilities.

4. Reuse existing mechanisms whenever possible.

5. Implement only inside the `favorite-web-tools` plugin.

6. Avoid modifying CMS core.

7. Avoid creating duplicate infrastructure.

8. Follow existing naming conventions.

9. Follow existing coding style.

10. Use existing migration mechanisms.

11. Use existing authentication/membership mechanisms.

12. Use existing admin framework.

13. Use existing routing framework.

14. Use existing error/logging mechanisms.

15. Validate configuration before activation.

16. Never implement arbitrary server-side code execution.

17. Never expose secrets to the browser.

18. Do not add usage limits, credits, tokens, quotas, or counters.

19. Do not implement Favorite API Connector functionality inside this plugin.

20. Do not guess repository structure when actual code can be inspected.



---



# 44. Required Acceptance Criteria



The implementation is acceptable only when:



* Tool configuration has one central model.

* Tool identity is configurable.

* Category is configurable.

* Engine is configurable.

* Access mode is configurable.

* Status is configurable.

* Inputs are configurable.

* Outputs are configurable.

* Engine-specific configuration is supported.

* Python tools can reference a configured Python service.

* Invalid configurations cannot be activated.

* DRAFT tools cannot execute publicly.

* DISABLED tools cannot execute.

* Access is enforced by the centralized Access Control system.

* No usage limits or counters exist.

* Arbitrary PHP execution is not supported.

* Arbitrary Python source execution is not supported.

* Secrets remain server-side.

* Configuration is stored using the existing CMS/plugin database architecture.

* Admin configuration uses the existing CMS admin architecture.

* Frontend receives only safe public configuration.

* No duplicate router/auth/database/configuration framework is created.

* Favorite API Connector remains separate.

* Plugin filesystem isolation is preserved.

* Existing CMS core functionality remains unchanged.

* The implementation follows the actual repository structure rather than assumptions.



---



# 45. Final Configuration Model



The overall model is:



```text

                    TOOL CONFIGURATION

                           │

        ┌──────────────────┼──────────────────┐

        │                  │                  │

     Identity          Category            Display

        │

        ├── Engine

        │     ├── HTML

        │     ├── CSS

        │     ├── JAVASCRIPT

        │     ├── PHP

        │     └── PYTHON\_API

        │

        ├── Access

        │     ├── FREE

        │     ├── LOGIN\_REQUIRED

        │     └── MEMBERSHIP\_REQUIRED

        │

        ├── Status

        │     ├── DRAFT

        │     ├── ACTIVE

        │     └── DISABLED

        │

        ├── Inputs

        │

        ├── Outputs

        │

        ├── Engine Settings

        │

        └── Frontend Settings

                           │

                           ▼

                    TOOL REGISTRY

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

                           ▼

                       EXECUTION

                           │

                           ▼

                    STANDARD OUTPUT

```



This configuration system is the central configuration layer for all Favorite Web Tools while preserving strict separation between configuration, access control, execution engines, and external API services.




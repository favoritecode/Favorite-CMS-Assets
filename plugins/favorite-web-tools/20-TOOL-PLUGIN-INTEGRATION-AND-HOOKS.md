# 20 — TOOL PLUGIN INTEGRATION AND HOOKS



## 1. Purpose



The **Favorite Web Tools Plugin Integration and Hooks System** defines how the `favorite-web-tools` plugin integrates with the existing Favorite CMS plugin ecosystem.



It covers:



* Plugin registration

* Plugin lifecycle

* Hooks

* Filters

* Events

* Services

* Route registration

* Admin integration

* Asset integration

* Database migration integration

* Authentication integration

* Membership integration

* API integration

* Cross-plugin dependencies

* Extension points

* Plugin isolation



The goal is to make Favorite Web Tools a properly integrated Favorite CMS plugin without modifying CMS core unnecessarily.



---



# 2. Core Principle



Favorite Web Tools must behave as a normal Favorite CMS plugin.



Conceptually:



```text

Favorite CMS

     │

     ├── Core

     ├── Plugin System

     │      │

     │      └── Favorite Web Tools

     │

     └── Existing Plugins

```



The plugin must use the existing CMS extension mechanisms wherever available.



---



# 3. Repository-First Rule



Before implementing integration, the AI agent must inspect the actual Favorite CMS repository for:



* Plugin registration.

* Plugin discovery.

* Plugin lifecycle.

* Plugin manifests.

* Hooks.

* Filters.

* Events.

* Service providers.

* Route registration.

* Admin menu registration.

* Asset registration.

* Migration loading.

* View registration.

* Configuration loading.

* Plugin dependency handling.



The implementation must follow actual repository conventions.



No integration mechanism should be invented when an existing CMS mechanism already exists.



---



# 4. Plugin Isolation



The plugin must remain isolated inside its own plugin boundary.



Conceptually:



```text

Favorite-CMS-Universal/

└── plugins/

    └── favorite-web-tools/

```



Only plugin-owned files should be added or modified unless the CMS explicitly requires a registration change.



---



# 5. CMS Core Protection



The plugin must not modify CMS core functionality unnecessarily.



Do not modify:



* Core authentication.

* Core user system.

* Core database abstraction.

* Core routing engine.

* Core admin framework.

* Core request lifecycle.

* Core theme system.

* Core security framework.



If the CMS provides a hook/service/extension point, use it instead.



---



# 6. Plugin Registration



The plugin must register through the Favorite CMS's existing plugin registration/discovery mechanism.



The agent must inspect existing plugins to determine:



* Manifest format.

* Plugin identifier.

* Plugin slug.

* Version format.

* Entry point.

* Boot method.

* Dependency declaration.

* Activation/deactivation behavior.



Do not invent a new plugin registration format.



---



# 7. Plugin Identity



The plugin identity is:



```text

Name: Favorite Web Tools

Slug: favorite-web-tools

```



The plugin's internal identifiers should remain consistent across:



* Registration.

* Database.

* Routes.

* Assets.

* Configuration.

* Documentation.



---



# 8. Plugin Lifecycle



Where the CMS supports plugin lifecycle events, Favorite Web Tools should integrate with:



```text

Install

Activate

Deactivate

Upgrade

Uninstall

```



The exact lifecycle must follow the CMS.



---



# 9. Installation



During installation, the plugin may:



* Register plugin-owned migrations.

* Create plugin-owned database structures through migrations.

* Register plugin configuration.

* Register required services.

* Register routes.

* Register admin integration.

* Register default categories where specified.



Installation must not modify unrelated CMS data.



---



# 10. Activation



Plugin activation should initialize the components required by Favorite Web Tools.



Activation must not automatically create arbitrary user data or usage records.



---



# 11. Deactivation



When deactivated:



* Public tool execution must stop according to CMS plugin behavior.

* Plugin routes should no longer be active where the CMS lifecycle requires this.

* Admin integration should no longer appear where appropriate.



Deactivation should not automatically destroy plugin data unless the CMS explicitly defines such behavior.



---



# 12. Uninstall



If Favorite CMS supports uninstall lifecycle handling, Favorite Web Tools should follow it.



Destructive deletion of plugin data must not happen automatically unless uninstall semantics explicitly require it.



The agent must follow existing CMS conventions.



---



# 13. Plugin Versioning



The plugin should maintain a version according to the repository's existing plugin/version convention.



Version changes may accompany:



* Feature additions.

* Schema changes.

* Configuration changes.

* Compatibility changes.



Do not create an independent package/versioning system unnecessarily.



---



# 14. Hooks



If Favorite CMS provides hooks, Favorite Web Tools may expose or consume them where useful.



Hooks should be used for extension points rather than direct modification of unrelated code.



---



# 15. Hook Design Principle



Hooks should be:



* Small.

* Predictable.

* Documented.

* Stable.

* Relevant to real extension needs.



Do not create dozens of hooks without an actual use case.



---



# 16. Tool Registration Hook



The system may provide an extension point for registering tool definitions programmatically if the CMS/plugin architecture supports this.



Conceptually:



```text

Before Tool Registry Finalization

        ↓

Other Plugin

        ↓

Register Tool Definition

```



The exact hook name must follow actual CMS conventions.



---



# 17. Category Extension Hook



Other plugins may optionally contribute categories where the architecture requires it.



However, categories should normally be managed by the Tool Category system.



Do not create duplicate category registries.



---



# 18. Tool Configuration Filter



Where appropriate, a filter may allow trusted internal extensions to modify or extend a tool's configuration before rendering/execution.



Such filtering must never allow public users to override:



* Access mode.

* Service credentials.

* Engine security.

* Protected configuration.



---



# 19. Tool Discovery Extension



The discovery system may expose extension points for:



* Additional metadata.

* Related tools.

* Search behavior.

* Display information.



Any extension must preserve the public/private configuration boundary.



---



# 20. Tool Execution Hooks



Execution may expose lifecycle points such as:



```text

Before Execution

After Execution

Execution Failed

```



Conceptually:



```text

Request

 ↓

Access

 ↓

Validation

 ↓

Before Execution

 ↓

Engine

 ↓

After Execution

```



Hooks must not bypass authorization or validation.



---



# 21. Before Execution Rule



A `before execution` extension must not be allowed to:



* Grant unauthorized access.

* Change membership state.

* Bypass input validation.

* Replace credentials.

* Redirect requests to arbitrary services.



Security-sensitive decisions remain centralized.



---



# 22. After Execution Rule



An `after execution` extension may transform or enrich results where explicitly supported.



It must preserve:



* Result type.

* Security.

* Authorization.

* Controlled download references.



Do not expose internal file paths.



---



# 23. Error Hooks



An error extension point may allow logging or controlled transformation of user-facing errors.



Internal errors must remain protected.



Stack traces and secrets must not be exposed through hooks.



---



# 24. Access Control Integration



Favorite Web Tools must integrate with the existing CMS authentication/membership architecture.



Conceptually:



```text

Favorite Web Tools

        │

        ▼

Existing CMS Auth

        │

        ▼

Existing Membership System

```



The plugin must not create a second user/membership system.



---



# 25. Authentication Hook Usage



If the CMS exposes authentication hooks/events, the plugin may consume them where necessary.



Examples:



* User login state changes.

* User logout.

* Account changes.



The plugin should not duplicate session state.



---



# 26. Membership Integration



For `MEMBERSHIP\_REQUIRED` tools, the plugin should use the existing membership system.



Membership state must be evaluated through the appropriate CMS/plugin integration.



Do not store a permanent copy of membership authorization in the tool record.



---



# 27. Membership Dependency



The plugin should not hard-code assumptions about a specific membership implementation unless the repository explicitly defines one.



Conceptually:



```text

Access Control

      ↓

Membership Adapter

      ↓

Existing Membership System

```



The exact integration must be determined from the repository.



---



# 28. Plugin-to-Plugin Integration



Favorite Web Tools may integrate with other Favorite CMS plugins when genuinely required.



Integration must occur through:



* Public services.

* Hooks.

* Filters.

* Events.

* Documented APIs.

* Existing extension interfaces.



Avoid directly modifying another plugin's internal files.



---



# 29. Favorite API Connector Integration



Favorite API Connector remains a separate plugin.



If Favorite Web Tools requires functionality provided by Favorite API Connector:



```text

Favorite Web Tools

       │

       ▼

Favorite API Connector

```



Use its public integration interface where available.



Do not copy its implementation into Favorite Web Tools.



---



# 30. Python Service Separation



Python processing services belong to the Python Service architecture of Favorite Web Tools.



A general third-party API integration system should remain the responsibility of Favorite API Connector where appropriate.



Do not turn the Python Service system into a duplicate general API marketplace/connector.



---



# 31. Service Registration



Where the CMS supports service registration, Favorite Web Tools may register internal services such as:



* Tool Registry.

* Tool Configuration Service.

* Tool Execution Service.

* Access Control Service.

* Python Service Manager.

* Result Renderer metadata service.



The exact service registration mechanism must follow the CMS.



---



# 32. Service Container



If Favorite CMS provides a service container/dependency injection mechanism, use it.



Do not create a second dependency injection container.



---



# 33. Dependency Injection



Internal services should receive dependencies through the CMS-supported mechanism.



Avoid:



* Excessive global state.

* Static service locators where avoidable.

* Duplicate database connections.

* Duplicate configuration loaders.



---



# 34. Route Registration Integration



Routes must be registered through the existing Favorite CMS route mechanism.



Conceptually:



```text

Plugin Bootstrap

      ↓

CMS Route Registration

      ↓

Favorite Web Tools Routes

```



Do not create a custom router inside the plugin.



---



# 35. Admin Menu Integration



The plugin should register its admin navigation through the existing CMS admin menu system if available.



Conceptually:



```text

Admin

  └── Favorite Web Tools

       ├── Tools

       ├── Categories

       ├── Python Services

       └── Settings

```



The exact menu mechanism follows the repository.



---



# 36. Asset Integration



CSS and JavaScript assets should be registered through the existing CMS/plugin asset system.



The plugin must not replace the global asset loader.



Assets should be scoped to the plugin where appropriate.



---



# 37. Conditional Asset Loading



Where practical:



* Tool frontend assets should load on tool pages.

* Admin assets should load in Favorite Web Tools admin pages.

* Python service management assets should load only where needed.



Avoid loading large tool-specific assets globally.



---



# 38. View Integration



If Favorite CMS provides a view/template system, Favorite Web Tools should register/use views through it.



Views should remain inside the plugin.



Conceptually:



```text

favorite-web-tools/

└── resources/

    └── views/

```



The exact structure must follow actual plugin conventions.



---



# 39. Theme Integration



The public frontend should integrate with the existing CMS theme/layout system.



The plugin should not create a completely separate site shell.



Where the CMS provides:



* Layout inheritance.

* Template sections.

* Theme hooks.

* Component helpers.



reuse them.



---



# 40. Database Migration Integration



Favorite Web Tools must register plugin-owned migrations through the CMS migration system.



The plugin must not create:



* Separate migration runner.

* Separate database connection.

* Separate schema management system.



---



# 41. Database Ownership



Only plugin-owned tables should be created.



Conceptually:



```text

favorite\_web\_tools

favorite\_web\_tool\_categories

favorite\_web\_tool\_python\_services

```



Exact names follow the repository conventions defined in:



`13-DATABASE-MIGRATION-SYSTEM.md`



---



# 42. Configuration Integration



Global plugin configuration should use the existing CMS configuration mechanism where appropriate.



Configuration must remain separate from:



* Tool-specific database configuration.

* User data.

* Credentials.



---



# 43. Environment Configuration



Environment-sensitive values such as private credentials should use the existing CMS/environment configuration system.



Examples:



* API credentials.

* Secret keys.

* Internal service settings.



These must not be exposed to the frontend.



---



# 44. Event Integration



If Favorite CMS provides an event system, Favorite Web Tools may dispatch or consume relevant events.



Potential conceptual events:



```text

ToolCreated

ToolUpdated

ToolActivated

ToolDisabled

ToolDeleted

PythonServiceUpdated

```



These are optional extension points.



Do not implement an event system if the CMS does not use one.



---



# 45. Event Rules



Events must not be used to bypass core business rules.



For example:



```text

ToolActivated

```



must occur only after activation validation has succeeded.



An event listener must not be able to accidentally activate an invalid tool.



---



# 46. Extension Safety



Third-party extensions must not be trusted with secrets simply because they are connected through a hook.



Do not pass private credentials to generic hooks.



---



# 47. Public vs Internal Hooks



Hooks should be categorized conceptually as:



```text

Public/Extension Hook

Internal Lifecycle Hook

Admin Hook

Execution Hook

```



Sensitive internal data should remain internal.



---



# 48. Hook Data



Hook payloads should contain only the data necessary for the extension point.



Avoid passing:



* Passwords.

* API keys.

* Bearer tokens.

* Private credentials.

* Unnecessary personal data.

* Internal filesystem paths.



---



# 49. Filter Safety



Filters may modify data only within the documented boundary.



For example, a display metadata filter may modify:



```text

title

description

icon

thumbnail

```



but should not be able to modify authorization implicitly.



---



# 50. Access Control Cannot Be Overridden by Display Filters



A display filter must never be treated as an authorization mechanism.



The following must remain centralized:



* Access mode.

* Authentication requirement.

* Membership requirement.

* Tool status.



---



# 51. Engine Extension



The engine system should support future engines through a controlled registration mechanism if the CMS architecture allows it.



Conceptually:



```text

Engine Registry

    │

    ├── HTML

    ├── CSS

    ├── JAVASCRIPT

    ├── PHP

    ├── PYTHON\_API

    └── Future Engine

```



A new engine should not require rewriting the entire tool platform.



---



# 52. Engine Registration Security



A newly registered engine must follow the same security boundaries.



It must not automatically gain:



* Shell access.

* Filesystem access.

* Arbitrary code execution.

* Credential access.



---



# 53. Tool Renderer Extension



The frontend renderer may support extension points for additional UI components.



Examples:



* Custom input renderer.

* Custom output renderer.

* Custom preview component.



Any custom renderer must follow the platform's security rules.



---



# 54. Input Renderer Extensions



A future plugin may provide an additional input type if the architecture intentionally supports it.



Such an extension must define:



* Input identifier/type.

* Frontend renderer.

* Backend validation.

* Serialization.

* Error handling.



Do not silently introduce incompatible input types.



---



# 55. Output Renderer Extensions



A future plugin may provide an additional result type if required.



It must define:



* Result identifier/type.

* Frontend rendering.

* Security rules.

* Download/preview behavior where applicable.



---



# 56. Search Integration



Favorite Web Tools may integrate with existing CMS search if appropriate.



However, public tool discovery must remain able to:



* Search tools.

* Filter categories.

* Respect ACTIVE visibility.



Do not leak draft or disabled tools into public search.



---



# 57. Cache Integration



If the CMS provides caching:



* Public metadata may use it.

* Tool configuration may use safe caching.

* Authorization must always be evaluated correctly.

* Membership state must not be incorrectly cached as permanent authorization.

* User-specific results must not be shared accidentally.



---



# 58. Queue/Job Integration



If Favorite CMS already provides queues/jobs, long-running tools may use them when necessary.



Do not create a separate queue system.



A queue should only be introduced when an actual tool requires asynchronous processing.



---



# 59. Storage Integration



Tool-generated files should use the existing CMS/plugin storage architecture where possible.



Do not create a separate storage abstraction unnecessarily.



Controlled download references must be used instead of exposing filesystem paths.



---



# 60. Logging Integration



If Favorite CMS provides logging, use it.



The plugin should not create a separate logging system unless necessary.



Logs should remain safe and should not contain secrets.



---



# 61. Error Integration



Plugin errors should use existing CMS exception/error handling where possible.



The plugin should not expose its own production debug pages.



---



# 62. Configuration Conflict Handling



If an integration changes tool configuration:



```text

Original Configuration

       ↓

Extension

       ↓

Validation

       ↓

Final Configuration

```



The final configuration must remain valid.



Security-critical fields cannot be silently overridden.



---



# 63. Dependency Declaration



If Favorite Web Tools genuinely requires another plugin, the dependency should be declared using the CMS's existing dependency mechanism if available.



Example:



```text

Favorite Web Tools

       │

       └── Optional integration

             ↓

       Favorite API Connector

```



Do not declare unnecessary hard dependencies.



---



# 64. Optional Dependencies



An optional integration should degrade gracefully when the dependency is unavailable.



Example:



```text

API Connector unavailable

       ↓

API Connector-dependent feature unavailable

       ↓

Other Web Tools continue working

```



Do not break the entire plugin unnecessarily.



---



# 65. Required Dependencies



If a tool cannot function without a required dependency:



* Detect the missing dependency.

* Prevent invalid activation.

* Show an administrator-friendly error.

* Do not allow public execution to fail unpredictably.



---



# 66. Plugin Activation Safety



During plugin activation, do not assume that every optional dependency is installed.



Activation should distinguish:



```text

Required dependency

Optional dependency

```



---



# 67. Cross-Plugin Data Access



Avoid directly reading another plugin's private database tables.



Prefer:



* Public service.

* Public repository interface.

* Hook.

* Filter.

* Event.

* Documented API.



Direct table coupling should be avoided unless the CMS architecture explicitly defines it.



---



# 68. Cross-Plugin File Access



Do not directly manipulate another plugin's internal files.



Use public extension points.



---



# 69. Cross-Plugin Authentication



Never bypass the CMS authentication system by directly inspecting another plugin's private session state.



Use the established CMS authentication service.



---



# 70. Cross-Plugin Membership



Never duplicate membership status inside Favorite Web Tools.



Use the existing membership integration.



---



# 71. Plugin Configuration API



If other internal components need access to Favorite Web Tools configuration, expose a controlled service/interface.



Do not require other plugins to know internal file paths or database implementation.



---



# 72. Developer Extension API



Future developers should be able to extend Favorite Web Tools through documented interfaces rather than modifying core plugin files.



Potential extension areas:



```text

Tool Registration

Engine Registration

Input Renderer

Output Renderer

Tool Discovery

Execution Lifecycle

Admin UI

```



Only implement extension points that are genuinely needed.



---



# 73. Backward Compatibility



Once a public extension point is released, changes should consider backward compatibility.



Avoid changing:



* Tool identifiers.

* Hook payload meaning.

* Public route contracts.

* Result contracts.



without a deliberate version/update strategy.



---



# 74. Hook Documentation



Every implemented public/internal extension hook should document:



* Hook name.

* When it fires.

* Arguments.

* Expected return value.

* Whether modification is allowed.

* Security restrictions.

* Example use case.



Documentation must describe actual implemented hooks.



---



# 75. No Hidden Extension Mechanisms



Do not create undocumented magic behavior such as:



* Automatically loading arbitrary files.

* Automatically executing functions based on database values.

* Automatically discovering arbitrary PHP files.

* Automatically executing plugin-provided code.



Extension behavior must be explicit and controlled.



---



# 76. Security Boundary



Plugin integration must preserve all security requirements from:



`16-TOOL-SECURITY-AND-SANDBOX.md`



In particular:



```text

Browser

  ↓

Frontend

  ↓

Route

  ↓

Access Control

  ↓

Validation

  ↓

Engine

  ↓

External Service

```



No hook or integration point may bypass this boundary.



---



# 77. No Arbitrary Code Loading



Database configuration must never determine arbitrary PHP file paths or functions to execute.



Avoid patterns equivalent to:



```text

include($\_POST\['file'])

call\_user\_func($\_POST\['function'])

```



or other user-controlled dynamic execution.



---



# 78. No Arbitrary Command Execution



Plugin hooks/services must not provide arbitrary:



* Shell.

* CMD.

* PowerShell.

* OS command execution.



---



# 79. No Arbitrary Python Execution



Python integration remains API/service based.



Hooks must not allow public requests to submit arbitrary Python source for execution.



---



# 80. Testing Integration



The plugin should test integration points including:



* Plugin registration.

* Plugin activation.

* Plugin deactivation.

* Migration loading.

* Route registration.

* Admin menu registration.

* Asset loading.

* Service registration.

* Authentication integration.

* Membership integration.

* Python service integration.

* API Connector integration where applicable.

* Hook execution.

* Event behavior.

* Dependency failure.



---



# 81. Failure Isolation



A failure in an optional integration should not unnecessarily break unrelated Web Tools functionality.



Example:



```text

Python Service unavailable

        ↓

Python-dependent tools unavailable

        ↓

HTML/CSS/JS tools remain functional

```



---



# 82. Upgrade Safety



Plugin upgrades must:



* Run required migrations.

* Preserve existing tool configurations.

* Preserve admin changes.

* Avoid overwriting custom configuration.

* Maintain compatible routes where possible.

* Maintain existing tool slugs unless intentionally changed.



---



# 83. Deactivation Safety



Deactivating the plugin should not automatically delete:



* Tool definitions.

* Categories.

* Python service configuration.

* Uploaded/generated data.



unless the CMS lifecycle explicitly requires it.



---



# 84. Uninstall Safety



Destructive uninstall behavior must be explicit.



If the CMS supports data-preserving uninstall, follow that convention.



Do not silently delete user/admin-created tool configuration.



---



# 85. AI Agent Implementation Rules



The AI agent must:



1. Inspect existing plugin implementations first.

2. Identify actual plugin lifecycle mechanisms.

3. Identify actual hooks.

4. Identify actual filters.

5. Identify actual events.

6. Identify service registration.

7. Identify route registration.

8. Identify admin integration.

9. Identify asset integration.

10. Identify migration integration.

11. Reuse existing CMS mechanisms.

12. Keep Favorite Web Tools isolated.

13. Avoid modifying CMS core unnecessarily.

14. Avoid modifying unrelated plugins.

15. Avoid duplicate frameworks.

16. Avoid duplicate authentication.

17. Avoid duplicate membership systems.

18. Avoid duplicate routing.

19. Avoid duplicate database systems.

20. Avoid duplicate logging systems.

21. Avoid duplicate storage systems.

22. Keep Favorite API Connector separate.

23. Preserve the Python Service boundary.

24. Preserve centralized access control.

25. Preserve centralized validation.

26. Preserve engine isolation.

27. Never expose credentials through hooks.

28. Never allow hooks to bypass authorization.

29. Never allow arbitrary code execution.

30. Never introduce usage limits, credits, tokens, or quotas.

31. Document implemented extension points.

32. Add integration tests.

33. Verify plugin activation/deactivation behavior.

34. Verify optional dependency failure behavior.

35. Follow actual repository conventions instead of assumptions.



---



# 86. Required Acceptance Criteria



The implementation is acceptable only when:



* Favorite Web Tools registers through the existing CMS plugin system.

* Plugin identity is consistent.

* Plugin lifecycle follows CMS conventions.

* Plugin-owned migrations load correctly.

* Plugin routes register through the CMS router.

* Admin routes integrate with the CMS admin system.

* Admin menu integrates correctly.

* Frontend assets use the CMS asset mechanism.

* Admin assets use the CMS asset mechanism.

* Existing CMS authentication is reused.

* Existing membership integration is reused.

* Existing CSRF protection is reused.

* Existing database layer is reused.

* Existing logging/error systems are reused where available.

* Existing storage system is reused where appropriate.

* No parallel router exists.

* No parallel authentication exists.

* No parallel membership system exists.

* No parallel database/ORM exists.

* No parallel admin framework exists.

* No arbitrary code-loading mechanism exists.

* No arbitrary PHP execution exists.

* No arbitrary Python execution exists.

* No shell/CMD/PowerShell execution exists.

* Public hooks cannot bypass access control.

* Public hooks cannot expose credentials.

* Tool configuration remains centralized.

* Engine registration remains centralized.

* Tool execution remains centralized.

* Python service integration remains controlled.

* Favorite API Connector remains separate.

* Optional dependencies fail gracefully.

* Required dependencies block invalid activation.

* Plugin upgrades preserve existing configuration.

* Plugin deactivation does not unexpectedly destroy data.

* Plugin uninstall follows CMS conventions.

* Integration tests pass.

* CMS core remains unchanged unless an actual CMS extension point requires a minimal registration change.



---



# 87. Final Integration Model



The complete integration architecture is:



```text

                         FAVORITE CMS

                              │

              ┌───────────────┴────────────────┐

              │                                │

        Plugin System                     Existing Systems

              │                                │

              ▼                                ├── Auth

   FAVORITE WEB TOOLS                         ├── Membership

              │                                ├── Router

      ┌───────┼────────┐                       ├── Admin

      │       │        │                       ├── Database

      ▼       ▼        ▼                       ├── Assets

   Routes   Services   Hooks                   ├── Storage

      │       │        │                       └── Logging

      │       │        │

      │       ├── Tool Registry

      │       ├── Tool Config

      │       ├── Access Control

      │       ├── Engine System

      │       └── Python Services

      │

      └───────────────┐

                      │

                      ▼

                Tool Execution

                      │

              ┌───────┴────────┐

              │                │

          Internal          External

          Engines            Services

              │                │

              │         ┌──────┴──────┐

              │         │             │

              │      Python API   API Connector

              │

              ▼

             Result

              │

              ▼

           Frontend

```



The fundamental rule is:



```text

FAVORITE CMS EXTENSION SYSTEM

              ↓

     FAVORITE WEB TOOLS

              ↓

      OWNED PLUGIN LOGIC

              ↓

     EXISTING CMS SERVICES

              ↓

       SAFE TOOL EXECUTION

```



Favorite Web Tools should extend Favorite CMS—not compete with it.



Every integration must prefer an existing CMS extension point over custom infrastructure, while keeping tool management, execution, configuration, engines, and Python services cleanly owned by the `favorite-web-tools` plugin.




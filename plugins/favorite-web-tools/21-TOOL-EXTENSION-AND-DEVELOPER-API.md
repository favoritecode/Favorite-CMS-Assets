# 21 — TOOL EXTENSION AND DEVELOPER API



## 1. Purpose



The **Favorite Web Tools Extension and Developer API** defines how developers and future Favorite CMS plugins can extend `favorite-web-tools` without modifying its core implementation.



The extension architecture may support:



* New tools.

* New tool engines.

* New input types.

* New output types.

* Custom renderers.

* Tool discovery extensions.

* Execution lifecycle extensions.

* Admin extensions.

* External integrations.

* Theme-aware frontend extensions.



The system must remain modular and backward-compatible.



---



# 2. Core Principle



Favorite Web Tools should provide controlled extension points instead of requiring developers to modify core plugin files.



Conceptually:



```text

Favorite Web Tools Core

        │

        ├── Tool API

        ├── Engine API

        ├── Input API

        ├── Output API

        ├── Renderer API

        ├── Lifecycle Hooks

        └── Integration API

```



Extensions should use documented interfaces whenever available.



---



# 3. Repository-First Rule



Before implementing any Developer API, the AI agent must inspect the actual Favorite CMS repository and existing plugins for:



* Interfaces.

* Service containers.

* Hook systems.

* Filters.

* Events.

* Plugin APIs.

* Theme APIs.

* Component systems.

* Route systems.

* Asset systems.

* Admin extension mechanisms.



Do not invent a second extension framework if Favorite CMS already provides one.



---



# 4. Plugin Ownership



Favorite Web Tools owns its internal:



* Tool Registry.

* Tool Configuration.

* Tool Execution.

* Engine System.

* Access Control integration.

* Python Service integration.

* Input/Output system.



External plugins may interact through supported extension points.



They must not directly modify internal implementation files.



---



# 5. Tool Extension API



A developer may add a tool through the supported Tool Registry interface.



Conceptually:



```text

Developer Extension

        ↓

Tool Registration API

        ↓

Favorite Web Tools Registry

        ↓

Tool Configuration

        ↓

Validation

```



The exact API name and implementation must follow repository conventions.



---



# 6. Tool Definition



A tool extension should conceptually define:



```text

id

name

slug

description

category

engine

access\_mode

status

inputs

outputs

configuration

display\_metadata

```



Only fields actually supported by the Tool Registry should be used.



---



# 7. Tool Registration Validation



A registered tool must pass the same validation rules as an administrator-created tool.



Registration must validate:



* Identity.

* Slug.

* Category.

* Engine.

* Access mode.

* Input schema.

* Output schema.

* Engine configuration.

* Dependencies.



An extension must not bypass activation validation.



---



# 8. Engine Extension API



The Engine System should support future engines through a controlled engine registration mechanism where the CMS/plugin architecture supports it.



Current engines:



```text

HTML

CSS

JAVASCRIPT

PHP

PYTHON\_API

```



Future engines may be added without rewriting the Tool Registry.



---



# 9. Engine Contract



A future engine should conceptually provide:



```text

Can Handle

Validate

Execute

Return Result

```



The exact class/interface names must follow the repository.



---



# 10. Engine Security



A new engine does not automatically receive additional privileges.



An engine must not automatically gain access to:



* Shell commands.

* CMD.

* PowerShell.

* Arbitrary PHP execution.

* Arbitrary Python execution.

* Private filesystem data.

* API credentials.



Every engine must follow:



`16-TOOL-SECURITY-AND-SANDBOX.md`



---



# 11. Input Type Extension



The standard input types are:



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



Future custom input types may be supported through an extension mechanism if genuinely required.



---



# 12. Custom Input Contract



A custom input type should define:



* Type identifier.

* Display renderer.

* Value serialization.

* Frontend validation.

* Backend validation.

* Error representation.

* Optional configuration schema.



The backend must remain authoritative.



---



# 13. Input Security



Custom input renderers must never be trusted as security mechanisms.



All submitted values remain untrusted.



Server-side validation must occur before execution.



---



# 14. Output Type Extension



Current output types:



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



A future plugin may introduce additional output types where required.



---



# 15. Custom Output Contract



A custom output type should define:



* Type identifier.

* Result structure.

* Frontend renderer.

* Preview behavior.

* Download behavior where required.

* Security rules.



Internal filesystem paths must never be exposed.



---



# 16. Renderer Extension



The frontend renderer system may support custom renderers for:



* Input fields.

* Output results.

* Preview components.

* Tool-specific UI.



Custom renderers must remain scoped to the plugin/tool context.



---



# 17. Theme Integration — Mandatory



Favorite Web Tools must integrate with the **currently active Favorite CMS theme**.



The plugin must NOT create an independent site shell for normal frontend tool pages.



The tool frontend should carry the active theme's:



* Header.

* Footer.

* Main page layout.

* Navigation structure where applicable.

* Global typography conventions where appropriate.

* Existing theme components where available.



Conceptually:



```text

Active CMS Theme

      │

      ├── Header

      │

      ├── Favorite Web Tools Content

      │

      └── Footer

```



---



# 18. No Independent Theme Shell



Favorite Web Tools must not create a replacement:



```text

Plugin Header

Plugin Footer

Plugin Site Layout

```



when the active CMS theme already provides these.



The plugin should render its content inside the existing theme layout.



---



# 19. Active Theme Compatibility



The implementation must inspect the actual Favorite CMS theme architecture to determine how:



* Header.

* Footer.

* Layout.

* Content sections.

* Theme assets.

* Theme classes.



are provided.



The plugin must use those existing mechanisms.



---



# 20. Theme Switching



If the administrator changes the active CMS theme, Favorite Web Tools should automatically use the newly active theme's frontend shell where the CMS architecture supports theme switching.



The plugin should not hard-code a specific theme.



Conceptually:



```text

Theme A Active

      ↓

Web Tools uses Theme A layout



Theme B Active

      ↓

Web Tools uses Theme B layout

```



---



# 21. Dark/Light Mode — Mandatory



Favorite Web Tools frontend must be **dark/light mode sensitive** to the active theme.



The plugin must detect and respect the theme's existing dark/light state instead of creating an unrelated theme system.



Conceptually:



```text

Active Theme

     │

     ├── Light Mode

     │      ↓

     │   Web Tools Light UI

     │

     └── Dark Mode

            ↓

         Web Tools Dark UI

```



---



# 22. Existing Theme Dark Mode



The agent must inspect the active theme implementation to determine how dark/light mode is represented.



If the theme uses a class such as:



```text

body.dark

```



Favorite Web Tools should respond to that existing state.



The plugin must not assume a different mechanism without repository/theme inspection.



---



# 23. No Independent Theme Toggle



Favorite Web Tools should not introduce a second independent dark/light toggle when the active theme already provides one.



The active theme remains responsible for:



* Theme switching.

* Theme preference.

* Theme state.



Favorite Web Tools follows that state.



---



# 24. Theme-Sensitive CSS



Plugin CSS must be scoped so that:



* Tool UI works in light mode.

* Tool UI works in dark mode.

* Theme colors can be respected.

* Text remains readable.

* Inputs remain readable.

* Buttons remain usable.

* Result panels remain readable.

* Code editors/previews remain usable.



Avoid hard-coded colors where theme variables/tokens are available.



---



# 25. Theme CSS Variables



If the active theme provides CSS variables/tokens, Favorite Web Tools should reuse them where practical.



Conceptually:



```text

Theme Variables

      ↓

Favorite Web Tools Components

```



Do not duplicate the entire theme color system inside the plugin.



---



# 26. Theme Isolation



Although the plugin follows the active theme, its tool-specific components must not accidentally break the theme.



Plugin CSS should avoid unnecessary global selectors such as:



```text

body {}

h1 {}

button {}

input {}

```



when scoped selectors can be used.



Prefer a plugin root/container where appropriate.



---



# 27. Plugin Root



A consistent plugin frontend root/container should be used where practical.



Conceptually:



```text

.theme-page

   └── .favorite-web-tools

          ├── Tool Header

          ├── Tool Inputs

          ├── Actions

          └── Results

```



Exact class naming follows implementation conventions.



---



# 28. Header/Footer Responsibility



The CMS theme owns the page-level:



```text

Header

Footer

```



Favorite Web Tools owns only the tool content area.



This keeps the plugin compatible with the broader Favorite CMS website.



---



# 29. SEO and Theme Layout



Tool pages should use the existing CMS page/template/SEO mechanisms where available.



The plugin should not create a second page-rendering architecture.



---



# 30. Mobile and Desktop Theme Compatibility



Tool pages must work inside the active theme on:



* Mobile.

* Tablet.

* Desktop.



The plugin should respect the theme's:



* Container width.

* Responsive breakpoints where practical.

* Typography.

* Spacing conventions.



Plugin-specific responsive rules may be added when necessary.



---



# 31. Theme Component Reuse



If the active CMS theme provides reusable components for:



* Buttons.

* Cards.

* Forms.

* Alerts.

* Modals.

* Tabs.

* Containers.



Favorite Web Tools should reuse them where appropriate instead of recreating visually conflicting alternatives.



---



# 32. Custom Tool UI



A tool may require specialized UI.



In that case:



* Keep it inside the plugin scope.

* Respect theme typography.

* Respect dark/light state.

* Avoid global style conflicts.

* Preserve accessibility.

* Preserve responsive behavior.



---



# 33. Theme-Aware JavaScript



Frontend JavaScript may detect theme state where necessary.



However, it should not take ownership of the theme system.



If the theme exposes an existing event/API for theme changes, use it.



Otherwise, implementation may observe the existing DOM/theme state only where appropriate.



---



# 34. Dynamic Theme Changes



If the CMS theme allows switching dark/light mode without a full page reload, Favorite Web Tools should update accordingly where practical.



For example:



```text

Light

  ↓

Theme changes

  ↓

Dark

  ↓

Tool UI updates

```



Avoid maintaining a conflicting plugin-only theme state.



---



# 35. Preview Theme Isolation



HTML/CSS/JS tool previews must remain isolated from the CMS theme when executing/rendering user-provided content.



For example:



```text

CMS Theme

    │

    └── Web Tools UI

            │

            └── Isolated Preview

```



User HTML/CSS/JS must not modify:



* CMS header.

* CMS footer.

* Theme DOM.

* Admin UI.

* Other page components.



---



# 36. Theme and Tool Preview Separation



Theme sensitivity applies to the **tool interface**.



It does not mean arbitrary user-provided HTML/CSS should inherit unrestricted control over the CMS page.



Preview isolation remains mandatory.



---



# 37. Developer API and Theme API



Future extensions should be able to access supported theme-aware rendering mechanisms through existing CMS APIs where available.



Do not expose internal theme implementation details unnecessarily.



---



# 38. Lifecycle Extension Points



Supported extension points may include:



```text

Tool Registered

Tool Validated

Tool Activated

Tool Disabled

Before Execution

After Execution

Execution Failed

Tool Updated

Tool Deleted

```



Only actual implemented hooks should be documented.



---



# 39. Extension Ordering



If multiple extensions operate on the same lifecycle stage, ordering should follow the CMS's existing hook/filter priority mechanism.



Do not invent a second priority system.



---



# 40. Extension Failure



A failed optional extension should not unnecessarily break unrelated tools.



Where appropriate:



```text

Extension fails

      ↓

Log controlled error

      ↓

Continue safely

```



Security-critical failures should fail closed.



---



# 41. Developer API Security



Developer APIs must not allow external/public requests to modify:



* Tool access mode.

* Membership authorization.

* Credentials.

* Service base URLs.

* Internal filesystem references.

* Security configuration.



---



# 42. No Public Registration API



Tool registration APIs are for trusted plugin/admin/server-side contexts.



Anonymous users must never be able to register arbitrary tools.



---



# 43. No Runtime Code Injection



Tool extension configuration must not become a mechanism for:



* Arbitrary PHP execution.

* Arbitrary Python execution.

* Shell execution.

* Dynamic file inclusion.

* Dynamic function invocation from public input.



---



# 44. Dependency-Aware Extensions



An extension that depends on another plugin should use the CMS dependency/integration mechanism.



Example:



```text

Extension

   ↓

Dependency Check

   ├── Available → Register

   └── Missing → Skip/Disable safely

```



---



# 45. API Connector Extension



Favorite API Connector remains independent.



If an extension needs API Connector functionality, it should communicate through its supported interface.



Do not duplicate:



* API credential management.

* Generic API request handling.

* Connector configuration.



---



# 46. Python API Extension



Python API tools continue to use the Python Service System.



A developer extension must not bypass:



* Service registry.

* Access control.

* Input validation.

* Credential protection.

* Response validation.



---



# 47. Admin Extension API



Future integrations may add appropriate admin configuration through the existing Favorite CMS admin extension system.



They must respect:



* CMS admin permissions.

* CSRF protection.

* Existing form validation.

* Existing admin layout.



---



# 48. Tool Extension Lifecycle



A typical developer-added tool should follow:



```text

Register

   ↓

Configure

   ↓

Validate

   ↓

Test

   ↓

Activate

   ↓

Public

```



Programmatic registration must not bypass lifecycle validation.



---



# 49. Backward Compatibility



Public extension points should remain stable where possible.



Changes to:



* Hook arguments.

* Tool contracts.

* Engine contracts.

* Result contracts.

* Renderer contracts.



should be deliberate and documented.



---



# 50. Naming and Namespacing



All extension identifiers should use appropriate namespaces/prefixes to avoid conflicts.



Do not use generic global names that may collide with:



* CMS.

* Themes.

* Other plugins.

* Other tools.



---



# 51. Documentation



Every publicly supported Developer API should document:



* Identifier/name.

* Purpose.

* Registration point.

* Input arguments.

* Return value.

* Lifecycle.

* Security restrictions.

* Compatibility expectations.



Do not document internal implementation details as public API.



---



# 52. Testing



Developer API tests should cover:



* Tool registration.

* Invalid registration.

* Engine registration.

* Input extension.

* Output extension.

* Renderer registration.

* Hook execution.

* Hook ordering.

* Extension failure.

* Dependency failure.

* Theme rendering.

* Active theme header/footer.

* Light mode.

* Dark mode.

* Dynamic theme change where supported.

* Preview isolation.

* Security boundaries.



---



# 53. Theme Acceptance Tests



The frontend must be tested with the active theme to confirm:



### Header



```text

CMS Theme Header

       ↓

Favorite Web Tools

```



### Content



```text

Favorite Web Tools UI

```



### Footer



```text

Favorite Web Tools

       ↓

CMS Theme Footer

```



### Light Mode



```text

Theme Light Mode

       ↓

Tool UI follows light state

```



### Dark Mode



```text

Theme Dark Mode

       ↓

Tool UI follows dark state

```



---



# 54. Required Acceptance Criteria



The implementation is acceptable only when:



* Developers can extend tools through supported APIs.

* New engines can be added through controlled extension mechanisms where supported.

* Custom input/output types can be introduced without rewriting core architecture where required.

* Renderer extensions remain isolated.

* Hooks cannot bypass security.

* Public users cannot register arbitrary tools.

* Arbitrary code execution is not introduced.

* Favorite API Connector remains separate.

* Python Service architecture remains separate.

* Existing CMS services are reused.

* No duplicate extension framework is created unnecessarily.

* Plugin remains isolated.

* Existing routes remain compatible.

* Existing theme layout remains compatible.

* Active theme Header is carried by Web Tools frontend pages.

* Active theme Footer is carried by Web Tools frontend pages.

* Plugin does not create an independent page shell unnecessarily.

* Theme switching remains compatible.

* Plugin UI follows active theme dark/light state.

* If the theme uses `body.dark`, Web Tools responds to that state.

* Plugin does not create a conflicting theme toggle.

* Theme CSS variables/tokens are reused where available.

* Plugin CSS does not unnecessarily override global theme styles.

* HTML/CSS/JS previews remain isolated from the CMS page.

* Tool pages remain responsive on mobile and desktop.

* Extension failures are isolated where safe.

* Required dependencies are validated.

* Optional dependencies fail gracefully.

* Integration tests pass.



---



# 55. Final Developer Extension Model



```text

                     FAVORITE CMS

                          │

                    Plugin System

                          │

                          ▼

               FAVORITE WEB TOOLS

                          │

       ┌──────────────────┼──────────────────┐

       │                  │                  │

       ▼                  ▼                  ▼

   Tool API           Engine API        Renderer API

       │                  │                  │

       ├── Tools           ├── HTML           ├── Input

       ├── Config          ├── CSS            ├── Output

       └── Registry        ├── JS             └── Preview

                           ├── PHP

                           └── Python API

                          │

                          ▼

                   Execution System

                          │

                          ▼

                    Existing CMS

                          │

                 ┌────────┴────────┐

                 ▼                 ▼

           Active Theme       CMS Services

                 │                 │

          ┌──────┴──────┐          ├── Auth

          ▼             ▼          ├── Membership

       Header         Footer       ├── Router

                                   ├── Database

                                   ├── Assets

                                   └── Storage

```



The frontend principle is:



```text

ACTIVE CMS THEME

      ↓

HEADER

      ↓

FAVORITE WEB TOOLS CONTENT

      ↓

FOOTER

```



And theme mode:



```text

ACTIVE THEME MODE

      ↓

 ┌────┴────┐

Light     Dark

  ↓         ↓

Web Tools Web Tools

Light UI  Dark UI

```



Favorite Web Tools therefore remains a **plugin inside Favorite CMS**, not a separate website/theme.



The plugin owns the tool platform; Favorite CMS owns the site shell and theme; the active theme owns the dark/light state.




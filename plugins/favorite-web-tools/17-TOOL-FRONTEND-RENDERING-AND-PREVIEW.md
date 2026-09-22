# 17 — TOOL FRONTEND RENDERING AND PREVIEW



## 1. Purpose



The **Tool Frontend Rendering and Preview System** defines how Favorite Web Tools are rendered and used on the public frontend.



It covers:



* Tool page rendering

* Tool discovery rendering

* Dynamic input forms

* Input validation UI

* Action buttons

* Processing states

* Result rendering

* HTML preview

* CSS preview

* JavaScript preview

* File results

* Download results

* Error states

* Responsive behavior

* Accessibility

* Theme compatibility



The frontend must use the existing Favorite CMS/theme architecture.



It must not create a separate frontend framework or application shell unless the repository already requires one.



---



# 2. Core Principle



The frontend is responsible for presentation and user interaction.



The backend remains authoritative for:



* Authentication

* Authorization

* Membership

* Tool status

* Input validation

* Tool configuration

* Engine selection

* Execution

* Security



Conceptually:



```text

FRONTEND

   │

   ├── Render Tool

   ├── Collect Input

   ├── Validate for UX

   ├── Submit Request

   ├── Show Processing

   └── Render Result

            │

            ▼

        BACKEND

            │

            ├── Authorize

            ├── Validate

            ├── Execute

            └── Return Result

```



---



# 3. Public Tool Page



Each ACTIVE tool should have a public tool page.



Conceptual URL:



```text

/tools/{tool-slug}

```



The exact route must follow the existing Favorite CMS routing architecture.



The tool page should contain:



```text

Tool Header

Tool Description

Input Area

Action Area

Processing State

Result Area

Error Area

Help/Instructions

```



Only the sections required by the tool should be rendered.



---



# 4. Tool Header



The tool header may contain:



* Tool name

* Icon

* Thumbnail where applicable

* Short description

* Full description

* Category

* Access indicator



Example:



```text

HTML Formatter

Format and beautify HTML code.



Category: HTML

Access: Free

```



Internal configuration must not be exposed.



---



# 5. Access Indicator



The frontend may display:



```text

Free

Login Required

Membership Required

```



These indicators are informational.



The backend remains authoritative.



The frontend must not assume that changing or hiding the indicator grants access.



---



# 6. Tool Page for FREE Tools



For:



```text

FREE

```



the user should be able to use the tool without login.



The UI should not unnecessarily request authentication.



Logged-in users should also be able to use the tool.



No usage counter or remaining-use display should exist.



---



# 7. Tool Page for LOGIN\_REQUIRED Tools



For:



```text

LOGIN\_REQUIRED

```



anonymous users should see an appropriate login prompt/action.



Conceptually:



```text

Login Required

Please log in to use this tool.

```



The exact login route/action must use the existing CMS authentication system.



Logged-in users should see the normal tool interface.



---



# 8. Tool Page for MEMBERSHIP\_REQUIRED Tools



For:



```text

MEMBERSHIP\_REQUIRED

```



anonymous users should receive an appropriate login action.



Logged-in users without active membership should receive an appropriate membership-related action/message.



Active members should see the normal tool interface.



The frontend must not independently determine membership validity.



---



# 9. Unlimited Membership Model



Active membership provides unlimited tool usage.



The frontend must not display:



* Credits remaining

* Tokens remaining

* Daily uses

* Monthly uses

* Usage quota

* Usage meter

* Remaining attempts



The membership UI may simply communicate:



```text

Membership Required

```



or other appropriate access information.



---



# 10. Dynamic Input Rendering



Tool inputs must be rendered from the tool's configured input schema.



The frontend must not hard-code every tool's form.



Conceptually:



```text

Tool Configuration

       ↓

Input Schema

       ↓

Input Renderer

       ↓

Dynamic Form

```



This allows new tools to use the existing platform without rebuilding the frontend.



---



# 11. Supported Input Types



The frontend must support:



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



The renderer should select the appropriate UI control based on the configured input type.



---



# 12. Text Input



For:



```text

TEXT

```



render an accessible single-line input.



Support where configured:



* Label

* Placeholder

* Help text

* Default value

* Required state

* Validation feedback



---



# 13. Textarea Input



For:



```text

TEXTAREA

```



render a multi-line editor/input area.



This is particularly useful for:



* HTML

* CSS

* JavaScript

* JSON

* Text processing



The UI may provide:



* Resizable area

* Monospace mode for code

* Line-aware presentation where useful

* Clear button



These are presentation features and must not alter the actual submitted data unexpectedly.



---



# 14. Number Input



For:



```text

NUMBER

```



render an appropriate numeric input.



Apply configured:



* Minimum

* Maximum

* Step

* Default



Frontend constraints are for UX.



The backend must validate the same constraints.



---



# 15. URL Input



For:



```text

URL

```



render a URL-oriented input.



The frontend may provide basic format validation.



The backend remains authoritative.



A URL must not automatically be fetched by the browser or server unless the tool explicitly requires URL processing.



---



# 16. File Input



For:



```text

FILE

```



render a file picker.



The UI may show:



* Selected filename

* File size

* Accepted format

* Remove/replace action



The backend must independently validate:



* File size

* File type

* MIME type

* Actual format where required



The browser's file metadata must not be trusted as a security boundary.



---



# 17. Select Input



For:



```text

SELECT

```



render configured options.



Only options provided by the tool configuration should be accepted.



The backend must validate that submitted values are allowed.



---



# 18. Checkbox Input



For:



```text

CHECKBOX

```



render a standard accessible checkbox.



The backend must normalize and validate the submitted boolean/value.



---



# 19. Radio Input



For:



```text

RADIO

```



render configured options.



Only configured values should be accepted.



The backend remains authoritative.



---



# 20. JSON Input



For:



```text

JSON

```



render an appropriate JSON input/editor.



The frontend may provide:



* JSON syntax assistance

* Basic formatting

* Basic validation

* Beautify action



The backend must parse and validate the actual submitted JSON.



JSON must never be treated as executable code.



---



# 21. Input Labels



Every visible input should have an accessible label.



Labels should clearly communicate:



* What the input represents.

* Whether it is required.

* Relevant constraints.



Avoid relying solely on placeholder text.



---



# 22. Input Help



Where configured, help text may appear near the relevant field.



Example:



```text

HTML Code

Paste the HTML you want to format.

```



Help text is presentation content and must be safely rendered.



---



# 23. Required Fields



Required fields should be visually identifiable.



The frontend may prevent submission when a required field is empty.



The backend must still perform required-field validation.



---



# 24. Frontend Validation



Frontend validation is intended to provide immediate feedback.



Examples:



* Required field missing.

* Invalid number.

* Invalid URL.

* Invalid JSON.

* Invalid option.

* Unsupported file type.



Frontend validation must not replace backend validation.



---



# 25. Backend Validation



The server must validate every execution request independently.



A user must not be able to bypass validation by:



* Disabling JavaScript.

* Modifying HTML.

* Editing browser requests.

* Calling the API directly.



---



# 26. Unknown Inputs



The frontend should submit only configured inputs.



The backend must not blindly process unknown fields.



Conceptually:



```text

Configured Inputs

      ↓

Accepted



Unknown Input

      ↓

Reject / Ignore according to API contract

```



The exact behavior must follow the execution API implementation.



---



# 27. Input Layout



The system should support a flexible layout without forcing every tool into the same visual structure.



Possible layout:



```text

Input

Input

Input

\[ Process ]

```



For code-oriented tools:



```text

┌─────────────────────────────┐

│ Source Input                │

│                             │

│                             │

└─────────────────────────────┘



\[ Format ] \[ Clear ]



┌─────────────────────────────┐

│ Result                      │

│                             │

└─────────────────────────────┘

```



The exact visual implementation should follow the existing CMS/theme design system.



---



# 28. Primary Action



Each tool should provide a clear primary action.



Examples:



```text

Format

Minify

Validate

Convert

Generate

Process

Check

```



The label should describe the actual operation.



Avoid generic labels when a more meaningful action is available.



---



# 29. Clear/Reset Action



Where appropriate, tools should provide a clear/reset action.



It should:



* Clear user inputs.

* Clear previous results.

* Reset temporary frontend state.



It must not alter saved tool configuration.



---



# 30. Duplicate Submission Prevention



While a request is processing, the frontend may temporarily disable the primary action.



This is a UX protection only.



It must not be treated as a server-side security mechanism.



The backend must safely handle repeated requests.



---



# 31. Processing State



When processing starts, show a clear processing state.



Examples:



```text

Processing...

Formatting...

Converting...

Uploading...

Preparing result...

```



The message should reflect the operation where practical.



---



# 32. Python Processing State



Python API tools may require more visible processing states.



Possible states:



```text

Uploading...

Sending request...

Processing...

Preparing result...

```



Do not display fake percentage progress.



If the backend does not provide real progress information, use an indeterminate loading state.



---



# 33. Long-Running Tools



For long-running processing:



* Keep the user informed.

* Avoid freezing the page.

* Use appropriate asynchronous behavior where supported.

* Handle timeout/error responses.

* Avoid fake progress.



The exact architecture depends on the tool's processing requirements.



---



# 34. Result Area



Every tool page should have a dedicated result area.



Conceptually:



```text

Input

 ↓

Action

 ↓

Processing

 ↓

Result

```



The result area should only appear when appropriate.



---



# 35. Result Types



The frontend must support:



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



The result renderer must choose the appropriate display method based on the returned result type.



---



# 36. TEXT Result



TEXT results should be displayed as text.



For code output:



* Use a suitable code presentation.

* Preserve formatting.

* Avoid interpreting the output as HTML.



Example:



```text

Formatted HTML source

```



should be displayed as source text, not automatically executed.



---



# 37. HTML Result



HTML results must distinguish between:



```text

HTML SOURCE

```



and:



```text

HTML PREVIEW

```



Source should be displayed safely as text/code.



Preview should use an isolated rendering environment.



---



# 38. HTML Preview



If HTML preview is supported:



```text

User HTML

 ↓

Isolated Preview

```



The preview must not gain unnecessary access to the parent application.



Do not inject untrusted HTML directly into the main CMS DOM when an isolated preview is required.



---



# 39. CSS Result



CSS source should normally be rendered as text/code.



If a visual preview is supported:



```text

User CSS

 ↓

Isolated Preview

 ↓

Preview Result

```



The preview must not unexpectedly affect:



* Tool page styles.

* CMS navigation.

* Other components.

* Admin UI.



---



# 40. JavaScript Result



JavaScript processing results should normally be shown as source/code.



For example:



```text

Minified JavaScript

```



must not automatically execute.



---



# 41. JavaScript Preview



If actual JavaScript execution is intentionally supported:



* It must run in an isolated environment.

* Parent-page access must be restricted.

* CMS session data must not be exposed.

* Sensitive browser storage must not be exposed where avoidable.

* Privileged APIs must not be available by default.



The frontend must never treat arbitrary user JavaScript as trusted application code.



---



# 42. JSON Result



JSON results should be rendered as structured JSON or formatted text.



The renderer must use safe JSON serialization/escaping.



JSON output must not become executable JavaScript.



---



# 43. File Result



For file results, the frontend may show:



* Filename.

* File type.

* File size where available.

* Preview where supported.

* Download action.



The browser must receive a controlled result reference, not an internal filesystem path.



---



# 44. Image Result



For image results:



* Show a safe preview.

* Provide download when configured.

* Handle loading errors.

* Avoid trusting arbitrary HTML around the image.



---



# 45. Audio Result



For audio results:



* Use an accessible audio player.

* Show appropriate controls.

* Provide download where configured.



---



# 46. Video Result



For video results:



* Use an appropriate video player.

* Support controls required by the tool.

* Provide download where configured.



If the result is externally hosted, follow the configured media/source rules.



---



# 47. Download Result



Download actions should use controlled download references.



The frontend must never construct downloads from raw server filesystem paths.



Conceptually:



```text

Tool Result

 ↓

Download Reference

 ↓

Download Handler

 ↓

File

```



---



# 48. Copy Action



Where appropriate, provide a Copy button for:



* TEXT

* HTML source

* JSON

* CSS

* JavaScript

* Other copyable output



Copy action should operate on the actual displayed result.



After copying, provide clear feedback such as:



```text

Copied

```



---



# 49. Download Action



Where configured, provide a Download action.



The frontend must use the controlled download mechanism returned by the backend.



Do not expose:



* Filesystem paths.

* Internal storage paths.

* Private server URLs.



---



# 50. Error Rendering



Errors should be presented clearly.



Possible categories:



```text

Validation Error

Access Error

Processing Error

Service Error

Timeout

Unexpected Error

```



Use safe user-facing messages.



---



# 51. Field-Level Errors



When the backend identifies a specific input problem, the frontend should associate the error with that field.



Example:



```text

URL

└── Please enter a valid URL.

```



This improves usability.



---



# 52. General Errors



For errors that do not belong to a single field:



```text

The tool could not process your request.

Please try again.

```



Do not expose internal stack traces or sensitive server details.



---



# 53. Access Errors



If the backend rejects access:



### Login Required



Show a login-related action using existing CMS authentication.



### Membership Required



Show appropriate membership information/action.



### Disabled



Show that the tool is currently unavailable where appropriate.



Do not expose internal authorization details.



---



# 54. Tool Not Found



If an invalid tool slug is requested, use the existing CMS 404/not-found architecture where possible.



Do not expose internal registry/database details.



---



# 55. Disabled Tool Page



A disabled tool should not normally be discoverable publicly.



If a previously bookmarked URL is accessed, the system should use an appropriate not-found/unavailable response according to CMS conventions.



The disabled tool must not execute.



---



# 56. Draft Tool Page



DRAFT tools must not be publicly usable.



Administrative preview/testing should use the existing admin authorization context.



Do not expose draft tools through normal public discovery.



---



# 57. Search Rendering



The Tool Home page should provide search where implemented.



Search results may display:



* Tool name.

* Short description.

* Category.

* Access indicator.

* Icon/thumbnail.

* Open action.



Only public ACTIVE tools should appear.



---



# 58. Category Rendering



Category pages should display ACTIVE tools belonging to that category.



The category page may include:



* Category name.

* Description.

* Icon/thumbnail.

* Tool list.

* Search/filter where appropriate.



DRAFT and DISABLED tools must not appear in public category listings.



---



# 59. Tool Cards



Tool cards may contain:



```text

Icon/Thumbnail

Tool Name

Short Description

Category

Access Indicator

Open Tool

```



Cards must remain responsive.



Do not expose internal IDs or configuration.



---



# 60. Empty States



If a search or category has no available tools, display a useful empty state.



Example:



```text

No tools found.

Try another search.

```



Do not expose internal database/debug information.



---



# 61. Loading State



Tool discovery and tool execution should have appropriate loading states.



Loading indicators should:



* Clearly indicate activity.

* Avoid fake progress.

* Not block unrelated navigation unnecessarily.



---



# 62. Responsive Design



The tool frontend must support:



* Desktop

* Laptop

* Tablet

* Mobile



Code inputs should remain usable on small screens.



Buttons should remain accessible.



Long code/output should allow:



* Horizontal scrolling where appropriate.

* Wrapping where appropriate.

* Copying.

* Downloading.



---



# 63. Accessibility



The frontend should follow accessible UI practices.



At minimum:



* Proper labels.

* Keyboard accessibility.

* Visible focus state.

* Meaningful button labels.

* Appropriate semantic elements.

* Accessible error messages.

* Accessible loading state where practical.

* Sufficient contrast according to the existing theme/design system.



Do not rely only on color to communicate state.



---



# 64. Keyboard Support



Users should be able to:



* Navigate inputs.

* Submit forms where appropriate.

* Activate buttons.

* Copy results.

* Dismiss temporary UI messages.



Tool-specific keyboard shortcuts may be added later when genuinely useful.



---



# 65. Theme Compatibility



The frontend must integrate with the existing Favorite CMS theme.



Do not assume a specific theme.



Styles should avoid unnecessary global selectors that could affect unrelated CMS pages.



Prefer plugin-scoped classes.



---



# 66. CSS Isolation



Plugin styles should be scoped appropriately.



Avoid broad selectors such as:



```text

body \*

div

button

input

```



when they could affect unrelated CMS components.



Use plugin/tool-specific class namespaces where appropriate.



---



# 67. JavaScript Isolation



Plugin JavaScript should avoid unnecessary global variables.



Prefer:



* Module patterns.

* Scoped functions.

* Namespaced functionality.

* Existing CMS asset conventions.



Do not overwrite unrelated global objects.



---



# 68. Frontend Asset Loading



Load CSS/JavaScript through the existing Favorite CMS asset mechanism.



Assets should be loaded only where needed when practical.



Do not create a second asset-management system.



---



# 69. Browser-Side Tool Processing



Some tools can safely process data entirely in the browser.



Examples may include:



* Simple formatting.

* Basic encoding/decoding.

* Lightweight converters.



For such tools:



```text

Input

 ↓

Browser Processing

 ↓

Result

```



may be appropriate.



However, the browser implementation is inspectable and must not contain secrets.



---



# 70. Server-Side Tool Processing



Use server-side processing when:



* Proprietary logic must remain private.

* PHP processing is required.

* File processing requires server infrastructure.

* External API credentials are required.

* Python API is required.

* Browser execution would be inappropriate.



Conceptually:



```text

Browser

 ↓

Execution API

 ↓

Server Engine

 ↓

Result

```



---



# 71. Python API Frontend Flow



For Python API tools:



```text

User Input

 ↓

Frontend Validation

 ↓

Upload/Request

 ↓

Execution API

 ↓

Access Check

 ↓

Python Service

 ↓

Processing

 ↓

Result

 ↓

Frontend Renderer

```



The frontend must not call private Python service credentials directly.



---



# 72. API Response Contract



The frontend should consume a standardized execution response.



Conceptually:



### Success



```text

{

  success: true,

  result: {

    type: "...",

    data: "..."

  }

}

```



### Error



```text

{

  success: false,

  error: {

    code: "...",

    message: "..."

  }

}

```



The exact API structure must follow the implementation defined in:



`10-TOOL-EXECUTION-API.md`



---



# 73. Result Renderer Architecture



The frontend should use a centralized result-rendering mechanism.



Conceptually:



```text

Result Type

    │

    ├── TEXT → Text Renderer

    ├── HTML → HTML Renderer

    ├── JSON → JSON Renderer

    ├── FILE → File Renderer

    ├── IMAGE → Image Renderer

    ├── AUDIO → Audio Renderer

    ├── VIDEO → Video Renderer

    └── DOWNLOAD → Download Renderer

```



This avoids duplicated rendering logic across tools.



---



# 74. Input Renderer Architecture



Likewise, inputs should use a centralized renderer.



Conceptually:



```text

Input Type

    │

    ├── TEXT

    ├── TEXTAREA

    ├── NUMBER

    ├── URL

    ├── FILE

    ├── SELECT

    ├── CHECKBOX

    ├── RADIO

    └── JSON

```



Each renderer should follow the common input contract.



---



# 75. Preview Architecture



Preview functionality should remain separate from ordinary result rendering where security requires isolation.



Conceptually:



```text

Source Result

    │

    ├── Display Source

    │

    └── Preview

          ↓

      Isolated Context

```



Do not assume that source output and rendered output are interchangeable.



---



# 76. Preview Lifecycle



A preview should support:



```text

Create

 ↓

Render

 ↓

Update

 ↓

Clear

 ↓

Destroy

```



When leaving the tool or clearing the result, temporary preview resources should be cleaned up where applicable.



---



# 77. Preview Security Boundary



Preview content must never gain more privilege than necessary.



For HTML/CSS/JavaScript:



```text

User Content

   ↓

Sandbox/Isolation

   ↓

Preview

```



Never:



```text

User Content

   ↓

Main CMS DOM

```



unless the content has been deliberately sanitized and the feature specifically requires direct rendering.



---



# 78. External Preview Content



If a tool previews external content:



* Validate the source where required.

* Avoid exposing unnecessary parent-page privileges.

* Handle loading errors.

* Follow the tool's configured security model.



---



# 79. Frontend State



The tool page may maintain temporary state such as:



* Current inputs.

* Processing state.

* Current result.

* Current error.

* Preview state.



Temporary frontend state must not be treated as persistent tool configuration.



---



# 80. Browser Storage



Browser storage should only be used when genuinely useful.



Do not store:



* API credentials.

* Private service credentials.

* Authentication secrets.

* Membership authorization state as a trusted value.



If input persistence is later implemented, it must follow the privacy/security requirements of that tool.



---



# 81. Navigation



The tool frontend should integrate with normal browser navigation.



Examples:



```text

Tools Home

 ↓

Category

 ↓

Tool

```



and:



```text

Search

 ↓

Tool

```



Use existing CMS routes and navigation patterns.



---



# 82. SEO



Public ACTIVE tool pages may support:



* SEO title.

* Meta description.

* Structured metadata where supported.

* Clean URLs.



SEO configuration must use existing Favorite CMS mechanisms.



Draft/disabled tools should not be publicly indexed as normal active tools.



---



# 83. Performance



The frontend should avoid unnecessary:



* Large JavaScript bundles.

* Repeated API requests.

* Duplicate rendering.

* Unnecessary polling.

* Heavy preview initialization.



Large results should be handled efficiently.



---



# 84. Large Results



For large outputs:



* Avoid unnecessarily duplicating data in memory.

* Prefer controlled file/download references when appropriate.

* Use efficient rendering.

* Avoid rendering enormous content directly into complex DOM structures when unnecessary.



The exact approach depends on the tool.



---



# 85. Error Recovery



The frontend should allow users to recover from common errors.



Examples:



```text

Validation Error

→ Correct input

→ Retry

```



```text

Processing Error

→ Retry

→ Clear

```



Do not automatically retry potentially expensive operations unless explicitly appropriate.



---



# 86. Network Failure



If the execution request fails due to a network problem:



Show a safe message such as:



```text

Unable to reach the processing service.

Please try again.

```



Do not expose internal service details.



---



# 87. Timeout UI



If a request times out:



```text

Processing timed out.

Please try again.

```



Do not display fake progress or claim completion.



---



# 88. Frontend Security Rules



The frontend must:



* Treat API responses as untrusted.

* Escape text output.

* Render HTML only through intended safe paths.

* Isolate active previews.

* Avoid exposing secrets.

* Avoid trusting client-side authorization.

* Avoid trusting client-side membership state.

* Avoid exposing internal paths.

* Avoid unsafe DOM insertion.



---



# 89. AI Agent Implementation Rules



When implementing the frontend rendering system, the AI agent must:



1. Inspect the existing Favorite CMS frontend/theme architecture.

2. Reuse existing layouts/components where appropriate.

3. Reuse existing routing.

4. Reuse existing asset loading.

5. Reuse existing authentication UI.

6. Reuse existing membership UI where applicable.

7. Implement only inside the `favorite-web-tools` plugin.

8. Avoid creating a separate frontend framework unless required.

9. Use dynamic input configuration.

10. Use centralized result rendering.

11. Keep frontend validation separate from backend authority.

12. Isolate HTML previews.

13. Isolate CSS previews.

14. Isolate JavaScript execution if ever supported.

15. Never expose API credentials.

16. Never trust client-side access state.

17. Never execute arbitrary PHP or Python in the browser.

18. Do not expose internal filesystem paths.

19. Do not create global CSS/JS conflicts.

20. Follow existing CMS/theme conventions.

21. Keep Favorite API Connector separate.

22. Do not introduce usage limits, credits, tokens, or counters.

23. Do not modify CMS core or unrelated plugins.

24. Test desktop, tablet, and mobile behavior.

25. Test accessibility and keyboard interaction.

26. Test error and loading states.

27. Follow actual repository structure instead of assumptions.



---



# 90. Required Acceptance Criteria



The implementation is acceptable only when:



* ACTIVE tools have public tool pages.

* DRAFT tools are not publicly usable.

* DISABLED tools are not publicly executable.

* Tool pages are dynamically rendered from configuration.

* Supported input types render correctly.

* Input labels and validation states are accessible.

* Frontend validation works as UX support.

* Backend validation remains authoritative.

* Access states are correctly represented.

* FREE tools work without login.

* LOGIN\_REQUIRED tools require authentication.

* MEMBERSHIP\_REQUIRED tools require active membership.

* Active members have unlimited access.

* No usage counters or quotas appear.

* Processing states are clear.

* No fake progress is shown.

* TEXT results render as text.

* HTML source is not accidentally executed.

* HTML previews are isolated.

* CSS previews cannot unexpectedly affect CMS UI.

* JavaScript results do not automatically execute.

* JavaScript execution, if implemented, is isolated.

* JSON is rendered safely.

* File results use controlled references.

* Download actions do not expose filesystem paths.

* Copy actions work for appropriate outputs.

* Errors are safe and understandable.

* Sensitive server details are not exposed.

* Tool search shows only public ACTIVE tools.

* Category pages show only public ACTIVE tools.

* Responsive layout works across device sizes.

* Keyboard accessibility is supported.

* Theme compatibility is preserved.

* Plugin CSS/JS does not unnecessarily affect unrelated CMS pages.

* Existing CMS routing/auth/theme/asset systems are reused.

* Favorite API Connector remains separate.

* Plugin isolation is preserved.

* CMS core remains unchanged.



---



# 91. Final Frontend Rendering Model



The complete frontend model is:



```text

                     TOOL CONFIGURATION

                            │

                            ▼

                    TOOL FRONTEND PAGE

                            │

              ┌─────────────┼─────────────┐

              │             │             │

            Header        Inputs       Access UI

                            │

                            ▼

                     INPUT RENDERER

                            │

                            ▼

                     USER ENTERS DATA

                            │

                            ▼

                  FRONTEND VALIDATION

                            │

                            ▼

                       EXECUTION

                            │

                            ▼

                     PROCESSING STATE

                            │

                            ▼

                    STANDARD RESULT

                            │

                            ▼

                     RESULT RENDERER

                            │

          ┌─────────────────┼─────────────────┐

          │                 │                 │

        TEXT              HTML              JSON

          │                 │                 │

          │              Preview              │

          │             (isolated)            │

          │                                   │

          ├──────────── FILE / IMAGE ─────────┤

          │                                   │

          └──────────── AUDIO / VIDEO ────────┘

                            │

                            ▼

                   COPY / DOWNLOAD / CLEAR

```



The fundamental frontend rule is:



```text

CONFIGURATION

     ↓

DYNAMIC UI

     ↓

USER INPUT

     ↓

SERVER-AUTHORITATIVE EXECUTION

     ↓

SAFE RESULT RENDERING

```



The frontend must provide a consistent, responsive, accessible tool experience while keeping all security-sensitive decisions on the server.




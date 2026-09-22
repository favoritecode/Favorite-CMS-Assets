# 29 — TOOL FRONTEND PAGE IMPLEMENTATION



## 1. Purpose



This document defines the implementation contract for the individual public tool page of `favorite-web-tools`.



The tool page is responsible for presenting a configured tool to the user and connecting the UI to the central Tool Execution API.



The page must use the active Favorite CMS theme.



---



# 2. Core Principle



The frontend tool page must be generated from the Tool Registry and Tool Configuration.



It must not contain a hard-coded implementation for every individual tool.



```text

Tool Configuration

        ↓

Tool Page Renderer

        ↓

Dynamic Inputs

        ↓

Execution API

        ↓

Dynamic Result Renderer

```



---



# 3. Public Tool URL



The conceptual public URL is:



```text

/tools/{tool-slug}

```



The exact routing must follow the existing Favorite CMS routing system.



Do not create a parallel router.



---



# 4. Page Structure



The conceptual page structure is:



```text

Active Theme Header

        ↓

Tool Page

 ├── Breadcrumb / Navigation

 ├── Tool Header

 ├── Tool Description

 ├── Access Information

 ├── Input Area

 ├── Action Area

 ├── Processing State

 ├── Result Area

 └── Error Area

        ↓

Active Theme Footer

```



---



# 5. Active Theme Integration



The tool page must render inside the active CMS theme.



The plugin must use:



* Existing Header.

* Existing Footer.

* Existing page layout where appropriate.

* Existing theme structure.

* Existing theme assets where appropriate.



The plugin must not create an independent website shell.



---



# 6. Theme Switching



If the administrator/user changes the active CMS theme, the tool page must continue to work with the new active theme.



Do not hard-code the tool page to one specific theme.



---



# 7. Dark/Light Mode



The tool page must follow the active theme's dark/light state.



If the theme uses:



```html

<body class="dark">

```



the Web Tools UI must respond accordingly.



The plugin must not create a second theme state system.



---



# 8. Theme Toggle



Do not create a duplicate theme-toggle button inside the Web Tools plugin.



The active theme remains responsible for theme switching.



---



# 9. Theme CSS Variables



Where available, reuse the active theme's:



* Colors.

* Spacing.

* Typography.

* Border radius.

* Shadows.

* Breakpoints.

* Buttons.

* Form styles.



Do not require the theme to expose variables if it does not already use them.



---



# 10. CSS Scope



Plugin CSS must be scoped to the Web Tools page/components.



Avoid global selectors such as:



```css

body {}

button {}

input {}

textarea {}

```



unless the existing CMS/theme architecture explicitly requires them.



Prefer plugin/component-specific selectors.



---



# 11. Tool Header



The tool header should display:



* Tool name.

* Short description.

* Category.

* Access mode.

* Icon or thumbnail where configured.



---



# 12. Tool Description



The full tool description may explain:



* What the tool does.

* Expected input.

* Expected output.

* Important usage instructions.



The description comes from tool configuration.



---



# 13. Category Display



The primary tool category may be displayed near the tool title.



Category information should come from the central Category System.



Do not hard-code categories into the page.



---



# 14. Access Display



The page should show the configured access state.



Supported labels:



```text

Free

Login Required

Membership Required

```



Do not use:



```text

Premium

VIP

Pro

Credits

Tokens

```



unless a completely separate future requirement explicitly introduces them.



---



# 15. FREE Tool



For a FREE tool:



```text

Access:

Free



Action:

Use Tool

```



Anonymous users may use the tool.



---



# 16. LOGIN\_REQUIRED Tool



For an unauthenticated visitor:



```text

Access:

Login Required

```



The UI should provide the appropriate existing CMS login action.



After login, the user may return to the tool page.



---



# 17. MEMBERSHIP\_REQUIRED Tool



For an unauthenticated visitor:



```text

Login Required

```



For an authenticated user without active membership:



```text

Membership Required

```



The exact login/membership navigation must use the existing CMS systems.



---



# 18. Active Membership



An active member can execute membership-required tools without usage restrictions.



Do not display:



```text

Uses Remaining

Credits Remaining

Daily Uses

Monthly Uses

```



---



# 19. Tool Inputs



The page dynamically renders inputs from the tool's configured Input Schema.



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



---



# 20. Input Renderer



Conceptually:



```text

Input Type

    ↓

Input Renderer

    ↓

HTML Form Control

```



The frontend must not hard-code the input fields of individual tools.



---



# 21. TEXT Input



Render a normal text field with:



* Label.

* Placeholder where configured.

* Help text where configured.

* Required state.

* Validation feedback.



---



# 22. TEXTAREA Input



Render a multiline text area for:



* HTML.

* CSS.

* JavaScript.

* JSON.

* Text.

* Other long-form tool inputs.



---



# 23. NUMBER Input



Render an appropriate numeric input.



Apply configured:



* Minimum.

* Maximum.

* Step.

* Default.



---



# 24. URL Input



Render a URL field with appropriate browser-level input behavior.



Backend validation remains authoritative.



---



# 25. SELECT Input



Render configured options.



The frontend must only display options supplied by the tool configuration.



The backend must independently validate the selected value.



---



# 26. CHECKBOX Input



Render checkbox controls according to the configured schema.



---



# 27. RADIO Input



Render configured mutually exclusive options.



---



# 28. JSON Input



Render a suitable multiline editor/input area.



The frontend may provide basic JSON validation for better UX.



Backend validation remains authoritative.



---



# 29. FILE Input



Render a file selector for configured file types.



Show:



* Selected filename.

* File state.

* Validation feedback.

* Remove/replace action where appropriate.



---



# 30. Multiple Files



Multiple file support may be enabled only when the tool configuration explicitly supports it.



Do not assume every FILE input accepts multiple files.



---



# 31. File Constraints



Frontend may display configured:



* Accepted formats.

* Maximum technical file size.

* Other relevant technical requirements.



Backend validation remains authoritative.



---



# 32. File Security



Never trust the frontend file validation.



The server must independently validate uploaded files.



---



# 33. Input Help



Optional help text may appear near an input.



Example:



```text

Enter the HTML code you want to format.

```



Help text comes from tool configuration.



---



# 34. Required Indicator



Required fields should have an accessible indication.



Do not rely solely on color.



---



# 35. Placeholder



Use configured placeholders where helpful.



Do not put essential instructions only inside placeholders.



---



# 36. Default Values



Configured default values may be rendered when the tool page loads.



They must not override user input after execution.



---



# 37. Frontend Validation



Frontend validation provides immediate UX feedback.



Examples:



```text

Required field missing

Invalid number

Invalid URL

Invalid JSON

Unsupported file

```



---



# 38. Backend Validation



Frontend validation is never the security boundary.



The server validates every execution request.



---



# 39. Input Preservation



When an execution fails due to processing/service error, preserve user inputs where practical.



This allows the user to retry without re-entering everything.



---



# 40. Primary Action



Every executable tool should provide a clear primary action.



Examples:



```text

Format

Minify

Validate

Convert

Process

Generate

Check

```



The label should reflect the configured tool operation where practical.



---



# 41. Generic Execute Label



If no specific action label is configured:



```text

Run Tool

```



may be used.



---



# 42. Clear/Reset



Where appropriate, provide:



```text

Clear

Reset

```



actions.



These affect only the frontend state.



---



# 43. Execution Request



On primary action:



```text

Collect Inputs

 ↓

Frontend Validation

 ↓

Execution API

```



---



# 44. Execution API



The frontend must use the central Tool Execution API.



Conceptually:



```text

POST /api/tools/{tool-slug}/execute

```



Exact routing follows the CMS.



---



# 45. Request Construction



The frontend constructs the request from the configured input fields.



It must not send arbitrary tool configuration.



---



# 46. Configuration Protection



The frontend must not be treated as the source of truth for:



```text

Engine

Access Mode

PHP Handler

Python Service

Python Endpoint

Credentials

```



---



# 47. Authentication



Protected tool requests should use the existing CMS authentication/session mechanism.



Do not create a separate frontend login system.



---



# 48. CSRF



If the CMS requires CSRF protection for execution requests, the frontend must use the existing CMS CSRF mechanism.



Do not invent a second token system.



---



# 49. Processing State



After sending an execution request, the page should show a processing state.



Examples:



```text

Processing...

```



For file/Python tools:



```text

Uploading...

Sending...

Processing...

Preparing result...

```



---



# 50. Duplicate Submission



While a request is processing, the primary action may be temporarily disabled.



This is a UX protection only.



It is not a usage limit.



---



# 51. Real Progress



If the backend/service provides real progress information, it may be displayed.



Otherwise, do not display fake percentages.



---



# 52. Request Failure



If the network request fails:



```text

Unable to connect. Please try again.

```



or an equivalent safe message should be displayed.



---



# 53. Authentication Error



If the API returns an authentication requirement:



```text

Please log in to use this tool.

```



with the existing CMS login action.



---



# 54. Membership Error



If membership is required:



```text

An active membership is required to use this tool.

```



Use the existing membership system/action.



---



# 55. Validation Error



Validation errors should be displayed near the relevant input where possible.



Example:



```text

HTML:

This field is required.

```



---



# 56. General Error



For non-field-specific failures, show a general error area.



Examples:



```text

Processing failed.

The service is temporarily unavailable.

Unable to generate the result.

```



---



# 57. Internal Error Protection



Never display:



* Stack traces.

* SQL errors.

* Internal filesystem paths.

* API keys.

* Tokens.

* Internal service URLs where inappropriate.

* Server environment details.



---



# 58. Result Area



Successful execution should render a dedicated result area.



Conceptually:



```text

Result

 ├── Result Header

 ├── Result Content

 └── Result Actions

```



---



# 59. Result Types



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



---



# 60. TEXT Result



Display text in a readable, selectable container.



Provide Copy where appropriate.



---



# 61. HTML Source Result



If the result represents HTML source:



* Display it as source text.

* Escape it appropriately.

* Provide Copy where useful.



Do not automatically execute it.



---



# 62. HTML Preview Result



If configured to render HTML:



```text

HTML Result

 ↓

Isolated Preview

```



Use the defined sandbox/isolation mechanism.



---



# 63. CSS Result



CSS source should normally be displayed as text.



If preview is enabled:



```text

CSS

 ↓

Isolated Preview

```



CSS must not affect the CMS page.



---



# 64. JavaScript Result



Processed JavaScript should be displayed as source/data.



Do not automatically execute it.



---



# 65. JavaScript Execution Result



If a tool intentionally supports JavaScript execution:



* Use an isolated environment.

* Restrict privileged access.

* Never expose CMS secrets.

* Clearly separate execution output from source output.



---



# 66. JSON Result



Render structured JSON safely.



Possible UI:



```text

Formatted JSON

```



with:



```text

Copy

```



where appropriate.



---



# 67. IMAGE Result



Render the image through a controlled result reference.



Do not expose internal storage paths.



---



# 68. AUDIO Result



Render the audio using the appropriate media output component.



---



# 69. VIDEO Result



Render the video using the appropriate media output component.



---



# 70. FILE Result



Show:



* Filename where safe.

* File type.

* File size where appropriate.

* Download action.



---



# 71. DOWNLOAD Result



Show a clear download action.



The download reference must come from the backend.



---



# 72. Result Actions



Depending on output type, support:



```text

Copy

Download

Open

Preview

Clear

```



Only show actions applicable to the result.



---



# 73. Copy



Copy should use safe browser APIs.



Show a temporary confirmation such as:



```text

Copied

```



---



# 74. Download



Download should use the controlled download endpoint/reference.



Do not construct filesystem paths in JavaScript.



---



# 75. Result Replacement



When the user runs the tool again, the new result should replace or update the previous result according to the tool UI configuration.



---



# 76. Multiple Outputs



If the execution response contains multiple outputs, render them in configured order.



Example:



```text

Output 1 → Text

Output 2 → Preview

Output 3 → Download

```



---



# 77. Empty Result



If the tool succeeds but returns no meaningful output:



```text

No result was generated.

```



or an equivalent configured empty state.



---



# 78. Result Loading



Large results should not unnecessarily block the entire page.



Use the appropriate rendering strategy for the result type.



---



# 79. Large Text



Large text outputs should use a scrollable/result container where appropriate.



Avoid unnecessary DOM duplication.



---



# 80. Large Files



Large generated files should be handled through controlled download/reference mechanisms rather than embedding the entire file in the page.



---



# 81. Tool Page State



The frontend should maintain clear states:



```text

INITIAL

READY

VALIDATING

SUBMITTING

PROCESSING

SUCCESS

ERROR

```



Exact state implementation may follow the existing frontend architecture.



---



# 82. State Reset



Clear/reset should return the page to the appropriate READY/INITIAL state.



---



# 83. Navigation



The tool page must support normal browser navigation.



Do not break:



* Back.

* Forward.

* Refresh.

* Direct URL access.



---



# 84. Refresh



Refreshing the page should load the tool configuration again.



Sensitive temporary results should not automatically be persisted in browser storage unless explicitly required.



---



# 85. Browser Storage



Do not store:



* API keys.

* Authentication credentials.

* Membership credentials.

* Python service credentials.

* Sensitive server configuration.



---



# 86. URL Parameters



Public URL parameters may be used for safe presentation/filtering where required.



Do not allow URL parameters to override server-side tool configuration.



---



# 87. SEO



Tool pages should use existing CMS SEO/title/meta mechanisms where available.



Configured fields may include:



* SEO title.

* SEO description.



Do not create a second SEO framework.



---



# 88. Breadcrumbs



If the active CMS/theme supports breadcrumbs, the tool page may integrate with them.



Example:



```text

Tools

 → HTML

 → HTML Formatter

```



---



# 89. Responsive Design



The tool page must work on:



```text

Mobile

Tablet

Desktop

```



---



# 90. Mobile Layout



On small screens:



* Inputs should remain readable.

* Buttons should remain accessible.

* Result areas should not overflow horizontally.

* Code/text areas should scroll appropriately.

* File controls should remain usable.



---



# 91. Tablet Layout



Use the active theme's responsive conventions where available.



---



# 92. Desktop Layout



The page may use wider tool/result layouts when appropriate.



Do not force excessive full-width content.



---



# 93. Accessibility



The tool page must support:



* Keyboard navigation.

* Labels.

* Focus states.

* Accessible error messages.

* Appropriate ARIA only where needed.

* Sufficient semantic structure.

* Screen-reader-friendly status updates.



---



# 94. Processing Announcement



When processing starts, the UI should expose the state to assistive technology where appropriate.



---



# 95. Error Announcement



Validation and execution errors should be accessible to users using assistive technologies.



---



# 96. Keyboard Support



Users should be able to:



* Navigate inputs.

* Submit where appropriate.

* Clear/reset.

* Access results.

* Copy/download.



---



# 97. Form Submission



Avoid accidental browser navigation/reload when executing a tool through JavaScript.



Use the CMS/frontend architecture appropriately.



---



# 98. JavaScript Architecture



Frontend JavaScript should be modular.



Conceptually:



```text

Tool Page Controller

 ├── Input Renderer

 ├── Validation

 ├── API Client

 ├── Loading State

 ├── Result Renderer

 └── Error Renderer

```



Do not create one giant script containing every tool's logic.



---



# 99. Tool-Specific Frontend Logic



Tool-specific frontend behavior may be configured/registered where genuinely necessary.



However, avoid hard-coding every tool into the central page controller.



---



# 100. Client-Side Tools



Some tools may process entirely in the browser.



Examples may include:



```text

Simple Encoding

Simple Formatting

Simple Conversion

```



Only do this when:



* Safe.

* No secret is required.

* Server-side protection is unnecessary.

* Performance is acceptable.



---



# 101. Server-Side Tools



Tools should use the execution API when they require:



* PHP.

* Python.

* Protected logic.

* File processing.

* Server resources.

* Secrets/API credentials.



---



# 102. Hybrid Tools



A tool may combine:



```text

Browser Processing

\+

Server Processing

```



where useful.



The architecture must remain explicit.



---



# 103. Python Tool Frontend



Python tools should use the same Tool Execution API as other tools.



The frontend should not directly manage private Python service credentials.



---



# 104. Python Upload UI



For file-based Python tools:



```text

Select File

 ↓

Validate Frontend

 ↓

Upload/Execute

 ↓

Processing State

 ↓

Result

```



---



# 105. Python Errors



Python service errors should appear as safe user-facing messages.



Do not expose raw Python exceptions.



---



# 106. Theme-Aware Result Components



Result cards, input panels, buttons, code blocks, alerts, and loading states must respond to the active theme.



---



# 107. No Global Theme Override



The plugin must not force:



```text

background

color

font

```



across the entire site.



Only the Web Tools UI should be styled.



---



# 108. Preview Isolation



All user-generated previews must remain isolated from:



* Site Header.

* Site Footer.

* Navigation.

* Theme state.

* Other tool components.



---



# 109. Security Boundary



The frontend must be treated as untrusted.



Users can modify:



* HTML.

* CSS.

* JavaScript.

* Requests.

* Browser state.



Therefore server-side enforcement remains authoritative.



---



# 110. API Response Trust



API responses should be rendered according to their declared result type.



Do not blindly inject arbitrary response content into privileged page DOM.



---



# 111. Error Boundary



Unexpected frontend errors should show a controlled error state instead of breaking the entire site page.



---



# 112. Performance



The implementation should:



* Load only required assets.

* Avoid unnecessary libraries.

* Avoid loading every tool's JavaScript on every page.

* Use modular assets where appropriate.

* Avoid unnecessary DOM work.



---



# 113. Asset Loading



Tool-specific assets should be loaded only when needed where the CMS asset architecture supports conditional loading.



---



# 114. Shared Components



Reusable frontend components should be centralized where appropriate.



Examples:



```text

Tool Header

Input Renderer

Action Bar

Loading State

Result Panel

Error Panel

Access Notice

```



---



# 115. No Duplicate Components



Do not create separate versions of the same renderer for every tool unless a tool genuinely requires custom behavior.



---



# 116. Search/Discovery Integration



The tool page may provide navigation back to:



```text

All Tools

Category

Related Tools

```



These must use the central Discovery system.



---



# 117. Related Tools



Related tools may be shown based on category or configured relationships.



Do not introduce an evaluative ranking system merely for related tools.



---



# 118. Access Consistency



The same access rules must apply whether the user reaches the tool through:



* Search.

* Category page.

* Direct URL.

* Related tool.

* Bookmark.



---



# 119. Direct Access



A protected tool may be directly visited.



The page can display its description and access requirement, but execution remains protected.



---



# 120. Admin Preview



Admin preview/testing may use the same frontend components where appropriate.



Admin authorization remains controlled by the CMS.



---



# 121. Frontend Configuration



Presentation settings may include:



```text

Layout

Result Layout

Preview Enabled

Copy Enabled

Download Enabled

Help Display

Loading Text

```



These settings must affect presentation only.



---



# 122. Frontend Configuration Security



Frontend settings must never:



* Grant access.

* Change engine.

* Change Python service.

* Expose credentials.

* Execute arbitrary code.



---



# 123. Internationalization



The frontend should support the CMS's existing language/i18n architecture where available.



The implementation should not assume English-only UI strings.



---



# 124. Bengali/English Compatibility



The UI must correctly display:



* Bengali.

* English.

* Mixed Bengali/English tool content.



Use Unicode-safe rendering.



---



# 125. Text Direction



If future localization requires RTL support, the component architecture should avoid assumptions that make RTL impossible.



Do not implement RTL unnecessarily unless the CMS requires it.



---



# 126. Frontend Error Codes



The frontend may map known backend error codes to friendly messages.



Example:



```text

AUTH\_REQUIRED

MEMBERSHIP\_REQUIRED

VALIDATION\_ERROR

PROCESSING\_ERROR

SERVICE\_UNAVAILABLE

TIMEOUT

```



---



# 127. Unknown Errors



Unknown errors should use a safe generic message.



Do not display raw backend internals.



---



# 128. Retry



Retry should repeat the normal execution process.



It must not skip:



* Access check.

* Input validation.

* Server configuration validation.



---



# 129. Session Changes



If the user logs in or membership state changes, the page should reflect the current CMS state after refresh/reload or the supported session update mechanism.



---



# 130. No Client-Side Authorization



Never implement:



```text

if (isMember) {

    allowExecution();

}

```



as the only protection.



The server must enforce authorization.



---



# 131. Execution Security Model



```text

Browser

   ↓

Untrusted Request

   ↓

CMS Execution API

   ↓

Server Validation

   ↓

Access Control

   ↓

Engine

```



---



# 132. AI Agent Implementation Rules



The AI agent must:



1. Inspect the actual CMS theme system.

2. Inspect existing frontend/page rendering conventions.

3. Inspect existing asset loading.

4. Inspect existing JavaScript architecture.

5. Inspect existing authentication UI.

6. Inspect existing membership UI/routes.

7. Reuse active theme Header.

8. Reuse active theme Footer.

9. Follow active theme dark/light mode.

10. Support `body.dark` where applicable.

11. Never create a duplicate theme toggle.

12. Keep CSS scoped.

13. Build the tool page dynamically from configuration.

14. Reuse the central Tool Execution API.

15. Reuse the central Input Renderer.

16. Reuse the central Output Renderer.

17. Keep frontend validation separate from backend validation.

18. Never trust client-side authorization.

19. Never expose credentials.

20. Never expose internal filesystem paths.

21. Never automatically execute returned HTML/JS in the privileged page.

22. Isolate previews.

23. Keep JavaScript processing separate from execution.

24. Support responsive layouts.

25. Support accessibility.

26. Avoid unnecessary dependencies.

27. Avoid loading unnecessary tool-specific assets.

28. Keep Python integration server-side.

29. Preserve plugin filesystem isolation.

30. Do not modify CMS core/theme files unless an existing documented integration point explicitly requires it.



---



# 133. Required Acceptance Criteria



The implementation is complete when:



* Individual tool pages render dynamically.

* Tool configuration determines page inputs.

* Tool name/description/category/access display correctly.

* FREE tools work anonymously.

* LOGIN\_REQUIRED tools use existing authentication.

* MEMBERSHIP\_REQUIRED tools use existing membership.

* Active members have unlimited access.

* No usage counters appear.

* No credits/tokens/quotas appear.

* All supported input types render.

* Required validation works.

* Backend validation remains authoritative.

* File inputs work where configured.

* Primary action works.

* Clear/reset works.

* Execution uses the central API.

* Loading state works.

* No fake progress is shown.

* Results render by output type.

* TEXT results work.

* HTML source works safely.

* HTML previews are isolated.

* CSS previews are isolated.

* JavaScript processing does not automatically execute.

* Intentional JavaScript execution is isolated.

* JSON renders safely.

* Image/audio/video outputs work.

* File/download results work.

* Download references are controlled.

* Errors are user-friendly.

* Internal errors are hidden.

* Retry works without bypassing security.

* Responsive design works.

* Keyboard navigation works.

* Accessibility requirements are met.

* Active theme Header appears.

* Active theme Footer appears.

* Theme switching works.

* Dark/light mode works.

* `body.dark` compatibility works where applicable.

* No duplicate theme toggle exists.

* Plugin CSS does not globally override the theme.

* Search/category/related navigation uses existing discovery.

* Python tools work through the central execution API.

* Python credentials remain server-side.

* Browser storage contains no secrets.

* CMS core remains untouched.

* Plugin filesystem isolation remains intact.

* Appropriate frontend/integration tests pass.



---



# 134. Final Frontend Model



```text

                    ACTIVE CMS THEME

                 ┌─────────────────────┐

                 │       HEADER        │

                 └──────────┬──────────┘

                            │

                            ▼

                    FAVORITE WEB TOOLS

                 ┌─────────────────────┐

                 │    Tool Header      │

                 │    Description      │

                 │    Access State     │

                 │                     │

                 │    Input Renderer   │

                 │         │           │

                 │         ▼           │

                 │    Action / Submit  │

                 │         │           │

                 │         ▼           │

                 │   Execution API     │

                 │         │           │

                 │         ▼           │

                 │  Processing State   │

                 │         │           │

                 │         ▼           │

                 │  Output Renderer    │

                 │         │           │

                 │         ▼           │

                 │   Result / Error    │

                 └──────────┬──────────┘

                            │

                            ▼

                 ┌─────────────────────┐

                 │       FOOTER        │

                 └─────────────────────┘

```



---



# Final Principle



**The individual tool page is a dynamic, theme-aware frontend shell for a registered tool. It renders inputs and outputs from configuration, sends execution requests through the central API, displays honest processing states, safely renders results, and relies entirely on server-side access control and validation. The active Favorite CMS theme remains responsible for Header, Footer, dark/light mode, and overall site identity.**




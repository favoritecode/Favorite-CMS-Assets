# Favorite Web Tools — Frontend Tool UI Specification



## 1. Purpose



This document defines the user-facing frontend interface for `favorite-web-tools`.



The frontend must provide a consistent experience across different tools while allowing each tool to define its own inputs, outputs, and engine behavior.



The frontend must remain separate from the Admin Panel.



---



# 2. Core Frontend Structure



The conceptual frontend structure is:



```text

Favorite Web Tools

│

├── Tools Home

├── Category

├── Tool Search

└── Tool Page

     ├── Tool Header

     ├── Input Area

     ├── Actions

     ├── Processing State

     ├── Result Area

     └── Error/Access State

```



The exact frontend routes and templates must follow the existing Favorite CMS architecture.



---



# 3. Tool Home



The main Web Tools page should provide a clear way to discover available tools.



Conceptually:



```text

Favorite Web Tools



\[ Search tools... ]



Categories

────────────────

Developer

HTML

CSS

JavaScript

PHP

Python

Text

...

```



Below the categories, active tools may be displayed as cards/list items.



---



# 4. Tool Cards



A tool card may contain:



* Tool name

* Short description

* Category

* Access indicator where useful

* Icon/thumbnail where available

* Open Tool action



Example:



```text

JSON Formatter

Format and beautify JSON data.



\[ Open Tool ]

```



The exact visual design should follow the existing Favorite CMS/theme conventions.



---



# 5. Tool URL



Each public tool should have a stable URL based on its slug.



Conceptually:



```text

/tools/{tool-slug}

```



Example:



```text

/tools/json-formatter

```



The actual route must follow the existing CMS routing conventions.



Do not create a separate router.



---



# 6. Tool Page



A tool page should contain:



```text

Tool Name

Tool Description



Input Area



\[ Process / Convert / Format ]



Processing State



Result Area

```



The action label may vary according to the tool.



---



# 7. Tool Header



The header should clearly display:



* Tool name

* Short description

* Category where useful

* Access information where useful



Avoid unnecessarily repeating technical engine information to normal users.



---



# 8. Input System



The frontend should render inputs based on the tool configuration.



Supported conceptual input types:



```text

Text

Textarea

Number

URL

File

Select

Checkbox

Radio

JSON

```



A tool may use one or multiple inputs.



---



# 9. Input Labels



Every input should have a clear label or understandable placeholder.



Example:



```text

JSON Input

\[ Paste JSON here... ]

```



Do not require users to understand internal field names.



---



# 10. Input Validation



Frontend validation may provide immediate feedback.



However:



```text

Frontend Validation

        ≠

Server-Side Validation

```



Server-side validation remains authoritative for server-side tool execution.



---



# 11. File Input



For tools that accept files, the UI should clearly show:



* Accepted file types

* File selection control

* Selected file information

* Upload/process state

* Errors



Example:



```text

Upload File

\[ Choose File ]



Supported: PDF, DOCX

```



The actual accepted formats are defined by the individual tool.



---



# 12. Multiple Inputs



Tools may have multiple inputs.



Example:



```text

Input File

Output Format

Quality

Options

```



The frontend should preserve a consistent layout while adapting to the tool configuration.



---



# 13. Primary Action



Each tool should have a clear primary action.



Examples:



```text

Format

Convert

Generate

Validate

Compress

Process

```



The action label should describe the actual tool operation.



---



# 14. Reset / Clear



Where appropriate, tools may provide:



```text

Clear

Reset

```



These actions should reset the current tool state without affecting unrelated application state.



---



# 15. Processing State



While a tool is processing, the UI should clearly communicate that work is in progress.



Example:



```text

Processing...

```



The UI should prevent accidental duplicate submissions when appropriate.



---



# 16. Python Processing State



Python-based tools may take longer than simple client-side tools.



The UI should therefore support a processing state such as:



```text

Uploading...

Processing...

Preparing result...

```



The exact stages should only be displayed when the backend can reliably determine them.



Do not show fake progress percentages.



---



# 17. Result Area



The result area should be rendered according to the tool's output type.



Possible result types:



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



# 18. Text Result



For text output, provide a readable result area.



Example:



```text

Result

────────────────

Formatted output...

────────────────

\[ Copy ]

```



---



# 19. JSON Result



JSON results should be displayed in a readable structured format where appropriate.



Possible actions:



```text

Copy

Download

```



The exact controls depend on the tool.



---



# 20. File Result



If the tool produces a file, the UI should provide an appropriate download action.



Example:



```text

Your file is ready.



\[ Download Result ]

```



Internal filesystem paths must never be displayed.



---



# 21. Media Result



For image/audio/video output, the frontend may provide an appropriate preview player.



Examples:



```text

Image Preview

Audio Player

Video Player

```



The tool configuration determines the output type.



---



# 22. Copy Action



For copyable text results, the UI may provide a Copy button.



After copying, the UI should provide a short confirmation.



Example:



```text

Copied

```



---



# 23. Download Action



For downloadable results, provide a clear download control.



The download mechanism must use the existing CMS/plugin architecture.



Do not expose internal storage paths.



---



# 24. Error State



Errors should be shown clearly without unnecessary technical information.



Example:



```text

Something went wrong.

Please try again.

```



Where useful, provide a more specific user-facing explanation.



---



# 25. Validation Error



Validation errors should identify what the user needs to correct.



Example:



```text

Please enter valid JSON.

```



Avoid generic errors when the system knows the actual input problem.



---



# 26. Access-Control UI



The frontend must reflect the three access modes.



### FREE



```text

\[ Use Tool ]

```



No login prompt is required.



---



### LOGIN\_REQUIRED



For an anonymous user:



```text

Login required to use this tool.



\[ Login ]

```



The login action must use the existing CMS authentication flow.



---



### MEMBERSHIP\_REQUIRED



For an anonymous user:



```text

Login and active membership required.



\[ Login ]

```



For a logged-in user without active membership:



```text

Active membership required to use this tool.



\[ Membership ]

```



The exact destination must use the existing CMS membership flow.



---



# 27. Access UI Is Not Security



Frontend access indicators are only UI.



For protected tools:



```text

Frontend

    ↓

Display access state



Backend

    ↓

Enforce access

```



The backend remains authoritative.



---



# 28. Tool Search



The frontend should support searching tools by:



* Tool name

* Slug

* Description where appropriate



Example:



```text

\[ Search tools... ]

```



Search should return active/public tools only unless the current user is an authorized administrator viewing admin data.



---



# 29. Category Browsing



Users should be able to browse tools by category.



Conceptually:



```text

Categories

├── Developer

├── HTML

├── CSS

├── JavaScript

├── PHP

├── Python

└── Text

```



The actual categories are managed through the Admin Panel.



---



# 30. Category Page



A category page may display:



```text

Category Name

Category Description



Tool Cards

Tool Cards

Tool Cards

```



Only active/public tools should be listed.



Access-restricted tools may still be discoverable if the product design allows it, but their access state must be clearly represented.



---



# 31. Access Labels



The frontend may use simple labels such as:



```text

Free

Login Required

Membership Required

```



These labels are informational.



Do not introduce a separate "Premium Category" label/system.



---



# 32. Membership Unlimited Message



Where appropriate, the frontend may communicate:



```text

Active membership includes unlimited use of membership-required tools.

```



Do not display usage counters because no usage-limit system exists.



---



# 33. Responsive Design



The frontend must work on:



* Desktop

* Laptop

* Tablet

* Mobile



The UI should adapt to smaller screens without requiring a separate mobile application.



---



# 34. Accessibility



The frontend should follow reasonable accessibility practices.



Examples:



* Proper labels

* Keyboard accessibility

* Visible focus state

* Understandable buttons

* Appropriate form semantics

* Useful error messages

* Sufficient text clarity



The implementation should reuse existing CMS/theme accessibility conventions where available.



---



# 35. Loading State



Tool loading states should be consistent.



Possible states:



```text

Idle

Processing

Success

Error

```



For file-based Python processing:



```text

Idle

Uploading

Processing

Result

Error

```



---



# 36. Prevent Duplicate Requests



Where appropriate, the frontend should prevent accidental repeated submissions while a request is already processing.



This is a UX feature only.



It must not be treated as a server-side request restriction.



---



# 37. Tool State



A tool page may maintain temporary UI state such as:



```text

Input

Processing

Result

Error

```



This state should remain isolated to the current tool page.



---



# 38. Client-Side Tools



For tools that can safely run entirely in the browser:



```text

User Input

   ↓

Browser Processing

   ↓

Result

```



This can reduce server requests and improve responsiveness.



The implementation should use client-side execution only when appropriate for the specific tool.



---



# 39. Server-Side Tools



For PHP or server-dependent tools:



```text

User Input

   ↓

Favorite CMS

   ↓

Access Control

   ↓

Engine

   ↓

Result

```



The frontend should communicate through the plugin's defined request/API interface.



---



# 40. Python Tools



For Python tools:



```text

User

 ↓

Tool UI

 ↓

Favorite Web Tools

 ↓

Access Control

 ↓

Python API Engine

 ↓

Python Service

 ↓

Result

```



The frontend must not communicate directly with private Python APIs when server-side authentication is required.



---



# 41. Browser Navigation



Tool pages should support normal browser navigation.



Where supported by the CMS/theme:



* Back

* Forward

* Refresh

* Direct tool URL access



Tool state should not break normal page navigation.



---



# 42. SEO



Public tools may provide basic SEO metadata:



* Page title

* Meta description

* Canonical URL where supported

* Structured content where appropriate



The implementation should reuse existing Favorite CMS SEO mechanisms.



Do not create a separate SEO framework.



---



# 43. Frontend Assets



Tool-specific CSS and JavaScript should remain inside the Web Tools plugin asset structure.



Assets should load only when needed.



Do not globally modify unrelated CMS/theme assets.



---



# 44. Theme Compatibility



The frontend should respect the existing Favorite CMS/theme architecture.



The plugin should avoid hardcoding assumptions about one specific theme unless the repository architecture requires it.



---



# 45. Frontend Error Boundary



A tool-specific frontend failure should not break the entire Favorite CMS page.



Where appropriate, JavaScript errors should be contained within the tool UI.



---



# 46. Frontend API Contract



Server-powered tools should communicate through a predictable plugin API/request contract.



Conceptually:



```text

POST /tool/execute

```



with a payload appropriate to the selected tool.



The actual endpoint, HTTP method, routing, CSRF behavior, and response format must follow the Favorite CMS architecture.



Do not create a second API framework.



---



# 47. Standard Frontend Response



The frontend should be able to handle a normalized result such as:



```text

{

    "success": true,

    "data": ...

}

```



or:



```text

{

    "success": false,

    "error": ...

}

```



The exact response schema must follow the final backend implementation.



---



# 48. Security Boundary



The frontend must never be treated as a trusted environment.



The implementation must assume:



* Users can inspect browser JavaScript.

* Users can inspect network requests.

* Users can modify frontend state.

* Users can send requests without using the visible UI.



Therefore protected functionality must be enforced by the backend where applicable.



Detailed security requirements will be defined in a separate specification.



---



# 49. Admin vs Public UI



The public frontend must not expose administrative controls.



For example:



```text

Public User

→ Use Tool



Administrator

→ Manage Tool

→ Configure Tool

→ Test Tool

```



These responsibilities must remain separate.



---



# 50. AI Agent Implementation Rules



The implementation agent must:



1. Inspect existing CMS/theme frontend conventions.

2. Reuse existing routing.

3. Reuse existing templates/views.

4. Reuse existing asset loading.

5. Reuse existing authentication UI.

6. Reuse existing membership UI/flow.

7. Build a consistent tool interface.

8. Support configurable input types.

9. Support configurable output types.

10. Provide clear loading states.

11. Provide clear validation errors.

12. Provide clear execution errors.

13. Support responsive layouts.

14. Keep frontend access indicators separate from backend authorization.

15. Avoid exposing credentials.

16. Avoid exposing internal filesystem paths.

17. Avoid direct private Python API calls from the browser.

18. Avoid duplicate API/router frameworks.

19. Do not introduce usage counters or quotas.

20. Do not introduce premium categories.

21. Do not modify CMS core unnecessarily.



---



# 51. Acceptance Criteria



The frontend system is correctly implemented when:



* Users can discover active tools.

* Users can search tools.

* Users can browse categories.

* Each tool has a stable public page.

* Tool inputs render according to configuration.

* Tool actions are clear.

* Processing states are visible.

* Results render according to output type.

* Copy/download actions work where applicable.

* Validation errors are understandable.

* Execution errors are understandable.

* Free tools work for anonymous users.

* Login-required tools correctly display login state.

* Membership-required tools correctly display membership state.

* Active members are not shown usage-limit counters.

* Server-side access remains authoritative.

* Python tools use the server-side Python API architecture.

* Responsive behavior works across desktop and mobile.

* Existing CMS/theme conventions are reused.

* CMS core remains untouched.



---



# 52. Final Frontend Model



```text

                    Tools Home

                        │

              ┌─────────┴─────────┐

              │                   │

          Categories            Search

              │                   │

              └─────────┬─────────┘

                        ↓

                   Tool Page

                        │

              ┌─────────┴─────────┐

              ↓                   ↓

          Input Area          Access State

              │                   │

              └─────────┬─────────┘

                        ↓

                   Tool Execute

                        │

                  Processing State

                        │

                        ↓

                   Result Area

                        │

              ┌─────────┼─────────┐

              ↓         ↓         ↓

            Text      File      Media

```



This frontend architecture is the source of truth for the public Favorite Web Tools user interface.




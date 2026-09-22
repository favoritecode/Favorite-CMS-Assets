# Favorite Web Tools — Tool Input \& Output System



## 1. Purpose



This document defines the standardized Input and Output System for the `favorite-web-tools` plugin.



The system must allow different tools to define their own:



* input fields

* validation rules

* processing requirements

* output types

* result presentation requirements



while maintaining one consistent architecture across all tools.



The system must work with:



* HTML tools

* CSS tools

* JavaScript tools

* PHP tools

* Python API tools



---



## 2. Core Principle



A tool must not require custom frontend/backend input handling for every individual field type.



Instead, the Tool Registry should describe the tool's input/output structure, and the platform should interpret that configuration.



Conceptually:



```text

Tool Configuration

        ↓

Input Schema

        ↓

Frontend Form

        ↓

User Input

        ↓

Server Validation

        ↓

Engine

        ↓

Output Schema

        ↓

Frontend Result UI

```



---



## 3. Input System



Every tool may define zero or more input fields.



A tool's input configuration should describe:



* field identifier

* label

* description/help text

* input type

* required/optional state

* default value

* validation rules

* placeholder

* selectable options where applicable

* accepted file types where applicable



The exact storage structure must follow the repository's implementation conventions.



---



## 4. Supported Input Types



The initial input system should support:



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



The architecture should remain extensible for future input types.



---



## 5. Text Input



`TEXT` is intended for short text values.



Examples:



* search term

* name

* hash input

* UUID namespace

* short configuration value



Conceptual configuration:



```json

{

  "type": "TEXT",

  "name": "text",

  "label": "Text",

  "required": true

}

```



---



## 6. Textarea Input



`TEXTAREA` is intended for larger text.



Examples:



* HTML source

* CSS source

* JavaScript source

* plain text

* Markdown

* long JSON strings



The UI should provide an appropriate editable area.



For code-oriented tools, the implementation may later integrate an existing code editor if the CMS/plugin architecture supports it.



Do not add a large editor framework unnecessarily during the initial implementation.



---



## 7. Number Input



`NUMBER` supports numeric values.



Validation may include:



* minimum

* maximum

* integer requirement

* decimal allowance



Example:



```json

{

  "type": "NUMBER",

  "name": "indent",

  "label": "Indent Size",

  "required": true,

  "default": 2

}

```



---



## 8. URL Input



`URL` is intended for URLs supplied by users.



The backend must validate the value as a URL according to the tool's requirements.



A valid URL format alone does not guarantee that the remote resource is safe or reachable.



Tools that fetch remote URLs must apply additional server-side validation appropriate to the operation.



---



## 9. File Input



`FILE` supports tools that process uploaded files.



The configuration may define:



* accepted extensions

* accepted MIME types

* maximum file size

* required/optional state

* single/multiple file behavior where supported



Example:



```json

{

  "type": "FILE",

  "name": "file",

  "label": "Upload File",

  "required": true

}

```



Uploaded files must be treated as untrusted data.



They must never automatically become executable server-side code.



---



## 10. Select Input



`SELECT` allows one option to be selected from a predefined set.



Example:



```json

{

  "type": "SELECT",

  "name": "format",

  "label": "Format",

  "options": \[

    {

      "value": "beautify",

      "label": "Beautify"

    },

    {

      "value": "minify",

      "label": "Minify"

    }

  ]

}

```



The backend must validate that the submitted value exists in the configured options.



Do not trust the browser's available option list.



---



## 11. Checkbox Input



`CHECKBOX` represents a boolean option.



Example:



```json

{

  "type": "CHECKBOX",

  "name": "remove\_comments",

  "label": "Remove Comments",

  "default": true

}

```



The backend must normalize the received value into the expected boolean representation.



---



## 12. Radio Input



`RADIO` allows one option from a small predefined set.



The backend must validate the submitted value against the configured options.



---



## 13. JSON Input



`JSON` is intended for structured JSON data.



The backend must validate:



* valid JSON syntax

* expected structure where required

* allowed data types where required



The system should preserve JSON as structured data instead of unnecessarily converting it into a string multiple times.



---



## 14. Multiple Inputs



A tool may define multiple input fields.



Example:



```text

HTML Input

CSS Input

JavaScript Input

Mode

Options

```



The frontend should render them in the configured order.



The backend should validate each field independently before execution.



---



## 15. Input Ordering



Tool configuration may define an explicit field order.



Example:



```text

1\. Input Text

2\. Format

3\. Indent Size

4\. Remove Comments

```



The frontend must respect this ordering.



The backend must not rely on the order submitted by the browser.



---



## 16. Required Fields



Each field may be:



```text

required

optional

```



Required fields must be rejected when missing or empty according to the field's semantics.



Optional fields may use configured defaults.



Server-side validation remains authoritative.



---



## 17. Default Values



Tool configuration may define default values.



Defaults should be applied consistently.



The backend must not blindly trust frontend-supplied defaults.



If a field has a server-defined default, the backend should apply it when the field is absent and the tool permits omission.



---



## 18. Placeholder and Help Text



Input fields may include:



* placeholder

* short description

* help text



These are presentation information.



They must not be treated as validation or security rules.



---



## 19. Input Validation Model



Validation should happen at multiple levels.



### Level 1 — Frontend Validation



Used for:



* immediate feedback

* required fields

* obvious format errors

* better UX



### Level 2 — Backend Validation



Used for:



* authoritative validation

* access to server-side rules

* file validation

* security-sensitive validation

* engine requirements



### Level 3 — Engine Validation



Used for:



* tool-specific processing requirements

* syntax validation

* engine-specific constraints



Conceptually:



```text

Frontend Validation

        ↓

Backend Validation

        ↓

Engine Validation

```



---



## 20. Validation Errors



Validation errors should identify the affected field where possible.



Conceptual response:



```json

{

  "success": false,

  "error": {

    "code": "INVALID\_INPUT",

    "fields": {

      "json": "Invalid JSON."

    }

  }

}

```



The exact response structure must follow the API conventions defined in:



```text

10-TOOL-EXECUTION-API.md

```



---



## 21. Input Sanitization



Sanitization must be appropriate to the input and processing context.



The system must not blindly sanitize code input in a way that changes the user's intended source.



For example, HTML formatter/minifier tools may need to process raw HTML as data.



Security must instead be enforced at the point where content is:



* rendered

* stored

* executed

* inserted into a privileged context



---



## 22. HTML Preview Safety



If a tool provides an HTML preview, user-provided HTML must not automatically execute dangerous content inside the Favorite CMS page.



The preview should use an appropriately isolated rendering mechanism where required, such as a sandboxed iframe or another safe architecture.



The exact implementation must follow the repository and browser-security requirements.



---



## 23. CSS Preview Safety



CSS preview content must remain isolated from the Favorite CMS interface.



User CSS must not accidentally modify:



* CMS navigation

* admin interface

* global page styles

* plugin management UI

* unrelated application elements



A preview should be isolated in an appropriate container or document context.



---



## 24. JavaScript Preview Safety



JavaScript source submitted to a tool must not automatically execute with Favorite CMS privileges.



If a future tool explicitly requires browser-side JavaScript execution, it must use an isolated execution environment appropriate for the intended feature.



The initial system must not treat:



```text

"JavaScript input"

```



as permission to execute arbitrary scripts in the application context.



---



## 25. File Validation



For uploaded files, validation should consider:



* extension

* MIME type where appropriate

* file size

* upload error state

* expected format

* tool-specific requirements



File validation must happen server-side.



Do not trust only:



```text

filename extension

```



or:



```text

Content-Type

```



supplied by the browser.



---



## 26. Output System



Every tool execution should produce a normalized result.



The output configuration determines how the frontend should display the result.



Supported initial output types:



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



## 27. Text Output



`TEXT` is intended for:



* formatted text

* generated text

* validation messages

* encoded/decoded values

* hashes

* converted values



The frontend should provide appropriate actions such as:



* copy

* select

* clear



where applicable.



---



## 28. HTML Output



`HTML` represents HTML content intended for display or preview.



The frontend must distinguish between:



```text

HTML shown as source code

```



and:



```text

HTML rendered as a preview

```



Raw HTML must not be injected into the main application DOM without an appropriate security boundary.



---



## 29. JSON Output



`JSON` should remain structured JSON where possible.



The frontend may display:



* formatted JSON

* collapsible JSON

* raw JSON

* copy action



depending on the tool.



The backend should return valid structured JSON when the output type is JSON.



---



## 30. File Output



`FILE` represents a generated or processed file.



Examples:



* converted file

* formatted file

* generated document

* processed archive where explicitly supported



The backend should use the existing CMS/plugin file handling mechanism.



The frontend should receive a controlled download reference.



---



## 31. Image Output



`IMAGE` represents an image result.



The result may use:



* controlled URL

* file reference

* appropriate binary response mechanism



The implementation should avoid unnecessarily embedding large image data directly into JSON.



---



## 32. Audio Output



`AUDIO` represents an audio result.



The frontend may provide:



* audio player

* download action



where appropriate.



---



## 33. Video Output



`VIDEO` represents a video result.



The frontend may provide:



* video player

* fullscreen

* download where explicitly supported



The result must use a controlled file/reference mechanism where appropriate.



---



## 34. Download Output



`DOWNLOAD` indicates that the main result is intended to be downloaded rather than displayed inline.



The response should provide a safe, controlled download reference.



The download endpoint must not expose arbitrary filesystem paths.



---



## 35. Output Metadata



Where useful, a result may include metadata.



Example:



```json

{

  "success": true,

  "data": {

    "type": "FILE",

    "name": "result.html",

    "mime": "text/html",

    "size": 12345,

    "download\_url": "..."

  }

}

```



The exact representation must follow the existing CMS/API conventions.



---



## 36. Multiple Outputs



A tool may eventually produce multiple output items.



Example:



```text

Processed Code

Validation Report

Statistics

Downloadable File

```



The output system should be extensible enough to support multiple results without breaking the basic single-result model.



The initial implementation does not need a complex multi-output framework unless an actual tool requires it.



---



## 37. Output Actions



Depending on output type, the frontend may support:



```text

Copy

Download

Open

Preview

Clear

```



Only actions appropriate to the output type should be displayed.



---



## 38. Output Security



Output must be treated according to its type.



Examples:



* text should be escaped when rendered as HTML

* HTML previews require isolation

* file downloads require controlled references

* URLs must not expose internal filesystem paths

* generated files must not become executable unintentionally



The browser must not receive internal server paths.



---



## 39. Input/Output Separation



The input schema and output schema should remain separate.



Conceptually:



```text

Tool

 ├── Input Schema

 ├── Execution Configuration

 └── Output Schema

```



This allows one engine to accept multiple input formats and produce different output types.



---



## 40. Engine Compatibility



The Input/Output System must not contain engine-specific assumptions unnecessarily.



Example:



```text

HTML Tool

 → TEXTAREA

 → TEXT



CSS Tool

 → TEXTAREA

 → TEXT



PHP Tool

 → JSON

 → JSON



Python Tool

 → FILE + OPTIONS

 → FILE

```



The engine determines how the validated input is processed.



The output system determines how the normalized result is presented.



---



## 41. Configuration Storage



Input/output configuration may be stored as structured tool configuration.



Where the repository supports JSON configuration, use structured JSON rather than arbitrary serialized executable content.



Do not store executable code inside the input/output schema.



---



## 42. No Arbitrary Execution Configuration



Input fields must never be interpreted as:



* PHP source

* shell commands

* Python source

* unrestricted server commands

* arbitrary executable configuration



The schema defines data.



The engine defines controlled processing.



---



## 43. File Size and Processing Constraints



Tools may need technical processing limits such as:



* maximum upload size

* maximum input size

* maximum generated output size

* request timeout



These are technical safeguards, not user usage quotas.



They must not become:



* daily limits

* monthly limits

* credits

* token systems

* usage counters



---



## 44. Large Text Input



Large text inputs should be handled efficiently.



The frontend should avoid unnecessary duplication of very large strings.



The backend should validate the input size before expensive processing.



Tools that require very large input may use a file-based workflow instead.



---



## 45. Tool-Specific Schema



A tool should be able to define only the fields it actually needs.



Example:



```text

JSON Formatter

 └── JSON Input



CSS Minifier

 └── CSS Input



URL Encoder

 └── URL/Text Input



Image Processor

 ├── Image File

 └── Processing Options

```



Do not force every tool to use every possible input type.



---



## 46. Schema Validation



Before a tool becomes `ACTIVE`, its input/output configuration should be validated.



Activation should fail if:



* input type is unsupported

* required configuration is missing

* select/radio options are invalid

* output type is unsupported

* engine configuration is incomplete

* Python service reference is invalid where required



---



## 47. Admin UI Integration



The Admin Panel should allow administrators to configure tool inputs and outputs using structured fields.



The UI should provide:



* add input

* remove input

* reorder inputs

* edit labels

* configure required state

* configure defaults

* configure validation

* configure output type

* configure output metadata



The exact UI should follow the existing Favorite CMS admin architecture.



---



## 48. Frontend Rendering



The frontend Tool UI should dynamically render configured inputs.



Conceptually:



```text

Tool Configuration

       ↓

Input Renderer

       ↓

Field 1

Field 2

Field 3

       ↓

Execute

```



This reduces the need to create separate frontend templates for every tool.



---



## 49. Accessibility



Input/output components must support:



* visible labels

* keyboard navigation

* focus states

* accessible error messages

* appropriate semantic controls

* screen-reader-friendly status messages



Do not rely only on color to communicate validation or processing state.



---



## 50. Error State Rendering



The frontend should distinguish:



```text

Validation Error

Access Error

Processing Error

External Service Error

System Error

```



Where the API provides field-level errors, they should be displayed near the relevant field.



General errors should appear in the tool's result/error area.



---



## 51. Loading and Processing State



During execution, the UI may show:



```text

Processing...

Uploading...

Preparing...

Waiting for service...

```



For Python tools, the UI may display a processing state appropriate to the actual operation.



Do not display fake percentage progress.



Only show real progress when the backend provides reliable progress information.



---



## 52. Reset Behavior



The frontend should provide a reset/clear mechanism where useful.



Reset should:



* clear user inputs

* restore configured defaults where appropriate

* clear previous results

* clear validation errors



Reset must not affect:



* tool configuration

* access mode

* membership

* server-side settings



---



## 53. Browser-Side vs Server-Side Input



A tool may process input entirely in the browser or send it to the backend.



The tool configuration should determine the execution model.



Examples:



```text

Browser-side:

Simple text transformation



Server-side:

PHP processing



Python API:

External AI/media/data processing

```



The frontend must not expose server-side secrets.



---



## 54. Standard Internal Model



Conceptually, the platform should work with:



```text

Tool

 ├── metadata

 ├── category

 ├── access

 ├── engine

 ├── inputs\[]

 ├── execution configuration

 └── outputs\[]

```



This model should be implemented according to the actual Favorite CMS repository conventions.



---



## 55. Repository Compatibility



Before implementing the Input/Output System, the AI agent must inspect:



* existing form components

* validation helpers

* request parsing

* file upload utilities

* response helpers

* admin form patterns

* frontend component conventions

* existing JSON/schema utilities



If suitable CMS functionality already exists, reuse it.



Do not create duplicate systems unnecessarily.



---



## 56. Implementation Rules for AI Agent



The AI agent must:



1. Read this specification.

2. Inspect the actual repository before coding.

3. Reuse existing CMS form/validation/file/response systems.

4. Implement standardized input definitions.

5. Implement standardized output definitions.

6. Support the initial input types.

7. Support the initial output types.

8. Validate inputs server-side.

9. Keep frontend validation as UX only.

10. Treat uploaded files as untrusted.

11. Isolate HTML/CSS/JavaScript previews appropriately.

12. Never execute arbitrary user PHP/JavaScript/Python.

13. Keep executable logic outside input/output schemas.

14. Keep Python credentials server-side.

15. Avoid unnecessary large framework dependencies.

16. Keep configuration structured.

17. Preserve future extensibility.

18. Keep implementation isolated inside the plugin.

19. Do not introduce usage quotas or credits.

20. Report repository conflicts before changing architecture.



---



## 57. Acceptance Criteria



This specification is complete when:



* Tools can define structured input fields.

* All initial input types are supported.

* Multiple inputs are supported.

* Required/optional fields work.

* Defaults work.

* Select/radio options are validated server-side.

* Number validation works.

* URL validation works.

* JSON validation works.

* File validation works.

* Frontend and backend validation are separated correctly.

* Output types are standardized.

* Text output works.

* HTML output works safely.

* JSON output works.

* File output works.

* Image output works.

* Audio output works.

* Video output works.

* Download output works.

* Large/binary results can use controlled file references.

* Output actions are type-appropriate.

* HTML/CSS/JavaScript previews are isolated appropriately.

* Arbitrary code execution is not introduced.

* Tool-specific schemas can be configured without creating custom systems for every tool.

* Admin configuration follows the existing CMS architecture.

* Frontend rendering can use the configured input/output schema.

* The system remains extensible for future input/output types.

* No usage limits, credits, tokens, quotas, or counters are introduced.

* Implementation remains isolated within `favorite-web-tools`.




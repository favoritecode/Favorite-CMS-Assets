# 30 — DEFAULT TOOL CATALOG

## 1. Purpose

This document defines the default tool catalog for the first release of the `favorite-web-tools` plugin.

The catalog provides the initial set of tools, their categories, engines, input/output expectations, and default access modes.

This document is the default seed/catalog specification.

The Tool Registry and Admin Dynamic Builder remain the authoritative systems for actual runtime configuration.

---

# 2. Core Principle

The catalog must be data-driven.

Do not hard-code individual tools into:

* Router.
* Frontend page.
* Execution API.
* Engine resolver.
* Authentication system.
* Theme.
* Admin framework.

Each tool must be represented through the central Tool Registry.

```text
Default Catalog
      ↓
Tool Registry
      ↓
Tool Configuration
      ↓
Tool Page
      ↓
Execution Engine
```

---

# 3. First Release Scope

The first release should focus on practical developer and text utility tools.

Initial categories:

```text
HTML
CSS
JavaScript
Developer
Text
PHP
Python
```

The catalog must remain extensible.

---

# 4. Access Mode Defaults

Unless explicitly specified otherwise, the initial tools should use:

```text
FREE
```

This allows the initial public catalog to be immediately usable.

Tools requiring authentication or membership may be configured later through the Admin Panel.

Supported access modes remain exactly:

```text
FREE
LOGIN_REQUIRED
MEMBERSHIP_REQUIRED
```

---

# 5. Usage Policy

No catalog tool may introduce:

* Daily limits.
* Monthly limits.
* Hourly limits.
* Usage counters.
* Credits.
* Tokens.
* Quotas.
* Remaining-use indicators.
* Premium usage tiers.

Active membership means unlimited access to membership-required tools.

---

# 6. Tool Status

Default seeded tools should normally be created as:

```text
ACTIVE
```

provided that their implementation and configuration are available and validated.

If an implementation is not ready, the tool must not be falsely activated.

It may instead be created as:

```text
DRAFT
```

---

# 7. Naming Convention

Each tool should have:

* Human-readable name.
* Stable slug.
* Short description.
* Category.
* Engine.
* Access mode.
* Input schema.
* Output schema.
* Tool-specific configuration.

Slugs must be lowercase and URL-safe.

Examples:

```text
html-formatter
css-minifier
json-validator
base64-encoder
```

---

# 8. HTML CATEGORY

The HTML category contains tools focused on HTML source processing and utilities.

---

## 8.1 HTML Formatter

```text
Name:
HTML Formatter

Slug:
html-formatter

Category:
HTML

Engine:
HTML

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Format and beautify HTML source code.

Expected behavior:

* Improve indentation.
* Improve readability.
* Preserve valid HTML structure.
* Return formatted source.

---

## 8.2 HTML Minifier

```text
Name:
HTML Minifier

Slug:
html-minifier

Category:
HTML

Engine:
HTML

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Reduce unnecessary whitespace and formatting in HTML source where safe.

---

## 8.3 HTML Validator

```text
Name:
HTML Validator

Slug:
html-validator

Category:
HTML

Engine:
HTML

Access:
FREE

Input:
TEXTAREA

Output:
JSON
```

Purpose:

Validate HTML and provide structured validation information.

Possible output:

```text
Valid
Errors
Warnings
Messages
```

---

## 8.4 HTML Encoder

```text
Name:
HTML Encoder

Slug:
html-encoder

Category:
HTML

Engine:
HTML

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Encode text into HTML entities.

---

## 8.5 HTML Decoder

```text
Name:
HTML Decoder

Slug:
html-decoder

Category:
HTML

Engine:
HTML

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Decode HTML entities into readable text.

---

## 8.6 HTML Preview

```text
Name:
HTML Preview

Slug:
html-preview

Category:
HTML

Engine:
HTML

Access:
FREE

Input:
TEXTAREA

Output:
HTML
```

Purpose:

Provide an isolated preview of HTML.

Security requirement:

The preview must be isolated from the main CMS page.

---

# 9. CSS CATEGORY

The CSS category contains CSS processing and utility tools.

---

## 9.1 CSS Formatter

```text
Name:
CSS Formatter

Slug:
css-formatter

Category:
CSS

Engine:
CSS

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Beautify and format CSS source.

---

## 9.2 CSS Minifier

```text
Name:
CSS Minifier

Slug:
css-minifier

Category:
CSS

Engine:
CSS

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Reduce unnecessary CSS whitespace and formatting.

---

## 9.3 CSS Validator

```text
Name:
CSS Validator

Slug:
css-validator

Category:
CSS

Engine:
CSS

Access:
FREE

Input:
TEXTAREA

Output:
JSON
```

Purpose:

Validate CSS and return structured validation information.

---

## 9.4 CSS Prefixer

```text
Name:
CSS Prefixer

Slug:
css-prefixer

Category:
CSS

Engine:
CSS

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Generate required vendor-prefixed CSS where supported by the selected implementation.

The exact prefixing behavior must be based on the actual processing library/implementation selected during development.

---

## 9.5 CSS Color Converter

```text
Name:
CSS Color Converter

Slug:
css-color-converter

Category:
CSS

Engine:
CSS

Access:
FREE

Input:
TEXT

Output:
TEXT
```

Purpose:

Convert common CSS color representations.

Examples:

```text
HEX
RGB
RGBA
HSL
HSLA
```

The exact supported formats should be defined by the implementation.

---

## 9.6 CSS Preview

```text
Name:
CSS Preview

Slug:
css-preview

Category:
CSS

Engine:
CSS

Access:
FREE

Input:
TEXTAREA

Output:
HTML
```

Purpose:

Preview CSS in an isolated environment.

CSS must never leak into the CMS page.

---

# 10. JAVASCRIPT CATEGORY

The JavaScript category focuses primarily on JavaScript source processing.

---

## 10.1 JavaScript Formatter

```text
Name:
JavaScript Formatter

Slug:
javascript-formatter

Category:
JavaScript

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Beautify JavaScript source code.

---

## 10.2 JavaScript Minifier

```text
Name:
JavaScript Minifier

Slug:
javascript-minifier

Category:
JavaScript

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Minify JavaScript source code.

---

## 10.3 JavaScript Validator

```text
Name:
JavaScript Validator

Slug:
javascript-validator

Category:
JavaScript

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXTAREA

Output:
JSON
```

Purpose:

Validate JavaScript syntax and return structured results.

---

# 11. DEVELOPER CATEGORY

The Developer category contains general-purpose developer utilities.

---

## 11.1 JSON Formatter

```text
Name:
JSON Formatter

Slug:
json-formatter

Category:
Developer

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Beautify JSON for readability.

---

## 11.2 JSON Validator

```text
Name:
JSON Validator

Slug:
json-validator

Category:
Developer

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXTAREA

Output:
JSON
```

Purpose:

Validate JSON syntax and provide structured results.

---

## 11.3 JSON Minifier

```text
Name:
JSON Minifier

Slug:
json-minifier

Category:
Developer

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Remove unnecessary JSON formatting while preserving valid JSON.

---

## 11.4 JSON Beautifier

```text
Name:
JSON Beautifier

Slug:
json-beautifier

Category:
Developer

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Provide readable formatted JSON.

If JSON Formatter already provides the same functionality, this tool may be omitted to avoid unnecessary duplication.

---

## 11.5 Base64 Encoder

```text
Name:
Base64 Encoder

Slug:
base64-encoder

Category:
Developer

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Encode text/data into Base64 where supported.

Unicode behavior must be handled correctly.

---

## 11.6 Base64 Decoder

```text
Name:
Base64 Decoder

Slug:
base64-decoder

Category:
Developer

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Decode Base64 input.

Invalid Base64 must return a controlled validation error.

---

## 11.7 URL Encoder

```text
Name:
URL Encoder

Slug:
url-encoder

Category:
Developer

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Encode text for URL usage.

---

## 11.8 URL Decoder

```text
Name:
URL Decoder

Slug:
url-decoder

Category:
Developer

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Decode URL-encoded text.

---

## 11.9 UUID Generator

```text
Name:
UUID Generator

Slug:
uuid-generator

Category:
Developer

Engine:
JAVASCRIPT

Access:
FREE

Input:
NONE

Output:
TEXT
```

Purpose:

Generate UUID values using a secure/appropriate implementation.

---

## 11.10 Hash Generator

```text
Name:
Hash Generator

Slug:
hash-generator

Category:
Developer

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXTAREA
SELECT

Output:
TEXT
```

Possible algorithms depend on implementation.

Examples may include:

```text
SHA-256
SHA-384
SHA-512
```

Do not advertise algorithms that the actual implementation does not support.

---

## 11.11 Timestamp Converter

```text
Name:
Timestamp Converter

Slug:
timestamp-converter

Category:
Developer

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXT

Output:
TEXT
```

Purpose:

Convert timestamps between supported formats.

The exact supported timestamp formats must be defined during implementation.

---

## 11.12 Regex Tester

```text
Name:
Regex Tester

Slug:
regex-tester

Category:
Developer

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXT
TEXTAREA
SELECT

Output:
JSON
```

Possible inputs:

```text
Pattern
Test Text
Flags
```

Security:

Regex processing must have appropriate technical protection against pathological patterns where applicable.

This is an infrastructure/resource concern, not a user quota.

---

## 11.13 Lorem Ipsum Generator

```text
Name:
Lorem Ipsum Generator

Slug:
lorem-ipsum-generator

Category:
Developer

Engine:
JAVASCRIPT

Access:
FREE

Input:
NUMBER
SELECT

Output:
TEXT
```

Possible configuration:

```text
Paragraphs
Words
Format
```

The implementation must define safe technical maximums if necessary.

These are not product usage limits.

---

# 12. TEXT CATEGORY

The Text category contains general text utilities.

---

## 12.1 Text Case Converter

```text
Name:
Text Case Converter

Slug:
text-case-converter

Category:
Text

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXTAREA
SELECT

Output:
TEXT
```

Possible modes:

```text
UPPERCASE
lowercase
Title Case
Sentence case
```

---

## 12.2 Text Counter

```text
Name:
Text Counter

Slug:
text-counter

Category:
Text

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXTAREA

Output:
JSON
```

Possible results:

```text
Characters
Characters Without Spaces
Words
Lines
Paragraphs
```

---

## 12.3 Remove Duplicate Lines

```text
Name:
Remove Duplicate Lines

Slug:
remove-duplicate-lines

Category:
Text

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Remove duplicate lines while preserving the configured order behavior.

---

## 12.4 Sort Lines

```text
Name:
Sort Lines

Slug:
sort-lines

Category:
Text

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXTAREA
SELECT

Output:
TEXT
```

Possible modes:

```text
A → Z
Z → A
Numeric
```

---

## 12.5 Find and Replace

```text
Name:
Find and Replace

Slug:
find-and-replace

Category:
Text

Engine:
JAVASCRIPT

Access:
FREE

Input:
TEXTAREA
TEXT
TEXT

Output:
TEXT
```

Possible fields:

```text
Text
Find
Replace
```

Optional advanced settings may be added later.

---

# 13. PHP CATEGORY

The PHP category contains PHP source-processing tools.

PHP tools must never execute arbitrary PHP source supplied by users.

---

## 13.1 PHP Formatter

```text
Name:
PHP Formatter

Slug:
php-formatter

Category:
PHP

Engine:
PHP

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Format PHP source code.

The implementation should use a controlled parser/formatter library or safe processing logic.

---

## 13.2 PHP Validator

```text
Name:
PHP Validator

Slug:
php-validator

Category:
PHP

Engine:
PHP

Access:
FREE

Input:
TEXTAREA

Output:
JSON
```

Purpose:

Validate PHP syntax without executing the submitted PHP program.

---

## 13.3 PHP Minifier

```text
Name:
PHP Minifier

Slug:
php-minifier

Category:
PHP

Engine:
PHP

Access:
FREE

Input:
TEXTAREA

Output:
TEXT
```

Purpose:

Perform controlled PHP source minification where supported.

The implementation must not require execution of user-supplied PHP.

---

# 14. PYTHON CATEGORY

The Python category is reserved for Python-powered tools.

Python tools use the external Python API Service architecture.

They do not execute arbitrary Python source directly inside Favorite CMS.

---

## 14.1 Python API Test Tool

```text
Name:
Python Service Test

Slug:
python-service-test

Category:
Python

Engine:
PYTHON_API

Access:
LOGIN_REQUIRED

Input:
TEXTAREA

Output:
JSON
```

Purpose:

Internal/admin-oriented validation of configured Python service communication.

This tool should not necessarily be exposed as a normal public tool.

If the existing admin architecture provides a dedicated service-test interface, prefer that instead of exposing this as a public catalog tool.

---

# 15. Future Python Tools

The Python category is intentionally extensible.

Potential future tools may include:

```text
Image Processing
Audio Processing
Video Processing
OCR
AI Text Processing
Document Processing
Data Processing
```

These should be added only after the corresponding Python API service is configured and tested.

Do not seed placeholder public tools that do not have a working backend.

---

# 16. Tools That Should NOT Be Default Seeded

The following should not be added to the default catalog merely because they may be useful later:

```text
AI Chat
AI Image Generator
Web Scraper
Website Screenshot
Video Downloader
Social Media Downloader
Email Sender
SMS Sender
Cloud Storage Manager
Payment Processor
SMM Panel Tool
Fraud Checker
Arbitrary API Caller
Arbitrary PHP Executor
Arbitrary Python Executor
Shell Executor
Command Prompt Executor
PowerShell Executor
```

Reasons may include:

* Separate plugin responsibility.
* Security complexity.
* External service dependency.
* Need for additional product requirements.
* Need for dedicated API integration.
* Potentially sensitive operations.

The catalog must remain focused.

---

# 17. Favorite API Connector Boundary

`Favorite API Connector` remains a separate plugin.

The Web Tools plugin must not become a generic API gateway.

If a future tool needs an external API:

```text
Favorite Web Tools
        ↓
Defined Integration Interface
        ↓
Favorite API Connector
        ↓
External API
```

The exact integration must follow the actual Favorite API Connector architecture.

---

# 18. Python Service Boundary

Python-powered tools must use:

```text
Favorite Web Tools
        ↓
Python API Engine
        ↓
Python Service Registry
        ↓
Configured Python API
```

The tool configuration must not contain arbitrary Python source code.

---

# 19. Tool Duplication Rule

Avoid creating multiple tools that perform exactly the same operation unless there is a clear user-facing distinction.

Example:

If:

```text
JSON Formatter
```

already provides beautification, a separate:

```text
JSON Beautifier
```

is optional and may be omitted.

The final seeded catalog should prioritize useful distinct tools rather than artificial tool-count inflation.

---

# 20. Category Independence

Categories do not determine:

* Access.
* Membership.
* Pricing.
* Usage limits.
* Credits.
* Tokens.

A tool's access mode is configured independently.

---

# 21. Engine Independence

A category does not automatically determine the engine.

For example:

```text
Developer
```

may contain:

```text
JAVASCRIPT
PHP
PYTHON_API
```

depending on the implementation.

---

# 22. Catalog Data Model

Each seeded tool should map to the Tool Registry structure.

Conceptually:

```text
Tool
├── name
├── slug
├── description
├── category
├── engine
├── access_mode
├── status
├── input_schema
├── output_schema
└── engine_configuration
```

---

# 23. Default Tool Configuration

Each default tool must have a complete valid configuration before activation.

Required areas:

```text
Identity
Category
Engine
Access
Status
Inputs
Outputs
Engine Configuration
Frontend Configuration
```

---

# 24. Seed Behavior

Default catalog seeding must be idempotent.

Running the seed process multiple times must not create duplicate tools.

---

# 25. Existing Tool Protection

If an administrator has modified an existing tool configuration, running an upgrade/seed process must not silently overwrite those changes.

Default seed data should establish missing tools/configuration only according to the migration/seed strategy defined in the database specification.

---

# 26. Slug Stability

Once a tool is publicly released, its slug should remain stable.

Do not automatically change:

```text
/tools/html-formatter
```

to another URL during routine updates.

If a slug must change, a controlled migration/redirect strategy should be used.

---

# 27. Tool Descriptions

Descriptions should be concise and user-oriented.

Avoid exposing:

* Internal implementation.
* Server paths.
* API credentials.
* Python service addresses.
* Internal handler names.
* Security-sensitive configuration.

---

# 28. Tool Icons and Thumbnails

Icons/thumbnails may be configured later through the Admin Panel.

The default catalog should not require a specific icon library unless the CMS/theme already provides one.

---

# 29. Search Compatibility

Every active default tool must be discoverable through:

* Tool name.
* Slug.
* Description.
* Category.

The catalog must integrate with the central Search and Discovery system.

---

# 30. Frontend Compatibility

Every default public tool must be compatible with the dynamic Tool Page renderer.

The frontend must not require a separate page template for each tool unless a genuinely unique UI is required.

---

# 31. Output Compatibility

Every default tool must return one of the standard output types:

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

No custom output type should be introduced merely for one tool.

---

# 32. Input Compatibility

Every default tool must use the standard input system.

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

# 33. Technical Resource Protection

Individual tools may require technical safeguards such as:

* Maximum request payload.
* Maximum temporary file size.
* Processing timeout.
* Regex execution protection.
* Memory-safe processing.
* Output size protection.

These exist for infrastructure reliability and security.

They must not be presented as product usage quotas.

---

# 34. No Usage Analytics Requirement

The default catalog does not require:

* Per-tool usage counters.
* User usage statistics.
* Daily usage charts.
* Monthly usage charts.
* Credit consumption.
* Token consumption.

Technical logs may still exist according to the platform's normal logging architecture.

---

# 35. Initial Catalog Summary

The initial public catalog should target approximately:

```text
HTML:
6 tools

CSS:
6 tools

JavaScript:
3 tools

Developer:
13 tools

Text:
5 tools

PHP:
3 tools

Python:
0 public tools initially
```

The exact final count may change if duplicate functionality is removed or implementation readiness differs.

The important requirement is that only genuinely implemented and validated tools become ACTIVE.

---

# 36. Recommended Initial Public Catalog

The intended first-release public tools are:

### HTML

```text
HTML Formatter
HTML Minifier
HTML Validator
HTML Encoder
HTML Decoder
HTML Preview
```

### CSS

```text
CSS Formatter
CSS Minifier
CSS Validator
CSS Prefixer
CSS Color Converter
CSS Preview
```

### JavaScript

```text
JavaScript Formatter
JavaScript Minifier
JavaScript Validator
```

### Developer

```text
JSON Formatter
JSON Validator
JSON Minifier
Base64 Encoder
Base64 Decoder
URL Encoder
URL Decoder
UUID Generator
Hash Generator
Timestamp Converter
Regex Tester
Lorem Ipsum Generator
```

### Text

```text
Text Case Converter
Text Counter
Remove Duplicate Lines
Sort Lines
Find and Replace
```

### PHP

```text
PHP Formatter
PHP Validator
PHP Minifier
```

### Python

```text
No public Python tool until a real Python API service and tool implementation are available.
```

---

# 37. Implementation Readiness Rule

A catalog entry is not considered implemented merely because its metadata exists.

A tool is ready for:

```text
ACTIVE
```

only when:

1. Its configuration is valid.
2. Its engine implementation exists.
3. Its input schema works.
4. Its output handling works.
5. Its access control works.
6. Its frontend page works.
7. Its execution API works.
8. Its security requirements pass.
9. Its tests pass.

---

# 38. Admin Extensibility

After installation, administrators should be able to create additional tools through the Dynamic Builder without modifying this catalog file.

The catalog defines defaults, not a permanent maximum number of tools.

---

# 39. Future Tool Categories

Future categories may be added through the Category System.

Possible future categories:

```text
Converters
Images
Audio
Video
Documents
Utilities
Security
Data
AI
```

These are future possibilities only.

Do not create empty categories unless required by the current implementation.

---

# 40. Catalog Governance

The AI agent must not independently add tools simply because they seem useful.

When implementing the initial release:

* Follow this catalog.
* Follow the Tool Registry.
* Follow Engine specifications.
* Follow Input/Output specifications.
* Follow Access Control.
* Follow Security rules.
* Follow the actual repository architecture.

If an implementation conflict exists, stop and inspect the repository/specifications before changing architecture.

---

# 41. Repository Compatibility

Before seeding the catalog, the AI agent must inspect:

* Existing migration system.
* Existing seed conventions.
* Existing plugin registration.
* Existing database naming conventions.
* Existing configuration conventions.
* Existing frontend conventions.
* Existing theme conventions.

Use the actual repository patterns.

Do not create an independent seed framework.

---

# 42. Acceptance Criteria

The default catalog is complete when:

* Initial categories are defined.
* Initial tools are defined.
* Every tool has a unique slug.
* Every tool has exactly one category.
* Every tool has exactly one engine.
* Every tool has exactly one access mode.
* Every tool has a valid status.
* Every tool has a valid input schema.
* Every tool has a valid output schema.
* Every tool has valid engine configuration.
* Default tools can be seeded idempotently.
* Seed operations do not duplicate tools.
* Existing administrator changes are protected.
* Active tools appear in public discovery.
* Tool pages render dynamically.
* Execution uses the central execution API.
* Access control uses the central access system.
* No tool introduces usage limits.
* No tool introduces credits/tokens/quotas.
* No arbitrary PHP execution exists.
* No arbitrary Python execution exists.
* No arbitrary shell/command execution exists.
* Python tools do not expose service credentials.
* Favorite API Connector remains separate.
* Tools are searchable.
* Categories are browseable.
* Theme integration works.
* Dark/light mode works.
* Mobile/tablet/desktop layouts work.
* Only actually implemented tools are activated.

---

# 43. Final Catalog Architecture

```text
                    DEFAULT CATALOG
                           │
                           ▼
                    TOOL REGISTRY
                           │
          ┌────────────────┼────────────────┐
          │                │                │
          ▼                ▼                ▼
      CATEGORY          ENGINE           ACCESS
          │                │                │
          │       ┌────────┼────────┐        │
          │       │        │        │        │
          │      HTML     CSS      JS/PHP    │
          │                         │        │
          │                      PYTHON API  │
          │                         │        │
          └──────────────┬──────────┴────────┘
                         ▼
                  TOOL CONFIGURATION
                         │
                         ▼
                    TOOL FRONTEND
                         │
                         ▼
                  EXECUTION API
                         │
                         ▼
                     TOOL ENGINE
                         │
                         ▼
                    RESULT OUTPUT
```

---

# Final Principle

**The Default Tool Catalog defines the initial set of genuinely useful, validated tools for Favorite Web Tools. It is a seed/catalog specification—not a hard-coded application structure. Every tool must use the central Registry, Configuration, Access Control, Input/Output System, Engine System, Execution API, Search/Discovery System, and theme-aware frontend. New tools can be added later through the Admin Dynamic Builder without changing the platform architecture.**

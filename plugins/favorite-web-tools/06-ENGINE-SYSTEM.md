# Favorite Web Tools — Engine System Specification



## 1. Purpose



This document defines the execution engine architecture for the `favorite-web-tools` plugin.



The engine system determines how a tool is processed after:



1. The tool is found.

2. The tool status is validated.

3. Access control is passed.

4. The tool configuration is validated.



The initial supported engines are:



```text

HTML

CSS

JAVASCRIPT

PHP

PYTHON\_API

```



---



## 2. Engine Architecture



The tool system must separate tool metadata from tool execution.



Conceptually:



```text

Tool Request

     ↓

Tool Registry

     ↓

Tool Status Check

     ↓

Access Control

     ↓

Engine Resolver

     ↓

Selected Engine

     ↓

Execution

     ↓

Standard Result

```



The Tool Registry must not contain the implementation of every engine.



---



## 3. Engine Contract



Each engine should follow a common internal contract.



Conceptually:



```text

Engine

├── canHandle(tool)

├── validate(tool, request)

├── execute(tool, request)

└── returnResult()

```



These method names are conceptual.



The actual implementation must follow the coding and service conventions already present in Favorite CMS.



Every engine should produce a standardized execution result.



---



## 4. Standard Execution Result



A successful execution should conceptually return:



```text

{

    "success": true,

    "data": ...

}

```



A failed execution should conceptually return:



```text

{

    "success": false,

    "error": ...

}

```



The final response structure must follow the existing CMS/API conventions where applicable.



The frontend should not need to understand the internal implementation of each engine.



---



# 5. HTML Engine



## 5.1 Purpose



The HTML engine supports tools whose primary processing is based on HTML.



Possible tools include:



* HTML Formatter

* HTML Minifier

* HTML Validator

* HTML Encoder

* HTML Decoder

* HTML Preview

* HTML-related converters



---



## 5.2 Execution Model



HTML tools may process input:



```text

User Input

    ↓

HTML Engine

    ↓

Processed HTML

    ↓

Result

```



Depending on the tool, processing may happen:



* Client-side

* Server-side



The implementation should choose the simplest appropriate execution model for the specific tool.



---



## 5.3 HTML Preview



If a tool renders user-provided HTML, the implementation must treat the rendered content separately from the normal application UI.



The exact rendering mechanism must follow the security architecture defined later for the plugin.



Do not assume that displaying arbitrary HTML inside the main application DOM is automatically safe.



---



# 6. CSS Engine



## 6.1 Purpose



The CSS engine supports CSS-related tools.



Examples:



* CSS Formatter

* CSS Minifier

* CSS Prefixer

* CSS Color Converter

* CSS Optimizer

* CSS Validator



---



## 6.2 Execution Model



Conceptually:



```text

CSS Input

    ↓

CSS Engine

    ↓

Processed CSS

    ↓

Result

```



CSS processing may be client-side or server-side depending on the individual tool.



---



## 6.3 CSS Preview



If CSS is applied to a preview area, preview rendering must remain isolated from the main application interface where necessary.



Do not allow tool-generated CSS to unintentionally alter the Favorite CMS interface.



---



# 7. JavaScript Engine



## 7.1 Purpose



The JavaScript engine supports JavaScript-related tools.



Examples:



* JavaScript Formatter

* JavaScript Minifier

* JavaScript Validator

* JavaScript Beautifier

* JavaScript Converter



---



## 7.2 Client-Side JavaScript



Some JavaScript tools can process data entirely inside the browser.



Example:



```text

Input

 ↓

Browser JavaScript

 ↓

Formatted Result

```



This can be used when the tool does not require server-side processing.



---



## 7.3 JavaScript Execution



A tool that actually executes user-provided JavaScript must be treated differently from a formatter/minifier.



The implementation must not assume that:



```text

format JavaScript

```



and:



```text

execute JavaScript

```



are equivalent operations.



Execution-capable tools require a separately designed execution model and must not be introduced simply by passing user input into dynamic browser/server execution.



---



## 7.4 Source Visibility



Browser-delivered JavaScript is visible to the user and can be inspected using browser developer tools.



Therefore:



```text

Client-side code ≠ secret code

```



Proprietary processing that must remain server-side should use PHP or a Python API/service where appropriate.



---



# 8. PHP Engine



## 8.1 Purpose



The PHP engine handles server-side PHP-based tools.



Possible tools include:



* PHP utility processors

* Server-side converters

* Server-side document processing

* Other controlled PHP implementations



---



## 8.2 PHP Execution Model



Conceptually:



```text

User Request

    ↓

Access Control

    ↓

PHP Tool Handler

    ↓

Validated Processing

    ↓

Result

```



---



## 8.3 No Arbitrary PHP Execution



The PHP engine must NOT become a generic PHP code execution service.



The system must not accept arbitrary PHP source from a user and execute it through mechanisms such as:



```text

eval()

```



or equivalent unrestricted dynamic execution.



Tools must use explicitly implemented PHP handlers.



---



## 8.4 Tool-Specific PHP Logic



A PHP tool should have controlled implementation logic.



Conceptually:



```text

Tool

 ↓

Known PHP Handler

 ↓

Validated Input

 ↓

Result

```



not:



```text

Tool

 ↓

User PHP Source

 ↓

eval()

```



---



# 9. Python API Engine



## 9.1 Purpose



The Python engine allows Favorite Web Tools to use Python-based processing through an external API/service.



Python execution should not require Python source code to be executed directly inside the CMS PHP process.



Conceptually:



```text

Favorite CMS

      ↓

Python API Service

      ↓

Python Processing

      ↓

API Response

      ↓

Favorite Web Tools

```



---



## 9.2 Python Service



A Python service may run:



* On the same server

* On another server

* On a dedicated API server

* On another infrastructure supported by the deployment



The Web Tools plugin should not assume a specific hosting topology.



---



## 9.3 Python Service Configuration



A configured Python service may conceptually contain:



```text

Service Name

Base URL

Authentication Configuration

Timeout

Status

```



Tool-specific configuration may reference:



```text

Service

Endpoint

HTTP Method

Input Mapping

Output Mapping

```



The exact schema must follow the implementation established during repository inspection.



---



## 9.4 Python Request Flow



Conceptually:



```text

User

 ↓

Favorite Web Tools

 ↓

Tool Registry

 ↓

Access Control

 ↓

Python API Engine

 ↓

Configured Python Service

 ↓

Endpoint

 ↓

Python Processing

 ↓

Response Validation

 ↓

Tool Result

 ↓

User

```



---



## 9.5 Python Credentials



Python API credentials must remain server-side.



They must not be exposed through:



* HTML

* Browser JavaScript

* Public tool metadata

* Frontend configuration

* Client-side API requests



---



## 9.6 Python API Timeout



The Python API engine should support a configurable timeout.



If the external service does not respond within the configured limit, the tool should return a controlled error.



The implementation must use the existing CMS HTTP/client conventions where available.



---



## 9.7 Python API Errors



The engine must distinguish between common failure conditions such as:



```text

Service unavailable

Connection failure

Timeout

Authentication failure

Invalid response

Tool processing error

```



Do not expose unnecessary internal credentials, stack traces, or infrastructure details to users.



Detailed diagnostics may be logged according to the CMS/plugin logging conventions.



---



## 9.8 Python Response Validation



The engine must not blindly trust an external Python API response.



The response should be validated against the expected tool result structure before returning it to the frontend.



---



# 10. Engine Resolver



The plugin should provide an engine-resolution mechanism.



Conceptually:



```text

engine = JAVASCRIPT

       ↓

JavaScript Engine

```



or:



```text

engine = PYTHON\_API

       ↓

Python API Engine

```



The resolver should not contain the full execution implementation of every engine.



---



# 11. Engine Registration



Engines should be registered through a controlled mechanism.



Conceptually:



```text

Engine Registry

├── HTML Engine

├── CSS Engine

├── JavaScript Engine

├── PHP Engine

└── Python API Engine

```



The exact registration mechanism must follow existing Favorite CMS plugin/service patterns.



---



# 12. Engine Independence



Each engine should remain independently maintainable.



For example:



```text

HTML Engine

CSS Engine

JavaScript Engine

PHP Engine

Python API Engine

```



Changes to one engine should not require rewriting unrelated engines.



---



# 13. Input Handling



Every engine must receive validated input.



Conceptually:



```text

Raw Request

    ↓

Request Validation

    ↓

Normalized Input

    ↓

Engine

```



The engine should not assume that frontend validation is sufficient.



---



# 14. File Input



Some tools may require file uploads.



Examples:



* File format converters

* Code file formatters

* Image utilities

* Document processors



File handling must use the CMS/plugin's established upload and storage mechanisms where applicable.



Each tool must explicitly define which file types it accepts.



The engine must not assume that every uploaded file is safe or valid merely because the browser selected it.



---



# 15. Output Types



Engines may return different output types depending on the tool.



Supported conceptual output types include:



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



The tool configuration must define the expected output type.



---



# 16. Streaming / Large Results



Some tools may eventually produce large outputs or media.



The engine architecture should not assume every result must be embedded directly into a JSON response.



Where appropriate, an engine may return a controlled result reference or downloadable resource using the CMS/plugin architecture.



Do not introduce a separate storage system unless required.



---



# 17. Engine + Access Control



Access control always occurs before protected execution.



Conceptually:



```text

Request

 ↓

Tool

 ↓

Access Control

 ↓

Engine

```



Never:



```text

Request

 ↓

Engine

 ↓

Access Control

```



Protected processing must not happen before permission is verified.



---



# 18. Engine + Tool Registry



The Tool Registry identifies the engine.



Example:



```text

Tool:

    name = JSON Formatter

    engine = JAVASCRIPT

```



The Registry provides configuration.



The Engine performs execution.



These responsibilities must remain separate.



---



# 19. Engine + Favorite API Connector



The Python API engine is responsible for communicating with configured Python services.



The separate `Favorite API Connector` plugin is responsible for its own third-party API integration use cases.



Favorite Web Tools must not duplicate the API Connector's functionality.



If future tools need supported third-party API integrations, the Web Tools plugin may integrate through the API Connector's public/plugin interface.



---



# 20. Adding a New Engine



The architecture should support future engines without rewriting the Tool Registry.



Conceptually:



```text

Existing Engine System

        ↓

New Engine Contract

        ↓

New Engine Implementation

        ↓

Engine Registration

```



A future engine must implement the common execution contract.



---



# 21. Engine Configuration Validation



Before execution, the selected engine must validate required configuration.



Examples:



### PHP



```text

Required handler/configuration exists

```



### Python API



```text

Python service exists

Endpoint exists

Required configuration is valid

```



### Client-side engine



```text

Required tool assets/configuration exist

```



Invalid configuration must result in a controlled error.



---



# 22. Engine Error Handling



All engines should return normalized errors to the Tool System.



Conceptually:



```text

Engine

 ↓

Execution Error

 ↓

Normalized Tool Error

 ↓

Frontend

```



Internal implementation details should not unnecessarily leak to users.



---



# 23. No Cross-Engine Duplication



Common functionality should be shared through appropriate services where practical.



For example:



```text

Request Validation

Response Formatting

Error Handling

Configuration Loading

```



should not be independently reimplemented in every engine unless the implementation genuinely requires it.



---



# 24. Repository Compatibility



Before implementing the engine system, the AI agent must inspect:



* Existing service/container patterns

* Existing plugin loading

* Existing HTTP client utilities

* Existing request/response handling

* Existing validation helpers

* Existing file upload/storage mechanisms

* Existing configuration system

* Existing error/logging conventions

* Existing asset loading



The agent must reuse these mechanisms where appropriate.



Do not introduce a second framework or parallel infrastructure without a documented requirement.



---



# 25. Implementation Rules for AI Agent



The implementation agent must:



1. Implement the five initial engines.

2. Keep engines separate from the Tool Registry.

3. Use a common engine contract.

4. Resolve engines through a controlled mechanism.

5. Validate input before execution.

6. Check access before protected execution.

7. Never use arbitrary PHP execution.

8. Never use `eval()` for user-provided PHP.

9. Never execute arbitrary Python source inside the CMS.

10. Use configured Python APIs for Python tools.

11. Keep Python credentials server-side.

12. Validate Python API responses.

13. Implement controlled timeout/error handling.

14. Reuse existing CMS HTTP and service mechanisms.

15. Reuse existing file/storage mechanisms where applicable.

16. Do not duplicate Favorite API Connector functionality.

17. Do not modify CMS core files.

18. Do not expose proprietary server-side implementation through frontend code.

19. Do not assume browser JavaScript can remain secret.

20. Do not add an engine-specific usage quota system.



---



# 26. Acceptance Criteria



The Engine System is correctly implemented when:



* HTML tools can use the HTML engine.

* CSS tools can use the CSS engine.

* JavaScript tools can use the JavaScript engine.

* PHP tools can use controlled PHP handlers.

* Python tools can communicate through configured Python APIs.

* Engines are resolved independently from the Tool Registry.

* All engines follow a common execution contract.

* Access control runs before protected execution.

* PHP does not provide arbitrary code execution.

* Python is not executed directly as arbitrary source inside CMS.

* Python credentials remain server-side.

* Python responses are validated.

* Engine errors are normalized.

* Existing CMS infrastructure is reused.

* New engines can be added without rewriting the entire Tool System.



---



# 27. Final Engine Model



The final conceptual model is:



```text

                    Favorite Web Tools

                           │

                    ┌──────┴──────┐

                    │ Tool Registry│

                    └──────┬──────┘

                           │

                    Access Control

                           │

                    Engine Resolver

                           │

       ┌──────────┬────────┼──────────┬────────────┐

       ↓          ↓        ↓          ↓            ↓

      HTML       CSS   JAVASCRIPT    PHP      PYTHON\_API

       │          │        │          │            │

       └──────────┴────────┴──────────┴────────────┘

                           │

                    Standard Result

                           │

                       Frontend

```



This engine architecture is the source of truth for implementing tool execution in Favorite Web Tools.




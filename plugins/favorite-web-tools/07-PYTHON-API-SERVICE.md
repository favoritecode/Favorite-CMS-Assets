# Favorite Web Tools — Python API Service Specification



## 1. Purpose



This document defines how `Favorite Web Tools` communicates with external Python-based services.



Python tools will be executed through HTTP/API communication rather than executing arbitrary Python source code directly inside the Favorite CMS application.



The Python API system must support Python tools running:



* On the same server

* On another server

* On a dedicated Python server

* On another supported API infrastructure



---



## 2. Core Architecture



The basic architecture is:



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

Python Endpoint

  ↓

Python Processing

  ↓

API Response

  ↓

Favorite Web Tools

  ↓

User

```



The Web Tools plugin is the client/orchestrator.



The Python service is the processing backend.



---



## 3. Python Service vs Python Tool



These are two separate concepts.



### Python Service



A reusable external Python API server.



Example:



```text

Python Processing Server

```



### Python Tool



A specific Favorite Web Tools tool that uses that service.



Example:



```text

Image Compressor

PDF Processor

Audio Processor

```



Conceptually:



```text

Python Service

 ├── Tool Endpoint A

 ├── Tool Endpoint B

 └── Tool Endpoint C

```



Multiple tools may use the same Python service.



---



## 4. Python Service Registry



Favorite Web Tools should maintain a configuration/registry for Python services.



Conceptually:



```text

Python Service Registry

├── Service A

├── Service B

└── Service C

```



The exact database/table/service implementation must follow the existing Favorite CMS plugin architecture.



Do not create a second general-purpose service-management framework.



---



## 5. Python Service Fields



A Python service may require fields conceptually equivalent to:



| Field            | Purpose                                  |

| ---------------- | ---------------------------------------- |

| `id`             | Unique service identifier                |

| `name`           | Human-readable service name              |

| `base\_url`       | API base URL                             |

| `authentication` | Server-side authentication configuration |

| `timeout`        | Request timeout                          |

| `status`         | Service status                           |

| `created\_at`     | Creation timestamp                       |

| `updated\_at`     | Last update timestamp                    |



The final field names/types must follow the repository's existing database conventions.



---



## 6. Service Status



Initial service statuses:



```text

ACTIVE

DISABLED

```



### ACTIVE



The service can be used by configured tools.



### DISABLED



Requests must not be sent to the service.



A disabled service should cause dependent tools to return a controlled error instead of attempting an API request.



---



## 7. Base URL



The Python service should define a base URL.



Example:



```text

https://python.example.com/api

```



Individual tools may then reference an endpoint such as:



```text

/process

```



Conceptually:



```text

Base URL

   +

Endpoint

   ↓

Final API Request

```



The implementation must validate the resulting URL according to the plugin's configuration rules.



---



## 8. Endpoint Configuration



A Python tool may define:



* Endpoint path

* HTTP method

* Input mapping

* Output mapping

* Required parameters

* Timeout override where appropriate



Example:



```text

Tool:

    Image Processor



Service:

    Python Processing Server



Endpoint:

    /image/process



Method:

    POST

```



These are conceptual examples.



---



## 9. HTTP Methods



The initial implementation should support the HTTP methods actually required by the tools.



At minimum, the architecture should be able to support:



```text

GET

POST

```



Additional methods may be supported when required by an actual tool.



Do not add unnecessary complexity before a real use case requires it.



---



## 10. Request Flow



A Python tool request should follow this sequence:



```text

1\. Receive user request

2\. Find tool

3\. Check tool status

4\. Check access

5\. Load Python service

6\. Check service status

7\. Validate tool configuration

8\. Validate user input

9\. Build API request

10\. Send request

11\. Validate response

12\. Normalize result

13\. Return result

```



Access control must happen before the external Python request.



---



## 11. Authentication



Python services may require authentication.



Authentication configuration must remain server-side.



Possible mechanisms may include:



* API key

* Bearer token

* Basic authentication

* Other supported service authentication



The exact mechanism should be implemented only when required.



---



## 12. Credential Protection



Python API credentials must never be sent to the browser.



They must not appear in:



* HTML source

* JavaScript variables

* Public tool configuration

* Tool metadata responses

* Browser network requests originating directly from the user



Conceptually:



```text

Browser

   ↓

Favorite CMS

   ↓

Private credentials

   ↓

Python API

```



Not:



```text

Browser

   ↓

Python API + private credential

```



---



## 13. Server-Side API Request



Where authentication is required, the CMS/plugin server should make the request.



Conceptually:



```text

User Browser

     ↓

Favorite CMS

     ↓

Python API Engine

     ↓

Python Service

```



The browser should not receive internal service credentials.



---



## 14. Input Mapping



Different Python tools may require different request formats.



The tool configuration may define how frontend inputs map to API parameters.



Example:



```text

Tool Input

    ↓

Input Mapping

    ↓

Python API Request

```



Example conceptual mapping:



```text

file → uploaded\_file

quality → compression\_level

```



The exact mapping format should be selected during implementation based on actual tool requirements.



---



## 15. File Uploads



Python tools may process files such as:



* Images

* PDFs

* Audio

* Video

* Documents

* Code files

* Archives where appropriate



The Web Tools plugin should receive the file through the CMS/plugin request flow and forward it to the Python service when required.



The implementation must use the existing CMS/plugin upload mechanisms where possible.



---



## 16. Large Files



Large file processing must not assume that the complete file should always be embedded in a normal JSON request.



Depending on the tool, the architecture may support:



```text

Multipart Upload

```



or another appropriate server-side transfer mechanism.



The implementation should select the simplest reliable method required by the actual tool.



---



## 17. Response Handling



Python services may return:



* JSON

* Text

* File data

* Download reference

* Structured processing result



The Python API engine must normalize the response into the Favorite Web Tools standard result format.



Conceptually:



```text

Python Response

      ↓

Response Validator

      ↓

Result Normalizer

      ↓

Favorite Web Tools Result

```



---



## 18. JSON Response



A Python API may return structured JSON.



Conceptual example:



```text

{

    "success": true,

    "result": "..."

}

```



The exact API response schema is not fixed by this document.



Each Python tool may define its expected response structure.



---



## 19. File Result



If the Python service generates a file, the result may be represented as:



```text

{

    "success": true,

    "type": "file",

    "result": "..."

}

```



The actual implementation should use an appropriate secure file/result delivery mechanism.



Do not expose internal filesystem paths to users.



---



## 20. Error Handling



The system must handle common API failures.



Examples:



```text

Service unavailable

Connection refused

Connection timeout

HTTP error

Authentication failure

Invalid request

Invalid response

Python processing failure

Unexpected response format

```



The frontend should receive a user-friendly error.



Internal technical details may be logged according to existing CMS/plugin conventions.



---



## 21. Timeout



Every Python service should have a reasonable request timeout.



Conceptually:



```text

Python Service

    ↓

Timeout Configuration

    ↓

API Request

```



If a request exceeds the timeout:



```text

Request

  ↓

Timeout

  ↓

Controlled Error

```



The implementation must use existing HTTP client conventions where available.



---



## 22. Retry Behavior



Automatic retries should not be introduced globally without a real requirement.



Some processing requests may not be safe to repeat.



If retry support is later required, it should be configured intentionally per service/tool and implemented with appropriate safeguards.



---



## 23. Service Health



A service-management interface may optionally provide a connection/health test.



Conceptually:



```text

Python Service

      ↓

Test Connection

      ↓

Available / Failed

```



A health check must not execute an actual user processing job unless explicitly designed to do so.



---



## 24. Tool Dependency



A Python tool may depend on a specific Python service.



Conceptually:



```text

Python Tool

     ↓

Required Service

     ↓

Endpoint

```



If the required service is disabled or unavailable, the tool should not attempt processing.



---



## 25. Service Deletion



A Python service should not be deleted blindly if tools depend on it.



Before deletion, the admin system should detect dependent tools.



Possible behavior:



```text

Service

  ↓

Used by 5 tools

  ↓

Deletion blocked

```



or the service may be disabled instead.



The exact behavior should follow existing CMS/plugin data-management conventions.



---



## 26. Service Configuration UI



The administration interface should eventually provide a Python Service management area.



Conceptually:



```text

Python Services

├── Add Service

├── Edit Service

├── Test Connection

├── Enable / Disable

└── View Dependent Tools

```



Exact routes and UI structure must follow the existing Favorite CMS admin architecture.



---



## 27. Tool-to-Service Relationship



A Python tool should reference a service rather than duplicating the service configuration.



Conceptually:



```text

Python Tool

    ↓

service\_id

    ↓

Python Service

    ↓

base\_url

    ↓

endpoint

```



This prevents repeated storage of the same service information across multiple tools.



---



## 28. Environment-Specific Configuration



Deployment environments may use different Python service URLs.



The implementation should avoid hardcoding environment-specific values into frontend source code.



Where appropriate, use the existing CMS configuration/environment mechanisms.



---



## 29. Local Development



The architecture should support local development.



Example:



```text

Favorite CMS

   ↓

Local Python API

   ↓

127.0.0.1 / local network service

```



The implementation must not assume that the Python API is always publicly hosted.



---



## 30. Production Deployment



Production may use:



```text

Favorite CMS Server

       ↓

Python API Server

```



The two systems may be physically separate.



The Web Tools plugin must communicate through the configured API URL rather than assuming local execution.



---



## 31. Python Source Code



Favorite Web Tools must not store arbitrary Python source code in the database for execution.



The Python service owns Python implementation.



Conceptually:



```text

Favorite Web Tools

    → API request



Python Service

    → Python implementation

```



This separation allows server-side Python logic to remain outside browser-delivered code.



---



## 32. Python API and Access Control



The request order is mandatory:



```text

User Request

     ↓

Tool Lookup

     ↓

Status Check

     ↓

Access Check

     ↓

Python Service Check

     ↓

Python API Request

```



A user who does not have permission must not trigger the Python processing request.



---



## 33. Python API and Favorite API Connector



`Favorite API Connector` remains a separate plugin.



The Python API engine is specifically responsible for configured Python processing services used by Favorite Web Tools.



The architecture must not merge:



```text

Favorite Web Tools

```



and:



```text

Favorite API Connector

```



into one plugin.



If a future requirement needs third-party API integration, the Web Tools plugin may use the public integration interface of Favorite API Connector.



---



## 34. Security Boundary



The detailed security implementation will be specified separately.



For this architecture, the mandatory boundaries are:



* Python credentials stay server-side.

* Arbitrary Python source is never executed by the CMS.

* Python API responses are validated.

* Access is checked before protected processing.

* Internal server paths are not exposed.

* Internal API credentials are not exposed.

* User input is validated before forwarding.

* External API failures are handled safely.



---



## 35. AI Agent Implementation Rules



The implementation agent must:



1. Inspect the repository before implementation.

2. Reuse existing CMS HTTP/client mechanisms.

3. Reuse existing configuration mechanisms.

4. Reuse existing plugin/database conventions.

5. Keep Python processing outside the PHP application.

6. Communicate with Python through configured APIs.

7. Keep Python services separate from tools.

8. Allow multiple tools to use one Python service.

9. Keep credentials server-side.

10. Validate API requests.

11. Validate API responses.

12. Implement controlled timeout handling.

13. Avoid automatic retries unless explicitly required.

14. Support file-based processing where required.

15. Avoid exposing filesystem paths.

16. Check tool access before calling the Python API.

17. Do not execute arbitrary Python source.

18. Do not create a second API Connector system.

19. Do not modify CMS core files.

20. Do not guess undocumented repository APIs.



---



## 36. Acceptance Criteria



The Python API Service system is correctly implemented when:



* Python tools can reference configured Python services.

* Multiple tools can use the same service.

* Service configuration is stored separately from tool configuration where appropriate.

* Service status can prevent unavailable services from being used.

* API requests are made server-side where credentials are required.

* Credentials never reach the browser.

* Tool access is checked before API execution.

* Inputs are validated.

* API responses are validated.

* JSON and file results can be normalized.

* Timeouts are handled.

* API errors are handled gracefully.

* Arbitrary Python source is never executed by Favorite CMS.

* Python services can run on the same or separate infrastructure.

* Favorite API Connector remains a separate plugin.

* CMS core remains untouched.



---



## 37. Final Python Architecture



```text

                    Favorite Web Tools

                           │

                     Tool Registry

                           │

                     Access Control

                           │

                    Python API Engine

                           │

                  Python Service Registry

                           │

                    Configured Service

                           │

                       Endpoint

                           │

                   Python Application

                           │

                     Processing Result

                           │

                    Response Validator

                           │

                    Result Normalizer

                           │

                        Frontend

```



This architecture is the source of truth for Python-based tool integration in Favorite Web Tools.




# Favorite Web Tools — Project Overview



## 1. Project Name



**Favorite Web Tools**



## 2. Plugin Slug



`favorite-web-tools`



## 3. Platform



Favorite CMS



## 4. Project Purpose



Favorite Web Tools is a modular tool platform plugin for Favorite CMS.



The plugin will allow the CMS administrator to create, manage, organize, and publish different types of web-based tools from a centralized system.



The platform must support multiple tool engines, including:



* HTML

* CSS

* JavaScript

* PHP

* Python API



The architecture must be designed so that additional tool engines and tools can be added in the future without rewriting the core plugin architecture.



---



## 5. Core Concept



The plugin will provide a unified system where users can access different tools according to the access level assigned to each tool.



Each tool can have one of three access modes:



### Free



Anonymous users can use the tool without creating an account or logging in.



### Login Required



The user must be logged into a Favorite CMS account before using the tool.



### Membership Required



The user must have an active membership to use the tool.



An active member can use membership-required tools without any usage limit.



There will be:



* No daily usage limit

* No monthly usage limit

* No credit system

* No per-tool usage counter

* No token system



Membership access remains available as long as the user's membership is active.



---



## 6. Initial Tool Categories



The initial platform should support tools in categories such as:



* HTML Tools

* CSS Tools

* JavaScript Tools

* Developer Tools

* Text Tools

* PHP Tools

* Python Tools



The category system must remain extensible so that new categories can be added later.



---



## 7. Initial Tool Examples



The following are examples of tools that the platform may support.



### HTML



* HTML Formatter

* HTML Minifier

* HTML Validator

* HTML Entity Encoder

* HTML Entity Decoder



### CSS



* CSS Formatter

* CSS Minifier

* CSS Prefixer

* CSS Color Converter



### JavaScript / JSON



* JavaScript Formatter

* JavaScript Minifier

* JSON Formatter

* JSON Validator

* JSON Beautifier



### Developer / Text



* Base64 Encoder

* Base64 Decoder

* URL Encoder

* URL Decoder

* HTML Encoder

* HTML Decoder

* UUID Generator

* Hash Generator

* Regex Tester

* Timestamp Converter

* Lorem Ipsum Generator



These are initial examples, not a final restriction on the tool system.



The architecture must allow additional tools to be added later.



---



## 8. Python Tool Support



The plugin must support external Python-based tools through an API/service architecture.



Python processing may run outside the Favorite CMS server.



Conceptually:



```text

User

  ↓

Favorite Web Tools

  ↓

Python API

  ↓

Python Tool

  ↓

Result

  ↓

Favorite Web Tools

  ↓

User

```



The Python implementation itself is outside the scope of this plugin unless specifically added later.



The plugin must provide a clean integration layer for communicating with authorized Python services.



---



## 9. Relationship With Favorite API Connector



`Favorite API Connector` and `Favorite Web Tools` are separate plugins.



### Favorite API Connector



Its purpose is to provide reusable external API connectivity for services such as:



* Fraud Checker APIs

* SMM Panel APIs

* Other third-party APIs



### Favorite Web Tools



Its purpose is to provide the user-facing web tool platform and tool execution architecture.



The two plugins must not become unnecessarily dependent on each other.



If integration is required in the future, it must be implemented through clean plugin APIs/interfaces rather than modifying CMS core files.



---



## 10. Favorite CMS Core Protection



This plugin must be completely isolated from Favorite CMS core.



The implementation must NOT modify:



* CMS core files

* Existing CMS application files

* Existing plugins

* Existing plugin functionality

* Core configuration

* Bootstrap files

* Authentication core

* Database core architecture

* Existing assets unrelated to this plugin



Before implementation, the AI agent must inspect the repository and understand the existing Favorite CMS plugin architecture.



The plugin must follow the existing CMS conventions wherever applicable.



Do not create a parallel architecture when an existing supported plugin architecture already exists.



---



## 11. Extensibility Requirement



The plugin must be designed as a long-term platform rather than a collection of hardcoded tools.



Adding a new tool should require minimal changes.



The architecture should support:



```text

Tool

├── Name

├── Slug

├── Description

├── Category

├── Engine

├── Access Mode

├── Status

├── Configuration

└── Tool-specific settings

```



Future tools should be able to reuse the existing:



* Tool registration

* Tool loading

* Access control

* Frontend rendering

* Backend execution

* Python API communication

* Admin management

* Error handling



systems.



---



## 12. Implementation Principle



The project must be implemented incrementally.



The AI agent must NOT immediately build the entire system without first:



1. Reading the complete project specification.

2. Inspecting the existing repository.

3. Understanding the current plugin architecture.

4. Identifying reusable CMS functionality.

5. Confirming the proposed implementation structure.

6. Implementing one phase at a time.

7. Testing each phase before continuing.



---



## 13. Source of Truth



This specification document and the other project specification files in this folder are the primary project requirements.



The AI agent must follow these documents during implementation.



If the repository structure conflicts with an assumption in this document, the agent must inspect the existing CMS architecture and report the conflict before making a destructive or architectural change.



The agent must not silently change the project's core requirements.



---



## 14. Current Project Status



**Status:** Planning



No implementation should be considered complete until the specification has been reviewed and the required implementation phases have been completed and tested.



---



## 15. Important Rule



Do not over-engineer the first version.



Build a clean, modular, maintainable foundation that can grow into a large Favorite Web Tools platform.



Future functionality should be added through the established extension points instead of repeatedly rewriting the core plugin.




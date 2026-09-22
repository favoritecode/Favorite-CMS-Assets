# 15 — TOOL LIFECYCLE AND VALIDATION



## 1. Purpose



The **Tool Lifecycle and Validation System** defines the complete lifecycle of a Favorite Web Tools tool from creation through removal.



The lifecycle must ensure that a tool cannot become publicly available until its configuration, dependencies, access settings, inputs, outputs, and engine requirements are valid.



The lifecycle covers:



```text

CREATE

  ↓

DRAFT

  ↓

CONFIGURE

  ↓

VALIDATE

  ↓

TEST

  ↓

ACTIVATE

  ↓

ACTIVE

  ↓

UPDATE / TEST

  ↓

DISABLE

  ↓

DELETE

```



The exact implementation must follow the existing Favorite CMS plugin architecture.



---



# 2. Core Principle



A tool must never become publicly executable simply because a database record exists.



Public availability requires:



```text

Valid Tool

\+

Valid Configuration

\+

Supported Engine

\+

Valid Dependencies

\+

Valid Access Configuration

\+

ACTIVE Status

```



The lifecycle system must act as a gate between administration and public execution.



---



# 3. Lifecycle States



The supported tool states are:



```text

DRAFT

ACTIVE

DISABLED

```



These states are defined in the Tool Registry and Configuration Systems.



No additional runtime status should be introduced unless the actual implementation requires it.



---



# 4. State Meaning



## 4.1 DRAFT



A tool is being created, configured, or tested.



DRAFT tools:



* May be visible to authorized administrators.

* May be edited.

* May be tested through an appropriate admin test mechanism.

* Must not appear in normal public tool discovery.

* Must not be publicly executable.



---



## 4.2 ACTIVE



The tool is approved for public use.



ACTIVE tools:



* May appear in public discovery.

* May have a public tool page.

* May receive execution requests.

* Must still pass access control before execution.



ACTIVE does not mean every user can use the tool.



Access depends on:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



---



## 4.3 DISABLED



The tool has been intentionally taken offline.



DISABLED tools:



* Must not be publicly executable.

* Should not appear in normal public discovery.

* May remain visible to administrators.

* May be edited and reactivated later.



Disabling is preferred to deletion when historical configuration should be preserved.



---



# 5. Lifecycle State Machine



Conceptually:



```text

             ┌───────────────┐

             │     DRAFT     │

             └───────┬───────┘

                     │

                Validate

                     │

                     ▼

             ┌───────────────┐

             │     TEST      │

             └───────┬───────┘

                     │

                 Activate

                     │

                     ▼

             ┌───────────────┐

             │     ACTIVE    │

             └───────┬───────┘

                     │

              Disable│Update

                     │

          ┌──────────┴──────────┐

          ▼                     ▼

   ┌───────────────┐      ┌───────────────┐

   │   DISABLED    │      │    DRAFT/     │

   │               │      │  VALIDATION   │

   └───────┬───────┘      └───────────────┘

           │

        Reactivate

           │

           ▼

        ACTIVE

```



The exact state-transition implementation must follow CMS conventions.



---



# 6. Tool Creation



When an administrator creates a new tool:



1. Create the tool record.

2. Assign required identity information.

3. Set initial status to `DRAFT`.

4. Configure category.

5. Configure engine.

6. Configure access mode.

7. Configure inputs.

8. Configure outputs.

9. Configure engine-specific settings.

10. Save the configuration.



A newly created tool must not automatically become ACTIVE unless the existing CMS explicitly requires such behavior and all activation validation is completed.



Recommended default:



```text

New Tool → DRAFT

```



---



# 7. Draft Configuration



While a tool is in DRAFT, administrators may configure:



* Name

* Slug

* Description

* Category

* Engine

* Access mode

* Inputs

* Outputs

* Engine settings

* Frontend settings

* Python service dependency

* Endpoint configuration where applicable



The tool remains unavailable to normal users.



---



# 8. Configuration Validation



Before activation, the complete configuration must be validated.



Validation must check:



### Identity



* Name exists.

* Slug exists.

* Slug is valid.

* Slug is unique.



### Category



* Category exists when required.

* Category reference is valid.

* Disabled/invalid category dependency is handled according to product rules.



### Engine



* Engine exists.

* Engine is supported.

* Engine configuration is valid.



### Access



Access mode must be exactly one of:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



### Inputs



* Input IDs are valid.

* Input types are supported.

* Required settings are present.

* Options are valid for SELECT/RADIO where applicable.

* File settings are valid where applicable.

* Validation configuration is valid.



### Outputs



* Output types are supported.

* Output configuration is valid.

* Engine can provide the configured output.



### Dependencies



* Required Python service exists.

* Required service is configured.

* Required endpoint exists.

* Required engine handler exists.



---



# 9. Activation Gate



Activation must be blocked when validation fails.



Conceptually:



```text

DRAFT

  ↓

VALIDATE

  ↓

PASS ─────────→ ACTIVE

  │

  └─ FAIL ────→ remain DRAFT

```



The system must never activate an invalid tool.



---



# 10. Validation Result



Validation should return structured information.



Conceptually:



```text

VALID

```



or:



```text

INVALID

 ├── field

 ├── error\_code

 └── message

```



Example:



```text

Engine: PYTHON\_API



Error:

Python service is not configured.

```



Another:



```text

Input: source\_code



Error:

Input type is missing.

```



Errors should be understandable to administrators.



---



# 11. Validation Layers



Validation should operate at multiple levels.



## Layer 1 — Configuration Validation



Checks whether the stored tool configuration is structurally valid.



## Layer 2 — Dependency Validation



Checks required services, handlers, categories, and related resources.



## Layer 3 — Engine Validation



Checks whether the selected engine can process the configured tool.



## Layer 4 — Execution Test



Checks whether the tool can actually process representative test input.



These layers must remain conceptually separate.



---



# 12. Static Validation vs Execution Test



Validation and testing are not the same.



### Validation



Determines whether the configuration is valid.



Example:



```text

Python Service exists = YES

Endpoint exists = YES

Method valid = YES

```



### Execution Test



Actually invokes the configured implementation using controlled test input.



Example:



```text

Input

 ↓

Python API

 ↓

Response

 ↓

Result

```



A tool should pass configuration validation before execution testing is attempted.



---



# 13. Admin Test Mode



Administrators should be able to test a DRAFT tool before activation.



Test mode should:



* Use the normal engine architecture.

* Use normal input validation.

* Use normal output handling.

* Use configured dependencies.

* Respect appropriate authorization.

* Avoid exposing the tool publicly.



The implementation should reuse the same execution architecture rather than creating a completely separate processing system.



---



# 14. Test Data



Test inputs may be supplied by the administrator.



The system must not require production user data for testing.



For Python API tools, test requests must use the configured service and endpoint.



Test results must not expose credentials.



---



# 15. Failed Test



If a tool configuration is valid but execution testing fails:



```text

Tool remains DRAFT

```



The system should report the relevant technical error.



It must not automatically activate a tool after a failed test.



---



# 16. Successful Test



A successful test indicates that the configured implementation can process the supplied test input.



Successful testing does not automatically imply that the tool must be activated.



Recommended flow:



```text

Validation Pass

\+

Test Pass

↓

Administrator activates tool

```



Activation remains an administrative action.



---



# 17. Activation



Activation is the transition:



```text

DRAFT → ACTIVE

```



Before activation, the system must verify:



* Configuration validity.

* Engine validity.

* Required dependencies.

* Input/output configuration.

* Access configuration.

* Required implementation availability.



If any required condition fails:



```text

Activation = rejected

```



---



# 18. Active Tool Execution



For an ACTIVE tool:



```text

User Request

 ↓

Tool Lookup

 ↓

Status Check

 ↓

Access Check

 ↓

Input Validation

 ↓

Engine Execution

 ↓

Output Validation

 ↓

Result

```



This follows the execution architecture defined in:



`10-TOOL-EXECUTION-API.md`



---



# 19. Active Tool Updates



An ACTIVE tool may be edited.



However, changes that affect execution must be validated before the new configuration is used.



Examples:



* Engine change

* Python service change

* Endpoint change

* Input schema change

* Output schema change

* Access mode change

* Handler change



The implementation must avoid partially applying an invalid configuration.



---



# 20. Configuration Update Safety



When an active configuration is updated:



```text

Current Valid Configuration

          ↓

New Configuration

          ↓

Validation

          ↓

Valid → Apply

Invalid → Reject

```



An invalid update must not replace a previously valid active configuration.



Where the existing CMS supports transactions/version-safe updates, use those mechanisms.



---



# 21. Engine Change Lifecycle



Changing an engine is a high-impact configuration change.



Example:



```text

HTML

 ↓

PYTHON\_API

```



The system must:



1. Detect the engine change.

2. Validate new engine settings.

3. Detect incompatible old settings.

4. Require required new dependencies.

5. Validate the complete configuration.

6. Test when appropriate.

7. Apply the new configuration only when valid.



---



# 22. Python Service Change



Changing a Python service reference must trigger dependency validation.



Example:



```text

Python Tool

 ↓

Service A

```



changed to:



```text

Python Tool

 ↓

Service B

```



The new service must be validated before the tool continues operating with the new configuration.



---



# 23. Access Mode Change



Changing access mode must use the centralized Access Control system.



Example:



```text

FREE

→

LOGIN\_REQUIRED

```



or:



```text

LOGIN\_REQUIRED

→

MEMBERSHIP\_REQUIRED

```



The tool configuration only declares the access mode.



Authorization logic remains centralized.



No usage limits or counters are introduced.



---



# 24. Membership Validation



For:



```text

MEMBERSHIP\_REQUIRED

```



the lifecycle system only validates that the access mode is valid.



Actual membership state is checked during execution by the Access Control system.



Active membership provides unlimited use.



Membership status must not be permanently copied into the tool configuration.



---



# 25. Disable Tool



An ACTIVE tool may be disabled:



```text

ACTIVE → DISABLED

```



After disabling:



* Public discovery must exclude it.

* Public execution must reject it.

* Existing public pages must not execute it.

* Administrator access may remain available.



Disabled status must be checked server-side.



---



# 26. Reactivate Tool



A disabled tool may be reactivated:



```text

DISABLED → ACTIVE

```



Before reactivation:



* Configuration must be validated.

* Dependencies must be valid.

* Engine must be available.

* Required access configuration must be valid.



If the configuration has become invalid:



```text

Reactivation = rejected

```



---



# 27. Delete Tool



Deletion is a destructive administrative action.



Before deleting:



* Check references.

* Check dependencies.

* Check whether historical configuration must be preserved.

* Follow existing Favorite CMS deletion conventions.



Where practical:



```text

ACTIVE → DISABLED

```



should be preferred over immediate deletion.



---



# 28. Safe Deletion



Deleting a tool must not:



* Delete unrelated CMS data.

* Modify CMS core.

* Delete another plugin's data.

* Delete shared resources unexpectedly.

* Break unrelated tools.



Plugin-owned relationships must be handled according to the database architecture.



---



# 29. Slug Changes



A published tool slug should be treated as a stable identifier.



Changing a slug may affect:



* Public URL.

* Search.

* Internal references.

* Related tool links.

* SEO.

* External links.



Therefore slug changes should be handled carefully.



If the existing CMS supports redirects or aliases, those mechanisms may be used.



Do not invent a separate routing/redirect system solely for this plugin.



---



# 30. Category Changes



Changing a tool's category should not change its access mode.



Example:



```text

Developer

→

Text

```



must not automatically change:



```text

FREE

→

MEMBERSHIP\_REQUIRED

```



Category and access remain independent.



---



# 31. Input Schema Changes



Changing inputs may affect existing frontend pages and execution behavior.



Examples:



```text

Required input added

Input removed

Input type changed

Validation changed

```



The updated configuration must be validated before activation.



Unknown public inputs must not be trusted or blindly processed.



---



# 32. Output Schema Changes



Changing outputs must remain compatible with the selected engine and frontend result renderer.



Example:



```text

TEXT

→

DOWNLOAD

```



requires the engine and frontend to support the new result type.



Invalid output configuration must prevent activation.



---



# 33. Python Dependency Failure



If a Python service becomes unavailable after a tool is ACTIVE:



* The tool remains configured according to its stored status unless administrative action changes it.

* Execution should fail safely.

* The user should receive a safe processing error.

* Credentials and internal service details must not be exposed.



A service outage must not automatically grant or bypass access.



---



# 34. Engine Failure



If an engine implementation fails:



* Return a controlled error.

* Do not expose sensitive internal details.

* Do not bypass access control.

* Do not silently switch to another engine unless explicitly configured.

* Log technical details using existing CMS mechanisms where appropriate.



---



# 35. Concurrent Configuration Changes



If multiple administrators can edit tools, the implementation should use existing Favorite CMS mechanisms for avoiding accidental overwrite where available.



Do not build a complex custom concurrency system unless the repository already requires it.



At minimum, the final saved configuration must remain structurally valid.



---



# 36. Public Discovery Rules



Only:



```text

ACTIVE

```



tools should appear in normal public discovery.



DRAFT and DISABLED tools must be excluded.



Protected tools can remain publicly discoverable when ACTIVE.



The access state determines what happens when a user attempts to use them.



---



# 37. Public Execution Rules



A tool may execute publicly only when:



```text

Tool exists

AND

Tool is ACTIVE

AND

Access is allowed

AND

Inputs are valid

AND

Engine configuration is valid

```



Any failed condition must prevent execution.



---



# 38. Lifecycle and Security



Lifecycle state must be enforced server-side.



The browser must not be able to turn:



```text

DRAFT → ACTIVE

```



or:



```text

DISABLED → ACTIVE

```



through modified frontend requests.



Only authorized administrator actions may perform lifecycle transitions.



The implementation must reuse Favorite CMS authorization and CSRF mechanisms.



---



# 39. Lifecycle and Usage Limits



The lifecycle system must not introduce usage restrictions.



Never add lifecycle states such as:



```text

QUOTA\_EXCEEDED

CREDITS\_EXHAUSTED

LIMIT\_REACHED

```



There are no:



* Daily limits

* Monthly limits

* Hourly limits

* Credits

* Tokens

* Usage quotas

* Usage counters



Access is determined only by the configured access mode and current authorization/membership state.



---



# 40. Lifecycle Logging



Technical lifecycle events may be logged using existing CMS facilities.



Useful events may include:



* Tool created.

* Tool updated.

* Tool validated.

* Tool test executed.

* Tool activated.

* Tool disabled.

* Tool reactivated.

* Tool deleted.

* Configuration validation failed.



Logs are for technical/admin purposes.



They must not become a hidden usage-tracking system.



---



# 41. Error Codes



Conceptual lifecycle errors may include:



```text

INVALID\_TOOL\_CONFIGURATION

INVALID\_STATE\_TRANSITION

ACTIVATION\_VALIDATION\_FAILED

TEST\_EXECUTION\_FAILED

MISSING\_ENGINE

UNSUPPORTED\_ENGINE

MISSING\_CATEGORY

MISSING\_HANDLER

MISSING\_PYTHON\_SERVICE

INVALID\_INPUT\_CONFIGURATION

INVALID\_OUTPUT\_CONFIGURATION

INVALID\_ACCESS\_MODE

DEPENDENCY\_VALIDATION\_FAILED

TOOL\_DISABLED

TOOL\_NOT\_ACTIVE

```



Exact naming should follow existing Favorite CMS conventions where available.



---



# 42. AI Agent Implementation Rules



When implementing the lifecycle system, the AI agent must:



1. Inspect the repository before implementation.

2. Inspect existing plugin lifecycle conventions.

3. Reuse existing admin authorization.

4. Reuse existing CSRF protection.

5. Reuse existing database transaction mechanisms.

6. Reuse existing validation utilities where appropriate.

7. Implement only inside `favorite-web-tools`.

8. Do not modify CMS core.

9. Do not create a second admin system.

10. Do not create a second authentication system.

11. Do not create a second membership system.

12. Do not create a second router.

13. Do not create a second database abstraction.

14. Do not create a second migration framework.

15. Do not introduce usage limits.

16. Do not introduce credits or tokens.

17. Do not expose credentials.

18. Do not enable arbitrary PHP/Python execution.

19. Keep Favorite API Connector separate.

20. Follow actual repository conventions instead of assumptions.



---



# 43. Required Acceptance Criteria



The implementation is acceptable only when:



* New tools start as DRAFT by default.

* DRAFT tools cannot execute publicly.

* DRAFT tools do not appear in normal public discovery.

* Tool configuration can be validated.

* Invalid configurations cannot become ACTIVE.

* Admins can test tools before activation.

* Failed tests do not automatically activate tools.

* Successful tests do not automatically activate tools unless explicitly designed otherwise.

* ACTIVE tools can execute according to access control.

* ACTIVE tool updates cannot replace valid configuration with invalid configuration.

* DISABLED tools cannot execute.

* DISABLED tools can be reactivated after validation.

* Deletion is handled safely.

* Tool dependencies are validated.

* Python service dependencies are checked.

* Engine changes are validated.

* Access changes use centralized Access Control.

* Membership state is checked at execution time.

* No usage limits, credits, tokens, quotas, or counters exist.

* Lifecycle transitions are protected by server-side authorization.

* Existing CMS security mechanisms are reused.

* Technical lifecycle logging does not become usage tracking.

* Favorite API Connector remains separate.

* Plugin filesystem isolation is preserved.

* CMS core and unrelated plugins remain unchanged.



---



# 44. Final Lifecycle Model



The complete lifecycle is:



```text

                         CREATE

                           │

                           ▼

                        DRAFT

                           │

                           ▼

                    CONFIGURE TOOL

                           │

                           ▼

                      VALIDATE

                     /         \\

                  FAIL          PASS

                   │              │

                   ▼              ▼

                 DRAFT           TEST

                                  │

                           ┌──────┴──────┐

                           │             │

                         FAIL          PASS

                           │             │

                           ▼             ▼

                         DRAFT        ACTIVATE

                                         │

                                         ▼

                                       ACTIVE

                                      /      \\

                               UPDATE        DISABLE

                                 │              │

                                 ▼              ▼

                             VALIDATE        DISABLED

                                 │              │

                              APPLY          REACTIVATE

                                 │              │

                                 └──────┬───────┘

                                        ▼

                                      ACTIVE

                                        │

                                      DELETE

                                        │

                                        ▼

                                     REMOVED

```



The lifecycle system must ensure that only validated, authorized, and properly configured tools become publicly executable.




# Favorite Web Tools — Access Control Specification



## 1. Purpose



This document defines how access permissions work for every tool inside the `favorite-web-tools` plugin.



The access system must remain simple and must support exactly three access modes:



1. `FREE`

2. `LOGIN\_REQUIRED`

3. `MEMBERSHIP\_REQUIRED`



There must be no usage quota, credit, token, daily limit, monthly limit, or similar restriction.



---



## 2. Access Modes



### 2.1 FREE



A `FREE` tool can be used by:



* Anonymous users

* Logged-in users

* Users without membership

* Users with active membership



No login or membership is required.



---



### 2.2 LOGIN\_REQUIRED



A `LOGIN\_REQUIRED` tool can be used only by authenticated CMS users.



Rules:



* Anonymous user → Access denied

* Logged-in user → Access allowed

* Membership status does not matter



Membership must not be required for this access mode.



---



### 2.3 MEMBERSHIP\_REQUIRED



A `MEMBERSHIP\_REQUIRED` tool requires:



1. A logged-in user

2. An active membership



Rules:



* Anonymous user → Access denied / login required

* Logged-in user without active membership → Access denied / membership required

* Logged-in user with active membership → Access allowed



While membership is active, the user has unlimited access to the tool.



---



## 3. Access Matrix



| User State                   | FREE  | LOGIN\_REQUIRED | MEMBERSHIP\_REQUIRED |

| ---------------------------- | ----- | -------------- | ------------------- |

| Anonymous                    | Allow | Deny           | Deny                |

| Logged-in, no membership     | Allow | Allow          | Deny                |

| Logged-in, active membership | Allow | Allow          | Allow               |



This matrix is the authoritative access behavior.



---



## 4. No Usage Restrictions



Favorite Web Tools must NOT implement:



* Daily usage limits

* Monthly usage limits

* Hourly usage limits

* Per-tool usage limits

* Credits

* Tokens

* Usage points

* Request counters

* Membership usage quotas

* Remaining-use counters



If a user has an active membership, access is unlimited for all tools configured as `MEMBERSHIP\_REQUIRED`.



---



## 5. Access Evaluation Order



Every tool execution must follow a consistent access evaluation process.



Conceptually:



```text

Request

  ↓

Find Tool

  ↓

Check Tool Status

  ↓

Determine Access Mode

  ↓

Check Authentication

  ↓

If required, Check Membership

  ↓

Allow or Deny

  ↓

Execute Tool

```



A disabled tool must never execute regardless of the user's access level.



---



## 6. Tool Status vs Access Control



Access mode and tool status are separate concepts.



Example:



```text

Status: ACTIVE

Access: FREE

```



means the tool is publicly usable.



Example:



```text

Status: ACTIVE

Access: MEMBERSHIP\_REQUIRED

```



means the tool requires active membership.



Example:



```text

Status: DISABLED

Access: FREE

```



means the tool is unavailable to everyone.



---



## 7. Authentication Integration



Favorite Web Tools must reuse the existing Favorite CMS authentication/user system.



Do NOT create:



* A second login system

* A separate user database

* A separate session system

* Duplicate authentication logic



The plugin must detect the existing CMS authentication state using the actual mechanisms provided by the repository.



The exact authentication API/function/service must be determined by inspecting the current repository implementation.



Do not guess CMS APIs.



---



## 8. Membership Integration



`MEMBERSHIP\_REQUIRED` must integrate with the existing Favorite CMS membership functionality/plugin when available.



The Web Tools plugin must not create a duplicate membership system.



The implementation should use an adapter/service boundary so the Tool System does not need to know the internal details of the membership implementation.



Conceptually:



```text

Favorite Web Tools

        ↓

Membership Adapter

        ↓

Existing CMS Membership System

```



The exact adapter implementation must be based on the actual repository and installed membership functionality.



---



## 9. Membership State



For a `MEMBERSHIP\_REQUIRED` tool, the system must verify whether the current user has an active membership.



Conceptually:



```text

isAuthenticated(user)

isMembershipActive(user)

```



These are separate checks.



Being logged in does not automatically mean the user has access to membership-required tools.



---



## 10. Membership Expiration



Membership status must be evaluated when access to a protected tool is requested.



If membership is no longer active:



```text

MEMBERSHIP\_REQUIRED

        ↓

Membership inactive

        ↓

Access denied

```



The system must not rely solely on an old browser/session state to grant protected access.



---



## 11. Server-Side Enforcement



Access control must be enforced server-side whenever the tool execution involves server-side processing.



The frontend must not be treated as the authority for access control.



For example, hiding a button such as:



```text

Run Tool

```



is only a UI behavior.



It is not an access-control mechanism.



The backend execution layer must independently verify access before performing protected operations.



---



## 12. Client-Side Tools



Some HTML/CSS/JavaScript tools may execute entirely inside the user's browser.



For these tools, the implementation must distinguish between:



1. UI visibility

2. Actual server-side access enforcement



If a tool is intended to be truly restricted to logged-in users or active members, its implementation must not rely only on hiding frontend UI controls.



The actual architecture must be selected based on the tool's execution model.



Browser-delivered JavaScript should not be treated as secret or inaccessible source code.



---



## 13. Access Check Before Execution



A tool execution request should conceptually follow:



```text

Validate Request

      ↓

Load Tool

      ↓

Check Status

      ↓

Check Access

      ↓

Execute Engine

      ↓

Return Result

```



If access fails:



```text

Do not execute the tool.

Do not perform the protected operation.

Return an appropriate access response.

```



---



## 14. Unauthorized Responses



The system should clearly distinguish between access situations.



### Login Required



When an anonymous user attempts to use a `LOGIN\_REQUIRED` tool:



```text

Login required to use this tool.

```



The frontend may provide a link/button to the existing CMS login page.



---



### Membership Required



When a logged-in user does not have active membership:



```text

Active membership required to use this tool.

```



The frontend may provide an appropriate membership/subscription action using the existing CMS membership flow.



---



## 15. Anonymous Users



Anonymous users must be supported normally for `FREE` tools.



The system must not force login globally.



Example:



```text

FREE Tool A

    → Anonymous user → Allowed



LOGIN\_REQUIRED Tool B

    → Anonymous user → Login required



MEMBERSHIP\_REQUIRED Tool C

    → Anonymous user → Login + active membership required

```



---



## 16. Admin Configuration



Every tool must have exactly one access mode.



Example:



```text

Access Mode:

\[ FREE ]

\[ LOGIN\_REQUIRED ]

\[ MEMBERSHIP\_REQUIRED ]

```



The administrator should not configure conflicting access requirements.



Categories and access modes are independent.



For example:



```text

Category: Developer

Access: FREE

```



and:



```text

Category: Developer

Access: MEMBERSHIP\_REQUIRED

```



are both valid.



There must be no requirement that an entire category uses the same access mode.



---



## 17. No Premium Categories



The system must not introduce a separate "Premium Category" concept.



Membership restriction belongs to the individual tool's access configuration.



Example:



```text

Category: Developer

Tool: Advanced JSON Tool

Access: MEMBERSHIP\_REQUIRED

```



The category itself remains normal.



---



## 18. Shared Access-Control Service



Access checks should be centralized rather than independently implemented inside every tool.



Conceptually:



```text

Tool Request

     ↓

Access Control Service

     ↓

FREE / LOGIN\_REQUIRED / MEMBERSHIP\_REQUIRED

     ↓

Allow / Deny

```



Individual tools should not need to recreate authentication or membership logic.



---



## 19. Engine Integration



All supported engines must respect the access-control layer.



This includes:



* HTML

* CSS

* JavaScript

* PHP

* Python API



Conceptually:



```text

Tool

 ↓

Access Control

 ↓

Engine Resolver

 ↓

Engine Execution

```



For Python API tools, access must be checked before the server makes the protected Python API request.



For PHP tools, access must be checked before protected server-side processing begins.



---



## 20. Favorite API Connector



Favorite Web Tools must not duplicate the functionality of the separate `Favorite API Connector` plugin.



If a tool later needs a third-party API, the Web Tools plugin may integrate with the supported API Connector interfaces.



Access control remains the responsibility of Favorite Web Tools for the tool itself.



API credentials remain outside frontend code and must follow the API Connector architecture where applicable.



---



## 21. Membership Plugin Unavailable



If a tool is configured as:



```text

MEMBERSHIP\_REQUIRED

```



but the required membership integration is unavailable or cannot determine membership status, the system must not accidentally grant protected access.



The implementation should fail safely and provide an appropriate error state.



The exact error-handling mechanism must follow the existing CMS/plugin conventions.



---



## 22. Access-Control Data



Tool configuration should store only the access mode required by the tool.



Conceptually:



```text

access\_mode = FREE

```



or:



```text

access\_mode = LOGIN\_REQUIRED

```



or:



```text

access\_mode = MEMBERSHIP\_REQUIRED

```



Do not store unnecessary usage counters or quota fields.



---



## 23. Access-Control Rules for AI Agent



The implementation agent must follow these rules:



1. Use exactly three access modes.

2. Do not introduce premium categories.

3. Do not introduce usage limits.

4. Do not introduce credits.

5. Do not introduce tokens.

6. Do not introduce daily/monthly quotas.

7. Do not create a second authentication system.

8. Do not create a second membership system.

9. Reuse the existing Favorite CMS authentication system.

10. Reuse the existing membership system/plugin through an appropriate integration boundary.

11. Do not guess authentication or membership APIs.

12. Inspect the repository before implementing the integration.

13. Enforce protected access before tool execution.

14. Keep access control independent from categories.

15. Active membership means unlimited access.

16. Do not expose API credentials to browser-side code.

17. Do not modify CMS core files to implement access control.

18. Keep all Web Tools-specific logic inside `favorite-web-tools`.



---



## 24. Acceptance Criteria



This specification is complete when:



* `FREE` tools work for anonymous users.

* `LOGIN\_REQUIRED` tools require authentication.

* `MEMBERSHIP\_REQUIRED` tools require an active membership.

* Active membership provides unlimited usage.

* No usage quota system exists.

* No credit/token system exists.

* Categories remain independent from access mode.

* Access checks are centralized.

* Existing CMS authentication is reused.

* Existing membership functionality is reused.

* Protected server-side execution cannot bypass the access check.

* Disabled tools cannot execute.

* Favorite API Connector remains a separate plugin.

* No CMS core modification is required.

* Exact integration is based on the actual repository implementation rather than guessed APIs.



---



## 25. Final Rule



The Favorite Web Tools access model is intentionally simple:



```text

FREE

→ Anyone can use



LOGIN\_REQUIRED

→ Logged-in users can use



MEMBERSHIP\_REQUIRED

→ Logged-in users with active membership can use

→ Active membership = Unlimited

```



This three-level model is the source of truth for all future Favorite Web Tools implementation.




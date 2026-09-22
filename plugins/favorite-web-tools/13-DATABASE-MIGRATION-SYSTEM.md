# Favorite Web Tools — Database \& Migration System



## 1. Purpose



This document defines the database, persistence, migration, and seed-data architecture for the `favorite-web-tools` plugin.



The database layer must store the persistent metadata required by the plugin, including:



* tools

* categories

* tool configuration

* input/output schemas

* Python service configuration references

* tool status

* access mode

* ordering and display metadata where required



The database design must remain isolated from Favorite CMS core data.



---



## 2. Core Principle



Favorite Web Tools must use the existing Favorite CMS database architecture.



It must not create:



* a separate database

* a second database connection framework

* a separate ORM

* a second migration engine

* a separate query abstraction



If Favorite CMS already provides database services, models, repositories, migrations, or query helpers, reuse them.



---



## 3. Database Technology



The implementation must follow the database technology already used by Favorite CMS.



The existing project documentation indicates a PHP + MySQL/MariaDB architecture.



The plugin must not assume a different database engine unless the repository explicitly supports it.



---



## 4. Plugin-Owned Data



The plugin may create its own isolated tables for its domain data.



Conceptually:



```text

favorite\_web\_tools

favorite\_web\_tool\_categories

favorite\_web\_tool\_python\_services

```



Additional tables may be introduced only when an actual requirement exists.



The final table names must follow the repository's existing plugin/migration naming conventions.



---



## 5. Tool Table



The main tool table should conceptually contain:



```text

id

name

slug

description

category\_id

engine

access\_mode

status

configuration

display\_order

created\_at

updated\_at

```



Only fields actually required by the implementation should be created.



Do not create unnecessary fields.



---



## 6. Category Table



The category table should conceptually contain:



```text

id

name

slug

description

icon

thumbnail

display\_order

status

created\_at

updated\_at

```



The exact fields depend on the final UI and repository conventions.



Do not create presentation fields that are not actually needed.



---



## 7. Python Service Table



Python service configuration should be stored separately from individual tools where the architecture requires reusable services.



Conceptually:



```text

id

name

base\_url

authentication\_type

credential\_reference

timeout

status

created\_at

updated\_at

```



Tools should reference the service rather than duplicating service configuration.



---



## 8. Python Credentials



Sensitive credentials must never be stored as publicly accessible tool metadata.



Where the existing CMS provides secure configuration/secret storage, use it.



If the repository provides environment-based configuration, credentials should remain in the appropriate server-side configuration/environment mechanism.



The database must not expose secrets through public APIs.



---



## 9. Tool Configuration



Tool-specific configuration may be stored in a structured configuration field where supported.



Example conceptual structure:



```json

{

  "inputs": \[],

  "outputs": \[],

  "execution": {}

}

```



The configuration may contain:



* input schema

* output schema

* engine-specific non-secret configuration

* processing options

* Python endpoint references

* request mapping

* display configuration



It must not contain arbitrary executable source intended to be dynamically evaluated.



---



## 10. No Executable Code in Configuration



The database must never be treated as a source of arbitrary executable code.



Do not store and execute:



```text

PHP source

Python source

Shell commands

Arbitrary JavaScript

SQL commands

```



from tool configuration.



The database stores metadata/configuration.



Controlled engine handlers perform execution.



---



## 11. Access Mode Storage



Each tool must have exactly one access mode:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



The database must not contain fields for:



```text

daily\_limit

monthly\_limit

credits

tokens

quota

usage\_count

```



These concepts are outside the project scope.



---



## 12. Status Storage



Each tool should have one status:



```text

ACTIVE

DRAFT

DISABLED

```



The status must control whether the tool is publicly executable/discoverable.



---



## 13. Engine Storage



The tool record must identify its execution engine.



Supported initial values:



```text

HTML

CSS

JAVASCRIPT

PHP

PYTHON\_API

```



Engine values must be validated.



Unknown engine identifiers must not be activated.



---



## 14. Category Relationship



A tool should reference its category through the appropriate relational mechanism.



Conceptually:



```text

favorite\_web\_tools.category\_id

        ↓

favorite\_web\_tool\_categories.id

```



The actual foreign-key strategy must follow the existing database architecture.



---



## 15. Referential Integrity



Where the repository uses database foreign keys, the plugin should use them appropriately.



Category deletion must not silently leave invalid tool references.



Possible behavior:



```text

Category has tools

        ↓

Prevent deletion

        OR

Require reassignment

        OR

Use safe existing CMS deletion behavior

```



The final behavior must match repository conventions.



---



## 16. Unique Constraints



The database should enforce uniqueness where appropriate.



At minimum:



```text

tool.slug

category.slug

```



should be unique within their respective namespaces.



The application should also validate uniqueness before saving.



Database constraints remain the final protection against duplicates.



---



## 17. Slug Rules



Tool and category slugs should be:



* URL-safe

* deterministic

* unique

* suitable for public routes



The implementation should reuse existing Favorite CMS slug-generation utilities if available.



Do not create a duplicate slug utility without a repository-specific reason.



---



## 18. Display Order



If the UI requires manual ordering, tools and categories may contain:



```text

display\_order

```



The ordering system should remain simple.



It must not be confused with:



* usage priority

* user ranking

* popularity tracking

* usage counters



No usage analytics are required by this system.



---



## 19. Timestamps



Persistent entities should use the timestamp conventions already used by Favorite CMS.



Where the CMS expects:



```text

created\_at

updated\_at

```



the plugin should follow that convention.



Do not introduce a different timestamp format.



---



## 20. Migration System



All plugin-owned database schema changes must be delivered through the existing Favorite CMS migration system.



The AI agent must first inspect:



```text

database/migrations/

```



and identify:



* migration naming convention

* migration class structure

* execution mechanism

* rollback behavior

* table naming conventions

* schema builder/query style



Then implement plugin migrations accordingly.



---



## 21. Migration Isolation



Plugin migrations must create or modify only plugin-owned database objects.



A migration must not:



* modify unrelated CMS tables

* modify authentication tables

* modify membership tables

* alter existing core schema

* rewrite CMS indexes

* modify unrelated plugin tables



unless a documented extension point explicitly requires it.



---



## 22. Migration Ordering



Plugin migrations must have deterministic ordering according to the existing CMS migration mechanism.



Dependencies should be clear.



For example:



```text

Category table

      ↓

Tool table

      ↓

Python service relationship/configuration

```



The actual ordering must follow the repository's migration conventions.



---



## 23. Initial Installation



When the plugin is installed/enabled, its required database migrations should be executed through the existing CMS/plugin installation mechanism.



Do not create a second installer that bypasses the CMS migration system.



---



## 24. Migration Rollback



Where the existing CMS migration system supports rollback, plugin migrations should provide appropriate rollback behavior.



Rollback should only affect plugin-owned schema.



Example:



```text

favorite\_web\_tools

favorite\_web\_tool\_categories

favorite\_web\_tool\_python\_services

```



No CMS core tables should be removed by plugin rollback.



---



## 25. Migration Safety



Before executing a migration, the implementation should follow the CMS's existing migration safety conventions.



Do not use destructive operations unnecessarily.



Schema changes should be incremental.



For production changes:



```text

Existing Data

    ↓

Safe Migration

    ↓

New Schema

```



Do not require users to manually delete plugin data unless explicitly documented.



---



## 26. Seed Data



The plugin may provide initial seed/default data for:



* initial categories

* example tools

* required system configuration



Seed data must be designed so that installation is repeatable.



The exact seed strategy must follow Favorite CMS conventions.



---



## 27. Initial Categories



If the product requires default categories, initial categories may include:



```text

HTML

CSS

JavaScript

Developer

Text

PHP

Python

```



These are initial defaults.



Administrators must be able to add additional categories later.



---



## 28. Example Tools



The plugin may optionally provide example/default tools during development.



Possible examples:



```text

HTML Formatter

HTML Minifier

HTML Validator



CSS Formatter

CSS Minifier

CSS Prefixer

CSS Color Converter



JavaScript Formatter

JavaScript Minifier



JSON Formatter

JSON Validator

JSON Beautifier



Base64 Encoder/Decoder

URL Encoder/Decoder

HTML Encoder/Decoder

UUID Generator

Hash Generator

Regex Tester

Timestamp Converter

Lorem Ipsum Generator

```



The final production seed list must be decided separately.



Do not make demo tools mandatory if the product does not require them.



---



## 29. Seed Idempotency



Running seed/setup logic multiple times must not create duplicate:



* categories

* tools

* services



Where default records are identified by stable slugs/keys, the seed logic should check for existing records before insertion.



---



## 30. Existing Data Protection



If an administrator has already modified a default category/tool, subsequent plugin updates must not blindly overwrite the administrator's configuration.



Seed/update behavior must distinguish:



```text

Initial Installation

```



from:



```text

Plugin Upgrade

```



Do not reset user configuration during normal upgrades.



---



## 31. Configuration Versioning



If tool configuration structure changes between plugin versions, the implementation may use a plugin configuration/schema version.



Example:



```text

configuration\_version

```



Only introduce this when it is actually required.



Do not add versioning fields without a migration/use case.



---



## 32. Database Queries



The plugin must use the database/query mechanisms already provided by Favorite CMS.



Queries must use:



* prepared statements

* parameter binding

* repository/query builder conventions

* existing model conventions



where supported.



Never concatenate untrusted user input directly into SQL.



---



## 33. Public Search Queries



Tool search should query only public-safe fields.



Search must not accidentally expose:



* private configuration

* Python service credentials

* internal notes

* internal file paths

* secret configuration



---



## 34. Database vs Files



The database should store metadata/configuration.



Generated or uploaded files should use the existing CMS/plugin file/storage mechanisms where appropriate.



Do not store large binary files directly in database fields unless the repository explicitly requires and supports that approach.



---



## 35. Database vs Code



The division should remain:



```text

Database

 ├── Tool metadata

 ├── Category metadata

 ├── Configuration

 └── Service references



Plugin Code

 ├── Engine handlers

 ├── API logic

 ├── Validation

 ├── Access control integration

 └── Rendering logic

```



Executable behavior belongs in controlled plugin code.



---



## 36. Database vs Environment Configuration



Environment-specific secrets and infrastructure configuration should remain outside public tool metadata.



Examples:



```text

Python API secret

Database credentials

External service credentials

Production-only secret

```



These should use the appropriate CMS/environment configuration mechanism.



---



## 37. Admin Data Management



The Admin Panel must use the same database layer as the public plugin.



Admin operations include:



* create tool

* update tool

* disable tool

* create category

* update category

* configure Python service

* update tool configuration



No separate admin database is permitted.



---



## 38. Delete vs Disable



Disabling a tool should normally be preferred over permanent deletion when the tool may be needed later.



Deletion should be available only when safe.



Before deletion, the system should consider:



* category relationships

* Python service dependencies

* existing references

* generated/configured data



---



## 39. Python Service Dependencies



Before deleting or disabling a Python service, the system should identify tools that depend on it.



Example:



```text

Python Service A

 ├── Tool 1

 ├── Tool 2

 └── Tool 3

```



The administrator should not accidentally break active tools without an appropriate warning/state transition.



---



## 40. Orphan Prevention



The system should avoid orphaned:



* tools

* categories

* Python service references

* configuration records



Database constraints and application-level validation should work together.



---



## 41. Database Indexing



Indexes should be added for fields that are actually used for:



* slug lookup

* category filtering

* status filtering

* engine filtering

* access filtering

* public discovery



Do not create excessive indexes without a query/use case.



---



## 42. Search Indexing



If conventional database text search is used, the appropriate indexes may be added based on the actual database/search implementation.



The initial implementation does not require an external search engine.



---



## 43. Caching and Database State



If tool/category metadata is cached:



* cache invalidation must occur after relevant admin changes

* public metadata may be cached

* access authorization must not depend on stale cached membership state



Database remains the authoritative persistence layer.



---



## 44. Transactions



Where multiple related database operations must succeed together, the plugin should use the existing CMS transaction mechanism.



Examples:



```text

Create category + related configuration

Create tool + required relationships

Update tool + dependent configuration

```



Do not invent a separate transaction layer.



---



## 45. Error Handling



Database failures must produce controlled application errors.



Do not expose:



* SQL statements

* database credentials

* stack traces

* internal table details unnecessarily



Technical details may be written to the existing server/application logs where appropriate.



---



## 46. Plugin Uninstallation



If Favorite CMS supports plugin uninstall lifecycle hooks, the plugin may provide an uninstall process.



The uninstall behavior must be explicitly defined before implementation.



Potentially destructive database deletion must not happen automatically unless that behavior is consistent with the CMS plugin system and explicitly intended.



---



## 47. Upgrade Compatibility



Plugin upgrades must preserve:



* existing tools

* existing categories

* tool configuration

* access modes

* Python service references

* administrator changes



Schema changes must be delivered through migrations.



Do not overwrite production data using seed scripts.



---



## 48. No Usage Tables



The database design must not create tables such as:



```text

tool\_usage

tool\_credits

tool\_tokens

tool\_quotas

daily\_usage

monthly\_usage

```



unless a future product requirement explicitly changes the project scope.



Current scope has no usage limitation system.



---



## 49. No Analytics Requirement



The initial database system does not require:



* user usage analytics

* tool popularity counters

* execution counts

* ranking scores

* per-user usage history



Existing CMS operational logging may still be used for technical debugging.



---



## 50. Repository Compatibility



Before implementation, the AI agent must inspect the actual repository for:



* migration classes

* schema builder

* database connection

* model/repository patterns

* plugin installation lifecycle

* plugin uninstall lifecycle

* seed conventions

* transaction helpers

* query helpers

* foreign-key conventions

* naming conventions

* timestamp conventions



The agent must adapt this specification to the repository.



---



## 51. Filesystem Isolation



Plugin migration/code changes must remain inside the appropriate plugin-owned scope.



Conceptually:



```text

Favorite-CMS-Universal/plugins/favorite-web-tools/

```



Plugin migrations must be placed according to the repository's actual plugin migration convention.



Assets remain under:



```text

Favorite-CMS-Assets/plugin-assets/favorite-web-tools/

```



Do not modify unrelated CMS files.



---



## 52. Implementation Rules for AI Agent



The AI agent must:



1. Read this specification before implementation.

2. Inspect the actual database/migration architecture first.

3. Reuse the existing migration system.

4. Reuse the existing database abstraction.

5. Create only plugin-owned tables.

6. Use repository naming conventions.

7. Add appropriate unique constraints.

8. Add appropriate foreign-key relationships where supported.

9. Keep tool/category/access data separate conceptually.

10. Store tool configuration as structured data where appropriate.

11. Never store arbitrary executable source for dynamic evaluation.

12. Keep credentials server-side.

13. Avoid storing large binary data in the database unnecessarily.

14. Make default seed data idempotent.

15. Protect existing administrator configuration during upgrades.

16. Support safe migration/upgrade behavior.

17. Avoid usage-limit tables and fields.

18. Reuse existing transactions and query mechanisms.

19. Keep plugin data isolated.

20. Test fresh installation.

21. Test migration execution.

22. Test rollback where supported.

23. Test seed behavior.

24. Test upgrade behavior with existing data.

25. Test duplicate slug prevention.

26. Test category/tool relationship integrity.

27. Test Python service dependency handling.

28. Report repository conflicts before changing architecture.



---



## 53. Acceptance Criteria



This specification is complete when:



* Plugin-owned database tables are clearly defined.

* Tool data is persisted correctly.

* Category data is persisted correctly.

* Python service configuration/references are persisted correctly.

* Tool configuration can be stored structurally.

* Access mode supports exactly the three required modes.

* Tool status supports ACTIVE/DRAFT/DISABLED.

* Tool and category slugs are unique.

* Category relationships remain valid.

* Migrations use the existing Favorite CMS migration system.

* Plugin migrations do not modify unrelated CMS schema.

* Rollback works where the CMS supports it.

* Default seed data is repeatable/idempotent.

* Default data does not overwrite administrator changes during upgrades.

* Database queries use the existing safe query mechanisms.

* Public search cannot expose private configuration.

* Credentials are not exposed through database-backed public metadata.

* File data uses appropriate storage mechanisms.

* Transactions use existing CMS infrastructure.

* Tool/category/service deletion is handled safely.

* Python service dependencies are detectable.

* No usage/credit/quota/token tables are introduced.

* No separate database or ORM is created.

* Plugin database changes remain isolated.




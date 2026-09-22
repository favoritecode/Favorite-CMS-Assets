# 32 — INSTALLATION, MIGRATION AND DEPLOYMENT



## 1. Purpose



This document defines the installation, activation, migration, upgrade, rollback, deployment, and operational requirements for the `favorite-web-tools` plugin.



The goal is to ensure that Favorite Web Tools can be installed and deployed safely inside Favorite CMS without modifying or breaking unrelated CMS functionality.



---



# 2. Core Principle



Favorite Web Tools is a plugin of Favorite CMS.



Installation and deployment must use the existing Favorite CMS architecture.



Do not create:



* A separate application installer.

* A separate database installer.

* A separate migration framework.

* A separate authentication system.

* A separate routing system.

* A separate deployment framework.



---



# 3. Repository-First Rule



Before implementation, the AI agent must inspect the actual repository and identify:



* Plugin installation mechanism.

* Plugin activation mechanism.

* Plugin deactivation mechanism.

* Plugin discovery/loading.

* Migration system.

* Seed conventions.

* Configuration system.

* Asset loading.

* Cache system.

* Environment configuration.

* Test commands.

* Existing deployment documentation.



The actual repository conventions take precedence over conceptual examples in this document.



---



# 4. Plugin Identity



Plugin:



```text

Name:

Favorite Web Tools



Slug:

favorite-web-tools

```



The slug must remain stable after release.



---



# 5. Installation Model



The plugin should be installable through the Favorite CMS plugin architecture.



Conceptually:



```text

Plugin Files

     ↓

Favorite CMS

     ↓

Plugin Discovery

     ↓

Plugin Registration

     ↓

Database Migration

     ↓

Default Catalog Seed

     ↓

Plugin Ready

```



---



# 6. Plugin Files



All plugin-specific runtime files must remain inside the designated plugin directory.



Conceptually:



```text

Favorite-CMS-Universal/

└── plugins/

    └── favorite-web-tools/

```



The exact internal structure must follow existing plugin conventions.



---



# 7. Plugin Assets



Plugin-specific assets must remain within the plugin's designated asset structure according to the existing Favorite CMS/Favorite-CMS-Assets architecture.



Do not duplicate assets unnecessarily.



---



# 8. CMS Core Isolation



Installation must not modify CMS core files unless an existing documented plugin integration point explicitly requires it.



Do not directly modify:



```text

Core application files

bootstrap.php

Existing authentication implementation

Existing database abstraction

Existing router

Existing theme

Existing unrelated plugins

```



---



# 9. Plugin Activation



Activation should register the plugin with Favorite CMS.



Activation may perform lightweight setup required by the existing plugin lifecycle.



Database schema changes must use the CMS migration system where applicable.



---



# 10. Database Migration



All Web Tools database schema must be managed through the existing CMS migration architecture.



Conceptual plugin-owned tables include:



```text

favorite\_web\_tools

favorite\_web\_tool\_categories

favorite\_web\_tool\_python\_services

```



Actual table naming must follow repository conventions.



---



# 11. Migration Ownership



Web Tools migrations may only create/modify plugin-owned database structures.



They must not unexpectedly modify:



* CMS core tables.

* User tables.

* Authentication tables.

* Membership tables.

* Theme tables.

* Other plugin tables.



---



# 12. Migration Ordering



Web Tools migrations must follow the existing CMS migration ordering system.



Do not invent a second migration numbering or execution mechanism.



---



# 13. Fresh Installation



A fresh installation should follow:



```text

Install CMS

    ↓

Install Favorite Web Tools

    ↓

Run CMS Migrations

    ↓

Create Web Tools Tables

    ↓

Seed Default Categories

    ↓

Seed Default Tools

    ↓

Plugin Ready

```



---



# 14. Default Category Seed



The initial categories defined by:



`30-DEFAULT-TOOL-CATALOG.md`



may be seeded during installation according to the existing CMS seed conventions.



Initial categories include:



```text

HTML

CSS

JavaScript

Developer

Text

PHP

Python

```



Only categories required by the implemented initial catalog should be seeded.



---



# 15. Default Tool Seed



Default tools should be seeded only when their implementation and configuration are available.



Do not create fake ACTIVE tools.



If an implementation is incomplete, use:



```text

DRAFT

```



or omit the tool until it is ready, according to the seed strategy.



---



# 16. Idempotent Seed



Running the seed process multiple times must not create duplicate:



* Categories.

* Tools.

* Slugs.

* Service definitions.



Use stable identifiers/slugs and existing CMS seed conventions.



---



# 17. Existing Configuration Protection



Upgrade or re-seeding must not silently overwrite administrator changes.



For example, if an administrator changes:



```text

Access Mode

Description

Input Configuration

Output Configuration

Frontend Configuration

Status

```



an upgrade must preserve those changes unless an explicit migration is required.



---



# 18. Plugin Configuration



Global plugin configuration must use the existing Favorite CMS configuration system.



Do not create a parallel `.env` parser/configuration framework.



Environment-specific secrets should use the existing secure configuration mechanism.



---



# 19. Python Service Configuration



Python service credentials and sensitive configuration must remain server-side.



They must not be stored in:



* Public HTML.

* Frontend JavaScript.

* Public API metadata.

* Browser storage.



---



# 20. Environment Configuration



Where required, support the environments used by Favorite CMS, such as:



```text

Development

Testing

Production

```



Exact environment handling must follow the repository.



---



# 21. Development Installation



Development setup should allow the AI agent/developer to:



1. Install the plugin.

2. Run migrations.

3. Seed catalog.

4. Open the admin panel.

5. Configure tools.

6. Test tools.

7. Run automated tests.

8. Test frontend pages.

9. Test theme integration.



---



# 22. Testing Installation



A test installation should use isolated test data/environment where supported.



Do not use production credentials or production-sensitive data.



---



# 23. Production Installation



Production installation must follow the existing Favorite CMS deployment process.



Conceptually:



```text

Backup

 ↓

Deploy Plugin Files

 ↓

Verify Dependencies

 ↓

Run Migration

 ↓

Verify Configuration

 ↓

Seed Missing Defaults

 ↓

Activate Plugin

 ↓

Run Smoke Tests

```



---



# 24. Pre-Deployment Backup



Before production database/schema changes, follow the site's existing backup procedure.



At minimum, database backup should be considered before migrations that modify production schema.



Do not create a new backup system unless the CMS/deployment environment requires it.



---



# 25. Dependency Check



Before activation, verify required dependencies.



Potential dependencies include:



* Favorite CMS plugin system.

* Existing authentication.

* Existing membership system when protected tools are configured.

* Existing database system.

* Existing theme system.

* Favorite API Connector where explicitly integrated.

* Python API service where Python tools are enabled.



---



# 26. Optional Dependency Behavior



If an optional dependency is unavailable:



* Unrelated Web Tools should continue working.

* Dependent tools should not falsely become ACTIVE.

* The system should show a clear admin warning.



---



# 27. Python Service Dependency



A Python tool may depend on a configured Python service.



If the service is:



```text

Missing

Disabled

Invalid

Unreachable

```



the dependent tool must not execute successfully as if the service were available.



---



# 28. Favorite API Connector Dependency



If a tool uses Favorite API Connector:



* Verify the connector is installed/configured.

* Verify the required integration exists.

* Do not duplicate API connector functionality inside Web Tools.

* Do not activate a dependent tool if its required integration is unavailable.



---



# 29. Membership Dependency



`MEMBERSHIP\_REQUIRED` tools must use the existing membership system.



The Web Tools plugin must not create its own membership implementation.



---



# 30. Activation Validation



Before activating the plugin or making a tool public, verify:



```text

Registry

Database

Engine

Access Control

Execution API

Frontend

Configuration

Dependencies

```



---



# 31. Tool Activation



Individual tools must follow the lifecycle defined in:



`15-TOOL-LIFECYCLE-AND-VALIDATION.md`



Only validated tools may become:



```text

ACTIVE

```



---



# 32. Plugin Deactivation



Deactivating the plugin should prevent normal Web Tools functionality from being available according to the CMS plugin lifecycle.



Deactivation must not automatically destroy the plugin's database data unless the CMS explicitly defines such behavior.



---



# 33. Data Preservation on Deactivation



Plugin deactivation should normally preserve:



* Tools.

* Categories.

* Python services.

* Configuration.



This allows later reactivation.



---



# 34. Plugin Uninstallation



If Favorite CMS supports an explicit uninstall lifecycle, Web Tools should follow it.



Uninstallation behavior must be clearly defined before implementation.



Do not automatically perform destructive database deletion merely because the plugin is deactivated.



---



# 35. Destructive Uninstall



If database deletion is supported, it should require an explicit uninstall operation according to CMS conventions.



It must not occur automatically during:



* Deactivation.

* Upgrade.

* Migration.

* Normal deployment.



---



# 36. Upgrade Model



Plugin upgrades should be incremental.



Conceptually:



```text

Version N

   ↓

Migration

   ↓

Version N+1

```



Each schema change should have an appropriate migration.



---



# 37. Upgrade Safety



An upgrade must preserve:



* Existing tools.

* Existing categories.

* Existing administrator configuration.

* Existing access settings.

* Existing frontend configuration.

* Existing Python service definitions.



Unless a documented migration specifically changes them.



---



# 38. New Default Tools During Upgrade



If a future release adds default tools:



* Add missing tools according to seed rules.

* Do not overwrite existing tools.

* Do not reset administrator changes.

* Do not silently change access modes.



---



# 39. Existing Tool Changes



If a future release modifies an existing tool:



* Provide an explicit migration when required.

* Validate the resulting configuration.

* Preserve administrator customization where possible.

* Do not silently replace active configuration with invalid configuration.



---



# 40. Migration Failure



If a migration fails:



```text

Migration Failure

      ↓

Stop/rollback according to CMS migration behavior

      ↓

Do Not Mark Migration Complete

      ↓

Report Error

```



Do not continue as though the migration succeeded.



---



# 41. Rollback



Rollback must use the existing CMS migration rollback mechanism if available.



Do not invent a second rollback framework.



---



# 42. Rollback Safety



Before rollback:



* Understand the migration's data impact.

* Follow existing CMS conventions.

* Protect administrator-created data where possible.

* Do not silently delete unrelated data.



---



# 43. Code Deployment



Deploy plugin code using the existing project workflow.



Possible workflow:



```text

Development

 ↓

Git Commit

 ↓

Git Push

 ↓

Production Pull/Deploy

 ↓

Migration

 ↓

Verification

```



The exact process depends on the actual hosting/deployment environment.



---



# 44. Git Requirements



Plugin implementation should be version-controlled.



Before deployment, verify:



```text

Working Tree

Changed Files

Migration Files

Plugin Files

Assets

Tests

Configuration Changes

```



---



# 45. Git Isolation



Before commit, confirm that unrelated CMS files have not been modified unintentionally.



A deployment commit should not accidentally include unrelated:



* CMS core changes.

* Other plugin changes.

* Theme changes.

* Temporary files.

* Secrets.

* Local environment files.



---



# 46. Secret Protection



Never commit:



```text

API Keys

Passwords

Bearer Tokens

Private Keys

Python Service Credentials

Production Secrets

```



Use the repository's existing environment/secret mechanism.



---



# 47. Dependency Installation



If the plugin introduces dependencies, they must follow the existing CMS dependency management system.



Do not create an isolated dependency manager inside the plugin without a real requirement.



---



# 48. Composer Dependencies



If PHP dependencies are required:



* Inspect the existing `composer.json`.

* Follow existing Composer conventions.

* Add only necessary dependencies.

* Avoid duplicate libraries already present in the CMS.



---



# 49. Frontend Dependencies



If JavaScript/CSS dependencies are required:



* Inspect existing frontend build/dependency conventions.

* Reuse existing dependencies where possible.

* Avoid shipping unnecessary libraries.



---



# 50. Python Dependencies



Python service dependencies belong to the Python service environment, not automatically to the CMS plugin.



Favorite Web Tools communicates with Python through the configured API boundary.



---



# 51. Asset Deployment



Ensure required:



* CSS.

* JavaScript.

* Icons.

* Frontend assets.

* Admin assets.



are deployed according to the CMS asset architecture.



---



# 52. Cache Invalidation



After deployment, use the existing CMS cache/asset cache invalidation mechanism if required.



Do not create a second cache system.



---



# 53. Configuration Cache



If the CMS caches configuration:



* Clear/rebuild it using the existing mechanism after deployment.

* Ensure new plugin configuration becomes available.



---



# 54. Route Cache



If the CMS caches routes:



* Rebuild/invalidate using the existing CMS mechanism.

* Verify Web Tools routes afterward.



---



# 55. Database Cache



If applicable, follow existing CMS database/cache conventions.



Do not cache authorization decisions in a way that bypasses current access state.



---



# 56. Production Smoke Test



Immediately after deployment, test:



```text

Plugin Loads

Admin Opens

Tools Catalog Opens

Search Works

Tool Page Opens

FREE Tool Executes

Protected Tool Access Works

Result Renders

Download Works Where Applicable

Theme Header Works

Theme Footer Works

Dark Mode Works

Light Mode Works

```



---



# 57. Protected Tool Smoke Test



Verify:



```text

Anonymous

 ↓

Protected Tool

 ↓

Blocked



Logged-in User

 ↓

LOGIN\_REQUIRED

 ↓

Allowed



Active Member

 ↓

MEMBERSHIP\_REQUIRED

 ↓

Allowed

```



---



# 58. Python Production Smoke Test



Only if a Python service is configured:



```text

Web Tool

 ↓

Execution API

 ↓

Python Service

 ↓

Result

```



Verify:



* Connection.

* Authentication.

* Response.

* Error handling.

* Timeout behavior.



---



# 59. Production Error Monitoring



Use the existing CMS/application logging and monitoring mechanisms.



Monitor for:



* Plugin boot failures.

* Migration errors.

* Execution errors.

* Python service errors.

* Database errors.

* Frontend/API failures.



Do not introduce usage analytics as part of this requirement.



---



# 60. Technical Logging



Technical logs may record:



* Error type.

* Request identifier where available.

* Tool identifier.

* Engine identifier.

* Service failure.

* Processing failure.



Avoid sensitive payloads.



---



# 61. No Usage Tracking



Deployment and operational monitoring must not introduce:



* Usage counters.

* User quotas.

* Credits.

* Token accounting.

* Daily usage limits.

* Monthly usage limits.



---



# 62. Health Verification



Where the CMS supports application health checks, Web Tools may expose appropriate technical health information.



Do not create a public health endpoint that exposes:



* Credentials.

* Internal URLs.

* Configuration.

* Infrastructure secrets.



---



# 63. Python Health Verification



A Python service may have an admin-side connection test.



Do not assume every Python service exposes a `/health` endpoint.



Use the configured service/test mechanism.



---



# 64. Maintenance Mode



If the existing CMS supports maintenance mode, use it during deployment when appropriate.



Do not create a plugin-specific maintenance mode.



---



# 65. Deployment Failure



If deployment fails:



```text

Detect Failure

 ↓

Stop Further Risky Changes

 ↓

Review Logs

 ↓

Restore/rollback according to deployment process

 ↓

Verify CMS

 ↓

Verify Web Tools

```



---



# 66. CMS Regression Check



After deployment, verify that unrelated CMS functionality remains operational.



At minimum check:



* Login.

* Admin access.

* Existing theme.

* Existing plugins.

* Existing routes.

* Database access.



Use the project's normal smoke/regression tests where available.



---



# 67. Plugin Isolation Verification



After deployment, inspect changed files and database changes.



Confirm that Web Tools has not unintentionally modified unrelated systems.



---



# 68. Production Database Verification



Verify plugin-owned tables exist and contain expected data.



Check:



```text

Tools

Categories

Python Services

```



where applicable.



Do not expose database credentials in verification output.



---



# 69. Production Catalog Verification



Verify:



* Expected default categories exist.

* Expected default tools exist.

* No duplicates exist.

* Only implemented tools are ACTIVE.

* Slugs are correct.

* Access modes are correct.



---



# 70. Deployment Version



The plugin should expose its version according to the existing CMS plugin metadata/versioning convention.



Do not create a separate version-management system.



---



# 71. Compatibility



Before production deployment, verify compatibility with:



* Supported Favorite CMS version.

* Supported PHP version.

* Required database version.

* Existing theme architecture.

* Required plugin dependencies.



Exact supported versions must be derived from the actual repository/environment.



---



# 72. Deployment Documentation



The implementation should provide concise documentation for:



* Installation.

* Activation.

* Migration.

* Upgrade.

* Python service configuration.

* Optional API Connector integration.

* Troubleshooting.

* Uninstallation.



Documentation should follow repository conventions.



---



# 73. Troubleshooting



Common operational issues should have clear diagnostics.



Examples:



```text

Plugin Not Loading

Migration Failed

Tool Not Appearing

Tool Cannot Execute

Membership Check Fails

Python Service Unavailable

Download Fails

Theme Styling Incorrect

```



Diagnostics must not reveal secrets.



---



# 74. Production Security Verification



Before final deployment verify:



```text

□ No secrets committed

□ No debug mode enabled unintentionally

□ No arbitrary PHP execution

□ No arbitrary Python execution

□ No shell execution

□ No exposed credentials

□ CSRF protection active

□ Access control active

□ File validation active

□ Download authorization active

□ Preview isolation active

□ Error messages sanitized

```



---



# 75. Production Performance Verification



Verify:



* Tool catalog loads acceptably.

* Search performs acceptably.

* Tool pages load without unnecessary assets.

* Execution API handles expected requests.

* Large results do not unnecessarily freeze the page.

* Python API calls respect configured technical timeouts.



---



# 76. Deployment Acceptance Gate



Production deployment is complete only when:



```text

Installation PASS

Migration PASS

Catalog PASS

Execution PASS

Access Control PASS

Frontend PASS

Theme PASS

Security PASS

Regression PASS

Smoke Test PASS

```



---



# 77. AI Agent Deployment Rules



The AI agent must:



1. Inspect repository deployment conventions first.

2. Reuse the CMS plugin lifecycle.

3. Reuse the CMS migration system.

4. Reuse the CMS configuration system.

5. Reuse the existing dependency system.

6. Preserve CMS core.

7. Preserve unrelated plugins.

8. Preserve active themes.

9. Protect existing administrator configuration.

10. Keep migrations idempotent where appropriate.

11. Never commit secrets.

12. Never create arbitrary execution infrastructure.

13. Never create a second authentication system.

14. Never create a second database system.

15. Never create a second router.

16. Never create a second migration system.

17. Never introduce product usage quotas.

18. Verify production dependencies.

19. Run tests before deployment.

20. Run smoke tests after deployment.

21. Report migration/deployment failures honestly.

22. Never mark an incomplete deployment as successful.



---



# 78. Final Installation Model



```text

                 FAVORITE CMS

                       │

                       ▼

              Plugin Discovery

                       │

                       ▼

             Favorite Web Tools

                       │

          ┌────────────┼────────────┐

          ▼            ▼            ▼

      Migration      Config       Assets

          │            │            │

          └────────────┼────────────┘

                       ▼

                 Tool Registry

                       │

                       ▼

                Default Catalog

                       │

                       ▼

                  Admin Setup

                       │

                       ▼

                Validation/Test

                       │

                       ▼

                    ACTIVE

                       │

                       ▼

                  Production

```



---



# 79. Final Upgrade Model



```text

Existing Production

        │

        ▼

Backup / Safety Check

        │

        ▼

Deploy New Plugin Version

        │

        ▼

Run CMS Migration

        │

        ▼

Seed Missing Defaults

        │

        ▼

Preserve Existing Config

        │

        ▼

Run Tests

        │

        ▼

Smoke Test

        │

        ▼

Production Ready

```



---



# 80. Final Principle



**Favorite Web Tools must behave like a native Favorite CMS plugin from installation through production deployment. The plugin owns only its own functionality, schema, configuration, assets, and runtime code. Favorite CMS remains authoritative for plugin lifecycle, routing, authentication, membership, database migrations, configuration, themes, assets, and deployment conventions. Upgrades must preserve administrator configuration, migrations must be safe, secrets must remain server-side, and every production deployment must pass testing and smoke verification before being considered complete.**




---

# Distribution Artifact Integrity Addendum

The release package should have one plugin root directory:

```text
favorite-web-tools/
```

Do not create double nesting such as `favorite-web-tools/favorite-web-tools/`.

For every release candidate:

1. Build the final ZIP once.
2. Record its file count, byte size, and SHA-256.
3. Install/update using that exact ZIP, not only the working source tree.
4. Clear relevant PHP/opcode/application/proxy caches as appropriate to the deployment environment.
5. Verify critical flows in a real browser.
6. Publish the exact verified bytes.
7. Preserve immutable versioned artifacts for prior releases.

Do not silently overwrite an already published versioned ZIP/tag. If code changes after a published release, increment the plugin version according to the repository's existing release convention.

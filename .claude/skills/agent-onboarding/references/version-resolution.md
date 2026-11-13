# Version Resolution Policy

> Authoritative reference for Syncfusion version matching. Read this before installing,
> upgrading, downgrading, or generating any Syncfusion code.

## The rule

The version rule differs by package manager. Do not apply npm's major-version rule to NuGet or Flutter.

| Package manager | Platforms | Version rule |
| --- | --- | --- |
| npm | React, Angular, Vue, JavaScript | All packages must share the same **major version**. Minor and patch may differ. |
| NuGet | Blazor, ASP.NET Core, ASP.NET MVC, MAUI, WPF, WinForms, WinUI | All packages must be at the **exact same version** (e.g. `27.1.48`, not just major `27`). |
| pub.dev | Flutter | All packages must use the **exact same version constraint** (e.g. `^27.1.48`). |

Mixing versions within a project causes licensing validation failures, package incompatibilities, and runtime issues.

## Always run the package manager

The package manager is the only thing that installs a package. It reads the registry, resolves
peer dependencies (e.g. `react` peer of `@syncfusion/ej2-react-grids`), updates the lockfile, and
populates `node_modules` (or the equivalent). None of that happens when the manifest is
hand-edited.

When you need a Syncfusion package installed, upgraded, downgraded, or removed:

| Platform | Manager | Use this command — never edit the manifest file directly |
| --- | --- | --- |
| React / Angular / Vue / JavaScript | npm | `npm install <pkg>@<selector>` / `npm uninstall <pkg>` |
| React / Angular / Vue / JavaScript | pnpm | `pnpm add <pkg>@<selector>` / `pnpm remove <pkg>` |
| React / Angular / Vue / JavaScript | yarn (classic) | `yarn add <pkg>@<selector>` / `yarn remove <pkg>` |
| Blazor / ASP.NET Core / ASP.NET MVC / MAUI / WPF / WinForms / WinUI | NuGet | `dotnet add package <pkg> --version <exact>` / `dotnet remove package <pkg>` |
| Flutter | pub.dev | `flutter pub add <pkg>:<constraint>` / `flutter pub remove <pkg>` |

If you cannot run the package manager in this environment (no network, sandboxed editor, approval
denied), stop and surface that to the human rather than editing the manifest on their behalf.
Hand-editing a manifest is a corrupt install that the next manager run will silently undo.

For npm the manifest file (`package.json`) is for the agent to read, not to write. For NuGet it
is the `.csproj`; for Flutter, `pubspec.yaml`. The manager is the writer; the agent is a reader.

Before installing any new Syncfusion package:

1. Inspect the project manifest (`package.json`, `.csproj`, or equivalent).
2. Identify all existing Syncfusion packages. If the project manifest cannot be read or parsed, stop and report the issue. Do not guess package versions.
3. For npm packages, extract the effective major version number from each Syncfusion dependency declaration regardless of version syntax (^, ~, >=, explicit version, etc.).
4. For NuGet packages, extract the exact version.
5. For Flutter packages, extract the exact version constraint.
6. Verify consistency:
    - npm: all packages share the same major version.
    - NuGet: all packages share the same exact version.
    - Flutter: all packages share the same exact version constraint.
7. If the detected versions are not consistent, stop immediately. Do not install, upgrade, downgrade, or generate Syncfusion code until the user chooses a version strategy.

## No existing Syncfusion packages

If no Syncfusion packages are present:

- Follow the installation guidance provided by the selected component skill.
- Determine the package version using the version-selection rules defined by this document.
- If the component skill explicitly recommends a version, use that version only when it does not conflict with this policy.

## Existing Syncfusion packages found

The version-matching strategy differs by package manager. Identify the platform first, then follow
the rule for that package manager.

| Platform family | Manifest | Package manager | Version rule |
| --- | --- | --- | --- |
| React, Angular, Vue, JavaScript | `package.json` | npm | Match the shared major version |
| Blazor, ASP.NET Core, ASP.NET MVC, MAUI, WPF, WinForms, WinUI | `.csproj` | NuGet (`dotnet add package`) | Match the exact version of existing packages |
| Flutter | `pubspec.yaml` | pub.dev (`flutter pub add`) | Match the exact version constraint of existing packages |

For NuGet and Flutter:

- NuGet: If existing Syncfusion packages differ in their exact version (for example, `27.1.48` and `27.2.3` in the same `.csproj`), stop and report the inconsistency.
- Flutter: If existing Syncfusion packages differ in their exact version constraint (for example, `^27.1.48` and `^27.2.3` in the same `pubspec.yaml`), stop and report the inconsistency.

Do not pick a version arbitrarily. Do not install, upgrade, downgrade, or perform version-alignment changes. Report the inconsistency and wait for the user to choose a version strategy.

## Fresh version gate

The Fresh Version Gate is mandatory and must run immediately before every new installation, installation, upgrade, downgrade, migration, or Syncfusion code-generation change. Re-read the selected project manifest and applicable lockfile. Do not reuse a version check
from an earlier step or conversation. If the manifest or lockfile changed since the last check,
invalidate the previous result and perform the version check again. If the manifest and lockfile disagree, report the mismatch and stop. Do not infer which one is correct.

If the project version cannot be determined unambiguously from the manifest and lockfile, stop and report the issue. Do not guess a version and do not install any Syncfusion package.

If Syncfusion packages are already present:

- Determine the project's required version using the rule for its package manager (see "The rule" table above). Do not default to major-version matching — that rule applies to npm only.
- If the discovered packages do not satisfy their package-manager rule (shared major for npm; shared exact version for NuGet; shared exact constraint for Flutter), fail closed and report the inconsistency instead of selecting a version arbitrarily.
- **npm only:** 
    1. Determine the project's shared major version.
    2. If the user explicitly requests a Syncfusion version:
        - If the requested major version matches the project's shared major version, install the exact version requested by the user.
        - If the requested major version differs from the project's shared major version, report a conflict and stop.
    3. If the user does not specify a version:
        - Install the latest published release available within the project's shared major version.
- **NuGet only:** All packages must be at the exact same version. Read the exact version from the existing `<PackageReference>` entries and use that version when installing the new package. See the NuGet section below.
- **Flutter only:** For Flutter, the version constraint string must match exactly, including any operators such as ^, >=, <=, ~>, or an explicit version. Read the exact constraint from existing `syncfusion_flutter_*` entries in `pubspec.yaml` and use that constraint when adding the new package. See the Flutter section below.

Example:

```text
Existing packages:
@syncfusion/ej2-react-grids      33.1.44
@syncfusion/ej2-react-buttons    33.2.7

Project major version = 33

New component:
@syncfusion/ej2-react-schedule

Install:
Latest available 33.x.x release
```

For JavaScript-family packages, the major-version selector can be used directly:

```bash
npm install @syncfusion/ej2-react-grids@33
```

## NuGet (.NET platforms)

Blazor, ASP.NET Core, ASP.NET MVC, MAUI, WPF, WinForms, WinUI.

Read the exact version from the existing `<PackageReference>` entries in `.csproj`. All Syncfusion
.NET packages in a project must be at the **exact same version**, not just the same major. They are
released and tested as a synchronized set.

Example:

```text
Existing packages in .csproj:
<PackageReference Include="Syncfusion.Blazor.Grid"    Version="27.1.48" />
<PackageReference Include="Syncfusion.Blazor.Themes"  Version="27.1.48" />

Project exact version = 27.1.48

New component:
Syncfusion.Blazor.Charts

Install:
dotnet add package Syncfusion.Blazor.Charts --version 27.1.48
```

Do not use `--version 27.*`. That leaves a floating range in the `.csproj`, which can silently pull
a different patch version on future restores and break the synchronized-set guarantee.

## Flutter (pub.dev)

Read the exact version constraint from existing `syncfusion_flutter_*` entries in `pubspec.yaml`.
Match that constraint exactly.

Example:

```text
Existing packages in pubspec.yaml:
syncfusion_flutter_charts: ^27.1.48
syncfusion_flutter_core:   ^27.1.48

Project version constraint = ^27.1.48

New component:
syncfusion_flutter_calendar

Install:
flutter pub add syncfusion_flutter_calendar:'^27.1.48'
```

## User-specified version

If the user explicitly requests a Syncfusion version, match it against the **package-manager rule**
in effect for the project — do not collapse all ecosystems to npm-style "same major".

1. Determine the project's Syncfusion versions from the existing installed packages.
2. Compare the requested version against the project's version strategy for that package manager:
   - **npm (React / Angular / Vue / JavaScript):** requested major must equal the project's shared
     major. Patch and minor differences within the same major are acceptable.
    If the user explicitly requests a version and its major version matches the
    project's shared major version, install the exact version requested by the user.
    If the user does not specify a version, install the latest published release
    available within the project's shared major version.
    If the requested major version differs from the project's shared major version,
    report a conflict and stop.
   - **NuGet (.NET — Blazor, ASP.NET Core, ASP.NET MVC, MAUI, WPF, WinForms, WinUI):** the project
     requires that all Syncfusion packages share the **exact same version**, not just the same
     major. If the requested version differs from the project's shared exact version (even within
     the same major, e.g. project on `27.1.48` and user asks for `27.2.3`), this is a conflict. Do not silently follow npm-style major-only logic. Do not install the new package, upgrade existing packages, downgrade existing packages, or perform any other version-alignment changes until the user explicitly chooses a version strategy.
   - **Flutter (pub.dev):** the project requires a single exact version constraint for all
     `syncfusion_flutter_*` packages in `pubspec.yaml`. A requested constraint that differs
     from the existing constraint (even within the same major, e.g. project on `^27.1.48` and
     user asks for `^27.2.3`) is a conflict. Do not install the new package, upgrade existing packages, downgrade existing packages, or perform any other version-alignment changes until the user explicitly chooses a version strategy.
3. If the requested version satisfies the version rule for the detected package manager, proceed using the standard installation process.

Do not install the package when a version conflict exists.

Instead, provide a version-difference report containing:

- Requested package name
- Requested version
- Detected package manager (npm, NuGet, or pub.dev)
- Project version — for npm: shared major.
- Project version — for NuGet: exact version.
- Project version — for Flutter: exact version constraint
- Existing Syncfusion packages and their current versions
- Every distinct version found and the package(s) that introduced it
- Explanation of the applicable version rule for the detected package manager
- Description of the detected conflict

After reporting the conflict, wait for human guidance before making any changes.

### npm example

Requested:

```text
@syncfusion/ej2-react-schedule@34.1.2
```

Project has:

```text
@syncfusion/ej2-react-grids      33.1.44
@syncfusion/ej2-react-buttons    33.2.7
```

Conflict: requested major (34) does not match project major (33). Stop and report.

### NuGet example

Requested:

```text
dotnet add package Syncfusion.Blazor.Charts --version 28.1.35
```

Project has:

```text
<PackageReference Include="Syncfusion.Blazor.Grid"   Version="27.1.48" />
<PackageReference Include="Syncfusion.Blazor.Themes" Version="27.1.48" />
```

Conflict: requested version (28.1.35) does not match project version (27.1.48). Stop and report.

### Flutter example

Requested:

```text
syncfusion_flutter_calendar: ^28.1.35
```

Project has:

```text
syncfusion_flutter_charts: ^27.1.48
syncfusion_flutter_core:   ^27.1.48
```

Conflict: requested constraint (^28.1.35) does not match project constraint (^27.1.48). Stop and report.
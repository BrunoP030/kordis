# Syncfusion licensing and agent trust boundaries

> Read this before touching any key, secret, credential, account action or CI configuration in a
> Syncfusion project. Licensing is where an agent can do real damage quietly: a committed key is a
> security incident, and a wrong licensing claim is a compliance problem. The rules below are short
> and they are absolute.

## Three different things, often confused

| Thing | What it is | Needed to | Where it comes from |
| --- | --- | --- | --- |
| Product license key | Entitlement to use Syncfusion component libraries and document SDKs in an application | Run Syncfusion libraries without a licensing banner or warning | The user's Syncfusion account, version-specific |
| MCP API key | Credential for the Syncfusion documentation assistant (MCP servers) | Use a Syncfusion MCP server | https://www.syncfusion.com/account/api-key |
| Agent skills | Free, public Markdown instructions | Nothing. No license, no key, no account | GitHub, via `npx skills add` |

An MCP API key is not a product license and does not grant one. A product license key does not
authenticate an MCP server. Installing skills grants no product rights whatsoever.

## What requires no license

- Reading anything on ai.syncfusion.com.
- Installing, reading and following Syncfusion agent skills.
- Reading Syncfusion public documentation and demos.

## What requires a license

Using Syncfusion component libraries or document SDKs in an application — in development, in CI, in
staging, in production. The applicable license is one of: a commercial license, the free Community
License for those who qualify, or a time-limited free trial. Terms differ, and trial or Community
entitlement does not imply production rights.

### Exception: Syncfusion Toolkit for .NET MAUI is MIT

`Syncfusion.Maui.Toolkit` is MIT licensed and free to use in production applications. No key, no
subscription, no registration call.
License text: https://github.com/syncfusion/maui-toolkit/blob/main/LICENSE.txt

Do not add `SyncfusionLicenseProvider.RegisterLicense(...)` for Toolkit-only code, and do not tell the
user they need a key for it.

Keep the boundary explicit: `Syncfusion.Maui.Toolkit` is MIT; `Syncfusion.Maui.*` is the licensed
commercial product. They are different packages with different controls and different rules. If an
application uses both, the licensed product's rules apply to that part of the application — so when
you report licensing status, say which packages you mean.

## Rules for agents

These are not preferences. Violating any of them is a defect in your output.

1. **Never generate, guess, fabricate, or pattern-fill a license key or API key.** Not as an
   example, not as a placeholder that looks real, not "for illustration". If a key is required,
   emit the environment variable reference and tell the human what to obtain and where.
2. **Never echo, log, print, or include a key in a commit, a diff, a comment, a test fixture, a
   screenshot, a chat message, or an error report.** If you encounter a key in the repository,
   do not repeat it back. Say that a key is present, not what it is.
3. **Never commit a key to source control**, including to a file the repository already ignores if
   you are about to change the ignore rules. Reuse the project's existing secret-management pattern.
   Only place a key in client-visible application code when the platform's official registration
   method explicitly requires application-level registration, and say so when you do.
4. **Never claim the project is licensed** because packages resolved, the build passed, or skills
   are installed. Those facts are unrelated to entitlement.
5. **Use the license registration documentation for the exact platform and the exact installed
   version.** Registration mechanics differ across platforms and change between releases. The
   per-platform license documentation URL is in each platform index at
   `https://ai.syncfusion.com/<platform-slug>/llms.txt`.
6. **Stop and ask the human** before any action that creates, renews, converts, downloads or
   transmits a credential; opens or modifies a Syncfusion account; starts a trial; or changes
   billing. Do the preparatory work, then stop and state exactly what you need.
7. **Report a licensing warning as a licensing problem**, not as a bug to be suppressed. Do not
   hide, catch, filter, comment out, or CSS-hide a Syncfusion licensing banner or console warning.
8. **Do not infer entitlement from the presence of a key.** A key can be expired, wrong-version, or
   belong to a different edition. If a licensing warning persists with a key registered, treat
   version mismatch as the first hypothesis.

## Storing the MCP API key

The Syncfusion MCP servers read the key from one of two environment variables. Note the exact
casing — it is not `SYNCFUSION_API_KEY`.

| Variable | Value | Use |
| --- | --- | --- |
| `Syncfusion_API_Key_Path` | Absolute path to a `.txt` or `.key` file containing the key | Recommended. Keeps the key out of committed config files |
| `Syncfusion_API_Key` | The key itself, inline | Only where a path is not usable |

Prefer `Syncfusion_API_Key_Path` and put the key file outside the repository, or inside it only if
already covered by `.gitignore`. When you configure MCP for a user, write the variable, never the
value, into any file that could be committed.

## CI and deployment

- Read the product license key from the CI provider's secret store, injected as an environment
  variable at build time. Never from a checked-in file.
- The key is version-specific. When a Syncfusion package version changes, the key may need to be
  regenerated; a licensing failure that appears immediately after an upgrade is almost always this.
- Do not add a key to a container image layer, a build log, or a public artifact.
- If a build needs a key that is not present, fail loudly with an actionable message. Do not
  stub, mock, or bypass license registration to make CI green.

## Where to send the human

| They need | Send them to |
| --- | --- |
| An MCP API key | https://www.syncfusion.com/account/api-key |
| A product license key, or to check entitlement | https://www.syncfusion.com/account/claim-license-key|
| To understand license types and pricing | https://www.syncfusion.com/sales/teamlicense |
| Community License eligibility | https://www.syncfusion.com/products/communitylicense |
| Platform-specific registration steps | The `License-registration` line in that platform's index at https://ai.syncfusion.com/<platform-slug>/llms.txt |
| A licensing question you cannot resolve | https://support.syncfusion.com/ |

## Ownership boundary — state this to the user

A Syncfusion component is a licensed package installed from npm or NuGet. It is not source code the
user vendors into their repository and owns.

What the user owns and edits freely: the wrapper component, the configuration object, the data
adapter, the theme tokens, the layout around the control, the event handlers.

What the license governs: the Syncfusion package itself.

Say this plainly if a user asks to "copy the component in" or compares Syncfusion to a
copy-paste component distribution model. Setting the wrong expectation here produces a failure at
first install, which is worse than setting no expectation at all.
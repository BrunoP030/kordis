# Verifying a Syncfusion implementation

Read this before telling a user that a Syncfusion implementation works.

## The rule

A clean compile is not verification. Syncfusion components fail at runtime for reasons a compiler
cannot see: a feature service that was never injected, a theme stylesheet that was never loaded, a
render mode that leaves the component static, a server endpoint that does not exist, a license that
was never registered. Every one of those builds successfully and then does not work.

Report what you verified, and say plainly what you could not.

## Checklist

Work through this before reporting success.

### Dependencies

- [ ] Every package the implementation imports is installed
- [ ] Syncfusion package versions are consistent with each other and with the rest of the project
- [ ] Peer dependencies resolved without warnings that matter

### Registration and wiring

- [ ] Every feature used has its module, service, provider, handler or tag helper registered
- [ ] The platform's mandatory initialiser is present — `AddSyncfusionBlazor()`,
      `ConfigureSyncfusionCore()`, `ConfigureSyncfusionToolkit()`, `@addTagHelper *, Syncfusion.EJ2`,
      `Grid.Inject(...)`, the Vue `provide` object, Angular feature providers
- [ ] Theme stylesheet loaded. An unstyled control almost always means a missing theme, not a defect
- [ ] Runtime assets that the component fetches at load are reachable — PDF Viewer `resourceUrl`,
      script managers, static web assets
- [ ] Any required server-side endpoint exists and responds — Document Editor, Spreadsheet, File
      Manager and server-backed PDF Viewer all need one

### Licensing

- [ ] License registration runs before the first Syncfusion component is created
- [ ] The key is read from configuration or the environment, never a literal
- [ ] No licensing banner or console warning appears at runtime
- [ ] No key, secret or credential appears anywhere in the diff
- [ ] No licensing warning has been suppressed, caught, filtered or CSS-hidden

### Behaviour

- [ ] The application builds
- [ ] The component renders, or the SDK call executes and produces output
- [ ] The requested feature is observably exercised — the grid actually paged, the chart drew the
      series, the export produced a file, the editor loaded a document
- [ ] Interaction works, not just initial render. A grid that displays but does not respond to clicks
      is the classic Blazor render-mode symptom and is not a Syncfusion defect
- [ ] Empty, loading and error states behave, where relevant
- [ ] Data with realistic shape and volume, not a single hard-coded row, where the feature is about
      volume

### Quality

- [ ] Console, terminal or device log clean of missing-module, missing-asset, missing-endpoint errors
- [ ] Keyboard access, labels and focus behaviour work for what changed
- [ ] Responsive layout holds at the breakpoints the project cares about
- [ ] Performance acceptable for the data volume actually used
- [ ] For mobile and desktop: verified on the platforms the app ships to, not only the dev machine

### Output hygiene

- [ ] Complete files emitted. No ellipsis, no "rest of your code", no partial diff as primary output
- [ ] No `any`, no unused imports, no deprecated properties
- [ ] Only one platform's Syncfusion API used

## When you cannot verify at runtime

Say so, precisely. "Builds clean; I could not start the dev server in this environment, so rendering
is unverified" is useful. "Done" is not.

Then give a short manual check the user can run in under a minute — the command to start, what they
should see, and the one symptom that would indicate the most likely failure.

## When it fails

Troubleshoot in this order:

1. Framework and Syncfusion package version compatibility
2. Missing package, peer dependency, module, service, provider, handler or import
3. Theme CSS, fonts, scripts, static assets, required server endpoint
4. Data shape, identifiers, date and number parsing, async lifecycle
5. Feature-specific configuration in the installed component skill
6. Current official documentation, or an MCP lookup
7. A minimal reproduction with unrelated application code removed

Preserve the original error text verbatim; do not paraphrase it. Distinguish a product defect from an
integration or configuration issue. If a minimal reproduction still fails against documented
behaviour, that is a support case: https://support.syncfusion.com/
# Official Syncfusion agent skill packs

Read this when choosing or installing a pack. Repository names below were verified against the
official Syncfusion repositories and the Agent Skills page on 2026-08-21. Note that the naming is not
perfectly uniform — `javascript-ui-controls-skills` breaks the `-ui-components-skills` pattern — so
copy the name from the table rather than constructing it.

## Selection rules

- Match the repository's framework first, then the product family, then the component.
- Prefer `--skill <skill_name>` when the exact component is known.
- Use the interactive form when the skill name is uncertain — it lists what the pack contains.
- Use `-y` to install a whole pack only when broad or repeated Syncfusion work is expected.
- Default to project-local installation. Use `-g` only when the user explicitly wants the skill
  across unrelated projects.
- After installation, inspect the skills directory and read the chosen `SKILL.md` before editing
  product code.
- Installing a skill installs no product packages.

## Commands

```bash
npx skills add syncfusion/<repository>                      # interactive
npx skills add syncfusion/<repository> -y                    # whole pack
npx skills add syncfusion/<repository> --skill <skill_name>   # one skill
npx skills add syncfusion/<repository> --agent cursor         # target one agent
npx skills add https://github.com/syncfusion/<repository>     # equivalent to the shorthand
```

`owner/repo` shorthand is valid. The installer is the `skills` CLI; `--list` shows a pack's contents
without installing, and `-g` installs globally.

## All 18 packs

| Platform or SDK | Repository | Skills | Agent index |
| --- | --- | --- | --- |
| React | `syncfusion/react-ui-components-skills` | 65 | /react/llms.txt |
| Angular | `syncfusion/angular-ui-components-skills` | 65 | /angular/llms.txt |
| JavaScript | `syncfusion/javascript-ui-controls-skills` | 53 | /javascript/llms.txt |
| Vue | `syncfusion/vue-ui-components-skills` | 33 | /vue/llms.txt |
| Blazor | `syncfusion/blazor-ui-components-skills` | 72 | /blazor/llms.txt |
| ASP.NET Core | `syncfusion/aspnetcore-ui-components-skills` | 67 | /aspnet-core/llms.txt |
| ASP.NET MVC | `syncfusion/aspnetmvc-ui-components-skills` | 23 | /aspnet-mvc/llms.txt |
| Flutter | `syncfusion/flutter-ui-components-skills` | 15 | /flutter/llms.txt |
| .NET MAUI | `syncfusion/maui-ui-components-skills` | 77 | /maui/llms.txt |
| Toolkit for .NET MAUI | `syncfusion/maui-toolkit-ui-components-skills` | 35 | /maui-toolkit/llms.txt |
| WinForms | `syncfusion/winforms-ui-components-skills` | 115 | /winforms/llms.txt |
| WPF | `syncfusion/wpf-ui-components-skills` | 99 | /wpf/llms.txt |
| WinUI | `syncfusion/winui-ui-components-skills` | 38 | /winui/llms.txt |
| Document SDK | `syncfusion/document-sdk-skills` | 12 | /document-sdk/llms.txt |
| Xamarin to .NET MAUI | `syncfusion/xamarin-maui-migration-skills` | 4 | /xamarin-to-maui-migration/llms.txt |

Prefix an agent index with `https://ai.syncfusion.com`.

## Narrow installation examples

Use names the pack exposes, not names constructed from memory.

```bash
npx skills add syncfusion/react-ui-components-skills --skill syncfusion-react-grid
npx skills add syncfusion/blazor-ui-components-skills --skill syncfusion-blazor-datagrid
npx skills add syncfusion/docx-editor-sdk-skills --skill syncfusion-blazor-docx-editor
npx skills add syncfusion/pdf-viewer-sdk-skills --skill syncfusion-react-pdf-viewer
npx skills add syncfusion/document-sdk-skills --skill syncfusion-dotnet-pdf
```

Skill names follow `syncfusion-<platform>-<component>`, but the component segment is not always what
you would guess — Blazor's grid skill is `syncfusion-blazor-datagrid`, React's is
`syncfusion-react-grid`. Confirm with `--list` or the platform inventory CSV.

## Where skills install

| Agent | Project directory | Global directory |
| --- | --- | --- |
| Claude Code | `.claude/skills/` | `~/.claude/skills/` |
| Cursor | `.agents/skills/` | `~/.cursor/skills/` |
| GitHub Copilot | `.agents/skills/` | `~/.copilot/skills/` |
| Codex | `.agents/skills/` | `~/.codex/skills/` |
| Gemini CLI | `.agents/skills/` | `~/.gemini/skills/` |
| Cline | `.agents/skills/` | `~/.agents/skills/` |
| Windsurf | `.windsurf/skills/` | `~/.codeium/windsurf/skills/` |
| Code Studio | `.codestudio/skills/` | `~/.codestudio/skills/` |
| Roo Code | `.roo/skills/` | `~/.roo/skills/` |
| Trae | `.trae/skills/` | `~/.trae/skills/` |
| OpenCode | `.agents/skills/` | `~/.config/opencode/skills/` |

Most agents share the `.agents/skills/` project directory and differ only in their global path. Only
Claude Code, Windsurf, Code Studio, Roo and Trae use a vendor-specific project directory.

There is no verified JetBrains skills directory. JetBrains IDEs are supported as MCP clients. If a
JetBrains-adjacent agent is in use, the installer supports Junie (`.junie/skills/`) and Firebender
(`.agents/skills/`) as named targets.

## Choosing between overlapping packs

- **Component pack vs editor or viewer SDK.** The component pack covers general UI construction; the
  editor or viewer SDK covers one specialised interactive document surface. Using both is often
  correct.
- **Editor or viewer SDK vs Document SDK.** Editors and viewers embed an interactive surface. The
  Document SDK creates, converts, signs and extracts programmatically. One feature often needs both.
- **.NET MAUI vs MAUI Toolkit.** Separate products, separate packages, separate initialisers. A
  `syncfusion-maui-*` skill does not apply unchanged to a `syncfusion-maui-toolkit-*` control.
- **Workflow packs.** The Xamarin migration pack is meant to be used
  *with* the .NET MAUI component pack, not instead of it.

## Handoff checklist

After selecting a skill:

- [ ] It targets this repository's framework and product family
- [ ] Its complete `SKILL.md` has been read
- [ ] Its package, module or service, and theme instructions are followed
- [ ] Current official documentation consulted for anything the skill does not cover
- [ ] The repository build runs and the requested feature is exercised

## Official sources

- Agent Skills: https://www.syncfusion.com/explore/agent-skills/
- Skill catalog: https://ai.syncfusion.com/skills/catalog.md
- React skills documentation: https://ej2.syncfusion.com/react/documentation/skills
- Agent Skills and MCP overview: https://www.syncfusion.com/blogs/post/ai-coding-assistant-agent-skills-mcp
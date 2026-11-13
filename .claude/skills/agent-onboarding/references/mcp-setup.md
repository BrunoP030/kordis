# Syncfusion MCP servers — reference for agents

## Decide whether you need it

| Situation | Do this |
| --- | --- |
| Generating or modifying Syncfusion code | Install the required Syncfusion skills. MCP is optional. |
| Confirming a current API or a release behaviour change | Query MCP, or search the platform docs |
| No API key, or the user has not authorised one | Skip MCP. Skills plus documentation is a complete path. Do not block. |
| User explicitly asked you to set up MCP | Follow https://ai.syncfusion.com/mcp/llms.txt for their editor |

Skills carry procedural knowledge and failure modes; MCP carries current facts. The anonymous path —
platform index plus skill pack plus public documentation — must work before any key exists.

## Environment variables

Exact casing. `SYNCFUSION_API_KEY` is not valid.

| Variable | Value |
| --- | --- |
| `Syncfusion_API_Key_Path` | Absolute path to a `.txt` or `.key` file containing the key. Recommended. |
| `Syncfusion_API_Key` | The key inline. Only where a path is not usable. |

Write the variable, never the value, into any file that could be committed. Put the key file outside
the repository.

## Server by platform

| Platform | Package | Registry | Server name |
| --- | --- | --- | --- |
| React | `@syncfusion/react-mcp` | npm | `sf-react-mcp` |
| Angular | `@syncfusion/angular-mcp` | npm | `sf-angular-mcp` |
| Vue | `@syncfusion/vue-mcp` | npm | `sf-vue-mcp` |
| JavaScript | `@syncfusion/javascript-mcp` | npm | `sf-javascript-mcp` |
| TypeScript | `@syncfusion/typescript-mcp` | npm | `sf-typescript-mcp` |
| Blazor | `Syncfusion.Blazor.MCP` | NuGet | `sf-blazor-mcp` |
| ASP.NET Core | `Syncfusion.AspNetCore.MCP` | NuGet | `sf-aspnetcore-mcp` |
| ASP.NET MVC | `Syncfusion.AspNetMvc.MCP` | NuGet | `sf-aspnetmvc-mcp` |
| .NET MAUI | `Syncfusion.Maui.MCP` | NuGet | `sf-maui-mcp` |
| WPF | `Syncfusion.WPF.MCP` | NuGet | `sf-wpf-mcp` |
| WinForms | `Syncfusion.WinForms.MCP` | NuGet | `sf-winforms-mcp` |
| WinUI | `Syncfusion.WinUI.MCP` | NuGet | `sf-winui-mcp` |
| UWP | `Syncfusion.UWP.MCP` | NuGet | `sf-uwp-mcp` |
| Document SDK | `Syncfusion.DocumentSdk.MCP` | NuGet | `sf-documentsdk-mcp` |

One rule: **npm `@syncfusion/<platform>-mcp` for JavaScript-family platforms, NuGet
`Syncfusion.<Platform>.MCP` for .NET platforms.**

**Never use an `@syncfusion/*-assistant` package.** That family is deprecated. A higher version number
on an `-assistant` package does not mean it supersedes the `-mcp` or `.MCP` package — it does not. If a
Syncfusion doc page still names one, the table above takes precedence.

No server is published for Flutter or the MAUI Toolkit. The PDF Viewer, DOCX Editor and Spreadsheet
Editor SDKs have no dedicated server — use the server for the framework hosting the editor.

npm servers are invoked as `npx -y <package>@latest`. NuGet servers are invoked as
`dnx <Package> --yes`, which requires .NET 10; the documented fallback on .NET 8 or 9 is
`dotnet tool run syncfusion-<platform>-mcp`.

## Canonical configuration shape

```json
{
  "servers": {
    "sf-react-mcp": {
      "type": "stdio",
      "command": "npx",
      "args": ["-y", "@syncfusion/react-mcp@latest"],
      "env": {
        "Syncfusion_API_Key_Path": "/absolute/path/to/syncfusion-api-key.txt"
      }
    }
  }
}
```

The wrapper key differs by editor: `servers` for VS Code and Code Studio, `mcpServers` for Cursor,
Claude Code, Windsurf, Cline and Gemini CLI. Codex uses TOML. JetBrains is configured through the AI
Assistant UI and needs `npx.cmd` on Windows. Per-editor files:
https://ai.syncfusion.com/mcp/llms.txt

## Not MCP

`Syncfusion.DocumentSDK.AI.AgentTools` is a .NET library exposing document operations as callable
tools to an `IChatClient` through the Microsoft Agent Framework, installed with
`dotnet add package Syncfusion.DocumentSDK.AI.AgentTools`. It has no transport, no MCP configuration
and no API key. Do not put it in an `mcpServers` block.

## Verify

1. Restart the editor. Most clients read MCP configuration only at startup.
2. Confirm the server appears under its `sf-<platform>-mcp` name with a tool count.
3. Ask something that requires retrieval rather than recall and check the answer cites documentation.
4. Server starts but every query fails: the API key. Check variable casing and that the path resolves.
5. Server does not start: run its command manually in a terminal and read the real error.

Do not report MCP as configured until it has answered a query.
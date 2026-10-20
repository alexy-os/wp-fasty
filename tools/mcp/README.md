# WP FastY configurator — MCP server

Stdio [MCP](https://modelcontextprotocol.io) server over the configurator (`.workspaces/theme-builder/src/configure`)
and the generator. It lives in the repo for now (a separate package later). Everything it writes goes to `dist/`
(paths are checked); it never publishes, uploads or pushes anything.

```bash
bun install                 # repo root: @modelcontextprotocol/sdk, zod, ajv
bun run mcp:server          # stdio server (normally started by the MCP client)
bun run mcp:smoke           # starts the server and calls every tool
```

## Tools

| Tool | Input | Result |
|---|---|---|
| `list_archetypes` | – | archetypes (reference theme, required post types, landings), palette presets, field types |
| `describe_schema` | `section?` (`postType`, `brand`, `output`, …) | site manifest v0.2 JSON Schema, whole or one part |
| `validate_manifest` | `answers` **or** `manifest` | schema + semantic rules; writes `dist/manifests/<slug>.json` |
| `generate_site` | `answers` or `manifest`, `pot?` | child theme in `dist/generated/<slug>/` |
| `package_site` | `answers` or `manifest` | `dist/<slug>/`: parent + child zips, plugin zips, `demo.zip`, Playground bundle |

`answers` is the configurator input (same as `bun run theme:new-site --answers`):

```json
{
  "archetype": "press",
  "name": "Town Events",
  "palette": "editorial-warm",
  "postTypes": [{ "name": "event", "labels": { "singular": "Event", "plural": "Events" },
                  "fields": { "starts_on": { "type": "date", "label": "Starts on" } } }]
}
```

Brand is a fixed `palette` id, or free `tokens` (`light`, optional `dark`, `radius`, `fonts`) that must pass
WCAG AA (4.5:1) on every text/surface pair. Extra post types are added on top of the archetype's own; name and
taxonomy clashes and reserved platform names are rejected. Fixtures: `manifests/answers/*.json`.

## Cursor

Project-level `.cursor/mcp.json` (the repo opened as the workspace):

```json
{
  "mcpServers": {
    "wpfasty-configurator": {
      "command": "bun",
      "args": ["${workspaceFolder}/tools/mcp/server.ts"]
    }
  }
}
```

Global `~/.cursor/mcp.json`: same entry with an absolute path, e.g. `"args": ["/home/me/wp-fasty/tools/mcp/server.ts"]`.
`package_site` needs `dist/latte.zip` (built by `bun run theme:pack -- latte` on first use) and `wp` (wp-cli) on PATH
for `.pot` files.
